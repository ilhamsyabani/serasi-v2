<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('pemohon.auth.change-password');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                $pbf = Auth::guard('pemohon')->user();
                if (! Hash::check($value, $pbf->getAuthPassword())) {
                    $fail('Password lama salah.');
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $pbf = Auth::guard('pemohon')->user();
        $pbf->password_hash = Hash::make($request->password);
        $pbf->save();

        Auth::guard('pemohon')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pemohon.login')->with('success', 'Password berhasil diubah. Silakan login kembali.');
    }
}
