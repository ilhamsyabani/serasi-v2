<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('internal.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'Alamat email tidak ditemukan dalam sistem.',
        ]);

        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return back()->with('success', 'Link reset password telah dikirim ke email Anda.');
            }

            return back()->withErrors(['email' => __($status)]);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim reset link email (internal)', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal mengirim email. Pastikan server email terkonfigurasi dengan benar.');
        }
    }
}
