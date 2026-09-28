<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        return AuditLog::query()
            ->when($request->query('action'), fn ($q, $a) => $q->where('action', $a))
            ->latest('id')
            ->paginate(50);
    }
}