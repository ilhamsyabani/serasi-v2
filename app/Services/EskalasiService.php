<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Distribusi;
use App\Models\Permohonan;
use App\Models\ReassignmentLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Modul M-17 — Eskalasi & Reassignment, khusus Ketua Tim (CLAUDE.md §3 poin 9).
 *
 * - Reassign: memindahkan permohonan ke staff lain. Status & jam SLA TIDAK direset;
 *   baris distribusi lama dinonaktifkan (bukan dihapus) agar tetap 1 staff aktif.
 * - Reminder manual: mengingatkan staff penanggung jawab lewat WhatsApp.
 *
 * Setiap aksi dicatat di `reassignment_log` dan `audit_trail`.
 */
class EskalasiService
{
    /** Tahap ketika permohonan sedang dipegang seorang staff dan boleh dialihkan. */
    public const STATUS_BISA_REASSIGN = [
        Permohonan::STATUS_PROSES_EVALUASI,
        Permohonan::STATUS_REVISI_1,
        Permohonan::STATUS_REVISI_2,
        Permohonan::STATUS_REVISI_3,
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
    ];

    /** Tahap ketika bola ada di staff — saat revisi, yang ditunggu adalah pemohon. */
    public const STATUS_BISA_REMINDER = [
        Permohonan::STATUS_PROSES_EVALUASI,
        Permohonan::STATUS_MENUNGGU_SURAT_PENGESAHAN,
    ];

    /** Jeda minimal antar reminder manual untuk permohonan yang sama (menit). */
    public const JEDA_REMINDER_MENIT = 60;

    public function __construct(private NotifikasiService $notifikasi) {}

    public function bisaReassign(Permohonan $permohonan): bool
    {
        return in_array($permohonan->status_saat_ini, self::STATUS_BISA_REASSIGN, true)
            && $permohonan->distribusiAktif !== null;
    }

    public function bisaReminder(Permohonan $permohonan): bool
    {
        return in_array($permohonan->status_saat_ini, self::STATUS_BISA_REMINDER, true)
            && $permohonan->distribusiAktif !== null;
    }

    /**
     * @throws \DomainException bila aksi tidak sah (pesannya aman ditampilkan ke pengguna)
     */
    public function reassign(Permohonan $permohonan, User $ketuaTim, User $staffBaru, string $alasan, ?string $ip = null): Distribusi
    {
        if (! $staffBaru->is_aktif || ! $staffBaru->isStaffSertifikasi()) {
            throw new \DomainException('Staff tujuan tidak valid atau tidak aktif.');
        }

        [$baru, $staffLama] = DB::transaction(function () use ($permohonan, $ketuaTim, $staffBaru, $alasan, $ip) {
            $terkunci = Permohonan::whereKey($permohonan->id)->lockForUpdate()->firstOrFail();
            $aktif = Distribusi::where('permohonan_id', $terkunci->id)->where('is_aktif', true)->latest('id')->first();

            if (! $aktif || ! in_array($terkunci->status_saat_ini, self::STATUS_BISA_REASSIGN, true)) {
                throw new \DomainException('Permohonan ini tidak sedang dipegang staff, sehingga tidak dapat dialihkan.');
            }

            if ($aktif->staff_id === $staffBaru->id) {
                throw new \DomainException('Permohonan sudah dipegang oleh staff tersebut.');
            }

            // 1 permohonan = 1 staff aktif: nonaktifkan SEMUA baris aktif, riwayat tetap ada.
            Distribusi::where('permohonan_id', $terkunci->id)->where('is_aktif', true)->update([
                'is_aktif' => false,
                'tanggal_reassign_terakhir' => now(),
            ]);

            $baru = Distribusi::create([
                'permohonan_id' => $terkunci->id,
                'ketua_tim_id' => $ketuaTim->id,
                'staff_id' => $staffBaru->id,
                'jenis' => 'reassignment',
                'is_aktif' => true,
                'tanggal' => now(),
            ]);

            ReassignmentLog::create([
                'permohonan_id' => $terkunci->id,
                'ketua_tim_id' => $ketuaTim->id,
                'staff_lama_id' => $aktif->staff_id,
                'staff_baru_id' => $staffBaru->id,
                'jenis_aksi' => ReassignmentLog::JENIS_REASSIGN,
                'alasan' => $alasan,
            ]);

            AuditTrail::create([
                'user_id' => $ketuaTim->id,
                'user_type' => AuditTrail::USER_TYPE_INTERNAL,
                'aksi' => 'reassign_staff',
                'modul' => 'M-17',
                'permohonan_id' => $terkunci->id,
                'detail' => ['staff_lama_id' => $aktif->staff_id, 'staff_baru_id' => $staffBaru->id, 'alasan' => $alasan],
                'ip_address' => $ip,
            ]);

            return [$baru, User::find($aktif->staff_id)];
        });

        $permohonan->refresh();

        // WA ke staff baru dan staff lama (CLAUDE.md §3 poin 13: reassignment).
        $this->notifikasi->kirimNotifikasiStaff($staffBaru, $permohonan, 'REASSIGNMENT');
        if ($staffLama) {
            $this->notifikasi->kirimNotifikasiStaff($staffLama, $permohonan, 'REASSIGNMENT');
        }

        return $baru;
    }

    /**
     * @return bool true bila WhatsApp berhasil terkirim
     * @throws \DomainException bila aksi tidak sah
     */
    public function kirimReminder(Permohonan $permohonan, User $ketuaTim, ?string $catatan = null, ?string $ip = null): bool
    {
        $staff = $permohonan->distribusiAktif?->staff;

        if (! $staff || ! in_array($permohonan->status_saat_ini, self::STATUS_BISA_REMINDER, true)) {
            throw new \DomainException('Reminder hanya bisa dikirim saat permohonan sedang dikerjakan staff.');
        }

        $terakhir = ReassignmentLog::where('permohonan_id', $permohonan->id)
            ->where('jenis_aksi', ReassignmentLog::JENIS_REMINDER)
            ->latest('id')
            ->value('created_at');

        if ($terakhir && now()->diffInMinutes($terakhir, true) < self::JEDA_REMINDER_MENIT) {
            throw new \DomainException('Reminder untuk permohonan ini baru saja dikirim. Coba lagi setelah ' . self::JEDA_REMINDER_MENIT . ' menit.');
        }

        $hasil = $this->notifikasi->kirimNotifikasiStaff($staff, $permohonan, 'REMINDER');
        $terkirim = $hasil !== null && $hasil->status_kirim === \App\Models\Notifikasi::STATUS_TERKIRIM;

        ReassignmentLog::create([
            'permohonan_id' => $permohonan->id,
            'ketua_tim_id' => $ketuaTim->id,
            'staff_lama_id' => $staff->id,
            'staff_baru_id' => null,
            'jenis_aksi' => ReassignmentLog::JENIS_REMINDER,
            'alasan' => $catatan,
        ]);

        AuditTrail::create([
            'user_id' => $ketuaTim->id,
            'user_type' => AuditTrail::USER_TYPE_INTERNAL,
            'aksi' => 'reminder_manual',
            'modul' => 'M-17',
            'permohonan_id' => $permohonan->id,
            'detail' => ['staff_id' => $staff->id, 'terkirim' => $terkirim, 'catatan' => $catatan],
            'ip_address' => $ip,
        ]);

        return $terkirim;
    }
}
