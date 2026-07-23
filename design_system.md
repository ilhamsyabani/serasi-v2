# Pharmacy UI/UX Design System & Style Guide

Dokumen ini adalah panduan sistem desain (Design System) komprehensif untuk aplikasi bidang farmasi/kesehatan. Panduan ini menggunakan tema **"Trusted Pharmacy" (Emerald Green & Navy)** dan memanfaatkan **Phosphor Icons** untuk menciptakan antarmuka yang profesional, higienis, terpercaya, dan mudah digunakan.

---

## 1. Filosofi Desain
* **Clean & Clinical:** Ruang kosong (whitespace) yang luas untuk menghindari beban kognitif. Pengguna aplikasi farmasi membutuhkan informasi yang jelas dan langsung.
* **Trust & Security:** Kombinasi warna Navy (profesionalisme/keamanan) dan Emerald Green (penyembuhan/kesehatan/alam).
* **Accessible & Legible:** Kontras rasio yang tinggi pada teks dan elemen interaktif. Sudut komponen melengkung lembut (rounded) untuk kesan ramah dan modern.

---

## 2. Palet Warna (Color Palette)

### Warna Utama (Brand Colors)
* **Primary (Emerald Green):** `#10B981` — Digunakan untuk Call to Action (CTA) utama, tombol "Beli", "Tebus Resep", ikon sukses, dan elemen interaktif positif.
* **Primary Hover (Dark Emerald):** `#059669` — Warna saat tombol utama di-hover.
* **Secondary/Heading (Navy Blue):** `#1E3A8A` — Digunakan untuk teks judul utama (H1, H2, H3), navigasi utama yang aktif, dan penekanan informasi penting.

### Warna Netral (Neutral & Surface)
* **Background Color:** `#F8FAFC` (Slate 50) — Warna latar belakang utama halaman. Memberikan kesan bersih tanpa menyilaukan.
* **Surface/Card Color:** `#FFFFFF` — Warna latar belakang kartu, modal, sidebar, dan dropdown.
* **Border Color:** `#E2E8F0` (Slate 200) — Warna garis batas halus pada form dan pemisah kartu.

### Warna Teks (Typography Colors)
* **Heading Text:** `#1E3A8A` (Navy Blue) — Untuk semua judul.
* **Body Text (Primary):** `#334155` (Slate 700) — Untuk teks paragraf utama dan nama obat.
* **Muted Text (Secondary):** `#64748B` (Slate 500) — Untuk deksripsi, label form, teks placeholder, dan metadata (tanggal, kategori).

### Warna Semantik (Semantic Colors)
* **Success:** `#10B981` (Sama dengan Primary) — Aksi berhasil, stok tersedia.
* **Warning:** `#F59E0B` (Amber 500) — Stok menipis, butuh perhatian, pesanan diproses.
* **Danger/Error:** `#EF4444` (Red 500) — Stok habis, butuh resep dokter, pesan error.
* **Info:** `#3B82F6` (Blue 500) — Informasi umum, tooltip.

---

## 3. Tipografi (Typography)
* **Font Family:** Sans-serif modern yang bersih seperti **Inter**, **Plus Jakarta Sans**, atau **Roboto**.
* **Hierarki:**
    * **H1 (Page Title):** 24px - 32px, Weight: Bold (700), Color: `#1E3A8A`.
    * **H2 (Section Title):** 18px - 20px, Weight: Semi-Bold (600), Color: `#1E3A8A`.
    * **H3 (Card Title / Drug Name):** 16px, Weight: Semi-Bold (600), Color: `#1E3A8A` atau `#334155`.
    * **Body Text (Default):** 14px, Weight: Regular (400), Color: `#334155`.
    * **Small Text / Labels:** 12px, Weight: Medium (500), Color: `#64748B`.
    * **Price Text:** 16px - 18px, Weight: Bold (700), Color: `#10B981`.

---

## 4. Ikonografi (Iconography: Phosphor Icons)
Aplikasi ini wajib menggunakan **Phosphor Icons** karena desainnya yang konsisten, bersih, dan sangat cocok untuk antarmuka medis/kesehatan.

* **Style/Weight Default:** `Regular` (ketebalan garis standar) atau `Duotone` (untuk menu navigasi aktif agar lebih menonjol).
* **Ukuran Ikon:**
    * Navigasi Sidebar/Topbar: `24px`
    * Ikon dalam tombol/CTA: `20px`
    * Ikon indikator/kecil (misal: ikon kalender, jam): `16px`
* **Contoh Penggunaan:**
    * `Pill` / `Prescription`: Menu resep atau daftar obat.
    * `FirstAid` / `Heartbeat`: Layanan kesehatan atau kategori obat.
    * `ShoppingCart`: Keranjang belanja.
    * `User` / `IdentificationCard`: Profil pasien/pengguna.

---

## 5. Komponen UI (UI Components)

### 5.1. Kartu (Cards)
* **Product/Medicine Card:**
    * Latar belakang: `#FFFFFF`
    * Border Radius: `16px` (Tailwind: `rounded-2xl`)
    * Border: `1px solid #E2E8F0` (Tailwind: `border border-slate-200`)
    * Shadow: Halus (Tailwind: `shadow-sm` atau `shadow-md` saat di-hover).
    * Padding Internal: `16px` atau `20px` (Tailwind: `p-4` atau `p-5`).

### 5.2. Tombol (Buttons)
* **Primary Button:** Latar `#10B981`, Teks Putih (`#FFFFFF`), Hover `#059669`. Border radius `8px` atau `12px` (`rounded-lg` / `rounded-xl`). Font Medium.
* **Secondary Button:** Latar `#F1F5F9` (Slate 100), Teks `#1E3A8A` (Navy), Hover latar `#E2E8F0`.
* **Outline Button:** Tanpa latar, Border `1px solid #10B981`, Teks `#10B981`. Hover: Latar hijau sangat transparan (`bg-emerald-50`).
* **Disabled Button:** Latar `#E2E8F0`, Teks `#94A3B8`. Tidak bisa diklik.

### 5.3. Form & Input
* **Text Field / Search:**
    * Background: `#FFFFFF`.
    * Border: `1px solid #E2E8F0`.
    * Focus State: Border berubah menjadi `#10B981`, dengan *ring* halus berwarna `#10B981` transparansi 20% (`ring-emerald-500/20`).
    * Border Radius: `8px` (`rounded-lg`).
    * Padding: Tinggi input `40px` - `44px`.
* **Checkbox & Radio:** Menggunakan warna *Primary* (`#10B981`) saat *checked*.

### 5.4. Label & Badge (Status Obat/Pesanan)
* Bentuk pil membulat penuh (`rounded-full`), padding atas-bawah kecil, kiri-kanan lebih lebar (`px-3 py-1`).
* Teks berukuran 12px (tebal/medium).
* **Tersedia:** Background hijau transparan (`bg-emerald-100`), teks hijau tua (`text-emerald-700`).
* **Resep Diperlukan:** Background merah transparan (`bg-red-100`), teks merah tua (`text-red-700`).

### 5.5. Navigasi & Sidebar
* **Sidebar Item Aktif:** Background transparan biru muda atau hijau muda, teks dan Phosphor Icon (`weight="fill"` atau `weight="duotone"`) berwarna `#10B981` atau `#1E3A8A`. Garis vertikal tebal di sisi kiri sebagai penanda.
* **Sidebar Item Pasif:** Teks dan ikon berwarna `#64748B`.

---

## 6. Layout & Spacing
* **Sistem Grid:** 12-kolom standar.
* **Spacing:** Gunakan skala kelipatan 4px atau 8px (Tailwind *spacing scale*).
* Jarak antar bagian (Section Gap): Minimal `32px` (`gap-8` atau `mb-8`) agar halaman tidak terasa padat.
* Jarak antar kartu (Grid Gap): `16px` hingga `24px` (`gap-4` hingga `gap-6`).

---

## 7. AI Prompting Guide (Panduan Handoff Developer)

Jika Anda meminta AI (seperti GitHub Copilot, v0.dev, atau Cursor) untuk membangun antarmuka ini, gunakan prompt berikut:

> "Buatkan komponen UI menggunakan kerangka kerja Tailwind CSS berdasarkan tema farmasi modern. 
> 1. Gunakan warna latar `bg-slate-50` (`#F8FAFC`). 
> 2. Untuk teks judul, gunakan warna Navy `text-[#1E3A8A]` dengan font-bold. Teks biasa gunakan `text-slate-700`, dan teks deksripsi `text-slate-500`. 
> 3. Warna aksi utama (tombol CTA, badge sukses, harga obat) wajib menggunakan Emerald Green `bg-emerald-500` (`#10B981`) dan hover `bg-emerald-600`.
> 4. Komponen kartu menggunakan `bg-white`, `rounded-2xl`, `border border-slate-200`, dan `shadow-sm`.
> 5. Integrasikan ikon menggunakan library **Phosphor Icons** (gunakan tag komponen Phosphor yang sesuai seperti `<Pill />`, `<Stethoscope />`, `<ShoppingCart />` dengan atribut `weight="regular"` atau `weight="duotone"`). 
> 6. Pastikan form input memiliki *focus ring* `focus:ring-emerald-500`. Berikan *padding* yang cukup luas agar antarmuka terlihat bersih dan higienis khas aplikasi medis."
