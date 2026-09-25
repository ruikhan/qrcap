<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QrToken extends Model
{
    protected $fillable = ['token_id', 'session_id', 'nonce', 'signature', 'issued_at', 'expires_at', 'revoked'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'expires_at' => 'datetime', 'revoked' => 'boolean'];
    }

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function isValidNow(): bool
    {
        return ! $this->revoked && now()->between($this->issued_at, $this->expires_at);
    }
}