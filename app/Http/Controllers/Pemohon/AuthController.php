<?php

namespace App\Http\Controllers\Pemohon;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\Pbf;
use App\Models\Role;
use App\Models\User;
use App\Services\StatusTransitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        if (Auth::guard('pemohon')->attempt($credentials)) {
            $request->session()->regenerate();
            $pbf = Auth::guard('pemohon')->user();

            if (!$pbf->otp_terverifikasi) {
                Auth::guard('pemohon')->logout();
                $request->session()->invalidate();
                return redirect()->route('pemohon.login')->with('otp_required', true)->with('pbf_id', $pbf->id);
            }

            return redirect()->intended(route('pemohon.dashboard'));
        }

        return back()->withErrors(['identifier' => 'Kredensial salah.'])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::guard('pemohon')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('pemohon.login');
    }
}
