<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\OtpLog;
use App\Models\Pbf;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    public function showForm(Request $request)
    {
        $pbfId = $request->session()->get('otp_pbf_id');
        if (! $pbfId) {
            return redirect()->route('pemohon.login');
        }

        $pbf = Pbf::find($pbfId);
        if (! $pbf) {
            return redirect()->route('pemohon.login');
        }

        if ($pbf->otp_terverifikasi) {
            Auth::guard('pemohon')->login($pbf);
            $request->session()->forget('otp_pbf_id');
            return redirect()->intended(route('pemohon.dashboard'));
        }

        return view('pemohon.auth.otp', compact('pbf'));
    }

    public function verify(Request $request)
    {
        $data = $request->validate([
            'kode' => 'required|digits:6',
        ]);

        $pbfId = $request->session()->get('otp_pbf_id');
        if (! $pbfId) {
            return redirect()->route('pemohon.login');
        }

        $pbf = Pbf::find($pbfId);
        if (! $pbf) {
            return redirect()->route('pemohon.login');
        }

        if ($this->otpService->terlaluBanyakAttempt($pbf)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['kode' => 'Terlalu banyak percobaan salah. Silakan logout dan login ulang untuk meminta OTP baru.']);
        }

        if ($this->otpService->verifikasiOtp($pbf, $data['kode'])) {
            Auth::guard('pemohon')->login($pbf);
            $request->session()->forget('otp_pbf_id');
            return redirect()->intended(route('pemohon.dashboard'))
                ->with('success', 'Verifikasi berhasil. Selamat datang!');
        }

        return redirect()->back()
            ->withInput()
            ->withErrors(['kode' => 'Kode OTP tidak valid atau sudah kedaluwarsa.']);
    }

    public function resend(Request $request)
    {
        $pbfId = $request->session()->get('otp_pbf_id');
        if (! $pbfId) {
            return redirect()->route('pemohon.login');
        }

        $pbf = Pbf::find($pbfId);
        if (! $pbf) {
            return redirect()->route('pemohon.login');
        }

        if ($this->otpService->terlaluBanyakAttempt($pbf)) {
            return redirect()->route('pemohon.logout');
        }

        $this->otpService->buatDanKirimOtp($pbf, OtpLog::CHANNEL_WHATSAPP);

        return redirect()->back()->with('success', 'Kode OTP baru telah dikirim via WhatsApp.');
    }
}
