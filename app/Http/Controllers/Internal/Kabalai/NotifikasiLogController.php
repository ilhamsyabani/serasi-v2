<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotifikasiLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Admin IT melihat semua notifikasi; kepala_balai hanya melihat miliknya
        $isAdminIt = $user->role?->kode === 'admin_it';

        $query = Notifikasi::query()
            ->with(['permohonan:id,no_registrasi,nama_pbf_snapshot,pbf_id', 'permohonan.pbf:id,nama_pbf,email,no_whatsapp'])
            ->latest();

        if (!$isAdminIt) {
            $permohonanIds = Permohonan::where('kepala_balai_id', $user->id)->pluck('id');
            $query->whereIn('permohonan_id', $permohonanIds);
        }

        // Filter search (no_registrasi atau nama_pbf)
        if ($request->filled('search')) {
            $search = trim($request->get('search'));
            $query->whereHas('permohonan', function ($q) use ($search) {
                $q->where('no_registrasi', 'like', "%{$search}%")
                  ->orWhere('nama_pbf_snapshot', 'like', "%{$search}%");
            });
        }

        // Filter status
        if ($request->get('status') === 'gagal') {
            $query->where('status_kirim', Notifikasi::STATUS_GAGAL);
        } elseif ($request->get('status') === 'terkirim') {
            $query->where('status_kirim', Notifikasi::STATUS_TERKIRIM);
        }

        // Filter channel
        if ($request->get('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        // Filter permohonan
        if ($request->get('permohonan_id')) {
            $query->where('permohonan_id', $request->get('permohonan_id'));
        }

        // Filter rentang tanggal
        if ($request->get('tanggal_dari')) {
            $query->whereDate('created_at', '>=', $request->get('tanggal_dari'));
        }
        if ($request->get('tanggal_sampai')) {
            $query->whereDate('created_at', '<=', $request->get('tanggal_sampai'));
        }

        $logs = $query->paginate(20)->withQueryString();

        // Dropdown permohonan untuk filter — Admin IT lihat semua
        if ($isAdminIt) {
            $permohonanList = Permohonan::orderByDesc('tanggal_pengajuan')->get(['id', 'no_registrasi', 'nama_pbf_snapshot']);
        } else {
            $permohonanList = Permohonan::where('kepala_balai_id', $user->id)
                ->orderByDesc('tanggal_pengajuan')
                ->get(['id', 'no_registrasi', 'nama_pbf_snapshot']);
        }

        // Statistik ringkasan
        if ($isAdminIt) {
            $stats = [
                'total' => Notifikasi::count(),
                'terkirim' => Notifikasi::where('status_kirim', Notifikasi::STATUS_TERKIRIM)->count(),
                'gagal' => Notifikasi::where('status_kirim', Notifikasi::STATUS_GAGAL)->count(),
                'email' => Notifikasi::where('channel', Notifikasi::CHANNEL_EMAIL)->count(),
                'whatsapp' => Notifikasi::where('channel', Notifikasi::CHANNEL_WHATSAPP)->count(),
            ];
        } else {
            $permohonanIds = Permohonan::where('kepala_balai_id', $user->id)->pluck('id');
            $stats = [
                'total' => Notifikasi::whereIn('permohonan_id', $permohonanIds)->count(),
                'terkirim' => Notifikasi::whereIn('permohonan_id', $permohonanIds)->where('status_kirim', Notifikasi::STATUS_TERKIRIM)->count(),
                'gagal' => Notifikasi::whereIn('permohonan_id', $permohonanIds)->where('status_kirim', Notifikasi::STATUS_GAGAL)->count(),
                'email' => Notifikasi::whereIn('permohonan_id', $permohonanIds)->where('channel', Notifikasi::CHANNEL_EMAIL)->count(),
                'whatsapp' => Notifikasi::whereIn('permohonan_id', $permohonanIds)->where('channel', Notifikasi::CHANNEL_WHATSAPP)->count(),
            ];
        }

        return view('internal.kabalai.notifikasi-log.index', compact('logs', 'stats', 'permohonanList'));
    }

    public function resend(Notifikasi $notifikasi)
    {
        $user = Auth::user();
        $isAdminIt = $user->role?->kode === 'admin_it';

        // Admin IT bisa resend semua; kepala_balai hanya miliknya
        if (!$isAdminIt) {
            abort_unless(
                $notifikasi->permohonan
                    && $notifikasi->permohonan->kepala_balai_id === $user->id,
                403
            );
        }

        $res = $this->executeResend($notifikasi);

        if ($res['success']) {
            return back()->with('success', $res['message']);
        }

        return back()->with('error', $res['message']);
    }

    public function resendAll(Request $request)
    {
        $user = Auth::user();
        $isAdminIt = $user->role?->kode === 'admin_it';

        if ($isAdminIt) {
            $failedLogs = Notifikasi::where('status_kirim', Notifikasi::STATUS_GAGAL)
                ->with(['permohonan.pbf'])
                ->get();
        } else {
            $permohonanIds = Permohonan::where('kepala_balai_id', $user->id)->pluck('id');
            $failedLogs = Notifikasi::whereIn('permohonan_id', $permohonanIds)
                ->where('status_kirim', Notifikasi::STATUS_GAGAL)
                ->with(['permohonan.pbf'])
                ->get();
        }

        if ($failedLogs->isEmpty()) {
            return back()->with('info', 'Tidak ada notifikasi yang gagal.');
        }

        $berhasil = 0;
        $gagal = 0;

        foreach ($failedLogs as $log) {
            $res = $this->executeResend($log);
            if ($res['success']) {
                $berhasil++;
            } else {
                $gagal++;
            }
        }

        return back()->with('success', "Kirim ulang selesai: {$berhasil} berhasil, {$gagal} gagal.");
    }

    /**
     * Eksekusi pengiriman ulang untuk satu notifikasi (WhatsApp atau Email).
     */
    private function executeResend(Notifikasi $notifikasi): array
    {
        $permohonan = $notifikasi->permohonan;
        [$email, $noWa, $nama] = $this->resolveRecipient($notifikasi);

        if ($notifikasi->channel === Notifikasi::CHANNEL_WHATSAPP) {
            if (!$noWa) {
                $msg = 'Nomor WhatsApp penerima kosong (' . $notifikasi->tujuan_tipe . ').';
                $notifikasi->increment('retry_count');
                $notifikasi->update(['error_message' => $msg]);
                return ['success' => false, 'message' => $msg];
            }

            $template = \App\Models\TemplateNotifikasi::where('kode_event', $notifikasi->template_kode)
                ->where('channel', Notifikasi::CHANNEL_WHATSAPP)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                $msg = 'Template WhatsApp tidak ditemukan untuk ' . $notifikasi->template_kode;
                $notifikasi->increment('retry_count');
                $notifikasi->update(['error_message' => $msg]);
                return ['success' => false, 'message' => $msg];
            }

            $isi = $this->compileMessage($template->isi_template, $permohonan, $notifikasi);

            Log::info('NotifikasiLogController: resend WA', [
                'notifikasi_id' => $notifikasi->id,
                'no_wa' => $noWa,
                'template_kode' => $notifikasi->template_kode,
            ]);

            $sender = app(\App\Services\WhatsappSender::class);
            $sent = $sender->send($noWa, $isi);

            $notifikasi->increment('retry_count');
            $notifikasi->update([
                'status_kirim' => $sent ? Notifikasi::STATUS_TERKIRIM : Notifikasi::STATUS_GAGAL,
                'sent_at' => $sent ? now() : null,
                'error_message' => $sent ? null : 'Gateway WhatsApp menolak pengiriman.',
            ]);

            return [
                'success' => $sent,
                'message' => $sent ? "WhatsApp berhasil dikirim ulang ke {$noWa}." : "WhatsApp gagal dikirim ke {$noWa}.",
            ];
        }

        if ($notifikasi->channel === Notifikasi::CHANNEL_EMAIL) {
            if (!$email) {
                $msg = 'Alamat email penerima kosong (' . $notifikasi->tujuan_tipe . ').';
                $notifikasi->increment('retry_count');
                $notifikasi->update(['error_message' => $msg]);
                return ['success' => false, 'message' => $msg];
            }

            if ($notifikasi->template_kode === 'AKUN_BARU') {
                try {
                    \Illuminate\Support\Facades\Mail::to($email)->send(
                        new \App\Mail\AkunBaruMail(
                            $email,
                            '***',
                            $permohonan?->nama_pbf_snapshot ?? $nama,
                            $permohonan?->no_registrasi ?? '-',
                            $permohonan?->nib_snapshot ?? '-',
                            $permohonan?->pbf?->alamat ?? '-',
                        )
                    );
                    $notifikasi->increment('retry_count');
                    $notifikasi->update([
                        'status_kirim' => Notifikasi::STATUS_TERKIRIM,
                        'sent_at' => now(),
                        'error_message' => null,
                    ]);
                    Log::info('NotifikasiLogController: AKUN_BARU email berhasil dikirim', [
                        'notifikasi_id' => $notifikasi->id,
                        'email' => $email,
                    ]);
                    return ['success' => true, 'message' => "Email berhasil dikirim ulang ke {$email}."];
                } catch (\Throwable $e) {
                    Log::error('NotifikasiLogController: AKUN_BARU email gagal', [
                        'notifikasi_id' => $notifikasi->id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                    ]);
                    $notifikasi->increment('retry_count');
                    $notifikasi->update([
                        'status_kirim' => Notifikasi::STATUS_GAGAL,
                        'error_message' => $e->getMessage(),
                    ]);
                    return ['success' => false, 'message' => "Email gagal dikirim ke {$email}: " . $e->getMessage()];
                }
            }

            $template = \App\Models\TemplateNotifikasi::where('kode_event', $notifikasi->template_kode)
                ->where('channel', Notifikasi::CHANNEL_EMAIL)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                $msg = 'Template email tidak ditemukan untuk ' . $notifikasi->template_kode;
                $notifikasi->increment('retry_count');
                $notifikasi->update(['error_message' => $msg]);
                return ['success' => false, 'message' => $msg];
            }

            $isi = $this->compileMessage($template->isi_template, $permohonan, $notifikasi);

            Log::info('NotifikasiLogController: resend email generik', [
                'notifikasi_id' => $notifikasi->id,
                'email' => $email,
                'template_kode' => $notifikasi->template_kode,
                'subject' => $template->subjek,
            ]);

            try {
                \Illuminate\Support\Facades\Mail::to($email)->send(
                    new \App\Mail\NotifikasiMail($isi, $notifikasi->template_kode, $template->subjek)
                );
                $notifikasi->increment('retry_count');
                $notifikasi->update([
                    'status_kirim' => Notifikasi::STATUS_TERKIRIM,
                    'sent_at' => now(),
                    'error_message' => null,
                ]);
                Log::info('NotifikasiLogController: email generik berhasil dikirim', [
                    'notifikasi_id' => $notifikasi->id,
                    'email' => $email,
                ]);
                return ['success' => true, 'message' => "Email berhasil dikirim ulang ke {$email}."];
            } catch (\Throwable $e) {
                Log::error('NotifikasiLogController: email generik gagal', [
                    'notifikasi_id' => $notifikasi->id,
                    'email' => $email,
                    'template_kode' => $notifikasi->template_kode,
                    'error' => $e->getMessage(),
                ]);
                $notifikasi->increment('retry_count');
                $notifikasi->update([
                    'status_kirim' => Notifikasi::STATUS_GAGAL,
                    'error_message' => $e->getMessage(),
                ]);
                return ['success' => false, 'message' => "Email gagal dikirim ke {$email}: " . $e->getMessage()];
            }
        }

        return ['success' => false, 'message' => "Channel {$notifikasi->channel} tidak didukung."];
    }

    /**
     * Resolve email, nomor WA, dan nama penerima berdasarkan tujuan_tipe dan tujuan_id.
     */
    private function resolveRecipient(Notifikasi $notifikasi): array
    {
        $permohonan = $notifikasi->permohonan;
        $tujuan = $notifikasi->tujuan();

        if ($notifikasi->tujuan_tipe === Notifikasi::TUJUAN_PEMOHON) {
            $email = $tujuan?->email ?? $permohonan?->email_snapshot ?? $permohonan?->pbf?->email;
            $noWa = $tujuan?->no_whatsapp ?? $permohonan?->no_wa_snapshot ?? $permohonan?->pbf?->no_whatsapp;
            $nama = $tujuan?->nama_pbf ?? $permohonan?->nama_pbf_snapshot ?? 'Pemohon';
        } else {
            $email = $tujuan?->email;
            $noWa = $tujuan?->no_whatsapp;
            $nama = $tujuan?->nama ?? ucfirst(str_replace('_', ' ', $notifikasi->tujuan_tipe));
        }

        return [$email, $noWa, $nama];
    }

    /**
     * Substitusi placeholder template dengan data permohonan.
     */
    private function compileMessage(string $template, ?Permohonan $permohonan, ?Notifikasi $notifikasi = null): string
    {
        $placeholders = [
            '{{no_registrasi}}' => $permohonan?->no_registrasi ?? '-',
            '{{nama_pbf}}' => $permohonan?->nama_pbf_snapshot ?? '-',
            '{{nib}}' => $permohonan?->nib_snapshot ?? '-',
            '{{alamat}}' => $permohonan?->pbf?->alamat ?? '-',
            '{{app_url}}' => config('app.url'),
        ];

        foreach ($placeholders as $key => $val) {
            $template = str_replace($key, $val, $template);
        }

        return $template;
    }
}
