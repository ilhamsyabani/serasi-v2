<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Services\NotifikasiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifikasiLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        // Admin IT melihat semua notifikasi; kepala_balai hanya melihat miliknya
        $isAdminIt = $user->role?->kode === 'admin_it';

        $query = Notifikasi::query()
            ->with(['permohonan:id,no_registrasi,nama_pbf_snapshot', 'permohonan.pbf:id,nama_pbf,email,no_whatsapp'])
            ->latest();

        if (!$isAdminIt) {
            $permohonanIds = Permohonan::where('kepala_balai_id', $user->id)->pluck('id');
            $query->whereIn('permohonan_id', $permohonanIds);
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

        $permohonan = $notifikasi->permohonan;

        if ($notifikasi->channel === Notifikasi::CHANNEL_WHATSAPP) {
            $noWa = $permohonan->pbf->no_whatsapp ?? null;
            if (!$noWa) {
                return back()->with('error', 'Nomor WhatsApp pemohon kosong. Tidak dapat mengirim ulang.');
            }

            $template = \App\Models\TemplateNotifikasi::where('kode_event', $notifikasi->template_kode)
                ->where('channel', Notifikasi::CHANNEL_WHATSAPP)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                return back()->with('error', 'Template WhatsApp tidak ditemukan.');
            }

            $isi = $template->isi_template;
            $placeholders = [
                '{{no_registrasi}}' => $permohonan->no_registrasi,
                '{{nama_pbf}}' => $permohonan->nama_pbf_snapshot,
            ];
            foreach ($placeholders as $key => $val) {
                $isi = str_replace($key, $val, $isi);
            }

            $sender = app(\App\Services\WhatsappSender::class);
            $sent = $sender->send($noWa, $isi);

            $notifikasi->update([
                'status_kirim' => $sent ? Notifikasi::STATUS_TERKIRIM : Notifikasi::STATUS_GAGAL,
                'sent_at' => $sent ? now() : null,
            ]);

            return back()->with('success', $sent ? 'WhatsApp berhasil dikirim ulang.' : 'WhatsApp gagal dikirim.');
        }

        // Email - kirim via AkunBaruMail jika AKUN_BARU, sonst via NotifikasiMail
        if ($notifikasi->template_kode === 'AKUN_BARU' && $notifikasi->channel === Notifikasi::CHANNEL_EMAIL) {
            $pbf = $permohonan->pbf;
            \Illuminate\Support\Facades\Mail::to($pbf->email)->queue(
                new \App\Mail\AkunBaruMail(
                    $pbf->email,
                    '***', // jangan kirim password asli saat resend
                    $permohonan->nama_pbf_snapshot,
                    $permohonan->no_registrasi,
                    $permohonan->nib_snapshot,
                    $pbf->alamat ?? '-',
                )
            );
            $notifikasi->update([
                'status_kirim' => Notifikasi::STATUS_TERKIRIM,
                'sent_at' => now(),
            ]);
            return back()->with('success', 'Email berhasil dikirim ulang.');
        }

        // Email generik via template
        $template = \App\Models\TemplateNotifikasi::where('kode_event', $notifikasi->template_kode)
            ->where('channel', Notifikasi::CHANNEL_EMAIL)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return back()->with('error', 'Template email tidak ditemukan.');
        }

        $email = $permohonan->pbf->email ?? null;
        if (!$email) {
            return back()->with('error', 'Email pemohon kosong. Tidak dapat mengirim ulang.');
        }

        $isi = $template->isi_template;
        $placeholders = [
            '{{no_registrasi}}' => $permohonan->no_registrasi,
            '{{nama_pbf}}' => $permohonan->nama_pbf_snapshot,
        ];
        foreach ($placeholders as $key => $val) {
            $isi = str_replace($key, $val, $isi);
        }

        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(
                new \App\Mail\NotifikasiMail($isi, $notifikasi->template_kode, $template->subjek)
            );
            $notifikasi->update([
                'status_kirim' => Notifikasi::STATUS_TERKIRIM,
                'sent_at' => now(),
            ]);
            return back()->with('success', 'Email berhasil dikirim ulang.');
        } catch (\Throwable $e) {
            $notifikasi->update([
                'status_kirim' => Notifikasi::STATUS_GAGAL,
                'error_message' => $e->getMessage(),
            ]);
            return back()->with('error', 'Email gagal dikirim: ' . $e->getMessage());
        }
    }

    public function resendAll(Request $request)
    {
        $user = Auth::user();
        $isAdminIt = $user->role?->kode === 'admin_it';

        if ($isAdminIt) {
            $failedLogs = Notifikasi::where('status_kirim', Notifikasi::STATUS_GAGAL)
                ->with('permohonan.pbf')
                ->get();
        } else {
            $permohonanIds = Permohonan::where('kepala_balai_id', $user->id)->pluck('id');
            $failedLogs = Notifikasi::whereIn('permohonan_id', $permohonanIds)
                ->where('status_kirim', Notifikasi::STATUS_GAGAL)
                ->with('permohonan.pbf')
                ->get();
        }

        if ($failedLogs->isEmpty()) {
            return back()->with('info', 'Tidak ada notifikasi yang gagal.');
        }

        $berhasil = 0;
        $gagal = 0;

        foreach ($failedLogs as $log) {
            $permohonan = $log->permohonan;
            if (!$permohonan) continue;

            try {
                if ($log->channel === Notifikasi::CHANNEL_WHATSAPP) {
                    $noWa = $permohonan->pbf->no_whatsapp ?? null;
                    if (!$noWa) {
                        $log->update(['error_message' => 'Nomor WhatsApp kosong']);
                        $gagal++;
                        continue;
                    }
                    $template = \App\Models\TemplateNotifikasi::where('kode_event', $log->template_kode)
                        ->where('channel', Notifikasi::CHANNEL_WHATSAPP)
                        ->where('is_active', true)->first();
                    if (!$template) {
                        $log->update(['error_message' => 'Template WhatsApp tidak ditemukan']);
                        $gagal++;
                        continue;
                    }
                    $isi = $this->buildTemplate($template->isi_template, $permohonan);
                    $sender = app(\App\Services\WhatsappSender::class);
                    $sent = $sender->send($noWa, $isi);
                    if ($sent) {
                        $log->update(['status_kirim' => Notifikasi::STATUS_TERKIRIM, 'sent_at' => now(), 'error_message' => null]);
                        $berhasil++;
                    } else {
                        $log->update(['error_message' => 'Gateway WhatsApp menolak']);
                        $gagal++;
                    }
                } elseif ($log->channel === Notifikasi::CHANNEL_EMAIL) {
                    if ($log->template_kode === 'AKUN_BARU') {
                        $pbf = $permohonan->pbf;
                        \Illuminate\Support\Facades\Mail::to($pbf->email)->queue(
                            new \App\Mail\AkunBaruMail(
                                $pbf->email, '***',
                                $permohonan->nama_pbf_snapshot,
                                $permohonan->no_registrasi,
                                $permohonan->nib_snapshot,
                                $pbf->alamat ?? '-',
                            )
                        );
                        $log->update(['status_kirim' => Notifikasi::STATUS_TERKIRIM, 'sent_at' => now(), 'error_message' => null]);
                        $berhasil++;
                    } else {
                        $template = \App\Models\TemplateNotifikasi::where('kode_event', $log->template_kode)
                            ->where('channel', Notifikasi::CHANNEL_EMAIL)
                            ->where('is_active', true)->first();
                        if (!$template) {
                            $log->update(['error_message' => 'Template email tidak ditemukan']);
                            $gagal++;
                            continue;
                        }
                        $email = $permohonan->pbf->email ?? null;
                        if (!$email) {
                            $log->update(['error_message' => 'Email kosong']);
                            $gagal++;
                            continue;
                        }
                        $isi = $this->buildTemplate($template->isi_template, $permohonan);
                        \Illuminate\Support\Facades\Mail::to($email)->send(
                            new \App\Mail\NotifikasiMail($isi, $log->template_kode, $template->subjek)
                        );
                        $log->update(['status_kirim' => Notifikasi::STATUS_TERKIRIM, 'sent_at' => now(), 'error_message' => null]);
                        $berhasil++;
                    }
                }
            } catch (\Throwable $e) {
                $log->update(['error_message' => $e->getMessage()]);
                $gagal++;
            }
        }

        return back()->with('success', "Kirim ulang selesai: {$berhasil} berhasil, {$gagal} gagal.");
    }

    private function buildTemplate(string $template, Permohonan $permohonan): string
    {
        $placeholders = [
            '{{no_registrasi}}' => $permohonan->no_registrasi,
            '{{nama_pbf}}' => $permohonan->nama_pbf_snapshot,
        ];
        foreach ($placeholders as $key => $val) {
            $template = str_replace($key, $val, $template);
        }
        return $template;
    }
}
