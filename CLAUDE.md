# CLAUDE.md

Panduan kerja untuk Claude Code (atau AI coding assistant lain) saat mengembangkan **Aplikasi Pengajuan Rancangan Denah PBF**. Baca juga `DESIGN.md` untuk detail lengkap analisis, business process, modul, dan skema database — file ini fokus pada **cara bekerja di repo**, bukan mengulang seluruh blueprint.

---

## 1. Ringkasan Proyek

Aplikasi untuk mengelola pengajuan, evaluasi, dan penerbitan **Surat Pengesahan Denah PBF** oleh BBPOM. Dua portal:

- **Portal Internal BBPOM** — SSO, 4 role: Kepala Balai, Ketua Tim Sertifikasi, Staff Sertifikasi, Administrator IT.
- **Portal Pelaku Usaha (PBF)** — login Email/No. WA + Password + OTP (sekali saat login pertama).

Alur inti: Pengajuan → Disposisi → Evaluasi → (Revisi maks. 3x, clock-off) → Terbit Surat Pengesahan, dengan cabang "Ditutup – Perlu Pengajuan Ulang" jika revisi ke-3 masih tidak lengkap (pemohon boleh ajukan ulang mandiri, tertaut ke permohonan lama).

## 2. Tech Stack

| Layer | Teknologi |
|---|---|
| Backend | Laravel 12 |
| Frontend | Blade + Tailwind CSS |
| JS | Alpine.js (default) — hindari framework JS berat kecuali benar-benar diperlukan |
| Database | MySQL |
| Notifikasi | Email (native Laravel Mail) + WhatsApp Gateway (HTTP client, lihat `sla_config`/konfigurasi terpisah) |
| Auth Internal | SSO BPOM (custom guard, jangan pakai Breeze/Jetstream default untuk portal internal) |
| Auth Pemohon | Guard terpisah: Email/No.WA + Password, dengan langkah OTP di tengah alur login |

## 3. Aturan Bisnis Kritis — JANGAN DILANGGAR

Ini adalah aturan yang sudah disepakati lintas Tahap 1–4 dan sering jadi sumber bug jika diabaikan:

1. **1 permohonan = 1 Staff Sertifikasi.** Tidak ada distribusi ke banyak staff sekaligus.
2. **Maks. 3 siklus revisi.** Siklus ke-4 tidak boleh terjadi — setelah revisi ke-3 masih "Tidak Lengkap", status **wajib** menjadi `ditutup_pengajuan_ulang`, bukan `revisi_4`.
3. **SLA clock-off saat status Revisi.** Selama menunggu upload revisi dari pemohon, durasi TIDAK dihitung sebagai keterlambatan SLA staff. Jangan hitung SLA evaluasi dari `created_at` permohonan secara naif — gunakan `status_log` dengan flag `is_clock_off`.
4. **Status akhir sukses = `Terbit Surat Pengesahan`.** Bukan "Selesai" — jangan buat status "Selesai" terpisah, ini sudah diputuskan final (revisi dari draft awal).
5. **Pengajuan pertama HANYA oleh Kepala Balai.** Pemohon tidak bisa membuat permohonan baru dari nol.
6. **Pengajuan ulang (setelah revisi ke-3 gagal) dilakukan MANDIRI oleh pemohon**, langsung di Portal Pelaku Usaha, TANPA melalui Kepala Balai. Ini pengecualian terhadap aturan #5 — jangan disamakan alurnya.
7. **Relasi permohonan lama↔baru wajib ditampilkan ke pemohon** (bukan cuma tercatat di DB), pakai `parent_permohonan_id` pada tabel `permohonan`.
8. **Kepala Balai bersifat view-only** — tidak ada tombol approval apa pun untuknya sebelum surat terbit.
9. **Ketua Tim punya dashboard sendiri (scoped ke timnya)**, terpisah dari akses view-only Kepala Balai. Ketua Tim juga punya hak reassign staff & kirim reminder (modul M-17) — jangan gabung hak ini ke role lain.
10. **Kredensial awal pemohon dikirim otomatis via WA/Email**, bukan proses self-registrasi manual.
11. **Tidak ada modul pembayaran/PNBP.** Jangan tambahkan fitur pembayaran kecuali diminta ulang secara eksplisit.
12. **Form evaluasi Staff disederhanakan.** Lengkap = klik OK saja (tanpa isian tambahan). Tidak Lengkap = isi kolom `catatan` saja — **jangan** tambahkan field narasi terpisah atau upload dokumen evaluasi, keduanya sudah dihapus dari desain.
13. **Notifikasi WA bukan cuma untuk pemohon.** Staff & Ketua Tim juga wajib menerima notifikasi WA di tahap tertentu (distribusi baru ke staff, revisi masuk, revisi selesai diupload pemohon, permohonan siap terbit, reassignment, reminder manual). Kepala Balai tidak termasuk penerima rutin. Jangan hardcode pengiriman notifikasi hanya ke `pbf` — tabel `notifikasi.tujuan_tipe` sudah mendukung `staff`/`ketua_tim`, gunakan itu.

## 4. Konvensi Database

- Nama tabel: `snake_case`, jamak (`permohonan` singular by design — istilah domain, bukan bahasa Inggris; tabel lain umumnya mengikuti pola `dokumen_permohonan`, `status_log`, dst. sesuai `DESIGN.md` §4).
- Riwayat/log **tidak pernah di-overwrite** — insert baris baru (lihat `distribusi`, `evaluasi`, `status_log`, `reassignment_log`). Ini untuk kebutuhan audit trail instansi pemerintah.
- Nilai SLA (jumlah hari per tahap) **tidak boleh di-hardcode** di kode — selalu ambil dari tabel `sla_config` agar Admin IT bisa mengubahnya tanpa deploy ulang.
- Hari kerja dihitung berdasarkan tabel `hari_libur` (kalender libur nasional), bukan asumsi Senin–Jumat polos.
- Enum status pakai representasi **string di kolom VARCHAR**, bukan native MySQL ENUM, agar migrasi/penambahan status baru lebih mudah tanpa `ALTER TABLE ... MODIFY`.

## 5. Struktur Kode (Konvensi Laravel)

- Ikuti struktur default Laravel 12 (`app/Models`, `app/Http/Controllers`, `app/Http/Requests`, `app/Services`).
- Logika bisnis kompleks (transisi status, perhitungan SLA, kontrol maks. 3 revisi, deteksi clock-off) **wajib** ditaruh di `app/Services/`, jangan di controller.
- Setiap perubahan status permohonan **wajib** lewat satu titik masuk (mis. `StatusTransitionService`) agar aturan #2–#4 di atas konsisten dan mudah diaudit — jangan update `permohonan.status_saat_ini` langsung dari banyak tempat.
- Setiap aksi yang mengubah data penting (disposisi, distribusi/reassign, evaluasi, upload surat) **wajib** mencatat ke `audit_trail`.
- Gunakan Laravel Policy untuk hak akses per role (lihat matriks di `DESIGN.md` §Hak Akses) — jangan cek role dengan `if` tersebar di view/controller.
- Route dua portal dipisah dengan prefix/namespace jelas, mis. `routes/internal.php` (Portal BBPOM, middleware SSO) dan `routes/pemohon.php` (Portal PBF, middleware guard `pemohon`).

## 6. Frontend / UI

- **Panduan UI Utama:** Wajib mengacu secara ketat pada `design_system.md` (Tema: *Trusted Pharmacy* - Emerald Green & Navy).
- **Framework:** Tailwind CSS (utility-first).
- **Ikonografi:** Gunakan **Phosphor Icons** (`@phosphor-icons/vue` atau sesuai framework) dengan style `regular` atau `duotone`.
- **Komponen Blade:** Buat komponen reusable untuk kartu, tombol, badge status, dan form input sesuai spesifikasi di `design_system.md`.
- **Warna SLA & Status:** 
    - Gunakan warna dari Design System: Hijau/Emerald (`#10B981`) untuk on-time/sukses, Kuning/Amber (`#F59E0B`) untuk at-risk/proses, dan Merah/Red (`#EF4444`) untuk late/gagal.
    - Hindari hardcode hex color di view, definisikan di `tailwind.config.js` atau gunakan utility class yang sudah ada (misal: `bg-emerald-500`).
- Form upload dokumen harus validasi tipe file & ukuran maksimal di sisi client (Alpine) **dan** server (Laravel Request) — jangan andalkan salah satu saja.

## 7. Testing & Kualitas

- Setiap perubahan pada `StatusTransitionService` (atau modul setara) wajib disertai test yang mencakup: batas 3 revisi, transisi ke `ditutup_pengajuan_ulang`, dan perhitungan clock-off.
- Jalankan `php artisan test` sebelum menganggap task selesai.
- Jangan commit kredensial WA Gateway/SSO — gunakan `.env`, referensikan lewat `config/services.php`.

## 8. Yang TIDAK Perlu Dikerjakan (Out of Scope)

Sesuai `DESIGN.md` §Ruang Lingkup — jangan menambahkan tanpa konfirmasi eksplisit dari product owner:
- Tanda tangan digital/elektronik di dalam sistem.
- Modul pembayaran/PNBP.
- Integrasi real-time ke sistem OSS/NIB nasional.
- Modul inspeksi lapangan fisik.
- BI/analytics lanjutan di luar Laporan & Ekspor (M-14) dasar.

## 9. Referensi

Lihat `DESIGN.md` untuk: daftar lengkap 17 modul, struktur menu, matriks hak akses per role, user journey per aktor, ERD, dan data dictionary lengkap.
