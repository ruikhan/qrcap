<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAdjustment extends Model
{
    protected $fillable = ['record_id', 'prior_status', 'new_status', 'reason', 'adjusted_by', 'adjusted_at'];

    protected function casts(): array
    {
        return ['adjusted_at' => 'datetime'];
    }

    public function record()
    {
        return $this->belongsTo(AttendanceRecord::class, 'record_id');
    }

    public function adjuster()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}