# SPK Penentuan Prioritas Produksi UMKM Camilan Jimerto (Pamekasan, Madura)

> **Sistem Pendukung Keputusan (SPK) Full-Stack Multi-Kriteria Menggunakan Metode Hybrid AHP (Analytic Hierarchy Process) dan SAW (Simple Additive Weighting)** dengan integrasi data transaksi penjualan riil dan rekap penjualan bulanan dinamis.

[![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![TailwindCSS](https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=black)](https://alpinejs.dev)
[![ApexCharts](https://img.shields.io/badge/ApexCharts-00E396?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://apexcharts.com)
[![Tests](https://img.shields.io/badge/Tests-Passing%20(46%20Assertions)-success?style=for-the-badge)](tests/Feature/)

---

## Ringkasan Sistem

Aplikasi ini dikembangkan untuk memecahkan permasalahan alokasi kapasitas produksi harian/mingguan pada **UMKM Camilan Jimerto**, sentra oleh-oleh dan produsen camilan khas Pamekasan, Madura. Dengan variasi **30 lini produk camilan** dan keterbatasan kapasitas penggorengan, tenaga kerja, serta modal kerja, sistem ini mengombinasikan:

1. **AHP (Analytic Hierarchy Process):** Menentukan bobot relatif kriteria ($C_1$ – $C_5$) melalui matriks perbandingan berpasangan Saaty (skala 1–9) disertai pengujian rasio konsistensi otomatis ($CR \le 0.10$).
2. **SAW (Simple Additive Weighting):** Menormalisasi matriks keputusan (membedakan kriteria *Benefit* dan *Cost*) lalu menghitung nilai preferensi komprehensif ($V_i$) untuk merangking seluruh 30 produk ke dalam 3 tier prioritas produksi:
   - **Prioritas Utama (Peringkat 1–10):** Produk lokomotif omzet dan margin tinggi, produksi penuh.
   - **Prioritas Menengah (Peringkat 11–20):** Produk stabil / pelengkap varian, produksi berbasis pesanan reguler.
   - **Prioritas Rendah (Peringkat 21–30):** Perputaran lambat atau kompleksitas tinggi, produksi terjadwal minimum.
3. **Sinkronisasi Dinamis Kriteria $C_1$ (Volume Penjualan):** Nilai mentah $C_1$ terhubung langsung dengan rekap bulanan & transaksi historis. Perubahan rekap otomatis memicu perhitungan ulang normalisasi dan perankingan SAW.

---

## Kriteria Keputusan (C1 – C5)

| Kode | Nama Kriteria | Tipe | Sumber Nilai | Deskripsi Operasional |
| :---: | :--- | :---: | :--- | :--- |
| **$C_1$** | **Volume Penjualan / Permintaan Pasar** | *Benefit* | Dinamis (Rekap Bulanan) | Akumulasi unit terjual dari rekap bulanan atau agregasi riwayat transaksi. |
| **$C_2$** | **Margin Keuntungan per Kemasan** | *Benefit* | Operasional (Skala Rupiah/%) | Persentase laba kotor terhadap harga pokok produksi (HPP). |
| **$C_3$** | **Daya Tahan Produk / Kadaluarsa** | *Benefit* | Operasional (Skala Hari) | Umur simpan camilan dalam kondisi kemasan standar (tanpa bahan pengawet berlebih). |
| **$C_4$** | **Ketersediaan Bahan Baku Lokal** | *Benefit* | Operasional (Skala 1–5) | Kemudahan pasokan bahan baku di pasar lokal Pamekasan/Madura (ikan, singkong, terigu, dll). |
| **$C_5$** | **Waktu & Kompleksitas Produksi** | *Cost* | Operasional (Skala Jam / 1–5) | Tingkat kesulitan, durasi penjemuran, dan kebutuhan tenaga kerja (semakin lama semakin rendah prioritas). |

---

## 30 Alternatif Produk (A1 – A30)

| Kode | Nama Produk | Kode | Nama Produk | Kode | Nama Produk |
| :---: | :--- | :---: | :--- | :---: | :--- |
| **A1** | Rambak | **A11** | Otok Pedas Manis | **A21** | Wafer Roll |
| **A2** | Blinjo | **A12** | Usus Ayam | **A22** | Kedelai |
| **A3** | Rungterung | **A13** | Tempe Bergizi | **A23** | Bidaran |
| **A4** | Rengginang | **A14** | Sus Kering | **A24** | Marning Pedas Manis |
| **A5** | Keripik Tette Teri | **A15** | Plintiran Coklat | **A25** | Keripik Tette Udang |
| **A6** | Tales | **A16** | Keripik Kentang | **A26** | Tela Ungu |
| **A7** | Keripik Singkong Rasa | **A17** | Amplang Tengiri | **A27** | Gaddung |
| **A8** | Keripik Pisang | **A18** | Sus Coklat | **A28** | Keripik Pedas |
| **A9** | Kacang Lorjuk | **A19** | Markoni Balado | **A29** | Keripik Tahu |
| **A10** | Sambal Goreng Teri | **A20** | Basreng Balado | **A30** | Keciput |

---

## Fitur-Fitur Utama

### 1. Executive Dashboard & Interactive Visualizations
- **Top 30 Horizontal Bar Chart (ApexCharts):** Batang warna visual per tier (*Utama/Menengah/Rendah*) dengan interaktivitas klik untuk membuka detail produk.
- **Donut Chart Bobot AHP:** Komposisi pembobotan kriteria saat ini dengan status konsistensi ($CR$).
- **KPI Summary Cards:** Produk prioritas teratas, rasio konsistensi AHP, total unit terjual, dan status periode aktif.

### 2. Level 2 Slide-over Drawer (Drill-down Produk)
- Klik pada produk mana pun untuk menampilkan laci slide-over tanpa reload halaman:
  - Mini bar chart tren penjualan 6 bulan terakhir.
  - Radar chart 5 dimensi (skor normalisasi $C_1$ – $C_5$).
  - Stacked bar chart kontribusi bobot kriteria terhadap skor akhir preferensi ($V_i$).
  - Tabel dekonstruksi nilai mentah vs nilai ternormalisasi.

### 3. Head-to-Head Comparison & Criterion Deep-Dive
- **Modal Perbandingan Produk:** Bandingkan 2 camilan berdampingan (contoh: *Keripik Tette Teri* vs *Keripik Tette Udang*).
- **Modal Analisis Kriteria:** Melihat ranking seluruh 30 produk khusus pada satu kriteria tertentu beserta statistik tertinggi, terendah, dan rata-rata.

### 4. What-If Sensitivity Simulator Panel
- Simulasi perubahan bobot kriteria menggunakan slider realtime.
- Melihat pergeseran peringkat secara langsung via AJAX tanpa memodifikasi data dasar di database.

### 5. Matriks Rekap Penjualan Cepat & Sinkronisasi
- Input volume dan omzet bulanan 30 produk sekaligus dalam 1 halaman tabel cepat.
- Tombol **"Simpan & Perbarui Dashboard"** yang secara otomatis mengagregasi volume penjualan dan menghitung ulang SAW.

### 6. Template & Importer CSV Cerdas
- Fitur **Download Template CSV Siap Isi** yang telah terisi 30 produk (`A1`–`A30`).
- Mendukung pemisah koma (`,`) dan titik koma (`;`) khas Microsoft Excel Indonesia.
- Pembersihan otomatis simbol mata uang (`Rp`, titik, koma).
- Checkbox otomatisasi sinkronisasi ke dashboard setelah proses import selesai.

### 7. Explorer Riwayat Transaksi (426 Transaksi Riil)
- Tabel penjelajah 426 baris riwayat penjualan toko/mitra dengan fitur pencarian teks, filter bulan, filter produk, dan paginasi.

### 8. Role-Based Access Control (RBAC) & Quick-Login
- **Admin:** Memiliki wewenang penuh atas input rekap penjualan bulanan, penilaian operasional $C_2$–$C_5$, pembuatan periode kalkulasi baru, dan manajemen master data.
- **Pemilik UMKM:** Fokus pada penyesuaian matriks perbandingan berpasangan AHP, pemantauan dashboard eksekutif, dan ekspor rekomendasi.
- **1-Click Quick Login:** Tombol login cepat di halaman login untuk kemudahan evaluasi dan demonstrasi.

---

## Arsitektur & Teknologi

- **Framework:** Laravel 11 (PHP 8.4)
- **Database:** MySQL 8.0+ / MariaDB 10.4+
- **Styling:** Tailwind CSS (via CDN)
- **Interaktivitas:** Alpine.js v3
- **Grafik & Visualisasi:** ApexCharts
- **Testing:** PHPUnit / Pest Test Framework

---

## Panduan Instalasi & Menjalankan Proyek

### 1. Prasyarat Sistem
Pastikan perangkat Anda telah terpasang:
- **PHP >= 8.2** (Ekstensi aktif: `curl`, `pdo_mysql`, `mbstring`, `gd`, `zip`, `intl`)
- **Composer**
- **MySQL / XAMPP**

### 2. Kloning / Masuk ke Direktori Proyek
```bash
cd "c:\Users\Arya Kusuma\camilan-jimerto-spk"
```

### 3. Konfigurasi Environment (.env)
Salin file konfigurasi jika belum ada:
```bash
copy .env.example .env
```
Pastikan pengaturan koneksi database pada `.env` telah sesuai:
```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=spk_jimerto
DB_USERNAME=root
DB_PASSWORD=
DB_COLLATION=utf8mb4_unicode_ci
```

### 4. Instalasi Dependensi & Generate App Key
```bash
composer install
php artisan key:generate
```

### 5. Buat Database & Jalankan Migrasi + Seeder
Buat database `spk_jimerto` di MySQL/phpMyAdmin, lalu jalankan:
```bash
php artisan migrate:fresh --seed
```
*Seeder akan otomatis mengisi:*
- Akun Admin dan Pemilik UMKM.
- 5 Kriteria ($C_1$ – $C_5$) dan 30 Produk camilan lengkap (`A1` – `A30`).
- Matriks perbandingan berpasangan AHP awal ($CR \le 0.10$).
- Nilai awal operasional ($C_2$, $C_3$, $C_4$, $C_5$).
- 426 baris riwayat transaksi penjualan riil Jimerto.
- Rekap penjualan bulanan & kalkulasi periode aktif 2026.

### 6. Jalankan Server Pengembangan
```bash
php artisan serve --port=8000
```
Buka browser dan akses: **`http://127.0.0.1:8000`**

---

## Akun & Kredensial Login

Anda dapat masuk menggunakan form login biasa atau mengklik tombol **1-Click Quick Login** di halaman login:

| Peran (Role) | Email | Password | Hak Akses Utama |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@jimerto.com` | `password` | Input Rekap Bulanan, Import CSV, Penilaian $C_2$–$C_5$, Kelola Periode |
| **Pemilik UMKM** | `pemilik@jimerto.com` | `password` | Penyesuaian Bobot AHP, Dashboard Eksekutif, Simulasi Sensitivitas |

---

## Menjalankan Pengujian (Automated Tests)

Seluruh logika perhitungan AHP, SAW, normalisasi, otentikasi RBAC, sinkronisasi rekap, simulasi sensitivitas, dan import CSV telah diuji dan terverifikasi 100% lulus:

```bash
php artisan test
```

Uji spesifik per modul:
```bash
# Uji fitur SPK utama (AHP, SAW, Sync, Simulation, RBAC)
php artisan test --filter=SpkJimertoTest

# Uji unduh template dan import CSV
php artisan test --filter=CsvImportTest
```

---

## Struktur Berkas Inti

```
camilan-jimerto-spk/
├── app/
│   ├── Http/Controllers/
│   │   ├── AuthController.php             # Login, logout, dan 1-click quick login
│   │   ├── DashboardController.php        # Executive dashboard & AJAX endpoints
│   │   ├── AhpController.php              # Matriks perbandingan AHP & uji konsistensi
│   │   ├── SalesRecapController.php       # Matriks rekap, download template & import CSV
│   │   ├── AssessmentController.php       # Input nilai operasional C2-C5
│   │   └── CalculationRunController.php   # Manajemen periode kalkulasi SPK
│   ├── Models/
│   │   ├── Product.php                    # 30 model produk camilan
│   │   ├── Criterion.php                  # 5 kriteria keputusan
│   │   ├── CalculationRun.php             # Periode & status aktif dashboard
│   │   ├── AhpComparison.php              # Matriks Saaty berpasangan
│   │   ├── AhpWeight.php                  # Bobot hasil eigen vector AHP
│   │   ├── ProductAssessment.php          # Matriks keputusan nilai mentah
│   │   ├── SawResult.php                  # Skor preferensi V_i, rank & tier
│   │   ├── MonthlySalesRecap.php          # Rekap bulanan volume & omzet
│   │   └── SalesTransaction.php           # 426 baris riwayat transaksi riil
│   └── Services/
│       ├── AhpService.php                 # Algoritma perhitungan AHP & konsistensi (CR)
│       ├── SawService.php                 # Algoritma normalisasi benefit/cost & preferensi
│       └── SalesRecapSyncService.php      # Agregasi penjualan ke C1 & auto-recalculate
├── database/
│   ├── migrations/
│   │   └── 2026_11_24_000001_create_spk_jimerto_tables.php
│   └── seeders/
│       └── SpkJimertoSeeder.php           # Seeder dataset Jimerto & inisialisasi SPK
├── public/
│   └── templates/
│       └── template_rekap_penjualan_jimerto.csv # Template CSV 30 produk siap isi
├── resources/views/
│   ├── layouts/app.blade.php              # Layout utama, sidebar, & quick switcher
│   ├── dashboard/index.blade.php          # Executive dashboard, ApexCharts, & drawer
│   ├── sales-recaps/index.blade.php       # Matriks input rekap & modal import CSV
│   ├── sales-recaps/transactions.blade.php# Explorer 426 transaksi riil
│   ├── ahp/index.blade.php                # Antarmuka perbandingan AHP
│   ├── assessments/index.blade.php        # Matriks input nilai C2-C5
│   └── auth/login.blade.php               # Halaman login dengan quick-login buttons
└── tests/Feature/
    ├── SpkJimertoTest.php                 # Pengujian fitur end-to-end
    └── CsvImportTest.php                  # Pengujian template & import CSV
```

---
