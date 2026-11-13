<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Pbf;
use App\Models\PasswordResetOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request)
    {
        return view('pemohon.auth.reset-password', [
            'token' => (string) $request->route('token'),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['bail', 'required', 'string'],
            'password' => ['bail', 'required', 'string', 'confirmed', 'min:8'],
        ]);

        $reset = PasswordResetOtp::where('token', (string) $validated['token'])
            ->where('status', PasswordResetOtp::STATUS_TERKIRIM)
            ->where('expires_at', '>', now())
            ->first();

        if (! $reset) {
            throw ValidationException::withMessages([
                'token' => 'Tautan reset password tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        $pbf = Pbf::where('email', (string) $reset->email)->first();

        if (! $pbf) {
            throw ValidationException::withMessages([
                'token' => 'Data akun pemohon tidak ditemukan.',
            ]);
        }

        $pbf->password_hash = Hash::make((string) $validated['password']);
        $pbf->save();

        $reset->update(['status' => PasswordResetOtp::STATUS_TERVERIFIKASI]);

        return redirect()->route('pemohon.login')->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
    }
}
