<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\DokumenPermohonan;
use App\Models\Evaluasi;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Pengajuan ulang permohonan berstatus `ditutup_pengajuan_ulang` oleh Kepala Balai
 * (CLAUDE.md §3 poin 6). Jalur ini berdampingan dengan pengajuan ulang mandiri oleh
 * pemohon: satu permohonan ditutup hanya boleh diajukan ulang SEKALI, siapa pun
 * yang lebih dulu.
 *
 * Permohonan baru memakai akun PBF yang sama (data PBF tidak diketik ulang) dan
 * tertaut ke permohonan lama lewat `parent_permohonan_id` (CLAUDE.md §3 poin 7).
 */
class PengajuanUlangService
{
    public const TEMPLATE = 'PENGAJUAN_ULANG';

    /** Permohonan ditutup yang belum diajukan ulang. */
    public function queryPerluPengajuanUlang(): Builder
    {
        return Permohonan::query()
            ->where('status_saat_ini', Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)
            ->whereDoesntHave('pengajuanUlang');
    }

    /** Permohonan ditutup yang sudah diajukan ulang (oleh siapa pun). */
    public function querySudahDiajukanUlang(): Builder
    {
        return Permohonan::query()
            ->where('status_saat_ini', Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG)
            ->whereHas('pengajuanUlang');
    }

    public function bisaDiajukanUlang(Permohonan $permohonan): bool
    {
        return $permohonan->status_saat_ini === Permohonan::STATUS_DITUTUP_PENGAJUAN_ULANG
            && ! $permohonan->pengajuanUlang()->exists();
    }

    /** Catatan evaluasi "Tidak Lengkap" terakhir — alasan permohonan ditutup. */
    public function alasanDitutup(Permohonan $permohonan): ?string
    {
        return Evaluasi::where('permohonan_id', $permohonan->id)
            ->where('hasil', Evaluasi::HASIL_TIDAK_LENGKAP)
            ->latest('id')
            ->value('catatan');
    }

    /**
     * Buat permohonan pengajuan ulang. Atomik & aman dari kiriman ganda: permohonan
     * lama dikunci, lalu diperiksa ulang bahwa belum ada pengajuan ulang.
     *
     * @param  array<string,UploadedFile>  $dokumen  kunci = jenis_dokumen (DokumenPermohonan::JENIS)
     *
     * @throws \DomainException bila permohonan lama tidak lagi bisa diajukan ulang
     */
    public function ajukanOlehKepalaBalai(Permohonan $lama, User $kepalaBalai, array $dokumen): Permohonan
    {
        $baru = DB::transaction(function () use ($lama, $kepalaBalai, $dokumen) {
            $terkunci = Permohonan::whereKey($lama->id)->lockForUpdate()->firstOrFail();

            if (! $this->bisaDiajukanUlang($terkunci)) {
                throw new \DomainException("Permohonan {$terkunci->no_registrasi} sudah diajukan ulang atau tidak lagi berstatus ditutup.");
            }

            // Data PBF diambil dari akun yang sudah terdaftar — tidak diketik ulang.
            $pbf = $terkunci->pbf;

            $baru = Permohonan::create([
                'no_registrasi' => Permohonan::generateNoRegistrasi(),
                'pbf_id' => $pbf->id,
                'parent_permohonan_id' => $terkunci->id,
                'nama_pbf_snapshot' => $pbf->nama_pbf,
                'nib_snapshot' => $pbf->nib,
                'email_snapshot' => $pbf->email,
                'no_wa_snapshot' => $pbf->no_whatsapp,
                'status_saat_ini' => Permohonan::STATUS_PENGAJUAN,
                'tanggal_pengajuan' => now(),
                'kepala_balai_id' => $kepalaBalai->id,
                'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
            ]);

            foreach ($dokumen as $jenis => $file) {
                if (! array_key_exists($jenis, DokumenPermohonan::JENIS) || ! $file instanceof UploadedFile) {
                    continue;
                }

                DokumenPermohonan::create([
                    'permohonan_id' => $baru->id,
                    'jenis_dokumen' => $jenis,
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => app(BerkasService::class)->simpan($file, 'dokumen_permohonan'),
                    'ukuran_file_kb' => (int) ceil($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by_user_id' => $kepalaBalai->id,
                    'uploaded_at' => now(),
                ]);
            }

            app(StatusTransitionService::class)->transisi(
                $baru,
                Permohonan::STATUS_PENGAJUAN,
                "Pengajuan ulang dari {$terkunci->no_registrasi} oleh Kepala Balai",
                $kepalaBalai,
                'internal'
            );

            AuditTrail::create([
                'user_id' => $kepalaBalai->id,
                'user_type' => 'internal',
                'aksi' => 'pengajuan_ulang',
                'modul' => 'M-02',
                'permohonan_id' => $baru->id,
                'detail' => [
                    'permohonan_lama_id' => $terkunci->id,
                    'no_registrasi_lama' => $terkunci->no_registrasi,
                    'dokumen' => array_keys($dokumen),
                ],
            ]);

            return $baru;
        });

        // Notifikasi setelah data tersimpan. Akun pemohon sudah ada: tanpa kredensial.
        $notif = app(NotifikasiService::class);
        $placeholder = ['{{no_registrasi_lama}}' => $lama->no_registrasi];
        $notif->kirim($baru, Notifikasi::TUJUAN_PEMOHON, $baru->pbf_id, Notifikasi::CHANNEL_WHATSAPP, self::TEMPLATE, null, $placeholder);
        $notif->kirim($baru, Notifikasi::TUJUAN_PEMOHON, $baru->pbf_id, Notifikasi::CHANNEL_EMAIL, self::TEMPLATE, null, $placeholder);

        return $baru;
    }
}
