<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    public function attendance(Request $request)
    {
        $query = AttendanceRecord::query()->with(['user:id,name,identifier', 'session:id,title,starts_at']);

        $query->when($request->input('session_id'), fn ($q) => $q->where('session_id', $request->input('session_id')));
        $query->when($request->input('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')));
        $query->when($request->input('status'), fn ($q) => $q->where('status', $request->input('status')));
        $query->when($request->input('group_id'), function ($q) use ($request) {
            $userIds = \App\Models\Group::findOrFail($request->input('group_id'))->members()->pluck('users.id');
            $q->whereIn('user_id', $userIds);
        });
        $query->when($request->input('date_from'), fn ($q) => $q->whereDate('checked_in_at', '>=', $request->input('date_from')));
        $query->when($request->input('date_to'), fn ($q) => $q->whereDate('checked_in_at', '<=', $request->input('date_to')));

        if ($request->input('format') === 'csv') {
            return $this->streamCsv($query->orderBy('checked_in_at')->get());
        }

        return response()->json($query->orderByDesc('checked_in_at')->paginate(50));
    }

    private function streamCsv($records)
    {
        $callback = function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Session', 'User', 'Identifier', 'Status', 'Checked In At', 'Late Minutes', 'Method']);
            foreach ($records as $r) {
                fputcsv($handle, [
                    $r->session->title ?? '',
                    $r->user->name ?? '',
                    $r->user->identifier ?? '',
                    $r->status,
                    optional($r->checked_in_at)->toDateTimeString(),
                    $r->late_minutes,
                    $r->check_in_method,
                ]);
            }
            fclose($handle);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="attendance_report.csv"',
        ]);
    }
}