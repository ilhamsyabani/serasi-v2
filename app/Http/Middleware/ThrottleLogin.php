<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ThrottleLogin
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 5, int $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        if ($response->getStatusCode() === 302 && $request->isMethod('POST')) {
            RateLimiter::clear($key);
        }

        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        $emailOrPhone = $request->input('nip') ?? $request->input('identifier') ?? $request->ip();

        return sha1($emailOrPhone . '|' . $request->ip());
    }
}
