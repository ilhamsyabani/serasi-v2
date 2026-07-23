# TAHAP 1 — ANALISIS SISTEM
## Aplikasi Pengajuan Rancangan Denah Pedagang Besar Farmasi (PBF)

**Dokumen:** System Requirement Analysis
**Versi:** 1.2 (Final Tahap 1 — seluruh klarifikasi terjawab)
**Disusun oleh:** Senior System Analyst / Solution Architect

---

## 1. Latar Belakang Singkat

BBPOM saat ini memproses pengajuan Surat Pengesahan Denah PBF secara manual/semi-manual (dokumen fisik/email, evaluasi manual, surat dibuat di Word). Hal ini menimbulkan potensi keterlambatan, sulitnya monitoring SLA, dan minimnya transparansi status bagi pemohon. Aplikasi ini dibangun untuk mendigitalisasi seluruh alur proses — mulai dari input permohonan, disposisi, evaluasi dokumen, revisi, hingga penerbitan Surat Pengesahan — dengan dua portal (Internal BBPOM dan Pelaku Usaha) yang saling terintegrasi melalui satu basis data status pengajuan.

---

## 2. Analisis Kebutuhan Aplikasi

### 2.1 Kebutuhan Fungsional (Functional Requirements)

| Kode | Kebutuhan | Keterangan |
|---|---|---|
| FR-01 | Input data & upload dokumen permohonan | Dilakukan oleh Kepala Balai (fungsi "Sekretaris" melekat pada role Kepala Balai) |
| FR-02 | Generate nomor registrasi permohonan otomatis | Format perlu disepakati (mis. `PBF/DENAH/2026/00001`) |
| FR-03 | Disposisi permohonan (Kepala Balai → Ketua Tim) | Dengan catatan disposisi opsional |
| FR-04 | Distribusi permohonan (Ketua Tim → Staff) | Satu permohonan hanya ditangani oleh **1 Staff Sertifikasi** (one-to-one) |
| FR-05 | Evaluasi dokumen oleh Staff (Lengkap/Tidak Lengkap) | Termasuk catatan, narasi, upload dokumen evaluasi |
| FR-06 | Notifikasi otomatis Email & WhatsApp | Terpicu di setiap perubahan status |
| FR-07 | Upload revisi oleh pemohon | Maksimal 3 kali; jika revisi ke-3 masih tidak lengkap, pemohon diberi kesempatan **pengajuan ulang** (permohonan baru) |
| FR-08 | Upload Surat Pengesahan yang sudah ditandatangani | Manual upload oleh Staff setelah TTD di aplikasi lain |
| FR-09 | Tracking status & histori pengajuan (timeline) | Untuk kedua portal, dengan lama waktu tiap status |
| FR-10 | Dashboard monitoring SLA | Untuk internal (per staff, per tahap, keterlambatan) |
| FR-11 | Autentikasi SSO (Internal) | Terhubung ke sistem SSO BPOM eksisting |
| FR-12 | Autentikasi Email/No. WA + Password + OTP (Pelaku Usaha) | Login menggunakan email atau no. WhatsApp + password; OTP diverifikasi **hanya saat pertama kali login** |
| FR-13 | Manajemen Role & Hak Akses | 4 role internal + 1 role eksternal (pemohon) |
| FR-14 | Riwayat/log aktivitas (audit trail) | Siapa mengubah apa, kapan (penting untuk instansi pemerintah) |
| FR-15 | Unduh Surat Pengesahan | Pemohon dapat mengunduh dokumen final |
| FR-16 | Master data PBF | Data NIB, nama PBF tersimpan untuk pengajuan berikutnya (hindari input ulang) |
| FR-17 | Laporan & Ekspor Data | Rekap jumlah pengajuan per periode/status/SLA; export ke Excel/PDF; cetak laporan |
| FR-18 | Dashboard monitoring (view-only) untuk Kepala Balai | Kepala Balai dapat melihat hasil evaluasi & progres tanpa hak approval tambahan |

### 2.2 Kebutuhan Non-Fungsional (Non-Functional Requirements)

| Kode | Kebutuhan | Keterangan |
|---|---|---|
| NFR-01 | Keamanan data | Data dokumen legalitas PBF bersifat sensitif → enkripsi, akses berbasis role |
| NFR-02 | Auditabilitas | Semua aksi tercatat (siapa, kapan, aksi apa) — kebutuhan khas instansi pemerintah |
| NFR-03 | Ketersediaan (Availability) | Perlu SLA uptime karena menyangkut proses perizinan resmi |
| NFR-04 | Performa | Waktu respon halaman, terutama upload dokumen (ukuran file besar) |
| NFR-05 | Kompatibilitas SSO | Mengikuti standar SSO BPOM (OAuth2/SAML/LDAP — perlu konfirmasi) |
| NFR-06 | Reliabilitas notifikasi | WhatsApp Gateway & Email harus punya mekanisme retry & log gagal kirim |
| NFR-07 | Skalabilitas | Kemungkinan jumlah PBF/pengajuan bertambah tiap tahun |
| NFR-08 | Usability | Pemohon dari luar instansi (non-teknis) harus mudah menggunakan portal |
| NFR-09 | Kepatuhan regulasi | Selaras dengan ketentuan tata naskah dinas & regulasi PBF BPOM |
| NFR-10 | Backup & recovery | Dokumen legal tidak boleh hilang |

---

## 3. Identifikasi Aktor

### 3.1 Portal Internal BBPOM

| Aktor | Deskripsi Peran |
|---|---|
| **Kepala Balai** | Melakukan input permohonan (fungsi "Sekretaris" melekat pada role ini), melakukan disposisi ke Ketua Tim Sertifikasi, menandatangani (di luar sistem), memantau seluruh proses secara **view-only** (tanpa tahap approval tambahan sebelum penerbitan) |
| **Ketua Tim Sertifikasi** | Menerima disposisi, mendistribusikan ke 1 Staff Sertifikasi per permohonan, memantau evaluasi, eskalasi jika SLA terlampaui |
| **Staff Sertifikasi** | Mengevaluasi dokumen, membuat catatan revisi, upload Surat Pengesahan final |
| **Administrator IT** | Mengelola user, role, master data, konfigurasi sistem, monitoring teknis, tidak terlibat proses bisnis sertifikasi |

> ✅ **Terkonfirmasi:** Fungsi "Sekretaris" yang melakukan input permohonan melekat pada role **Kepala Balai** (bukan role terpisah). Hak akses input permohonan akan diberikan pada akun Kepala Balai di struktur menu Tahap 3.

### 3.2 Portal Pelaku Usaha

| Aktor | Deskripsi Peran |
|---|---|
| **Pemohon (PBF)** | Login via Email/No. WhatsApp + Password (OTP saat pertama kali login), memantau status pengajuan, mengunggah revisi dokumen, mengunduh Surat Pengesahan, menerima notifikasi |

### 3.3 Aktor Sistem (Non-Manusia)

| Aktor | Deskripsi |
|---|---|
| **SSO BPOM** | Sistem eksternal untuk autentikasi pegawai internal |
| **WhatsApp Gateway** | Pihak ketiga pengirim notifikasi WA |
| **Email Server (SMTP)** | Pengirim notifikasi email & OTP |

---

## 4. Identifikasi Permasalahan (Current Pain Points)

Berdasarkan alur bisnis yang dijelaskan, berikut permasalahan yang diasumsikan melatarbelakangi kebutuhan sistem ini (mohon dikoreksi jika ada yang tidak sesuai kondisi riil):

1. **Tidak ada visibilitas status real-time** — pemohon tidak tahu posisi berkasnya tanpa menghubungi BBPOM secara manual.
2. **SLA sulit dipantau** — tidak ada mekanisme otomatis untuk mendeteksi keterlambatan di tiap tahap (disposisi, evaluasi, revisi).
3. **Proses revisi tidak terstruktur** — batas maksimal 3 kali revisi sulit dikontrol jika masih manual (email/fisik).
4. **Dokumen tersebar** — surat permohonan, pernyataan, denah, izin PBF, STRA, dan dokumen revisi kemungkinan tersebar di banyak media (email, folder lokal, fisik), sulit ditelusuri kembali.
5. **Proses penerbitan surat semi-manual** — pembuatan di Word dan tanda tangan di aplikasi lain berpotensi menimbulkan versi dokumen yang tidak konsisten atau human error.
6. **Tidak ada notifikasi otomatis** — pemohon harus proaktif bertanya status, membebani staff.
7. **Audit trail lemah** — sulit menelusuri histori siapa yang memproses/mengubah apa jika terjadi sengketa atau kebutuhan pelaporan/audit internal.
8. **Duplikasi data pemohon** — tanpa master data, PBF yang sama harus input ulang data setiap kali mengajukan.

---

## 5. Tujuan Sistem

1. Menyediakan **satu platform terintegrasi** untuk pengajuan, evaluasi, dan penerbitan Surat Pengesahan Denah PBF.
2. Memberikan **transparansi status** kepada pemohon secara real-time melalui portal dan notifikasi otomatis (Email & WhatsApp).
3. Menyediakan **kontrol SLA** di setiap tahap proses (disposisi 1 hari, evaluasi 7 hari, dst.) beserta mekanisme monitoring/alert bagi internal BBPOM.
4. Menstandardisasi **alur revisi dokumen** dengan batas maksimal yang otomatis dikontrol sistem (3 kali).
5. Menyediakan **jejak audit (audit trail)** lengkap atas seluruh proses untuk kebutuhan akuntabilitas instansi pemerintah.
6. Mengurangi **beban administratif manual** (pencarian dokumen, komunikasi status manual) bagi Staff dan Ketua Tim Sertifikasi.
7. Menjadi **basis data terpusat** riwayat pengajuan & dokumen legal PBF untuk kebutuhan pelaporan dan pengambilan keputusan.

---

## 6. Ruang Lingkup Aplikasi

### 6.1 Termasuk dalam Lingkup (In-Scope)

- Portal Internal BBPOM (4 role) dengan SSO.
- Portal Pelaku Usaha dengan autentikasi Email/No. WA + Password + OTP (saat pertama kali login).
- Manajemen alur proses: Pengajuan → Disposisi → Evaluasi → Revisi (maks. 3x) → jika masih tidak lengkap setelah revisi ke-3, pemohon dapat melakukan **pengajuan ulang** (permohonan baru) → Penerbitan Surat Pengesahan.
- Upload/download dokumen (Surat Permohonan, Surat Pernyataan, Rancangan Denah, Izin PBF, STRA, dokumen revisi, Surat Pengesahan final).
- Notifikasi otomatis (Email & WhatsApp) di setiap perubahan status.
- Dashboard tracking status & timeline SLA (internal & pemohon).
- Manajemen user & hak akses (role-based access control).
- Audit log aktivitas.
- Master data PBF (NIB, nama PBF, kontak) untuk penggunaan ulang.
- Modul **Laporan & Ekspor** (rekap pengajuan, statistik SLA; export Excel/PDF; cetak).

### 6.2 Tidak Termasuk dalam Lingkup (Out-of-Scope) — *asumsi awal, mohon dikonfirmasi*

- **Proses tanda tangan digital/elektronik** — dilakukan di aplikasi lain (sesuai penjelasan bisnis), sistem ini hanya menerima hasil upload dokumen yang sudah ditandatangani.
- **Pembayaran/retribusi (PNBP)** — dikonfirmasi **tidak ada biaya** dalam proses ini, sehingga modul pembayaran tidak diperlukan.
- **Integrasi langsung ke sistem OSS/NIB nasional** untuk validasi NIB secara real-time (kecuali dinyatakan lain).
- **Manajemen inspeksi/pemeriksaan lapangan fisik ke lokasi PBF** (di luar proses administratif denah).
- **Modul pelaporan statistik/BI tingkat lanjut** (dashboard analitik kompleks) — versi awal fokus pada operasional & SLA dasar.

> ⚠️ Bagian *out-of-scope* ini penting untuk disepakati di awal agar tidak terjadi *scope creep* saat development.

---

## 7. Asumsi Bisnis (Business Assumptions)

Berikut asumsi yang saya gunakan dalam analisis ini — mohon dikonfirmasi/dikoreksi karena akan berdampak besar ke desain proses dan database:

1. **A-01:** Satu permohonan hanya dapat didisposisikan ke **satu** Ketua Tim Sertifikasi pada satu waktu (bukan multi-disposisi paralel).
2. **A-02 (terkonfirmasi):** Ketua Tim mendistribusikan satu permohonan ke **1 Staff Sertifikasi saja** (relasi one-to-one, bukan tim/beberapa staff sekaligus).
3. **A-03 (terkonfirmasi):** Fungsi "Sekretaris" yang melakukan input permohonan **melekat pada role Kepala Balai** — bukan role baru, bukan pula Staff Sertifikasi.
4. **A-04:** Revisi dihitung **per siklus evaluasi**, artinya jika Staff menyatakan "Tidak Lengkap", itu terhitung 1 kali revisi, meskipun pemohon mengunggah beberapa file sekaligus dalam 1 sesi.
5. **A-05 (terkonfirmasi):** Jika revisi ke-3 masih dinyatakan "Tidak Lengkap", pemohon **diberi kesempatan mengajukan ulang** (membuat permohonan baru dari awal), bukan status "Ditolak" permanen. Permohonan lama akan ditutup dengan status akhir tersendiri (diusulkan: "Ditutup - Pengajuan Ulang Diperlukan") dan tertaut ke permohonan baru sebagai riwayat.
6. **A-06:** SLA dihitung dalam **hari kerja** (Senin–Jumat, di luar hari libur nasional), memerlukan kalender hari libur di sistem.
7. **A-07 (terkonfirmasi):** Pemohon login menggunakan **Email atau No. WhatsApp + Password**, dengan **OTP diverifikasi hanya saat pertama kali login** (bukan setiap kali login). NIB tetap disimpan sebagai identitas/data PBF, namun bukan kredensial login utama.
8. **A-08:** Nomor WhatsApp dan Email pemohon yang diinput saat permohonan adalah kontak yang **sama** dipakai untuk OTP login.
9. **A-09:** Surat Pengesahan yang diupload staff berupa **file PDF hasil tanda tangan** (bukan Word), karena proses TTD dilakukan di aplikasi lain sebelum diupload kembali.
10. **A-10:** Satu NIB dapat mengajukan **lebih dari satu** permohonan denah dari waktu ke waktu (riwayat pengajuan per PBF perlu disimpan, bukan hanya 1 pengajuan aktif) — termasuk hasil dari skenario pengajuan ulang pada A-05.

---

## 8. Risiko yang Mungkin Terjadi

| Kategori | Risiko | Dampak | Mitigasi Awal |
|---|---|---|---|
| **Integrasi** | SSO internal BPOM memiliki dokumentasi/API terbatas atau proses integrasi lama | Timeline development molor | Koordinasi awal dengan tim SSO BPOM, siapkan mock SSO untuk development paralel |
| **Integrasi** | WhatsApp Gateway pihak ketiga memiliki rate limit/biaya per pesan | Notifikasi gagal/terlambat saat volume tinggi | Pilih provider dengan SLA jelas, siapkan fallback ke Email jika WA gagal |
| **Proses Bisnis** | Perlu mekanisme yang jelas untuk menautkan permohonan lama ke permohonan pengajuan ulang (pasca revisi ke-3 gagal) | Riwayat pengajuan per PBF bisa terfragmentasi jika tidak dirancang dengan baik | Didesain di Tahap 2 (relasi permohonan lama-baru) & Tahap 4 (struktur tabel) |
| **Proses Bisnis** | Akses input permohonan melekat pada 1 akun Kepala Balai — berpotensi jadi bottleneck jika volume tinggi | Keterlambatan input di FR-01 | Pertimbangkan multi-user/delegasi akses di bawah akun Kepala Balai (dibahas di Tahap 3) |
| **Keamanan** | Dokumen legal (Izin PBF, STRA) adalah data sensitif | Risiko kebocoran data jika akses tidak dibatasi dengan baik | RBAC ketat + enkripsi dokumen + access log |
| **Operasional** | Proses tanda tangan tetap manual di luar sistem | Potensi bottleneck/inkonsistensi versi dokumen | Pastikan proses upload surat final punya validasi (checksum/versi) sebelum status "Terbit" |
| **Data** | Belum ada aturan retensi/arsip dokumen jangka panjang | Storage membengkak, sulit tata kelola arsip | Rencanakan strategi retensi & storage (lokal vs cloud) di Tahap 3/4 |
| **Kepatuhan** | Sebagai aplikasi instansi pemerintah, mungkin perlu tunduk pada standar keamanan tertentu (mis. audit BSSN, PSE) | Risiko proyek tertunda jika baru diketahui di akhir | Konfirmasi kebutuhan compliance di awal proyek |
| **Adopsi Pengguna** | Pemohon (PBF) eksternal mungkin kurang familiar dengan sistem digital | Tingkat kesalahan input/upload tinggi, banyak revisi | UI/UX sederhana, panduan penggunaan, validasi form yang jelas |
| **Teknis** | Ukuran/format file dokumen tidak seragam (PDF, JPG, scan) | Kegagalan upload/validasi | Tetapkan standar format & ukuran maksimal file sejak awal |

---

## 9. Pertanyaan Klarifikasi

Status setelah putaran diskusi:

1. ✅ **Terjawab** — Fungsi "Sekretaris" melekat pada role **Kepala Balai**.
2. ✅ **Terjawab** — Distribusi Ketua Tim ke Staff selalu **satu-ke-satu** (1 permohonan = 1 Staff).
3. ✅ **Terjawab** — Jika revisi ke-3 masih "Tidak Lengkap", pemohon diberi kesempatan **pengajuan ulang** (permohonan baru).
4. ✅ **Terjawab** — **Tidak ada biaya/retribusi (PNBP)**. Seluruh proses tanpa biaya.
5. ✅ **Terjawab** — Autentikasi pemohon menggunakan **Email/No. WA + Password**, dengan **OTP hanya saat pertama kali login**.
6. ✅ **Terjawab** — Kepala Balai **dapat melihat (read-only/monitoring)** hasil evaluasi & dokumen, namun **tidak ada tahap approval tambahan** dari Kepala Balai sebelum status "Terbit Surat Pengesahan". Penerbitan cukup diselesaikan oleh Staff Sertifikasi (setelah surat final diupload).
7. ✅ **Terjawab** — Dibutuhkan fitur **cetak & export laporan** (rekap pengajuan, statistik SLA, dsb.) di versi awal.

Seluruh poin klarifikasi Tahap 1 telah terjawab. Ringkasan dampak jawaban No. 6 dan 7 terhadap dokumen:

- **Dampak No. 6:** Kepala Balai memiliki hak akses **view-only/monitoring dashboard** di seluruh tahap proses (termasuk hasil evaluasi & surat final), tapi tombol/aksi "Terbit Surat Pengesahan" adalah wewenang **Staff Sertifikasi** — ini akan tercermin di hak akses per role pada Tahap 3.
- **Dampak No. 7:** Ditambahkan modul baru **Laporan & Ekspor Data** ke daftar kebutuhan fungsional (lihat FR-17 di bagian 2.1) — mencakup rekap jumlah pengajuan per periode, status, SLA, dan kemungkinan besar export ke Excel/PDF.

---

**Status Dokumen:** Final untuk Tahap 1 (v1.2) — menunggu persetujuan akhir Anda untuk lanjut ke **Tahap 2 — Business Process** (Business Flow, Flowchart, BPMN sederhana, Use Case Diagram, Activity Diagram).
