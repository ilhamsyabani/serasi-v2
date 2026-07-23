<?php

namespace App\Policies;

use App\Models\Permohonan;
use App\Models\User;

class PermohonanPolicy
{
    /** viewAny: Kabalai (list miliknya), Katim, Staff, Admin IT. */
    public function viewAny(User $user): bool
    {
        return $user->isKepalaBalai()
            || $user->isKetuaTim()
            || $user->isStaffSertifikasi()
            || $user->isAdminIt();
    }

    /**
     * Hak LIHAT detail permohonan (view-only, tidak memberi hak aksi apa pun —
     * aksi tetap dijaga policy/route terpisah, lihat CLAUDE.md §3 poin 8 & 9).
     *
     * - Kepala Balai & Admin IT: seluruh permohonan. Kepala Balai adalah pemberi
     *   disposisi untuk semua permohonan balai, dan permohonan hasil pengajuan
     *   ulang mandiri pemohon punya `kepala_balai_id` NULL — pembatasan ke
     *   `kepala_balai_id === user->id` membuat permohonan tersebut mustahil dibuka.
     * - Ketua Tim: permohonan yang didisposisikan ke dirinya, ditambah yang belum
     *   didisposisikan (perlu terlihat agar bisa disiapkan pendistribusiannya).
     * - Staff: permohonan yang pernah ATAU sedang ditugaskan padanya. Memakai
     *   `distribusiAktif` saja membuat staff kehilangan akses ke berkas yang sudah
     *   selesai maupun yang di-reassign — padahal jejaknya tetap perlu dibaca.
     */
    public function view(User $user, Permohonan $permohonan): bool
    {
        if ($user->isKepalaBalai() || $user->isAdminIt()) {
            return true;
        }

        if ($user->isKetuaTim()) {
            $ketuaTimId = $permohonan->disposisi?->ketua_tim_id;

            return $ketuaTimId === null || $ketuaTimId === $user->id;
        }

        if ($user->isStaffSertifikasi()) {
            return $permohonan->distribusi()->where('staff_id', $user->id)->exists();
        }

        return false;
    }

    /** Hanya Kepala Balai yang boleh buat permohonan baru. */
    public function create(User $user): bool
    {
        return $user->isKepalaBalai();
    }

    /** Kepala Balai boleh edit data pemohon di permohonan miliknya (belum final). */
    public function update(User $user, Permohonan $permohonan): bool
    {
        if ($user->isKepalaBalai()) {
            return $user->id === $permohonan->kepala_balai_id && !$permohonan->isStatusAkhir();
        }
        return false;
    }

    /** Tidak ada delete. */
    public function delete(User $user, Permohonan $permohonan): bool
    {
        return false;
    }
}
