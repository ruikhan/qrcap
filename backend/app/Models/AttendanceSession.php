<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = [
        'title', 'description', 'starts_at', 'ends_at', 'status', 'location',
        'present_grace_minutes', 'qr_rotation_seconds', 'qr_expiry_seconds',
        'require_location', 'geofence_lat', 'geofence_lng', 'geofence_radius_meters',
        'created_by', 'opened_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'require_location' => 'boolean',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function eligibility()
    {
        return $this->hasMany(SessionEligibility::class, 'session_id');
    }

    public function staff()
    {
        return $this->belongsToMany(User::class, 'session_staff', 'session_id', 'user_id');
    }

    public function qrTokens()
    {
        return $this->hasMany(QrToken::class, 'session_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function eligibleUserIds(): array
    {
        $direct = $this->eligibility()->whereNotNull('user_id')->pluck('user_id');

        $groupIds = $this->eligibility()->whereNotNull('group_id')->pluck('group_id');
        $viaGroups = $groupIds->isEmpty()
            ? collect()
            : \App\Models\Group::whereIn('id', $groupIds)->with('members:id')->get()
                ->flatMap(fn ($g) => $g->members->pluck('id'));

        return $direct->merge($viaGroups)->unique()->values()->all();
    }
}