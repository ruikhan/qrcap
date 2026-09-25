<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceSession;
use App\Services\AuditLogService;
use App\Services\QrTokenService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function __construct(
        private QrTokenService $qrTokens,
        private AuditLogService $audit,
    ) {}

    public function index(Request $request)
    {
        $sessions = AttendanceSession::query()
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('starts_at')
            ->paginate(20);

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string'],
            'present_grace_minutes' => ['nullable', 'integer', 'min:0'],
            'qr_rotation_seconds' => ['nullable', 'integer', 'min:5'],
            'qr_expiry_seconds' => ['nullable', 'integer', 'min:10'],
            'require_location' => ['nullable', 'boolean'],
            'geofence_lat' => ['nullable', 'numeric'],
            'geofence_lng' => ['nullable', 'numeric'],
            'geofence_radius_meters' => ['nullable', 'integer', 'min:1'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'exists:groups,id'],
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $session = AttendanceSession::create([
            ...collect($data)->only([
                'title', 'description', 'starts_at', 'ends_at', 'location',
                'present_grace_minutes', 'qr_rotation_seconds', 'qr_expiry_seconds',
                'require_location', 'geofence_lat', 'geofence_lng', 'geofence_radius_meters',
            ])->toArray(),
            'status' => 'draft',
            'created_by' => $request->user()->id,
        ]);

        foreach ($data['user_ids'] ?? [] as $userId) {
            $session->eligibility()->create(['user_id' => $userId]);
        }
        foreach ($data['group_ids'] ?? [] as $groupId) {
            $session->eligibility()->create(['group_id' => $groupId]);
        }
        if (! empty($data['staff_ids'])) {
            $session->staff()->sync($data['staff_ids']);
        }

        $this->audit->log($request->user(), 'session.created', 'attendance_sessions', $session->id, null, $session->toArray(), $request);

        return response()->json($session->load('eligibility', 'staff'), 201);
    }

    public function show(AttendanceSession $session)
    {
        return response()->json($session->load('eligibility', 'staff', 'creator'));
    }

    public function update(Request $request, AttendanceSession $session)
    {
        if ($session->status !== 'draft') {
            return response()->json(['message' => 'Only draft sessions can be edited. Use corrections after opening.'], 422);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['sometimes', 'date'],
            'ends_at' => ['sometimes', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string'],
            'present_grace_minutes' => ['nullable', 'integer', 'min:0'],
            'qr_rotation_seconds' => ['nullable', 'integer', 'min:5'],
            'qr_expiry_seconds' => ['nullable', 'integer', 'min:10'],
        ]);

        $before = $session->toArray();
        $session->update($data);
        $this->audit->log($request->user(), 'session.updated', 'attendance_sessions', $session->id, $before, $session->toArray(), $request);

        return response()->json($session);
    }

    public function open(Request $request, AttendanceSession $session)
    {
        if ($session->status !== 'draft' && $session->status !== 'paused') {
            return response()->json(['message' => "Cannot open a session with status '{$session->status}'."], 422);
        }

        $before = $session->toArray();
        $session->update(['status' => 'open', 'opened_at' => $session->opened_at ?? now()]);
        $this->audit->log($request->user(), 'session.opened', 'attendance_sessions', $session->id, $before, $session->toArray(), $request);

        return response()->json($session);
    }

    public function pause(Request $request, AttendanceSession $session)
    {
        if (! $session->isOpen()) {
            return response()->json(['message' => 'Only an open session can be paused.'], 422);
        }

        $before = $session->toArray();
        $session->update(['status' => 'paused']);
        $this->audit->log($request->user(), 'session.paused', 'attendance_sessions', $session->id, $before, $session->toArray(), $request);

        return response()->json($session);
    }

    public function close(Request $request, AttendanceSession $session)
    {
        if (in_array($session->status, ['closed', 'cancelled'])) {
            return response()->json(['message' => 'Session already closed.'], 422);
        }

        $before = $session->toArray();
        $session->update(['status' => 'closed', 'closed_at' => now()]);

        $existingUserIds = $session->attendanceRecords()->pluck('user_id')->all();
        foreach (array_diff($session->eligibleUserIds(), $existingUserIds) as $userId) {
            $session->attendanceRecords()->create([
                'user_id' => $userId,
                'status' => 'absent',
                'check_in_method' => 'system',
            ]);
        }

        $this->audit->log($request->user(), 'session.closed', 'attendance_sessions', $session->id, $before, $session->toArray(), $request);

        return response()->json($session->fresh());
    }

    public function currentQr(Request $request, AttendanceSession $session)
    {
        if (! $session->isOpen()) {
            return response()->json(['message' => 'Session is not open.'], 422);
        }

        $issued = $this->qrTokens->issue($session);

        return response()->json([
            'payload' => $issued['payload'],
            'expires_at' => $issued['model']->expires_at,
            'rotates_in_seconds' => $issued['rotates_in_seconds'],
        ]);
    }

    public function live(AttendanceSession $session)
    {
        $expected = count($session->eligibleUserIds());

        $counts = $session->attendanceRecords()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recent = $session->attendanceRecords()
            ->with('user:id,name,identifier')
            ->latest('checked_in_at')
            ->limit(25)
            ->get(['id', 'user_id', 'status', 'checked_in_at', 'late_minutes', 'check_in_method']);

        return response()->json([
            'session' => $session->only('id', 'title', 'status', 'starts_at', 'ends_at'),
            'expected' => $expected,
            'present' => (int) ($counts['present'] ?? 0),
            'late' => (int) ($counts['late'] ?? 0),
            'absent' => (int) ($counts['absent'] ?? 0),
            'pending_review' => (int) ($counts['pending_review'] ?? 0),
            'recent' => $recent,
        ]);
    }
}