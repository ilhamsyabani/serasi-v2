<?php

namespace Database\Seeders;

use App\Models\TemplateNotifikasi;
use Illuminate\Database\Seeder;

class TemplateNotifikasiSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Email templates
            ['kode_event' => 'DISTRIBUSI_BARU', 'channel' => 'email', 'subjek' => 'Permohonan Baru - Distribusi Staff', 'isi_template' => "Yth. Staff Sertifikasi,\n\nTerdapat permohonan baru No. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nMohon segera diproses.", 'is_active' => true],
            ['kode_event' => 'REVISI_DITERIMA', 'channel' => 'email', 'subjek' => 'Revisi Permohonan Diterima', 'isi_template' => "Yth. Staff Sertifikasi,\n\nPemohon telah mengunggah revisi untuk permohonan {{no_registrasi}}.\nPBF: {{nama_pbf}}\n\nSilakan review kembali.", 'is_active' => true],
            ['kode_event' => 'PERMOHONAN_SIAP_TERBIT', 'channel' => 'email', 'subjek' => 'Permohonan Siap Terbit', 'isi_template' => "Yth. Staff Sertifikasi,\n\nPermohonan {{no_registrasi}} (PBF: {{nama_pbf}}) telah memenuhi persyaratan.\nMohon upload Surat Pengesahan.", 'is_active' => true],
            ['kode_event' => 'SURAT_TERBIT', 'channel' => 'email', 'subjek' => 'Surat Pengesahan Terbit', 'isi_template' => "Yth. {{nama_pbf}},\n\nSelamat! Surat Pengesahan Denah PBF untuk permohonan {{no_registrasi}} telah terbit.\nSilakan login ke portal untuk mengunduh surat.", 'is_active' => true],
            // WA templates
            ['kode_event' => 'DISTRIBUSI_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔔 *Permohonan Baru*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nMohon segera diproses.", 'is_active' => true],
            ['kode_event' => 'REVISI_DITERIMA', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔔 *Revisi Masuk*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nRevisi telah diupload. Silakan review.", 'is_active' => true],
            ['kode_event' => 'REASSIGNMENT', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔄 *Reassignment*\n\nNo. Reg: {{no_registrasi}}\n\nPermohonan dialihkan ke Staff lain.", 'is_active' => true],
            ['kode_event' => 'REMINDER', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "⏰ *Reminder*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPermohonan mendekati batas SLA. Segera diproses.", 'is_active' => true],
            ['kode_event' => 'SURAT_TERBIT', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "✅ *Surat Terbit*\n\nNo. Reg: {{no_registrasi}}\n\nSurat Pengesahan telah terbit. Silakan login ke portal.", 'is_active' => true],
            // WA: Kabalai disposisi ke Ketua Tim
            ['kode_event' => 'DISPOSISI_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "📋 *Permohonan Baru - Didisposisikan*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPermohonan baru telah didisposisikan. Mohon segera ditugaskan ke Staff Sertifikasi.", 'is_active' => true],
            // WA: Staff minta revisi ke Pemohon
            ['kode_event' => 'REVISI_DIMINTA', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "📝 *Permintaan Revisi*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nDokumen belum lengkap. Mohon lakukan revisi sesuai catatan evaluator.", 'is_active' => true],
            // WA: Kredensial akun baru ke Pemohon (password disubstitusi manual via pesanManual)
            ['kode_event' => 'AKUN_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔐 *Akun Portal PBF*\n\nYth. {{nama_pbf}},\n\nAkun Portal Pelaku Usaha Anda telah dibuat.\n\nUsername: {{username}}\nPassword: {{password}}\n\nLogin di: {{app_url}}\n\nPada login pertama, kode OTP akan dikirim via WhatsApp.", 'is_active' => true],
        ];

        foreach ($templates as $t) {
            TemplateNotifikasi::updateOrCreate(
                ['kode_event' => $t['kode_event'], 'channel' => $t['channel']],
                $t
            );
        }
    }
}
