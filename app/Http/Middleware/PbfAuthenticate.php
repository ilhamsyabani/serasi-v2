<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PbfAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('pemohon')->check()) {
            return redirect()->route('pemohon.login');
        }

        return $next($request);
    }
}
