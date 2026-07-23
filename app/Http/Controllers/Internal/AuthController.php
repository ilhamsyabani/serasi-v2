<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('internal.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required',
        ]);

        $credentials = [
            'nip' => $request->nip,
            'password' => $request->password,
            'is_aktif' => true,
        ];

        if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            \Illuminate\Support\Facades\RateLimiter::clear(
                sha1($request->nip . '|' . $request->ip())
            );

            return redirect()->intended(route('internal.dashboard'));
        }

        throw ValidationException::withMessages([
            'nip' => 'NIP atau password salah.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('internal.login');
    }
}
