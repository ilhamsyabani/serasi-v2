<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('internal.login');
        }

        $user = Auth::guard('web')->user();

        if (!$user || !in_array($user->role->kode, $roles)) {
            abort(403);
        }

        return $next($request);
    }
}
