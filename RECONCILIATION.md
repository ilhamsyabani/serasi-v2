# RECONCILIATION.md — Rekonsiliasi Skema Database

> Dokumen khusus yang menjembatani **DESIGN.md / Tahap 4** (acuan resmi yang sudah disetujui)
> dengan **kode aktual** yang sudah ada di `database/migrations/` dan `app/Models/`.
>
> Tujuan: memberi satu daftar keputusan yang harus dieksekusi sebelum development modul
> berlanjut, sehingga aturan di [CLAUDE.md](CLAUDE.md) dan [DESIGN.md](DESIGN.md) tidak
> dilanggar.

---

## 0. Konteks

Saat repo ini dibuka, sudah ada 18 migration kustom dan 17 model yang ditulis — tetapi
sebagian besar **menyimpang** dari desain final di `DESIGN.md §6` & `Tahap 4`. Tanpa
rekonsiliasi, setiap service/controller/policy yang kita tulis selanjutnya akan:
- melawan aturan di `CLAUDE.md §3` (13 aturan bisnis),
- mereferensikan kolom yang tidak ada di migration (atau sebaliknya),
- mengunci kita ke arah yang tidak bisa diubah murah di tengah jalan.

**Keputusan yang diambil** (lihat ringkasan di bagian bawah dokumen ini):

1. **Skema final** mengikuti `DESIGN.md §6` & `Tahap 4` (sudah disetujui dan ditandatangani
   lewat klarifikasi). Migration/model yang menyimpang akan **diubah** supaya konsisten.
2. Keputusan konkret untuk tiap deviasi dicatat di bagian **2. Putusan**.

---

## 1. Inventarisasi Deviasi

### 1.1 Nama tabel yang tidak cocok dengan dokumen

| Acuan (DESIGN.md/Tahap 4) | Kode aktual | Status |
|---|---|---|
| `roles` | `role` (migration) **+ override** `$table = 'roles'` di `Role.php` | ❌ Migration harus rename ke `roles` |
| `users` | `users` | ✅ |
| `pbf` | tabel dipecah → `master_data_pbf` + `pemohons` | ⚠️ Lihat putusan di §2.A |
| `permohonan` | `permohonans` (migration) **+ override** `$table = 'permohonan'` di `Permohonan.php` | ❌ Migration harus rename ke `permohonan` |
| `dokumen_permohonan` | `dokumen_permohonan` | ✅ |
| `disposisi` | `disposisi` | ✅ |
| `distribusi` | `distribusi` | ✅ tapi `unique()` salah — lihat §1.3 |
| `evaluasi` | `evaluasi` | ✅ |
| `revisi` | **tidak ada** | ❌ Tambahkan |
| `dokumen_revisi` | **tidak ada** | ❌ Tambahkan |
| `surat_pengesahan` | **tidak ada** | ❌ Tambahkan |
| `status_log` | `status_history` (nama + kolom berbeda) | ❌ Rename ke `status_log`, kolom distandarkan |
| `notifikasi` | `notifikasi_log` (kolom tidak dukung tujuan_tipe) | ❌ Tambah `tujuan_tipe` & `tujuan_id` (polimorfik) |
| `otp_log` | `password_reset_otp` (campur first-login & reset) | ❌ Pecah jadi `otp_log` + `password_reset_tokens` (yang terakhir sudah ada dari Laravel default) |
| `sla_config` | `sla_config` (kolom berbeda) | ❌ Standarkan ke kolom `kode_tahap` |
| `hari_libur` | **tidak ada** | ❌ Tambahkan |
| `audit_trail` | `audit_trail` (polymorphic vs FK eksplisit) | ⚠️ Lihat putusan di §2.B |
| `reassignment_log` | `reassignment_log` (unique salah + kolom jenis_aksi hilang) | ❌ Perbaiki keduanya |

### 1.2 Kolom yang menyimpang dari dokumen

| Tabel | Kolom di dokumen (DESIGN.md) | Kolom aktual (migration) | Putusan |
|---|---|---|---|
| `permohonan.status_saat_ini` | `VARCHAR(50)` denormalisasi string | `unsignedTinyInteger status_id` FK | **Ikut dokumen**: pakai VARCHAR string. CLAUDE.md §4 tegas: *"Enum status pakai representasi string di kolom VARCHAR, bukan native MySQL ENUM"*. |
| `permohonan.revisi_ke` | tidak ada di Tahap 4 (ada di `evaluasi.siklus_ke`) | `tinyInteger revisi_ke` | **Pertahankan**: cache cepat untuk kontrol 3-batas; tambahkan komentar bahwa angka ini harus sama dengan `MAX(evaluasi.siklus_ke) WHERE hasil='tidak_lengkap'`. |
| `permohonan.input_by_pemohon_id` | n/a (Tahap 4 pakai `dibuat_oleh_tipe ENUM`) | `foreignId input_by_pemohon_id` → `pemohons.id` | **Ikut dokumen**: pakai `dibuat_oleh_tipe ENUM('kepala_balai','pemohon')` saja. Field input_by_user_id sudah cukup + nullable. |
| `status_history` | nama tabel `status_log`, kolom `waktu_mulai`/`waktu_selesai`, `is_clock_off`, `durasi_hari_kerja` | `started_at`/`ended_at`, `is_clockoff`, `sla_target_hari_kerja`, `is_late`, FK ke `users`/`pemohons` | **Rename + standar kolom** ke `waktu_mulai`/`waktu_selesai`/`is_clock_off`/`durasi_hari_kerja`. Field FK actor boleh disimpan juga untuk audit (lihat `audit_trail`), tapi kolom wajib minimum sesuai dokumen. |
| `permohonan.sla_deadline_current`, `permohonan.is_overdue` | tidak ada di Tahap 4 | ada di migration repo | **Pertahankan**: kolom turunan yang membantu query dashboard SLA (M-08). Catat sebagai denormalisasi yang konsisten dengan `status_log`. |
| `evaluasi.revisi_ke` vs `siklus_ke` | Tahap 4 §3.8: kolom `siklus_ke` | `revisi_ke` di migration + komentar menjelaskan | **Rename** ke `siklus_ke` supaya istilah seragam dengan Tahap 4 & CLAUDE.md. (Permohonan.revisi_ke tetap sebagai cache; bukan field yang sama.) |
| `evaluasi.hasil` | `ENUM('lengkap','tidak_lengkap')` (Tahap 4) **tapi CLAUDE.md §4 melarang native ENUM** | `ENUM('LENGKAP','TIDAK_LENGKAP')` | **Ikut CLAUDE.md**: pakai `VARCHAR(20)` + CHECK constraint atau validasi aplikasi. Evaluasi::$casts sudah handle. |
| `dokumen_permohonan.jenis_dokumen` | `ENUM(...)` (Tahap 4) | `ENUM(...)` 8 nilai (lebih banyak dari 5) | **Ikut CLAUDE.md**: pakai VARCHAR string. Daftar 8 nilai di migration dapat dipindah ke konstanta class `DokumenPermohonan`. |
| `audit_trail` | `user_id`/`user_type`/`permohonan_id` | polymorphic `auditable_type`/`auditable_id` | **Ikut dokumen**: FK eksplisit + `user_type`. Lihat §2.B untuk gabungan keputusan. |
| `notifikasi_log` | `tujuan_tipe` ENUM + `tujuan_id` polimorfik | hanya `pemohon_id` FK | **Wajib tambah**: `tujuan_tipe` + `tujuan_id`. Tanpa ini, M-09 tidak bisa mengirim ke Staff/Ketua Tim. |
| `sla_config` | `kode_tahap VARCHAR(50) UNIQUE` (Tahap 4) | `status_id` FK ke status_master | **Ikut dokumen**: pakai `kode_tahap`. Lebih portabel jika ada revisi nomor urut status. |
| `reassignment_log` | `jenis_aksi ENUM('reassign','reminder','lainnya')` + nullable `staff_lama_id`/`staff_baru_id` | tanpa `jenis_aksi`, tanpa `staff_lama_id`/`staff_baru_id`, hanya distribusi saat ini + unique di permohonan_id | **Perbaiki**: gabung `reminder_log` jadi `reassignment_log.jenis_aksi = 'reminder'`, dan izinkan multiple baris per permohonan (hapus unique). Lihat §1.3. |

### 1.3 Deviasi yang menyebabkan bug

#### ❌ `distribusi.permohonan_id` di-UNIQUE

```php
// database/migrations/2026_07_22_045002_create_distribusi.php
$table->foreignId('permohonan_id')->unique()->constrained('permohonans');
```

**Akibat bug:** Begitu Ketua Tim melakukan reassignment (M-17), aplikasi tidak bisa
insert baris baru distribusi untuk permohonan yang sama. `Distribusi::scopeAktif()`
tidak akan pernah bisa menukar staff. Migrasi ini **harus dihilangkan `unique()`-nya**.

Sesuai `DESIGN.md §6.1` dan `Distribusi.php` doc-block: 1 permohonan = N baris
distribusi (histori), tepat 1 baris `is_aktif = true` pada satu waktu.

#### ❌ `reassignment_log.permohonan_id` di-UNIQUE

```php
// database/migrations/2026_07_22_071620_create_reassignment_log.php
$table->foreignId('permohonan_id')->unique()->constrained('permohonans');
```

**Akibat bug:** Tidak bisa mencatat beberapa kali reassignment/reminder per permohonan.
Sejalan dengan §1.3 di atas: harus 1:N.

#### ⚠️ `disposisi.permohonan_id` di-UNIQUE

```php
// database/migrations/2026_07_22_044752_create_disposisi.php
$table->foreignId('permohonan_id')->unique()->constrained('permohonans');
```

**Benar** sesuai A-01 Tahap 1: *"1 permohonan hanya dapat didisposisikan ke satu Ketua Tim"*.
Migration ini **dipertahankan**.

---

## 2. Putusan

### 2.A. `pbf` jadi satu tabel, bukan dua

**Putusan:** Satu tabel `pbf` (sesuai DESIGN.md/Tahap 4). Argumen:

- Asumsi desain di Tahap 1 & 4 sudah final: *1 NIB = 1 akun login*. Asumsi multi-PIC
  belum diputuskan. Jangan bayar kompleksitas yang belum perlu.
- `master_data_pbf` (master) + `pemohons` (akun) memerlukan join berlapis untuk
  hampir semua query permohonan.
- Snapshot data PBF di `permohonan` (kolom `nama_pbf_snapshot`/`nib_snapshot`/...)
  sudah cukup untuk jaga histori bila master berubah.

**Eksekusi:** Lihat §3.

### 2.B. `audit_trail` schema

**Putusan:** Ikut dokumen — `user_id`/`user_type` + `permohonan_id` (FK eksplisit),
bukan polymorphic. Alasan:

- 99% auditTrail selalu terkait permohonan (sulit membayangkan aksi sistem yang
  tidak attach ke permohonan mana).
- FK eksplisit lebih murah join-nya dan jelas untuk audit.

### 2.C. Notifikasi dukungan Staff/Ketua Tim (CLAUDE.md §3 #13)

**Putusan:** Tambah `tujuan_tipe` + `tujuan_id` ke `notifikasi_log` (atau tabel
bernama `notifikasi` sesuai dokumen). Tujuan_tipe: `pemohon`/`staff`/`ketua_tim`/
`kepala_balai`.

### 2.D. Relasi permohonan ↔ distribusi

**Putusan:** 1:N historis + `is_aktif` flag. Hapus `unique()` di migration.

### 2.E. Scaffold rekonsiliasi: rename migrations atau buat migrasi perbaikan?

**Putusan:** Buat migration **perbaikan** (`*_fix_*`) untuk perubahan yang preserving
data, lalu perbaiki model. Untuk rename tabel/kolom yang belum ada data
(production masih kosong) — rename langsung di migration asal. File migration
akan tetap di repo dengan revisi, plus migration tambah kalau struktur sudah ada.

> **Heuristik:** kalau nama hanya berubah → edit migration asal. Kalau kolom hilang
> untuk menambah yang lebih sesuai → migration tambahan + biarkan kolom lama
> nullable sebagai safety.

### 2.F. Status referensi: pakai string atau FK?

**Putusan:** Pakai `VARCHAR(50)` denormalisasi di `permohonan.status_saat_ini`
(dan `status_log.status`), lihat di `status_master` via `kode`. Alasan:

- Sesuai CLAUDE.md §4 (larang native ENUM).
- Memudahkan transisi status tanpa query JOIN di list permohonan.
- `status_master` tetap jadi tabel referensi untuk label/warna/urutan.

### 2.G. `evaluasi.revisi_ke` vs `siklus_ke`

**Putusan:** Rename `evaluasi.revisi_ke` → `evaluasi.siklus_ke` (istilah Tahap 4).
`permohonan.revisi_ke` tetap sebagai cache (opsional, denormalisasi).

---

## 3. Rencana Eksekusi (Migration Order)

Eksekusi dalam urutan ini pada database **sebelum** ada production data (saat ini SQLite
lokomatis, masih kosong). Tiap step bisa di-commit terpisah.

### Step 1 — Rename/standar tabel yang sudah ada

1. `0000_01_01_000000_create_role.php` → rename tabel ke `roles`.
2. `2026_07_22_040133_create_pemohons.php` → rename ke `pbf`, gabung dengan
   `master_data_pbf`, isi kolom kredensial (`password_hash`, `otp_terverifikasi`).
   Hapus file `2026_07_22_035425_create_master_data_pbf.php` (atau biarkan sebagai
   no-op jika dipertahankan untuk historis).
3. `2026_07_22_041355_create_permohonans.php` → rename ke `permohonan`, ganti
   `status_id` jadi `status_saat_ini VARCHAR(50)`, ganti `input_by_pemohon_id`
   jadi `dibuat_oleh_tipe ENUM`. Pertahankan `revisi_ke`/`sla_deadline_current`/
   `is_overdue` sebagai denormalisasi.

### Step 2 — Perbaiki `distribusi` dan `reassignment_log`

1. `distribusi`: hapus `->unique()` dari `permohonan_id`.
2. `reassignment_log`: hapus `->unique()` dari `permohonan_id`, tambah
   `jenis_aksi ENUM('reassign','reminder','lainnya')`, tambah nullable
   `staff_lama_id`/`staff_baru_id`/`alasan`. Hapus `reminder_log` (digabung).

### Step 3 — Buat tabel yang belum ada

1. `revisi` — sesuai Tahap 4 §3.9 (1:0..1 ke evaluasi).
2. `dokumen_revisi` — sesuai §3.10.
3. `surat_pengesahan` — sesuai §3.11.
4. `hari_libur` — sesuai §3.17 (wajib untuk SLA).

### Step 4 — `status_history` → `status_log`

1. Rename tabel: `status_history` → `status_log`.
2. Rename kolom: `started_at` → `waktu_mulai`, `ended_at` → `waktu_selesai`,
   `is_clockoff` → `is_clock_off`. Ganti `sla_target_hari_kerja` (int) dengan
   `durasi_hari_kerja` (int) yang diisi saat `waktu_selesai` terisi.

### Step 5 — `notifikasi_log` upgrade

Tambah migration: `add_tujuan_tipe_to_notifikasi_log` →
`tujuan_tipe ENUM('pemohon','staff','ketua_tim','kepala_balai') NULLABLE` +
`tujuan_id BIGINT UNSIGNED NULLABLE` (polimorfik, tanpa FK agar bisa
merujuk ke `pbf.id` maupun `users.id`).

Hapus `pemohon_id` lama karena jadi redundan dengan `tujuan_id` ketika
`tujuan_tipe = 'pemohon'`. Validasi aplikasi wajib memastikan tipe cocok
dengan tabel referensi.

### Step 6 — Standar `sla_config`

Rename kolom `status_id` (FK) → `kode_tahap VARCHAR(50)` UNIQUE. Isi seed
awal dengan 5 kode tahap (`pengajuan`, `didisposisikan`, `proses_evaluasi`,
`revisi_1/2/3`, `ditutup_pengajuan_ulang`, `menunggu_surat_pengesahan`,
`terbit_surat_pengesahan`) dan nilai default (1/1/7/null/null/null/null/3/null).

### Step 7 — Standar `evaluasi`

Rename `revisi_ke` → `siklus_ke`. Ganti `ENUM('LENGKAP','TIDAK_LENGKAP')`
jadi `VARCHAR(20)` (sesuai CLAUDE.md §4). Evaluasi.php sudah handle sebagai
string & cast tetap valid.

### Step 8 — Standar `dokumen_permohonan.jenis_dokumen`

Ganti `ENUM(...)` jadi `VARCHAR(50)`. Definisikan 8 konstanta di model
(`SURAT_PERMOHONAN`, `SURAT_PERNYATAAN`, `RANCANGAN_DENAH`, `IZIN_PBF`,
`STRA_PJ`, `DOKUMEN_REVISI`, `DOKUMEN_HASIL_EVALUASI`, `SURAT_PENGESAHAN_FINAL`).

### Step 9 — Pecah OTP

Rename `password_reset_otp` → `otp_log`. Tambahkan migration terpisah
untuk `password_reset_tokens` (atau重用 Laravel default) — khusus reset
password, tidak terkait first-login OTP.

### Step 10 — `audit_trail` schema

Ubah `auditable_type`/`auditable_id` (polymorphic) → `user_id`/`user_type`/
`permohonan_id`. Tetap catat `data_before`/`data_after` sebagai JSON.

### Step 11 — Update Models

Perbarui `app/Models/*` agar `$table`, `$fillable`, relasi, cast, scope
sesuai dengan migration baru. Hapus override `$table = 'roles'` di Role.php
dan `$table = 'permohonan'` di Permohonan.php.

### Step 12 — Seeder

Buat seeder dasar: `RoleSeeder`, `StatusMasterSeeder`, `SlaConfigSeeder`,
`HariLiburSeeder`, `UserSeeder` (4 akun awal — Kepala Balai/Ketua Tim/
Staff/Admin IT, password di-hash bcrypt), `TemplateNotifikasiSeeder`.

---

## 4. Yang TIDAK Berubah (Tetap Sesuai CLAUDE.md/DESIGN.md)

1. ✅ Composer pakai Laravel 12 (`^12.0`), PHP 8.2, Tailwind v4, Vite.
2. ✅ Folder `app/Http`, `app/Models`, `app/Providers`, `database/{factories,migrations,seeders}`.
3. ✅ `.env` ada APP_KEY, DB sqlite (untuk dev); MySQL untuk production.
4. ✅ Doc-block di model sudah menyebut `DESIGN.md/Tahap 4/CLAUDE.md` referensi
   dengan tepat.
5. ✅ Daftar 13 aturan bisnis di CLAUDE.md.

---

## 5. Tambah ke Repo (Selesai Rekonsiliasi)

Selain migration/model, perhatikan juga hal-hal berikut sebelum mulai MVP:

1. **Routes split** — pindah route internal ke `routes/internal.php`,
   route pemohon ke `routes/pemohon.php`. Update `bootstrap/app.php` agar
   load keduanya. ✅ `bootstrap/app.php` dengan callback `then:`, routes di-register
2. **App services folder** — `app/Services/` (wadah `StatusTransitionService`,
   `SlaCalculator`, `NotifikasiService`, `OtpService`). ✅ Semua sudah ada
3. **App policies folder** — `app/Policies/` (PermohonanPolicy, DistribusiPolicy,
   UserPolicy). ✅ Dibuat + didaftarkan di AppServiceProvider
4. **App requests folder** — `app/Http/Requests/` untuk validasi form. ✅ 10 Request dibuat
5. **Alpine.js** — `npm install alpinejs` lalu import di `resources/js/app.js`
   (Tailwind v4 + Vite). ✅ Terinstall + dikonfigurasi
6. **Tailwind config warna** — badge status 9 warna + 3 warna SLA
   (hijau on-time, kuning at-risk, merah late) di `@theme` Tailwind v4. ✅
   Diisi di `resources/css/app.css`
7. **Sejalan dengan Tahap 5** — semua halaman di Tahap 5 sudah punya layout,
   tinggal eksekusi Blade per wireframe. ✅ Views sudah ada (23 blade files)
8. **CLAUDE.md** — baris "Backend | Laravel 13" di tabel Tech Stack
   perlu diganti ke **Laravel 12**. ✅ Sudah diganti
9. **Tahap 5 encoding** — re-save sebagai UTF-8 bersih. ✅ Sudah diperbaiki
10. **`config/services.php`** — tambah block `sso_bpom` & `wa_gateway`
    untuk credential env. ✅ Sudah ditambahkan
11. **Testing** — `tests/Feature/` minimal untuk: 3-batas revisi, transisi
    ke `ditutup_pengajuan_ulang`, clock-off calculation. ⏳ Belum dibuat
12. **Bug fix** — `AuditTrail` import di `StatusTransitionService` hilang. ✅ Sudah diperbaiki

---

## 6. Ringkasan Eksekusi

Setelah step 1–12 selesai:

- ✅ Skema DB di repo = IDENTIK dengan DESIGN.md §6 + Tahap 4.
- ✅ Service/Policy/Request bisa ditulis dengan tenang tanpa menebak kolom.
- ✅ Aturan CLAUDE.md §3 dapat ditegakkan lewat code (bukan hanya dokumen).
- ✅ MVP Phase 1 Tahap 6 bisa mulai: M-01 (auth temporary) → M-13 →
  M-02 → M-03 → M-04 → M-05 → M-06 → M-12 → M-07 → M-09 (email) →
  M-11 (logging).
- ✅ 42 routes terdaftar, `php artisan` sehat, `php artisan route:list` sukses.

