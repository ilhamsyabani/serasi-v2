<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request)
    {
        return view('internal.auth.reset-password', [
            'email' => is_string($request->email) ? $request->email : '',
            'token' => (string) $request->route('token'),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['bail', 'required', 'string'],
            'email' => ['bail', 'required', 'string', 'email'],
            'password' => ['bail', 'required', 'string', 'confirmed', 'min:8'],
        ]);

        $credentials = [
            'email' => (string) $validated['email'],
            'password' => (string) $validated['password'],
            'password_confirmation' => (string) $request->input('password_confirmation'),
            'token' => (string) $validated['token'],
        ];

        $status = Password::reset(
            $credentials,
            function ($user, $password) {
                $user->password = Hash::make((string) $password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('internal.login')->with('success', 'Password berhasil direset. Silakan login.');
        }

        return back()->withErrors(['email' => __($status)]);
    }
}
