<?php

namespace App\Services\Signage;

use App\Models\SignageScreen;

class SignageStreamAccess
{
    public const COOKIE = 'gc_signage_stream';

    public static function issue(SignageScreen $screen): string
    {
        $payload = $screen->id.'.'.now()->addMinutes(15)->timestamp;
        return $payload.'.'.hash_hmac('sha256', $payload, $screen->token_hash);
    }

    public static function screen(?string $cookie): ?SignageScreen
    {
        if (!$cookie || !preg_match('/^(\d+)\.(\d{10})\.([0-9a-f]{64})$/D', $cookie, $parts) || $parts[2] <= time()) {
            return null;
        }
        $screen = SignageScreen::find($parts[1]);
        if (!$screen || !$screen->token_hash) return null;
        $expected = hash_hmac('sha256', $parts[1].'.'.$parts[2], $screen->token_hash);
        return hash_equals($expected, $parts[3]) ? $screen : null;
    }
}
