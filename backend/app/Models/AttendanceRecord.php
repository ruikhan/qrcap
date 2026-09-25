<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'session_id', 'user_id', 'status', 'checked_in_at', 'check_in_method',
        'late_minutes', 'qr_token_id', 'check_in_lat', 'check_in_lng', 'device_meta',
    ];

    protected function casts(): array
    {
        return ['checked_in_at' => 'datetime'];
    }

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function adjustments()
    {
        return $this->hasMany(AttendanceAdjustment::class, 'record_id');
    }
}