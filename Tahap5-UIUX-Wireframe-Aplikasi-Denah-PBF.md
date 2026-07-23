# TAHAP 5 — UI/UX WIREFRAME
## Aplikasi Pengajuan Rancangan Denah Pedagang Besar Farmasi (PBF)

**Dokumen:** UI/UX Wireframe (Low-Fidelity)
**Versi:** 1.1 (Final — seluruh klarifikasi terjawab)
**Acuan:** Tahap 3 — Struktur Menu & Navigasi (v1.1), Tahap 4 — Struktur Database (v1.1), disetujui

> Catatan: Wireframe berikut bersifat **low-fidelity** (fokus pada tata letak, komponen, dan alur informasi — bukan desain visual/warna final). Notasi kotak (ASCII) dipakai agar bisa dibaca langsung di sini; styling akhir (Tailwind CSS, warna, ikon) ditentukan saat implementasi front-end.

---

## 0. Prinsip Desain Umum

1. **Konsisten dua portal, beda nuansa** — Portal Internal (BBPOM) menonjolkan efisiensi kerja (tabel, filter, aksi cepat); Portal Pemohon menonjolkan kejelasan status & kesederhanaan (non-teknis, sesuai NFR-08).
2. **Timeline sebagai elemen sentral** — mengingat status & durasi SLA adalah kebutuhan inti (FR-09), komponen timeline horizontal/vertikal muncul di banyak halaman (dashboard, detail, tracking).
3. **Role-aware layout** — sidebar/menu internal berubah sesuai role (Tahap 3 §3.1), bukan 4 dashboard terpisah, agar 1 codebase Blade dapat dipakai lintas role dengan conditional rendering.
4. **Aksi kontekstual muncul sesuai status** — tombol seperti "Evaluasi", "Disposisi", "Upload Surat" hanya tampil jika status & role mengizinkan (mencegah aksi tidak sah di level UI, selain validasi backend).
5. **Notifikasi keterlambatan SLA** ditandai warna (mis. kuning = mendekati deadline, merah = lewat deadline) berdasarkan `is_overdue` (Tahap 4 §4.6).

---

## 1. Halaman Login

### 1.1 Login Portal Internal BBPOM

```
┌──────────────────────────────────────────────┐
│                 [Logo BBPOM]                  │
│         Aplikasi Pengesahan Denah PBF          │
│              — Portal Internal —               │
│                                                │
│         ┌────────────────────────────┐        │
│         │   Masuk dengan SSO BPOM     │        │
│         │        [ Login SSO ]        │        │
│         └────────────────────────────┘        │
│                                                │
│     Hanya untuk pegawai internal BBPOM.       │
│     Kendala login? Hubungi Admin IT.          │
└──────────────────────────────────────────────┘
```
*Catatan: Tidak ada form email/password — langsung redirect ke halaman SSO BPOM (FR-11), sesuai NFR-05.*

### 1.2 Login Portal Pelaku Usaha (Pemohon)

```
┌──────────────────────────────────────────────┐
│                 [Logo BBPOM]                  │
│      Portal Pemohon — Pengesahan Denah PBF     │
│                                                │
│  Email atau No. WhatsApp                      │
│  ┌────────────────────────────────────────┐   │
│  │                                        │   │
│  └────────────────────────────────────────┘   │
│  Password                                     │
│  ┌────────────────────────────────────────┐   │
│  │                                        │   │
│  └────────────────────────────────────────┘   │
│                             [Lupa Password?]  │
│         ┌────────────────────────────┐        │
│         │          Masuk              │        │
│         └────────────────────────────┘        │
│                                                │
│  Belum punya akun? Akun dibuat otomatis saat  │
│  BBPOM memproses permohonan pertama Anda.     │
└──────────────────────────────────────────────┘
```

### 1.3 Verifikasi OTP (Hanya Muncul Saat Login Pertama Kali)

```
┌──────────────────────────────────────────────┐
│           Verifikasi Login Pertama            │
│                                                │
│  Kode OTP telah dikirim ke:                   │
│  email@contoh.com / 08xx-xxxx-xxxx            │
│                                                │
│      ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐ ┌───┐       │
│      │   │ │   │ │   │ │   │ │   │ │   │       │
│      └───┘ └───┘ └───┘ └───┘ └───┘ └───┘       │
│                                                │
│         [ Verifikasi ]   [Kirim Ulang]        │
│                                                │
│  * Verifikasi ini hanya diminta 1x saat login │
│    pertama (A-07). Login berikutnya cukup     │
│    email/no. WA + password.                   │
└──────────────────────────────────────────────┘
```

---

## 2. Dashboard Internal BBPOM (4 Dashboard Terpisah per Role)

> Sesuai konfirmasi Anda: masing-masing role memiliki **layout dashboard tersendiri**, bukan 1 layout dengan blok conditional. Menu utama (header/sidebar) tetap konsisten strukturnya, namun **konten & fokus dashboard berbeda total** per role.

### 2.1 Dashboard — Kepala Balai

```
┌──────────────────────────────────────────────────────────────────┐
│ [Logo]  Dashboard   Permohonan   Laporan   Master Data            │
│                                                     Halo, {nama} ▾ │
├──────────────────────────────────────────────────────────────────┤
│  RINGKASAN SELURUH PERMOHONAN                                     │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐     │
│  │Pengajuan│ │Evaluasi │ │ Revisi  │ │ Terbit  │ │Terlambat│     │
│  │  Baru   │ │berjalan │ │ aktif   │ │bln ini  │ │  🔴     │     │
│  │   12    │ │   8     │ │   5     │ │   20    │ │   3     │     │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘ └─────────┘     │
│                                                                    │
│  Monitoring View-Only — Seluruh Permohonan (M-15)                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ [Filter status ▾] [Filter Ketua Tim ▾] [Cari No.Reg/PBF...] │ │
│  │ No.Reg | PBF | Status | Ketua Tim | Staff | SLA | Terlambat?│ │
│  │ 001/26 | PT.A| Evaluasi | Andi | Budi | 3/7 hr |    -       │ │
│  │ 002/26 | PT.B| Revisi-2 | Andi | Sari | clock-off|   -      │ │
│  │ 003/26 | PT.C| Evaluasi | Rani | Tono | 8/7 hr  |   🔴      │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  Tombol utama: [Input Permohonan Baru]  [Disposisi Tertunda: 2]   │
└──────────────────────────────────────────────────────────────────┘
```

### 2.2 Dashboard — Ketua Tim Sertifikasi

```
┌──────────────────────────────────────────────────────────────────┐
│ [Logo]  Dashboard   Permohonan   Laporan                          │
│                                                     Halo, {nama} ▾ │
├──────────────────────────────────────────────────────────────────┤
│  RINGKASAN TIM SAYA                                                │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐                 │
│  │Baru     │ │Sedang   │ │Mendekati│ │Terlambat│                 │
│  │diterima │ │dievaluasi│ │deadline │ │  🔴     │                 │
│  │   3     │ │   8     │ │   2     │ │   1     │                 │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘                 │
│                                                                    │
│  Permohonan di Bawah Tim Saya                                     │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ No.Reg | PBF | Status | Staff | SLA berjalan | Aksi          │ │
│  │ 001/26 | PT.A| Evaluasi | Budi | 3/7 hari    | [Reminder]    │ │
│  │ 003/26 | PT.C| Evaluasi | Tono | 8/7 hari 🔴 | [Reassign]    │ │
│  │ 010/26 | PT.E| Didisposisikan | - | belum distribusi | [Distribusikan]│
│  └────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  Riwayat Reassignment & Reminder (M-17)   [Lihat Log Lengkap →]  │
└──────────────────────────────────────────────────────────────────┘
```

### 2.3 Dashboard — Staff Sertifikasi

```
┌──────────────────────────────────────────────────────────────────┐
│ [Logo]  Dashboard   Permohonan Saya                                │
│                                                     Halo, {nama} ▾ │
├──────────────────────────────────────────────────────────────────┤
│  RINGKASAN PERMOHONAN SAYA                                         │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐                              │
│  │Perlu    │ │Menunggu │ │Mendekati│                              │
│  │dievaluasi│ │revisi   │ │deadline │                              │
│  │   4     │ │   2     │ │   1 🟡  │                              │
│  └─────────┘ └─────────┘ └─────────┘                              │
│                                                                    │
│  Daftar Permohonan yang Ditangani                                 │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ No.Reg | PBF | Status | Sisa waktu evaluasi | Aksi           │ │
│  │ 001/26 | PT.A| Perlu Evaluasi | 3 hari lagi | [Evaluasi →]  │ │
│  │ 002/26 | PT.B| Revisi-2 (menunggu pemohon) | clock-off | -  │ │
│  │ 004/26 | PT.D| Menunggu Surat | 1 hari lagi | [Upload Surat]│ │
│  └────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

### 2.4 Dashboard — Administrator IT

```
┌──────────────────────────────────────────────────────────────────┐
│ [Logo]  Dashboard   Master Data   Administrasi ▾                  │
│                                                     Halo, {nama} ▾ │
├──────────────────────────────────────────────────────────────────┤
│  RINGKASAN TEKNIS SISTEM                                           │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐                 │
│  │User     │ │Notifikasi│ │Notifikasi│ │Storage │                 │
│  │aktif    │ │terkirim  │ │gagal 🔴  │ │terpakai│                 │
│  │   18    │ │   240    │ │    3     │ │  62%   │                 │
│  └─────────┘ └─────────┘ └─────────┘ └─────────┘                 │
│                                                                    │
│  Menu Cepat                                                        │
│  [Manajemen User & Role]  [Audit Trail]  [Konfigurasi Sistem]     │
│  [Master Data PBF]        [Log Notifikasi Gagal]                  │
│                                                                    │
│  Aktivitas Terbaru (Audit Trail ringkas)                          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ 22 Jul 09:12 — Budi S. (Staff) submit evaluasi No.001/26     │ │
│  │ 22 Jul 08:40 — Rani (Ketua Tim) reassign No.003/26           │ │
│  └────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Dashboard Pemohon

```
┌──────────────────────────────────────────────────────────────────┐
│ [Logo]           Dashboard   Pengajuan Saya   Notifikasi   Profil ▾│
├──────────────────────────────────────────────────────────────────┤
│  Halo, PT. Contoh Farma                                           │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │  PERMOHONAN AKTIF — No. Reg: PBF/DENAH/2026/00045          │    │
│  │                                                            │    │
│  │  ●───────●───────●───────○───────○───────○                │    │
│  │ Pengajuan Disposisi Evaluasi Revisi Menunggu  Terbit        │    │
│  │                                    Surat                    │    │
│  │                                                            │    │
│  │  Status saat ini: PROSES EVALUASI                          │    │
│  │  Estimasi selesai evaluasi: 4 hari kerja lagi               │    │
│  │                                                            │    │
│  │              [ Lihat Detail Pengajuan → ]                  │    │
│  └──────────────────────────────────────────────────────────┘    │
│                                                                    │
│  Riwayat Pengajuan Lain                                           │
│  ┌──────────────────────────────────────────────────────────┐    │
│  │ No.Reg           | Tanggal    | Status               |    │    │
│  │ PBF/DENAH/2025/012 | 12-03-2025| Terbit Surat Pengesahan|  │→   │
│  └──────────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────────┘
```
*Catatan: Bila tidak ada permohonan aktif dan status terakhir "Ditutup – Perlu Pengajuan Ulang", blok ini diganti tombol besar **"Ajukan Permohonan Baru"** (M-02, pengajuan ulang mandiri).*

---

## 4. Halaman Tracking Status (Horizontal Stepper + Waktu Pengerjaan)

```
┌──────────────────────────────────────────────────────────────────────────┐
│  ← Kembali            Tracking Status — No. Reg: PBF/DENAH/2026/00045     │
├──────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│   ✅──────✅──────🔵──────⚪──────⚪──────⚪                                │
│ Pengajuan Disposisi Proses  Revisi Menunggu  Terbit                       │
│                    Evaluasi        Surat                                  │
│                                                                            │
│  1 hari kerja  1 hari kerja  3/7 hari   —        —         —              │
│  (selesai)     (selesai)     (berjalan) (belum)  (belum)   (belum)        │
│                                                                            │
│  21 Jul 09:00  22 Jul 10:15  23 Jul —    -        -         -             │
├──────────────────────────────────────────────────────────────────────────┤
│  Detail Tahap Aktif: PROSES EVALUASI                                      │
│  • Mulai        : 23 Jul 2026, 08:30                                      │
│  • Target SLA   : 7 hari kerja (deadline: 31 Jul 2026)                   │
│  • Telah berjalan: 3 hari kerja                                          │
│  • Ditangani oleh: Staff Sertifikasi (nama disamarkan untuk pemohon)     │
│                                                                            │
│  ℹ Jika status berubah menjadi "Revisi ke-N", jam SLA berhenti berjalan  │
│    (clock-off) — waktu tunggu Anda mengunggah revisi tidak dihitung      │
│    sebagai keterlambatan.                                                 │
└──────────────────────────────────────────────────────────────────────────┘
```

**Perilaku dinamis stepper:**
- Setiap simpul (node) menampilkan: **nama status**, **durasi tahap tersebut** (hari kerja aktual jika sudah selesai, atau "X/Y hari kerja" jika sedang berjalan), dan **tanggal mulai**.
- Jika permohonan mengalami revisi, node **"Revisi ke-N"** disisipkan secara dinamis di antara node "Proses Evaluasi" dan bisa berulang hingga 3×, masing-masing menampilkan label **"clock-off"** alih-alih hitungan hari kerja (sesuai Tahap 2 §2).
- Skenario **"Ditutup – Perlu Pengajuan Ulang"** ditampilkan sebagai node akhir bergaris merah, dengan tautan **"Lihat permohonan baru →"** jika pemohon sudah mengajukan ulang (relasi `parent_permohonan_id`, Tahap 4 §4.6).
- Pada layar sempit (mobile), stepper tetap horizontal dengan **scroll ke samping**, bukan berubah jadi vertikal — mengikuti preferensi "horizontal lengkap" yang Anda minta.

---

## 5. Halaman Detail Pengajuan (Portal Internal)

```
┌──────────────────────────────────────────────────────────────────┐
│  ← Daftar Permohonan     Detail Permohonan — PBF/DENAH/2026/00045 │
├──────────────────────────────────────────────────────────────────┤
│ [Data Pemohon] [Dokumen] [Timeline] [Evaluasi] [Riwayat Revisi]   │ ← tab
├──────────────────────────────────────────────────────────────────┤
│  TAB: Data Pemohon                                                │
│  NIB           : 1234567890123                                   │
│  Nama PBF       : PT. Contoh Farma                                │
│  Email          : contoh@farma.co.id                              │
│  No. WhatsApp   : 0812xxxxxxx                                    │
│  Status saat ini: PROSES EVALUASI     [🟡 3/7 hari kerja]          │
│  Ditangani oleh : Budi S. (Staff Sertifikasi)                     │
│                                                                    │
│  ── Aksi sesuai role & status ──                                  │
│  [Kepala Balai]  → tombol "Disposisi ke Ketua Tim" (jika status   │
│                     masih Pengajuan)                              │
│  [Ketua Tim]     → tombol "Distribusikan ke Staff" / "Reassign"   │
│  [Staff]         → tombol "Isi Form Evaluasi" (buka tab Evaluasi) │
│                     / "Upload Surat Pengesahan" (jika Lengkap)    │
└──────────────────────────────────────────────────────────────────┘
```

---

## 6. Halaman Evaluasi (Staff Sertifikasi)

```
┌──────────────────────────────────────────────────────────────────┐
│  ← Kembali ke Detail       Form Evaluasi — PBF/DENAH/2026/00045   │
├──────────────────────────────────────────────────────────────────┤
│  Dokumen yang perlu diperiksa:                                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ 📄 Surat Permohonan.pdf         [ 👁 Lihat di Aplikasi ]     │ │
│  │ 📄 Surat Pernyataan.pdf         [ 👁 Lihat di Aplikasi ]     │ │
│  │ 📄 Rancangan Denah.pdf          [ 👁 Lihat di Aplikasi ]     │ │
│  │ 📄 Izin PBF.pdf                 [ 👁 Lihat di Aplikasi ]     │ │
│  │ 📄 STRA Penanggung Jawab.pdf    [ 👁 Lihat di Aplikasi ]     │ │
│  └────────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Panel Viewer PDF Tertanam (muncul saat "Lihat" diklik)      │ │
│  │  ┌──────────────────────────────────────────────────────┐  │ │
│  │  │         (rendering halaman PDF di dalam aplikasi)      │  │ │
│  │  └──────────────────────────────────────────────────────┘  │ │
│  │  [◀ Hal.] 1 / 4 [Hal. ▶]   🔍 Zoom          [ Tutup ✕ ]    │ │
│  │  * Tidak ada tombol unduh/print — dokumen hanya bisa       │ │
│  │    dilihat di dalam aplikasi (kontrol akses dokumen legal) │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  Hasil Evaluasi:                                                  │
│  ( ) Lengkap        (•) Tidak Lengkap                             │
│                                                                    │
│  ── Muncul jika "Tidak Lengkap" ──                                │
│  Catatan Ketidaksesuaian *                                        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                                                              │ │
│  └────────────────────────────────────────────────────────────┘ │
│  Narasi Evaluasi *                                                │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │                                                              │ │
│  └────────────────────────────────────────────────────────────┘ │
│  Upload Dokumen Hasil Evaluasi (opsional)   [+ Tambah File]       │
│                                                                    │
│  Revisi ke: 1 dari maks. 3                                        │
│                                                                    │
│                          [ Batal ]   [ Submit Evaluasi ]          │
└──────────────────────────────────────────────────────────────────┘
```
*Catatan: Jika ini adalah evaluasi setelah revisi ke-3 dan hasilnya masih "Tidak Lengkap", tombol submit menampilkan konfirmasi tambahan: "Permohonan akan ditutup dan pemohon harus mengajukan ulang. Lanjutkan?" — mencegah kesalahan klik pada aksi final (Tahap 2 §1.2 poin 5).*

---

## 7. Halaman Upload Revisi (Portal Pemohon)

```
┌──────────────────────────────────────────────────────────────────┐
│  ← Kembali          Upload Revisi — PBF/DENAH/2026/00045          │
├──────────────────────────────────────────────────────────────────┤
│  Status: REVISI KE-1 dari maks. 3                                 │
│                                                                    │
│  Catatan dari Staff Sertifikasi:                                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ "Rancangan denah belum mencantumkan skala ruang penyimpanan │ │
│  │  khusus obat keras. Mohon dilengkapi."                      │ │
│  └────────────────────────────────────────────────────────────┘ │
│  📄 Lihat dokumen hasil evaluasi (jika ada)                       │
│                                                                    │
│  Upload Dokumen Revisi *                                          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │        [ + Pilih File ]     atau tarik & letakkan di sini    │ │
│  │        Format: PDF, JPG, PNG — maks. 10 MB                   │ │
│  └────────────────────────────────────────────────────────────┘ │
│  📎 rancangan_denah_revisi1.pdf         [Hapus]                  │
│                                                                    │
│                              [ Kirim Revisi ]                    │
│                                                                    │
│  ℹ Setelah dikirim, status berubah menjadi "Proses Evaluasi" dan │
│    akan diperiksa kembali oleh petugas.                           │
└──────────────────────────────────────────────────────────────────┘
```

---

## 8. Halaman Monitoring SLA (Internal — Kepala Balai / Ketua Tim / Admin IT)

```
┌──────────────────────────────────────────────────────────────────┐
│ [Logo]  Monitoring SLA                                Halo, {nama}│
├──────────────────────────────────────────────────────────────────┤
│  Filter: [Semua Status ▾] [Semua Staff ▾] [Rentang Tanggal 📅]    │
│                                                                    │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐                 │
│  │ Total Aktif │ │ Tepat Waktu │ │ Terlambat 🔴│                 │
│  │     25      │ │     20      │ │      5       │                 │
│  └─────────────┘ └─────────────┘ └─────────────┘                 │
│                                                                    │
│  Daftar Permohonan Berisiko/Terlambat                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │No.Reg   |PBF   |Tahap saat ini|Staff|Target|Berjalan|Status │ │
│  │003/2026 |PT.C  |Evaluasi      |Tono |7 hari|8 hari  |🔴 +1  │ │
│  │007/2026 |PT.D  |Disposisi     |-    |1 hari|1 hari  |🟡 Batas│ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                    │
│  [Khusus Ketua Tim] Aksi cepat pada baris: [Reassign] [Reminder] │
│                                                                    │
│                              [ Export ke Excel/PDF (M-14) ]       │
└──────────────────────────────────────────────────────────────────┘
```
*Catatan: Data bersumber dari `is_overdue` + `status_history` + `sla_config` (Tahap 4 §4.11–4.12). Kepala Balai melihat **seluruh** data (view-only), Ketua Tim hanya permohonan di bawah timnya + tombol aksi (M-17), Admin IT melihat untuk keperluan teknis/laporan.*

---

## 9. Ringkasan Komponen UI yang Dipakai Berulang

| Komponen | Dipakai di Halaman | Catatan Implementasi |
|---|---|---|
| Timeline (horizontal/vertikal) | Dashboard Pemohon, Tracking Status | Komponen Blade reusable, terima array status dari `status_history` |
| Kartu ringkasan angka | Dashboard Internal, Monitoring SLA | Reusable Blade component `<x-summary-card>` |
| Tabel dengan filter & badge status | Dashboard Internal, Monitoring SLA | Alpine.js/vanilla JS untuk filter sisi client, badge warna dari `status_master` |
| Upload dropzone | Input Permohonan (Kepala Balai), Upload Revisi (Pemohon), Upload Surat (Staff) | Validasi tipe & ukuran file di client + server (NFR-04) |
| Badge status SLA (hijau/kuning/merah) | Hampir semua halaman berisi tabel/detail permohonan | Turunan dari `is_overdue` & perbandingan `sla_deadline_current` |

---

## 10. Keputusan Desain (Terkonfirmasi)

1. ✅ **Terkonfirmasi** — 4 role internal memiliki **dashboard terpisah sepenuhnya** (Kepala Balai, Ketua Tim, Staff, Admin IT masing-masing punya layout & fokus konten sendiri), bukan 1 layout dengan blok conditional. Sudah diintegrasikan di bagian 2.1–2.4.
2. ✅ **Terkonfirmasi** — Tracking Status pemohon memakai **horizontal stepper lengkap dengan status & waktu pengerjaan tiap tahap** (bukan timeline vertikal). Sudah diintegrasikan di bagian 4.
3. ✅ **Terkonfirmasi** — Seluruh dokumen (terutama dokumen legal: Izin PBF, STRA, Rancangan Denah, dll.) menggunakan **PDF viewer tertanam di dalam aplikasi**, tanpa tombol unduh/print, agar dokumen tidak dapat diakses/disimpan dari luar sistem. Sudah diintegrasikan di bagian 6 (Evaluasi) — pola yang sama berlaku di semua halaman lain yang menampilkan dokumen internal (Detail Pengajuan, Riwayat Revisi).
   > **Catatan implementasi:** viewer tertanam murni mencegah akses *biasa* (klik kanan/tombol unduh dinonaktifkan di UI); ini bukan proteksi mutlak terhadap screenshot atau screen-recording. Jika dibutuhkan kontrol lebih ketat (watermark dinamis per user, disable print via browser, dsb.), perlu didiskusikan sebagai kebutuhan keamanan tambahan saat development.
   > **Pengecualian:** modul **Unduh Surat Pengesahan (M-12)** untuk pemohon **tetap mempertahankan tombol unduh biasa** — karena file ini justru dokumen resmi yang *memang* harus dimiliki pemohon sebagai hasil akhir proses (bukan dokumen kerja internal yang perlu dibatasi).

Seluruh poin klarifikasi Tahap 5 telah terjawab dan diintegrasikan ke dalam wireframe di atas.

---

**Status Dokumen:** Final untuk Tahap 5 (v1.1) — menunggu persetujuan akhir Anda untuk lanjut ke **Tahap 6 — Development Plan** (Sprint, prioritas fitur, modul dikerjakan lebih dulu, estimasi waktu, dependency antar modul).

