<?php

namespace App\Http\Controllers\Internal\KetuaTim;

use App\Http\Controllers\Controller;
use App\Models\Permohonan;
use App\Models\User;
use App\Services\EskalasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * M-17 — Eskalasi & Reassignment. Hak ini KHUSUS Ketua Tim dan hanya atas permohonan
 * yang didisposisikan ke timnya (CLAUDE.md §3 poin 9).
 */
class EskalasiController extends Controller
{
    /** Permohonan harus milik tim Ketua Tim yang sedang login. */
    private function pastikanMilikTim(Permohonan $permohonan): void
    {
        abort_unless(
            $permohonan->disposisi?->ketua_tim_id === Auth::id(),
            403,
            'Permohonan ini tidak berada di bawah tim Anda.'
        );
    }

    public function reassign(Request $request, Permohonan $permohonan, EskalasiService $eskalasi)
    {
        $this->pastikanMilikTim($permohonan);

        $aktif = $permohonan->distribusiAktif;
        if ($aktif) {
            Gate::authorize('reassign', $aktif);
        }

        $data = $request->validate([
            'staff_id' => 'required|integer',
            'alasan' => 'required|string|min:5|max:1000',
        ], [
            'alasan.required' => 'Alasan pengalihan wajib diisi.',
            'alasan.min' => 'Alasan pengalihan terlalu singkat.',
        ]);

        $staffBaru = User::find($data['staff_id']);
        if (! $staffBaru) {
            return back()->with('error', 'Staff tujuan tidak ditemukan.');
        }

        try {
            $eskalasi->reassign($permohonan, Auth::user(), $staffBaru, $data['alasan'], $request->ip());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Permohonan {$permohonan->no_registrasi} dialihkan ke {$staffBaru->nama}.");
    }

    public function reminder(Request $request, Permohonan $permohonan, EskalasiService $eskalasi)
    {
        $this->pastikanMilikTim($permohonan);

        $data = $request->validate([
            'catatan' => 'nullable|string|max:500',
        ]);

        try {
            $terkirim = $eskalasi->kirimReminder($permohonan, Auth::user(), $data['catatan'] ?? null, $request->ip());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return $terkirim
            ? back()->with('success', 'Reminder WhatsApp terkirim ke staff penanggung jawab.')
            : back()->with('warning', 'Reminder dicatat, tetapi WhatsApp gagal terkirim (periksa nomor WA staff atau gateway).');
    }
}
