<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // `is_aktif` ikut jadi kredensial agar user yang dinonaktifkan Admin IT tidak bisa masuk.
        $credentials = [
            'nip' => $request->nip,
            'password' => $request->password,
            'is_aktif' => true,
        ];

        if (Auth::guard('web')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Dispatcher `internal.dashboard` mengarahkan ke dashboard per role.
            return redirect()->intended(route('internal.dashboard'));
        }

        return back()->withErrors(['nip' => 'NIP atau password salah.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('internal.login');
    }
}
