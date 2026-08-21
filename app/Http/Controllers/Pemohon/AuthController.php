<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\ConfigSetting;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('pemohon.auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL)
            ? ['email' => $data['identifier'], 'password' => $data['password']]
            : ['no_whatsapp' => $data['identifier'], 'password' => $data['password']];

        if (! Auth::guard('pemohon')->attempt($credentials)) {
            throw ValidationException::withMessages([
                'identifier' => 'Kredensial salah.',
            ]);
        }

        $request->session()->regenerate();
        $pbf = Auth::guard('pemohon')->user();

        // Cek apakah OTP diaktifkan dari Admin IT
        if (! ConfigSetting::get('otp_pemohon_enabled', false)) {
            return redirect()->intended(route('pemohon.dashboard'));
        }

        // OTP aktif — cek apakah sudah terverifikasi
        Auth::guard('pemohon')->logout();
        $request->session()->invalidate();

        if ($pbf->otp_terverifikasi) {
            Auth::guard('pemohon')->login($pbf);
            $request->session()->regenerate();
            return redirect()->intended(route('pemohon.dashboard'));
        }

        app(OtpService::class)->buatDanKirimOtp($pbf, 'whatsapp');
        $request->session()->put('otp_pbf_id', $pbf->id);
        return redirect()->route('pemohon.otp')->with('info', 'Kode OTP telah dikirim via WhatsApp.');
    }

    public function logout(Request $request)
    {
        Auth::guard('pemohon')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('pemohon.login');
    }
}
