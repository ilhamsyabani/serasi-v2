<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Models\SuratPengesahan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Koreksi status oleh Admin IT: mengembalikan permohonan yang keliru dinilai "Lengkap"
 * (atau yang sedang proses evaluasi) menjadi "Tidak Lengkap", sehingga masuk ke
 * revisi BERIKUTNYA, atau ditutup bila kuota revisi sudah habis.
 *
 * Nomor revisi tidak bisa dipilih bebas: siklus selalu maju lewat
 * StatusTransitionService::mintaRevisiAtauTutup(), jadi kuota MAKS_REVISI tetap
 * terjaga (CLAUDE.md §3 poin 2). Riwayat tidak ditimpa (CLAUDE.md §4): koreksi
 * ditambahkan sebagai evaluasi baru, status_log baru, dan audit_trail.
 */
class KoreksiStatusService
{
    /** Status asal yang boleh dikoreksi. Status akhir (terbit/ditutup) tidak boleh. */
    public const STATUS_BOLEH = [
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
        Permohonan::STATUS_PROSES_EVALUASI,
    ];

    public function __construct(
        private StatusTransitionService $transisi,
        private NotifikasiService $notif,
    ) {}

    /** Alasan koreksi ditolak, atau null bila boleh. */
    public function alasanTidakBoleh(Permohonan $p): ?string
    {
        if (!in_array($p->status_saat_ini, self::STATUS_BOLEH, true)) {
            return 'Hanya permohonan berstatus Menunggu Surat atau Proses Evaluasi yang dapat dikembalikan ke revisi.';
        }
        if (SuratPengesahan::where('permohonan_id', $p->id)->exists()) {
            return 'Surat pengesahan sudah diunggah.';
        }
        $logAktif = StatusLog::where('permohonan_id', $p->id)->whereNull('waktu_selesai')->orderByDesc('id')->first();
        if (!$logAktif || $logAktif->status !== $p->status_saat_ini) {
            return 'Status permohonan tidak sinkron dengan riwayat status (status_log). Hubungi pengembang.';
        }
        return null;
    }

    /** Status tujuan bila dikoreksi sekarang: revisi_N atau ditutup_pengajuan_ulang. */
    public function statusTujuan(Permohonan $p): string
    {
        return $this->transisi->statusSetelahTidakLengkap($p);
    }

    /**
     * @param string $catatan Catatan ketidaksesuaian, dibaca pemohon sebagai petunjuk revisi.
     * @param string $alasan  Alasan internal koreksi (audit trail, notifikasi internal).
     */
    public function kembalikanKeRevisi(Permohonan $permohonan, string $catatan, string $alasan, User $aktor, bool $kirimNotifikasi = true): StatusLog
    {
        if (!$aktor->isAdminIt()) {
            throw new \RuntimeException('Hanya Admin IT yang dapat mengoreksi status permohonan.');
        }

        $statusAsal = null;
        $log = DB::transaction(function () use ($permohonan, $catatan, $alasan, $aktor, $kirimNotifikasi, &$statusAsal) {
            // Kunci baris agar submit ganda tidak memajukan revisi dua kali.
            $p = Permohonan::whereKey($permohonan->id)->lockForUpdate()->firstOrFail();
            if ($tolak = $this->alasanTidakBoleh($p)) {
                throw new \RuntimeException($tolak);
            }
            $statusAsal = $p->status_saat_ini;

            $evaluasiTerakhir = Evaluasi::where('permohonan_id', $p->id)
                ->orderByDesc('siklus_ke')->orderByDesc('id')->first();

            // Menunggu surat = mengoreksi evaluasi "Lengkap" yang keliru → siklus yang sama.
            // Proses evaluasi = belum dievaluasi → ini evaluasi siklus berikutnya.
            $siklus = $statusAsal === Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN && $evaluasiTerakhir
                ? $evaluasiTerakhir->siklus_ke
                : ($evaluasiTerakhir?->siklus_ke ?? 0) + 1;

            $evaluasi = Evaluasi::create([
                'permohonan_id' => $p->id,
                'staff_id' => $aktor->id,
                'siklus_ke' => $siklus,
                'hasil' => Evaluasi::HASIL_TIDAK_LENGKAP,
                'catatan' => $catatan,
                'tanggal_evaluasi' => now(),
            ]);

            $log = $this->transisi->mintaRevisiAtauTutup($p, "Koreksi Admin IT: $alasan", $aktor, 'internal');

            AuditTrail::create([
                'user_id' => $aktor->id,
                'user_type' => 'internal',
                'aksi' => 'koreksi_status',
                'modul' => 'M-07',
                'permohonan_id' => $p->id,
                'detail' => [
                    'dari' => $statusAsal,
                    'ke' => $log->status,
                    'alasan' => $alasan,
                    'evaluasi_dikoreksi_id' => $statusAsal === Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN ? $evaluasiTerakhir?->id : null,
                    'evaluasi_koreksi_id' => $evaluasi->id,
                    'kirim_notifikasi' => $kirimNotifikasi,
                ],
            ]);

            return $log;
        });

        $permohonan->refresh();
        if ($kirimNotifikasi) {
            $this->kirimNotifikasi($permohonan, $log->status, $aktor);
        }

        return $log;
    }

    /** Notifikasi sama seperti evaluasi Tidak Lengkap biasa, ditambah ke evaluator aktif. */
    private function kirimNotifikasi(Permohonan $p, string $statusBaru, User $aktor): void
    {
        $template = $statusBaru === Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG ? 'DITUTUP_PENGAJUAN_ULANG' : 'REVISI_DIMINTA';

        $this->notif->kirim($p, Notifikasi::TUJUAN_PEMOHON, $p->pbf_id, Notifikasi::CHANNEL_WHATSAPP, $template);
        $this->notif->kirim($p, Notifikasi::TUJUAN_PEMOHON, $p->pbf_id, Notifikasi::CHANNEL_EMAIL, $template);

        $ketuaTimId = $p->disposisi?->ketua_tim_id;
        if ($ketuaTimId && ($ketuaTim = User::find($ketuaTimId))) {
            $this->notif->kirimNotifikasiKetuaTim($ketuaTim, $p, $template);
            $this->notif->kirim($p, Notifikasi::TUJUAN_KETUA_TIM, $ketuaTimId, Notifikasi::CHANNEL_EMAIL, $template);
        }

        // Evaluator perlu tahu hasil evaluasinya dikoreksi (kecuali ia Ketua Tim yang sudah dikirimi).
        $staffId = $p->distribusiAktif?->staff_id;
        if ($staffId && $staffId !== $ketuaTimId && $staffId !== $aktor->id && ($staff = User::find($staffId))) {
            $this->notif->kirimNotifikasiStaff($staff, $p, $template);
        }
    }
}
