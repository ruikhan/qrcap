<?php

use App\Http\Controllers\Admin\AttendanceAdjustmentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CheckinController;
use Illuminate\Support\Facades\Route;

// ---- Public ----
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// ---- Authenticated (any logged-in user) ----
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Attendee check-in flow
    Route::post('/checkin/validate', [CheckinController::class, 'validateToken'])->middleware('throttle:30,1');
    Route::post('/checkin/confirm', [CheckinController::class, 'confirm'])->middleware('throttle:10,1');
    Route::get('/me/attendance', [CheckinController::class, 'myAttendance']);

    // Staff+ : session operation (open/pause/close/live/QR) — ownership/assignment can be
    // refined later; for MVP, staff+admin+super_admin can operate sessions they're assigned to.
    Route::middleware('role:staff,admin,super_admin')->group(function () {
        Route::get('/admin/sessions/{session}/qr/current', [SessionController::class, 'currentQr']);
        Route::get('/admin/sessions/{session}/live', [SessionController::class, 'live']);
        Route::post('/admin/sessions/{session}/open', [SessionController::class, 'open']);
        Route::post('/admin/sessions/{session}/pause', [SessionController::class, 'pause']);
        Route::post('/admin/sessions/{session}/close', [SessionController::class, 'close']);
        Route::get('/admin/sessions', [SessionController::class, 'index']);
        Route::get('/admin/sessions/{session}', [SessionController::class, 'show']);
    });

    // Admin+ : full session & people management, corrections, reports, audit log
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::post('/admin/sessions', [SessionController::class, 'store']);
        Route::put('/admin/sessions/{session}', [SessionController::class, 'update']);

        Route::post('/admin/attendance/{record}/adjust', [AttendanceAdjustmentController::class, 'store']);

        Route::get('/admin/reports/attendance', [ReportController::class, 'attendance']);

        Route::get('/admin/users', [UserController::class, 'index']);
        Route::post('/admin/users', [UserController::class, 'store']);
        Route::put('/admin/users/{user}', [UserController::class, 'update']);

        Route::get('/admin/groups', [GroupController::class, 'index']);
        Route::post('/admin/groups', [GroupController::class, 'store']);
        Route::put('/admin/groups/{group}', [GroupController::class, 'update']);

        Route::get('/admin/audit-logs', [AuditLogController::class, 'index']);
    });

    // Super admin only: role management would go here (e.g. /admin/roles CRUD) once needed.
});