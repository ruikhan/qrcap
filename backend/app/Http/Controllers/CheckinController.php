<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Services\AuditLogService;
use App\Services\QrTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    public function __construct(
        private QrTokenService $qrTokens,
        private AuditLogService $audit,
    ) {}

    public function validateToken(Request $request)
    {
        $request->validate(['payload' => ['required', 'string']]);

        $qrToken = $this->qrTokens->verify($request->string('payload'));
        if (! $qrToken) {
            return response()->json(['message' => 'This QR code is invalid or has expired.'], 422);
        }

        $session = $qrToken->session;
        if (! $session->isOpen()) {
            return response()->json(['message' => 'This session is not currently open.'], 422);
        }

        $user = $request->user();
        if (! in_array($user->id, $session->eligibleUserIds(), true)) {
            return response()->json(['message' => 'You are not eligible for this session.'], 403);
        }

        return response()->json([
            'session' => $session->only('id', 'title', 'description', 'location', 'starts_at', 'ends_at'),
            'already_checked_in' => $session->attendanceRecords()->where('user_id', $user->id)->exists(),
        ]);
    }

    public function confirm(Request $request)
    {
        $data = $request->validate([
            'payload' => ['required', 'string'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
        ]);

        $user = $request->user();

        $qrToken = $this->qrTokens->verify($data['payload']);
        if (! $qrToken) {
            return response()->json(['message' => 'This QR code is invalid or has expired.'], 422);
        }

        $session = $qrToken->session()->lockForUpdate()->first();
        if (! $session->isOpen()) {
            return response()->json(['message' => 'This session is not currently open.'], 422);
        }

        if (! in_array($user->id, $session->eligibleUserIds(), true)) {
            return response()->json(['message' => 'You are not eligible for this session.'], 403);
        }

        if ($session->require_location && (! isset($data['lat']) || ! isset($data['lng']))) {
            return response()->json(['message' => 'Location is required to check in to this session.'], 422);
        }
        if ($session->require_location && ! $this->withinGeofence($session, $data['lat'], $data['lng'])) {
            return response()->json(['message' => 'You are not within the required check-in location.'], 422);
        }

        $existing = AttendanceRecord::where('session_id', $session->id)->where('user_id', $user->id)->first();
        if ($existing) {
            return response()->json(['receipt' => $this->presentReceipt($existing), 'already_checked_in' => true]);
        }

        [$status, $lateMinutes] = $this->decideStatus($session);

        try {
            $record = DB::transaction(function () use ($session, $user, $qrToken, $status, $lateMinutes, $data, $request) {
                $record = AttendanceRecord::create([
                    'session_id' => $session->id,
                    'user_id' => $user->id,
                    'status' => $status,
                    'checked_in_at' => now(),
                    'check_in_method' => 'qr',
                    'late_minutes' => $lateMinutes,
                    'qr_token_id' => $qrToken->id,
                    'check_in_lat' => $data['lat'] ?? null,
                    'check_in_lng' => $data['lng'] ?? null,
                    'device_meta' => substr((string) $request->userAgent(), 0, 255),
                ]);

                $this->audit->log($user, 'attendance.checked_in', 'attendance_records', $record->id, null, $record->toArray(), $request);

                return $record;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $existing = AttendanceRecord::where('session_id', $session->id)->where('user_id', $user->id)->first();
            if ($existing) {
                return response()->json(['receipt' => $this->presentReceipt($existing), 'already_checked_in' => true]);
            }
            throw $e;
        }

        return response()->json(['receipt' => $this->presentReceipt($record), 'already_checked_in' => false], 201);
    }

    public function myAttendance(Request $request)
    {
        $records = $request->user()->attendanceRecords()
            ->with('session:id,title,starts_at,location')
            ->latest('checked_in_at')
            ->paginate(20);

        return response()->json($records);
    }

    private function decideStatus($session): array
    {
        $graceDeadline = $session->starts_at->clone()->addMinutes($session->present_grace_minutes);
        $now = now();

        if ($now->lessThanOrEqualTo($graceDeadline)) {
            return ['present', 0];
        }

        return ['late', $now->diffInMinutes($session->starts_at)];
    }

    private function withinGeofence($session, float $lat, float $lng): bool
    {
        if (! $session->geofence_lat || ! $session->geofence_lng || ! $session->geofence_radius_meters) {
            return true;
        }

        $earthRadiusMeters = 6371000;
        $dLat = deg2rad($lat - (float) $session->geofence_lat);
        $dLng = deg2rad($lng - (float) $session->geofence_lng);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad((float) $session->geofence_lat)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;
        $distance = $earthRadiusMeters * 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $distance <= $session->geofence_radius_meters;
    }

    private function presentReceipt(AttendanceRecord $record): array
    {
        return [
            'id' => $record->id,
            'session_id' => $record->session_id,
            'status' => $record->status,
            'checked_in_at' => $record->checked_in_at,
            'late_minutes' => $record->late_minutes,
            'server_time' => now(),
        ];
    }
}