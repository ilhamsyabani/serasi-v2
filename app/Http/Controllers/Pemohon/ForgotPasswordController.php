<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Pbf;
use App\Services\OtpService;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('pemohon.auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'identifier' => ['required', 'string'],
        ], [
            'identifier.required' => 'Email atau No. WhatsApp wajib diisi.',
        ]);

        $field = filter_var($request->identifier, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'no_whatsapp';
        $value = $request->identifier;

        $pbf = Pbf::where($field, $value)->first();

        if (! $pbf) {
            return back()->withInput()->withErrors([
                'identifier' => 'Email atau nomor WhatsApp tidak ditemukan dalam sistem.',
            ]);
        }

        OtpService::generateAndSendPasswordResetLink($pbf);

        $messages = ['Tautan reset password telah dikirim ke email Anda.'];
        if (empty($pbf->no_whatsapp)) {
            $messages[] = 'No. WhatsApp belum terdaftar — gunakan tautan dari email.';
        } else {
            $messages[] = 'Silakan cek WhatsApp dan/atau email Anda.';
        }

        return back()->with('success', implode(' ', $messages));
    }
}
