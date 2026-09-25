<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceAdjustmentController extends Controller
{
    public function __construct(private AuditLogService $audit) {}

    public function store(Request $request, AttendanceRecord $record)
    {
        $data = $request->validate([
            'new_status' => ['required', 'in:present,late,absent,excused,pending_review,rejected,cancelled'],
            'reason' => ['required', 'string', 'min:5'],
        ]);

        $priorStatus = $record->status;

        $updated = DB::transaction(function () use ($record, $data, $priorStatus, $request) {
            $record->update(['status' => $data['new_status']]);

            $adjustment = $record->adjustments()->create([
                'prior_status' => $priorStatus,
                'new_status' => $data['new_status'],
                'reason' => $data['reason'],
                'adjusted_by' => $request->user()->id,
                'adjusted_at' => now(),
            ]);

            $this->audit->log(
                $request->user(),
                'attendance.updated',
                'attendance_records',
                $record->id,
                ['status' => $priorStatus],
                ['status' => $data['new_status'], 'reason' => $data['reason']],
                $request,
            );

            return $adjustment;
        });

        return response()->json([
            'record' => $record->fresh(),
            'adjustment' => $updated,
        ], 201);
    }
}