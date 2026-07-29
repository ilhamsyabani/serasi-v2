<?php

namespace App\Listeners;

use App\Events\PermohonanStatusChanged;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Services\NotifikasiService;

/**
 * Listener otomatis yang dipicu setiap kali status permohonan berubah.
 * Matriks notifikasi berdasarkan transisi status.
 *
 * Aturan CLAUDE.md §3 poin 13:
 * - Pemohon terima notifikasi di tahap: pengajuan baru, revisi, terbit surat, ditutup
 * - Staff terima notifikasi di tahap: distribusi baru, revisi upload, siap terbit
 * - Ketua Tim terima notifikasi di tahap: disposisi baru, revisi masuk/diproses, siap terbit
 * - Kepala Balai TIDAK termasuk penerima rutin (view-only)
 *
 * Nota bene: notifikasi yang TIDAK bisa ditentukan dari status saja (mis. reassignment
 * karena ditriger dari aksi eksplisit KT, bukan transisi status) tetap dipanggil langsung
 * dari controller, bukan di sini.
 */
class PermohonanNotifier
{
    public function __construct(private NotifikasiService $notif) {}

    public function handle(PermohonanStatusChanged $event): void
    {
        $p = $event->permohonan;
        $dari = $event->statusLama;
        $ke = $event->statusBaru;
        $ktId = $p->disposisi?->ketua_tim_id;
        $staffId = $p->distribusiAktif?->staff_id;
        $pbfId = $p->pbf_id;

        // ── Transisi: → pengajuan (permohonan baru dibuat) ──────────────────
        // Pemohon diberitahu bahwa pengajuannya sudah tercatat
        if ($ke === Permohonan::STATUS_PENGAJUAN && $dari === null) {
            if ($pbfId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_PEMOHON, $pbfId,
                    Notifikasi::CHANNEL_WHATSAPP, 'PENGAJUAN_BARU'
                );
            }
            return;
        }

        // ── Transisi: pengajuan → didisposisikan ──────────────────────────────
        // Ketua Tim diberitahu ada permohonan baru untuk ditugaskan
        if ($dari === Permohonan::STATUS_PENGAJUAN && $ke === Permohonan::STATUS_DIDISPOSISIKAN) {
            if ($ktId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_KETUA_TIM, $ktId,
                    Notifikasi::CHANNEL_WHATSAPP, 'DISPOSISI_BARU'
                );
            }
            return;
        }

        // ── Transisi: didisposisikan → proses_evaluasi (distribusi) ───────────
        // Staff diberitahu bahwa permohonan ditugaskan ke mereka
        if ($dari === Permohonan::STATUS_DIDISPOSISIKAN && $ke === Permohonan::STATUS_PROSES_EVALUASI) {
            if ($staffId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_STAFF, $staffId,
                    Notifikasi::CHANNEL_EMAIL, 'DISTRIBUSI_BARU'
                );
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_STAFF, $staffId,
                    Notifikasi::CHANNEL_WHATSAPP, 'DISTRIBUSI_BARU'
                );
            }
            return;
        }

        // ── Transisi: proses_evaluasi → revisi_1/2/3 ─────────────────────────
        // Pemohon diberitahu bahwa dokumen belum lengkap dan perlu revisi.
        // Notifikasi REVISI_DIMINTA sudah dipanggil langsung dari EvaluasiController
        // (karena butuh catatan evaluasi). Di sini kita hanya handle kasus transisi
        // otomatis jika ada alur lain yang menyentuh revisi.
        if ($dari === Permohonan::STATUS_PROSES_EVALUASI
            && in_array($ke, [Permohonan::STATUS_REVISI_1, Permohonan::STATUS_REVISI_2, Permohonan::STATUS_REVISI_3], true)
        ) {
            if ($pbfId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_PEMOHON, $pbfId,
                    Notifikasi::CHANNEL_WHATSAPP, 'REVISI_DIMINTA'
                );
            }
            return;
        }

        // ── Transisi: proses_evaluasi → ditutup_pengajuan_ulang ───────────────
        // Pemohon diberitahu bahwa 3x revisi gagal.
        if ($dari === Permohonan::STATUS_PROSES_EVALUASI
            && $ke === Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG
        ) {
            if ($pbfId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_PEMOHON, $pbfId,
                    Notifikasi::CHANNEL_WHATSAPP, 'DITUTUP_PENGAJUAN_ULANG'
                );
            }
            return;
        }

        // ── Transisi: proses_evaluasi → menunggu_surat_pengesahan ────────────
        // Staff diberitahu bahwa evaluasi lengkap dan siap terbit surat.
        // (Notifier ini sebagai backup — notifikasi SIAP_TERBIT sudah dipanggil
        // langsung dari EvaluasiController dengan trigger eksplisit.)
        if ($dari === Permohonan::STATUS_PROSES_EVALUASI
            && $ke === Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN
        ) {
            if ($staffId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_STAFF, $staffId,
                    Notifikasi::CHANNEL_WHATSAPP, 'SIAP_TERBIT'
                );
            }
            return;
        }

        // ── Transisi: menunggu_surat → terbit_surat_pengesahan ───────────────
        // Pemohon diberitahu bahwa Surat Pengesahan sudah terbit.
        if ($dari === Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN
            && $ke === Permohonan::STATUS_TERBIT_SURAT_PENGESAHAN
        ) {
            if ($pbfId) {
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_PEMOHON, $pbfId,
                    Notifikasi::CHANNEL_EMAIL, 'SURAT_TERBIT'
                );
                $this->notif->kirim(
                    $p, Notifikasi::TUJUAN_PEMOHON, $pbfId,
                    Notifikasi::CHANNEL_WHATSAPP, 'SURAT_TERBIT'
                );
            }
            return;
        }
    }
}
