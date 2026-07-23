# DESIGN.md — Aplikasi Pengajuan Rancangan Denah PBF

Dokumen rancangan konsolidasi (analisis sistem, business process, perancangan sistem, dan database) hasil pembahasan bertahap. Untuk panduan kerja/konvensi kode, lihat `CLAUDE.md`. 

---

## 1. Latar Belakang & Tujuan

BBPOM mendigitalisasi proses pengajuan **Surat Pengesahan Denah PBF**, dari input permohonan sampai penerbitan surat, melalui dua portal terintegrasi:

- **Portal Internal BBPOM** (SSO) — Kepala Balai, Ketua Tim Sertifikasi, Staff Sertifikasi, Administrator IT.
- **Portal Pelaku Usaha** — login Email/No. WA + Password, OTP hanya saat login pertama.

**Tujuan utama:** transparansi status real-time bagi pemohon, kontrol SLA otomatis per tahap, standardisasi alur revisi (maks. 3x), audit trail penuh, dan sentralisasi data legal PBF.

**Di luar ruang lingkup:** tanda tangan digital (dilakukan di aplikasi lain), pembayaran/PNBP (tidak ada biaya), integrasi real-time ke OSS/NIB nasional, inspeksi lapangan fisik, BI lanjutan.

---

## 2. Aktor

| Aktor | Portal | Peran Inti |
|---|---|---|
| Kepala Balai | Internal | Input permohonan pertama (fungsi "Sekretaris" melekat di sini), disposisi ke Ketua Tim, monitoring **view-only** (tanpa approval) |
| Ketua Tim Sertifikasi | Internal | Distribusi ke 1 Staff, dashboard SLA sendiri (scoped ke timnya), reassign staff & reminder |
| Staff Sertifikasi | Internal | Evaluasi dokumen, catat revisi, upload surat pengesahan final |
| Administrator IT | Internal | Kelola user/role, master data, audit trail, konfigurasi sistem (SLA, hari libur, template notifikasi, integrasi) |
| Pemohon (PBF) | Pelaku Usaha | Lihat status, upload revisi, unduh surat, **ajukan ulang mandiri** (khusus pasca revisi ke-3 gagal) |

---

## 3. Alur Bisnis & Status Permohonan

```text
Pengajuan (Kepala Balai input, SLA 1 hk)
   → Didisposisikan (Kepala Balai → Ketua Tim, SLA 1 hk)
   → Proses Evaluasi (Ketua Tim → 1 Staff, SLA 7 hk)
        ├─ Lengkap → Menunggu Surat Pengesahan (SLA 3 hk atau lebih cepat) → Terbit Surat Pengesahan (status akhir)
        └─ Tidak Lengkap → Revisi ke-N (N=1,2,3; SLA clock-off, tanpa batas waktu)
              → pemohon upload → kembali ke Proses Evaluasi
              → jika revisi ke-3 masih Tidak Lengkap → Ditutup – Perlu Pengajuan Ulang (status akhir)
                    → pemohon ajukan ulang MANDIRI via portal (tanpa lewat Kepala Balai)
                    → permohonan baru menampilkan relasi eksplisit "Diajukan ulang dari No. XXX"

```

**9 Status baku:** `Pengajuan`, `Didisposisikan`, `Proses Evaluasi`, `Revisi ke-1`, `Revisi ke-2`, `Revisi ke-3`, `Ditutup – Perlu Pengajuan Ulang`, `Menunggu Surat Pengesahan`, `Terbit Surat Pengesahan`.

**Aturan SLA penting:**

* Dihitung dalam hari kerja (mengacu kalender hari libur).
* Status Revisi bersifat **clock-off** — jam SLA berhenti selama menunggu pemohon.
* Nilai SLA dikonfigurasi (bukan hardcode): Pengajuan 1 hk, Disposisi 1 hk, Evaluasi 7 hk, Menunggu Surat Pengesahan 3 hk atau lebih cepat.

---

## 4. Modul Aplikasi (17 Modul)

| Kode | Modul | Ringkasan |
| --- | --- | --- |
| M-01 | Autentikasi & Akses | SSO (internal), Email/WA + Password + OTP sekali (pemohon) |
| M-02 | Manajemen Permohonan | Input (Kepala Balai) / ajukan ulang mandiri (Pemohon), no. registrasi, relasi lama↔baru |
| M-03 | Disposisi & Distribusi | Kepala Balai → Ketua Tim → Staff (1:1) |
| M-04 | Evaluasi Dokumen | Lengkap/Tidak Lengkap, catatan, narasi, lampiran, kontrol maks. 3 revisi |
| M-05 | Revisi Dokumen (Pemohon) | Upload revisi per siklus |
| M-06 | Penerbitan Surat Pengesahan | Upload PDF surat final bertanda tangan |
| M-07 | Tracking Status & Timeline | Timeline 9 status + durasi per tahap |
| M-08 | Dashboard Monitoring SLA | Scoped per role (Ketua Tim: timnya; Staff: miliknya) |
| M-09 | Notifikasi Otomatis | Email & WhatsApp Gateway, log & retry |
| M-10 | Manajemen User & Role | CRUD user internal (Admin IT) |
| M-11 | Audit Trail | Log aktivitas lintas modul |
| M-12 | Unduh Dokumen | Unduh surat final (Pemohon) |
| M-13 | Master Data PBF | NIB, nama, kontak — reuse otomatis |
| M-14 | Laporan & Ekspor Data | Rekap, cetak, export Excel/PDF |
| M-15 | Monitoring View-Only (Kepala Balai) | Lihat seluruh progres tanpa approval |
| M-16 | Konfigurasi Sistem | SLA, hari libur, template notifikasi, integrasi (Admin IT) |
| M-17 | Eskalasi & Reassignment | Reassign staff, reminder, monitor progres (Ketua Tim) |

---

## 5. Hak Akses per Role (Ringkasan)

Legenda: **F** = Full, **E** = Eksekusi aksi spesifik, **V** = View only, **–** = Tidak ada akses.

| Modul | Kepala Balai | Ketua Tim | Staff | Admin IT | Pemohon |
| --- | --- | --- | --- | --- | --- |
| M-02 Manajemen Permohonan | F (pengajuan pertama) | V | V | V | F (khusus pengajuan ulang) |
| M-03 Disposisi & Distribusi | E (disposisi) | E (distribusi) | – | V | – |
| M-04 Evaluasi | V | V | F | V | – |
| M-05 Revisi | V | V | V | V | F |
| M-06 Penerbitan Surat | V | V | E | V | – |
| M-08 Dashboard SLA | – (lihat M-15) | V (dashboard sendiri) | V (miliknya) | V | – |
| M-12 Unduh Dokumen | V | V | V | V | E |
| M-14 Laporan & Ekspor | F | F | – | F | – |
| M-15 View-Only | F | – | – | – | – |
| M-17 Eskalasi | – | F | – | – | – |

---

## 6. Skema Database (Ringkasan)

### 6.1 Tabel Inti

| Tabel | Fungsi |
| --- | --- |
| `roles`, `users` | Role & akun internal BBPOM |
| `pbf` | Master data + akun login pemohon (NIB, kontak, password_hash) |
| `permohonan` | Data pengajuan; `parent_permohonan_id` (self-FK) untuk relasi pengajuan ulang; `dibuat_oleh_tipe` (kepala_balai/pemohon) |
| `dokumen_permohonan` | 5 dokumen wajib, dengan `versi` |
| `disposisi` | Kepala Balai → Ketua Tim (1 per permohonan) |
| `distribusi` | Ketua Tim → Staff, histori reassignment (`is_aktif` flag) |
| `evaluasi` | Siklus evaluasi (0=awal, 1–3=pasca revisi), hasil, catatan, narasi |
| `dokumen_evaluasi` | Lampiran evaluasi dari Staff |
| `revisi`, `dokumen_revisi` | Upload revisi pemohon per siklus |
| `surat_pengesahan` | File PDF final + metadata |
| `status_log` | Riwayat status, `is_clock_off`, `durasi_hari_kerja` — basis timeline & SLA |
| `notifikasi` | Log Email/WA per event, retry |
| `reassignment_log` | Aksi M-17 (reassign/reminder) |
| `otp_log` | Riwayat OTP pemohon |
| `sla_config` | Nilai SLA per tahap (dikelola Admin IT, bukan hardcode) |
| `hari_libur` | Kalender libur untuk hitung hari kerja |
| `audit_trail` | Log aktivitas lintas modul |

---

## 7. Asumsi Desain Terbuka (Perlu Konfirmasi Lanjutan)

1. **Struktur akun pemohon** — asumsi saat ini 1 NIB = 1 akun login. Jika satu PBF punya beberapa PIC dengan login terpisah, tabel `pbf` perlu dipecah jadi master data + akun kredensial (1:N).
2. **Channel notifikasi** — asumsi setiap event mengirim ke Email **dan** WhatsApp sekaligus; belum dikonfirmasi apakah pemohon bisa memilih preferensi channel.
3. **Reassignment di tengah siklus revisi** — belum dikonfirmasi apakah Staff baru melanjutkan siklus revisi yang sama atau evaluasi diulang dari awal.
4. **Retensi/arsip dokumen** — belum ada kebijakan penghapusan otomatis dokumen lama.

---

## 8. Tahap 5: Panduan UI/UX (Trusted Pharmacy Theme)

Aplikasi akan menggunakan **Tailwind CSS** dengan antarmuka yang mengacu secara penuh pada **`design_system.md`** (Tema: *Trusted Pharmacy* - Emerald Green & Navy). Ini menggantikan panduan Shadcn UI/Dasbor Shape sebelumnya untuk lebih menyesuaikan dengan standar aplikasi farmasi/kesehatan.

### 8.1 Tema & Skema Warna (Berdasarkan Design System)
* **Background Utama:** Slate 50 (`#F8FAFC`) — memberikan kesan bersih dan higienis.
* **Aksen Utama (Primary):** Emerald Green (`#10B981`) — digunakan untuk CTA utama, tombol "Terbit Surat", indikator sukses, dan elemen interaktif.
* **Warna Sekunder/Heading:** Navy Blue (`#1E3A8A`) — untuk judul, teks penting, dan navigasi aktif (memberikan kesan profesional & aman).
* **Tipografi:** Sans-serif modern yang bersih (seperti Inter, Plus Jakarta Sans, atau Roboto). Teks utama menggunakan warna Slate 700 (`#334155`).
* **Ikonografi:** **Phosphor Icons** wajib digunakan untuk seluruh ikon aplikasi (style `regular` atau `duotone`).

### 8.2 Struktur Layout (Dashboard Internal BBPOM)
* Menggunakan sistem grid 12-kolom standar dengan *spacing* yang lega (ruang kosong/whitespace luas).
* **Sidebar (Kiri):**
    * Lebar tetap.
    * Item aktif menggunakan latar belakang transparan biru/hijau muda dengan teks dan Phosphor Icon berwarna Primary/Secondary. Terdapat garis vertikal penanda di sisi kiri.
    * Item pasif berwarna Muted (`#64748B`).
* **Header (Atas):** Berisi judul halaman (Navy Blue, Bold) dan elemen pencarian/profil.
* **Kartu Konten:** Berlatar putih (`#FFFFFF`), sudut melengkung `rounded-2xl`, dengan batas halus (`border-slate-200`) dan bayangan lembut (`shadow-sm`).

### 8.3 Komponen Utama (Implementasi Tailwind CSS)
Sesuai panduan di `design_system.md`:
1. **Tombol (Buttons):** 
    * Primary: Latar `bg-emerald-500`, teks putih, `rounded-lg` atau `rounded-xl`.
    * Secondary: Latar `bg-slate-100`, teks Navy.
2. **Form Input:** Latar putih, border halus, *focus state* dengan ring Emerald (`focus:ring-emerald-500/20`). Padding luas agar terlihat higienis.
3. **Badge Status:** Bentuk pil (`rounded-full`), warna disesuaikan dengan semantik (Hijau untuk selesai/tersedia, Amber untuk proses/revisi, Merah untuk gagal/tutup).
4. **Tabel & Data:** Jarak antar baris lebar, teks mudah dibaca dengan kontras yang baik.

---

## 9. Status Dokumen

Tahap 1 (Analisis Sistem), Tahap 2 (Business Process), Tahap 3 (Perancangan Sistem), Tahap 4 (Perancangan Database), **Tahap 5 (UI/UX)**, dan **Tahap 6 (Development Plan)** telah disetujui. Dokumen ini adalah konsolidasinya sebagai referensi kerja penuh.
