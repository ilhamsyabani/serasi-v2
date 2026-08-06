<?php

namespace Database\Seeders;

use App\Models\TemplateNotifikasi;
use Illuminate\Database\Seeder;

class TemplateNotifikasiSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // ── Email templates ───────────────────────────────────────────────
            ['kode_event' => 'AKUN_BARU', 'channel' => 'email', 'subjek' => 'Akun Portal Pelaku Usaha - SERASI', 'isi_template' => "Yth. {{nama_pbf}}\n\nAkun Portal Pelaku Usaha Anda telah dibuat.\n\nUsername: {{username}}\nPassword: {{password}}\n\nSilakan login di: {{app_url}}\nPada login pertama, kode OTP akan dikirimkan via WhatsApp.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'PENGAJUAN_BARU', 'channel' => 'email', 'subjek' => 'Pengajuan Diterima - SERASI', 'isi_template' => "Yth. {{nama_pbf}}\n\nPengajuan Anda telah diterima dengan detail sebagai berikut:\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPengajuan Anda sedang dalam proses. Pantau status secara berkala di portal.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'DISPOSISI_BARU', 'channel' => 'email', 'subjek' => 'Permohonan Baru - Didisposisikan', 'isi_template' => "Yth. {{nama_pbf}}\n\nPermohonan Anda telah didisposisikan ke Ketua Tim Sertifikasi.\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPermohonan akan segera diproses oleh tim sertifikasi. Pantau status di portal.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'REVISI_DIMINTA', 'channel' => 'email', 'subjek' => 'Permintaan Revisi - SERASI', 'isi_template' => "Yth. {{nama_pbf}}\n\nBerdasarkan hasil evaluasi, permohonan Anda memerlukan perbaikan:\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nSilakan login ke portal dan upload dokumen revisi sesuai catatan evaluator.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'DITUTUP_PENGAJUAN_ULANG', 'channel' => 'email', 'subjek' => 'Permohonan Ditutup - SERASI', 'isi_template' => "Yth. {{nama_pbf}}\n\nMohon maaf, setelah 3 (tiga) kali revisi, permohonan Anda tidak memenuhi persyaratan.\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nAnda dapat mengajukan permohonan baru secara mandiri di portal.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'REVISI_UPLOADED', 'channel' => 'email', 'subjek' => 'Revisi Diupload - SERASI', 'isi_template' => "Yth. Staff Sertifikasi,\n\nPemohon telah mengunggah revisi untuk permohonan:\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nSilakan review kembali kelengkapan dokumen.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'DISTRIBUSI_BARU', 'channel' => 'email', 'subjek' => 'Permohonan Baru - Distribusi Staff', 'isi_template' => "Yth. Staff Sertifikasi,\n\nTerdapat permohonan baru yang ditugaskan kepada Anda:\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nMohon segera diproses.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'REVISI_DITERIMA', 'channel' => 'email', 'subjek' => 'Revisi Permohonan Diterima', 'isi_template' => "Yth. Staff Sertifikasi,\n\nPemohon telah mengunggah revisi untuk permohonan:\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nSilakan review kembali kelengkapan dokumen.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'PERMOHONAN_SIAP_TERBIT', 'channel' => 'email', 'subjek' => 'Permohonan Siap Terbit', 'isi_template' => "Yth. Staff Sertifikasi,\n\nPermohonan berikut telah memenuhi persyaratan:\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nMohon upload Surat Pengesahan Denah PBF.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            ['kode_event' => 'SURAT_TERBIT', 'channel' => 'email', 'subjek' => 'Surat Pengesahan Terbit - SERASI', 'isi_template' => "Yth. {{nama_pbf}}\n\nSelamat! Surat Pengesahan Denah PBF untuk permohonan Anda telah terbit.\n\nNo. Registrasi: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nSilakan login ke portal untuk mengunduh Surat Pengesahan.\n\nHormat kami,\nTim BBPOM", 'is_active' => true],

            // ── WhatsApp templates ───────────────────────────────────────────
            ['kode_event' => 'AKUN_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔐 *Akun Portal PBF*\n\nYth. {{nama_pbf}},\n\nAkun Portal Pelaku Usaha Anda telah dibuat.\n\nUsername: {{username}}\nPassword: {{password}}\n\nLogin di: {{app_url}}\n\nPada login pertama, kode OTP akan dikirim via WhatsApp.", 'is_active' => true],

            ['kode_event' => 'PENGAJUAN_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "📋 *Pengajuan Diterima*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPengajuan Anda telah diterima dan sedang dalam proses. Pantau status di portal.", 'is_active' => true],

            ['kode_event' => 'DISPOSISI_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "📋 *Permohonan Baru - Didisposisikan*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPermohonan baru telah didisposisikan. Mohon segera ditugaskan ke Staff Sertifikasi.", 'is_active' => true],

            ['kode_event' => 'DISTRIBUSI_BARU', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔔 *Permohonan Baru*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nMohon segera diproses.", 'is_active' => true],

            ['kode_event' => 'REVISI_DIMINTA', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "📝 *Permintaan Revisi*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nDokumen belum lengkap. Mohon lakukan revisi sesuai catatan evaluator.", 'is_active' => true],

            ['kode_event' => 'DITUTUP_PENGAJUAN_ULANG', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "⚠️ *Permohonan Ditutup*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nSetelah 3 kali revisi, permohonan tidak memenuhi persyaratan.\nSilakan ajukan permohonan baru secara mandiri di portal.", 'is_active' => true],

            ['kode_event' => 'REVISI_DITERIMA', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔔 *Revisi Masuk*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nRevisi telah diupload. Silakan review.", 'is_active' => true],

            ['kode_event' => 'REVISI_UPLOADED', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "📤 *Revisi Diupload*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPemohon telah mengunggah revisi. Mohon review kembali.", 'is_active' => true],

            ['kode_event' => 'SIAP_TERBIT', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "✅ *Dokumen Lengkap*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nDokumen telah memenuhi persyaratan. Menunggu penerbitan Surat Pengesahan.", 'is_active' => true],

            ['kode_event' => 'SURAT_TERBIT', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "✅ *Surat Terbit*\n\nNo. Reg: {{no_registrasi}}\n\nSurat Pengesahan telah terbit. Silakan login ke portal.", 'is_active' => true],

            ['kode_event' => 'REASSIGNMENT', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "🔄 *Reassignment*\n\nNo. Reg: {{no_registrasi}}\n\nPermohonan dialihkan ke Staff lain.", 'is_active' => true],

            ['kode_event' => 'REMINDER', 'channel' => 'whatsapp', 'subjek' => '', 'isi_template' => "⏰ *Reminder*\n\nNo. Reg: {{no_registrasi}}\nPBF: {{nama_pbf}}\n\nPermohonan mendekati batas SLA. Segera diproses.", 'is_active' => true],
        ];

        foreach ($templates as $t) {
            TemplateNotifikasi::updateOrCreate(
                ['kode_event' => $t['kode_event'], 'channel' => $t['channel']],
                $t
            );
        }
    }
}
