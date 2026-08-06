<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Pbf;
use App\Models\PasswordResetOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request)
    {
        return view('pemohon.auth.reset-password', [
            'token' => $request->route('token'),
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $reset = PasswordResetOtp::where('token', $request->token)
            ->where('status', PasswordResetOtp::STATUS_TERKIRIM)
            ->where('expires_at', '>', now())
            ->first();

        if (! $reset) {
            throw ValidationException::withMessages([
                'token' => 'Tautan reset password tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        $pbf = Pbf::where('email', $reset->email)->first();

        $pbf->password_hash = Hash::make($request->password);
        $pbf->save();

        $reset->update(['status' => PasswordResetOtp::STATUS_TERVERIFIKASI]);

        return redirect()->route('pemohon.login')->with('success', 'Password berhasil direset. Silakan login dengan password baru.');
    }
}
