<?php

namespace App\Http\Requests;

use App\Models\DokumenPermohonan;
use App\Models\Permohonan;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi input permohonan baru oleh Kepala Balai (M-03).
 *
 * Aturan kelima dokumen diambil dari DokumenPermohonan::aturanValidasi() agar
 * daftar ekstensi & batas ukuran hanya didefinisikan di satu tempat — lihat
 * CLAUDE.md §6: validasi file wajib ada di sisi server, bukan hanya di Alpine.
 */
class StorePermohonanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Permohonan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'nib' => 'required|string|max:30',
            'nama_pbf' => 'required|string|max:200',
            'alamat' => 'nullable|string|max:500',
            'email' => 'required|email|max:150',
            'no_whatsapp' => 'required|string|max:20',
            // Dokumen opsional saat input: kelengkapan diperiksa Staff pada tahap evaluasi.
        ] + DokumenPermohonan::aturanValidasi(wajib: false);
    }

    public function attributes(): array
    {
        $atribut = [
            'nib' => 'NIB',
            'nama_pbf' => 'Nama PBF',
            'no_whatsapp' => 'No. WhatsApp',
        ];

        foreach (DokumenPermohonan::JENIS as $kode => $meta) {
            $atribut[$kode] = $meta['label'];
        }

        return $atribut;
    }

    public function messages(): array
    {
        $maksMb = round(DokumenPermohonan::UKURAN_MAKS_KB / 1024);
        $ekstensi = strtoupper(implode(', ', DokumenPermohonan::EKSTENSI_DIIZINKAN));

        return [
            '*.mimes' => 'Berkas :attribute harus berformat ' . $ekstensi . '.',
            '*.max' => 'Ukuran berkas :attribute maksimal ' . $maksMb . ' MB.',
        ];
    }
}
