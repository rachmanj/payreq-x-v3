<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginThrottle
{
    public static function limiterName(): string
    {
        return 'login';
    }

    public static function signatureKey(Request $request): string
    {
        $username = Str::lower((string) $request->input('username', ''));

        return $username.'|'.$request->ip();
    }

    public static function rateLimiterKey(Request $request): string
    {
        return md5(self::limiterName().self::signatureKey($request));
    }

    public static function clearForRequest(Request $request): void
    {
        RateLimiter::clear(self::rateLimiterKey($request));
    }
}
