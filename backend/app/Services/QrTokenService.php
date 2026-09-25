<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\QrToken;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Issues and verifies short-lived, signed QR tokens for a session.
 *
 * The QR code itself never proves presence — it only carries an opaque,
 * signed, expiring reference that the backend re-validates on check-in.
 */
class QrTokenService
{
    public function issue(AttendanceSession $session): array
    {
        $tokenId = (string) Str::uuid();
        $nonce = Str::random(32);
        $issuedAt = now();
        $expiresAt = $issuedAt->clone()->addSeconds($session->qr_expiry_seconds);

        $signature = $this->sign($session->id, $tokenId, $nonce, $issuedAt->timestamp, $expiresAt->timestamp);

        $qrToken = QrToken::create([
            'token_id' => $tokenId,
            'session_id' => $session->id,
            'nonce' => $nonce,
            'signature' => $signature,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAt,
        ]);

        return [
            'model' => $qrToken,
            'payload' => $this->encode($session->id, $tokenId, $nonce, $issuedAt->timestamp, $expiresAt->timestamp, $signature),
            'rotates_in_seconds' => $session->qr_rotation_seconds,
        ];
    }

    public function verify(string $payload): ?QrToken
    {
        $parts = $this->decode($payload);
        if (! $parts) {
            return null;
        }

        [$sessionId, $tokenId, $nonce, $issuedAt, $expiresAt, $signature] = $parts;

        $expected = $this->sign($sessionId, $tokenId, $nonce, $issuedAt, $expiresAt);
        if (! hash_equals($expected, $signature)) {
            return null; // tampered payload
        }

        $qrToken = QrToken::where('token_id', $tokenId)->where('session_id', $sessionId)->first();
        if (! $qrToken || $qrToken->revoked) {
            return null;
        }

        if (! now()->between($qrToken->issued_at, $qrToken->expires_at)) {
            return null; // expired or not yet valid
        }

        return $qrToken;
    }

    private function sign(int $sessionId, string $tokenId, string $nonce, int $issuedAt, int $expiresAt): string
    {
        $secret = Config::get('app.key');
        $data = implode('|', [$sessionId, $tokenId, $nonce, $issuedAt, $expiresAt]);

        return hash_hmac('sha256', $data, $secret);
    }

    private function encode(int $sessionId, string $tokenId, string $nonce, int $issuedAt, int $expiresAt, string $signature): string
    {
        $raw = implode('|', [$sessionId, $tokenId, $nonce, $issuedAt, $expiresAt, $signature]);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    private function decode(string $payload): ?array
    {
        $raw = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($raw === false) {
            return null;
        }

        $parts = explode('|', $raw);
        if (count($parts) !== 6) {
            return null;
        }

        [$sessionId, $tokenId, $nonce, $issuedAt, $expiresAt, $signature] = $parts;

        if (! ctype_digit($sessionId) || ! ctype_digit($issuedAt) || ! ctype_digit($expiresAt)) {
            return null;
        }

        return [(int) $sessionId, $tokenId, $nonce, (int) $issuedAt, (int) $expiresAt, $signature];
    }
}