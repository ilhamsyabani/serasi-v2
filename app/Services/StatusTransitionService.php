<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Models\User;

/**
 * Satu-satunya titik masuk perubahan status permohonan (CLAUDE.md §5).
 *
 * Semua aturan siklus status dijaga di sini agar konsisten & mudah diaudit:
 * - Maks. 3 siklus revisi; revisi_4 dst. DITOLAK (CLAUDE.md §3 poin 2).
 * - `permohonan.revisi_ke` dipelihara otomatis saat masuk tahap revisi.
 * - Tahap revisi_1/2/3 = clock-off (SLA staff berhenti, CLAUDE.md §3 poin 3).
 */
class StatusTransitionService
{
    /** Batas jumlah siklus revisi. Revisi ke-(MAKS+1) tidak boleh terjadi. */
    public const MAKS_REVISI = 3;

    public function transisi(Permohonan $permohonan, string $statusBaru, ?string $catatan = null, ?User $actor = null, ?string $aktorTipe = 'internal'): StatusLog
    {
        $statusLama = $permohonan->status_saat_ini;

        if ($statusLama === $statusBaru && StatusLog::where('permohonan_id', $permohonan->id)->whereNull('waktu_selesai')->exists()) {
            throw new \RuntimeException('Status sudah aktif dan sama.');
        }

        // Penjaga aturan maks 3 revisi + pemeliharaan revisi_ke. Ditaruh SEBELUM
        // menulis apa pun agar transisi ilegal (revisi_4) gagal tanpa efek samping.
        if (preg_match('/^revisi_(\d+)$/', $statusBaru, $m)) {
            $ke = (int) $m[1];
            if ($ke > self::MAKS_REVISI) {
                throw new \RuntimeException(
                    "Revisi ke-$ke melebihi batas " . self::MAKS_REVISI
                    . '. Setelah revisi ke-' . self::MAKS_REVISI . ' gagal, gunakan status ditutup_pengajuan_ulang.'
                );
            }
            $permohonan->revisi_ke = $ke;
        }

        // Tutup baris status yang sedang berjalan, hitung durasi hari kerjanya.
        $aktif = StatusLog::where('permohonan_id', $permohonan->id)->whereNull('waktu_selesai')->first();
        if ($aktif) {
            $aktif->update([
                'waktu_selesai' => now(),
                'durasi_hari_kerja' => app(SlaCalculator::class)->hitungHariKerja($aktif->waktu_mulai, now()),
            ]);
        }

        $isClockOff = in_array($statusBaru, [
            Permohonan::STATUS_REVISI_1,
            Permohonan::STATUS_REVISI_2,
            Permohonan::STATUS_REVISI_3,
        ], true);

        $log = StatusLog::create([
            'permohonan_id' => $permohonan->id,
            'status' => $statusBaru,
            'waktu_mulai' => now(),
            'waktu_selesai' => null,
            'is_clock_off' => $isClockOff,
            'durasi_hari_kerja' => null,
        ]);

        // save() (bukan update([])) agar revisi_ke yang di-set di atas ikut tersimpan.
        $permohonan->status_saat_ini = $statusBaru;
        $permohonan->save();

        if ($actor) {
            AuditTrail::create([
                'user_id' => $actor->id,
                'user_type' => $aktorTipe,
                'aksi' => 'status_transition',
                'modul' => 'M-07',
                'permohonan_id' => $permohonan->id,
                'detail' => ['dari' => $statusLama, 'ke' => $statusBaru, 'catatan' => $catatan],
            ]);
        } else {
            AuditTrail::create([
                'user_id' => 0,
                'user_type' => $aktorTipe ?? 'system',
                'aksi' => 'status_transition',
                'modul' => 'M-07',
                'permohonan_id' => $permohonan->id,
                'detail' => ['dari' => $statusLama, 'ke' => $statusBaru, 'catatan' => $catatan],
            ]);
        }

        return $log;
    }

    /**
     * Hasil evaluasi "Tidak Lengkap": minta revisi berikutnya, atau tutup permohonan
     * bila kuota 3 revisi sudah habis. Inilah satu-satunya cara yang benar untuk
     * bercabang setelah evaluasi gagal — controller tidak boleh menghitung sendiri.
     *
     * Jumlah revisi dihitung dari `status_log` aktual (bukan field `revisi_ke` yang
     * bisa tertinggal pada data lama), sehingga tahan terhadap ketidakkonsistenan.
     */
    public function mintaRevisiAtauTutup(Permohonan $permohonan, ?string $catatan, ?User $actor = null, string $aktorTipe = 'internal'): StatusLog
    {
        $revisiSudah = StatusLog::where('permohonan_id', $permohonan->id)
            ->whereIn('status', [
                Permohonan::STATUS_REVISI_1,
                Permohonan::STATUS_REVISI_2,
                Permohonan::STATUS_REVISI_3,
            ])
            ->count();

        $berikutnya = $revisiSudah + 1;

        if ($berikutnya > self::MAKS_REVISI) {
            return $this->transisi(
                $permohonan,
                Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG,
                $catatan ?? ('Revisi ke-' . self::MAKS_REVISI . ' masih tidak lengkap — perlu pengajuan ulang.'),
                $actor,
                $aktorTipe
            );
        }

        return $this->transisi($permohonan, 'revisi_' . $berikutnya, $catatan, $actor, $aktorTipe);
    }
}
