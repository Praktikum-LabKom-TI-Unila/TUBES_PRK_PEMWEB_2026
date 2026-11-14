# 🎓 DynaQuiz — Sistem Latihan Soal & Kuis Interaktif
### Tugas Besar Praktikum Pemrograman Web 2026 — Kelompok 14
**Tema Fakultas 05:** Fakultas FKIP (Keguruan & Ilmu Pendidikan)

---

## 👥 Identitas & Anggota Kelompok 14

| No | Nama Praktikan | Peran & Tanggung Jawab Utama (Jobdesk) |
| :---: | :--- | :--- |
| **1** | **Bintang Prasetyo Kusumo Wicaksono** | **Team Lead** & Manajemen Branch Git / Integrasi Repositori |
| **2** | **Farrel Akbar Deazeva (Farel)** | **UI/UX & Frontend:** Desain Claymorphism 4 Tema, Layout Template, Halaman Publik & Dashboard Pelajar |
| **3** | **Nanda Nabila Fauziah (Nanda)** | **Autentikasi & Admin:** Sistem Login/Register, Penjaga Sesi Peran (`auth.php`), Panel Kelola Pengguna, Mapel & Persetujuan Kuis |
| **4** | **Zalfa Syifa (Zalfa)** | **Manajemen Kuis Pengajar:** Skema Database (`schema.sql`), CRUD Kuis & Soal, Alur Status Kuis (Draft, Menunggu, Terbit, Ditolak, Ditarik), dan Hasil Kuis Pengajar |
| **5** | **Kholifah Wulandari (Wulan)** | **Mesin Pengerjaan & API:** Endpoint API Jawab/Selesai (`api/`), Timer Server Anti-Cheat, Logika Pengerjaan Interaktif (`kerjakan.js`), Katalog & Responsivitas Mobile |

---

## 📖 Deskripsi Singkat Sistem

**DynaQuiz** adalah platform pembelajaran berbasis web (*web-based assessment system*) yang dibangun secara murni menggunakan **PHP Native** dan database **MySQL/MariaDB** tanpa bantuan *framework* CSS atau backend eksternal. 

Sistem ini dirancang khusus untuk menunjang aktivitas belajar-mengajar pada lingkungan pendidikan (FKIP). Pengajar dapat menyusun materi evaluasi berupa kuis pilihan ganda secara terstruktur, sedangkan Admin memastikan kualitas materi sebelum kuis dipublikasikan. Pelajar dapat menguji pemahaman materi melalui pengerjaan kuis interaktif dengan timer yang adil, serta langsung memperoleh skor, analisis jawaban benar/salah, pembahasan mendalam, riwayat evaluasi, dan papan peringkat (*leaderboard*).

---

## 🌟 Fitur-Fitur Unggulan

1. **Desain Claymorphism & 4 Tema Warna Pastel (Mobile-First):**
   * Antarmuka visual ramah pelajar dengan efek clay 3D yang lembut.
   * Mendukung 4 tema tampilan: **Sky Bubblegum (Default)**, **Lavender Dream**, **Mint Cloud**, dan **Peach Pop** dengan pemilih tema instan tanpa kedip (*FOUC-free*).
   * Desain sepenuhnya responsif hingga resolusi mobile **360 px** dengan target sentuh minimal **44 px**.
2. **Pengerjaan Kuis Interaktif & Timer Adil (Mode Fokus):**
   * Mode fokus satu soal per layar tanpa navbar untuk meminimalkan distraksi.
   * Perhitungan sisa waktu pengerjaan dihitung dari sisi server (*server-computed timer*); memuat ulang (*refresh*) halaman tidak akan mengulang waktu.
   * Umpan balik (*instant feedback*) dan pembahasan langsung muncul setiap soal dijawab.
   * Pengumpulan otomatis saat waktu habis (*auto-submit*).
3. **Empat Peran Pengguna (Role-Based Access):**
   * **Guest (Tamu):** Mencoba latihan kuis publik tanpa perlu mendaftar akun terlebih dahulu.
   * **Pelajar:** Mengerjakan kuis publik/privat, melihat pembahasan lengkap, riwayat skor, dan peringkat.
   * **Pengajar:** Membuat kuis baru, mengelola bank soal, menguji coba kuis lewat fitur **Pratinjau Kuis**, mengajukan publikasi, dan memantau analitik skor pelajar.
   * **Admin:** Memoderasi dan menyetujui kuis yang diajukan pengajar, mengelola data pengguna (aktifkan/nonaktifkan/reset sandi), dan mengelola mata pelajaran/tingkat kelas.
4. **Standar Keamanan Ketat (PHP Native):**
   * Semua interaksi basis data menggunakan **PDO Prepared Statements** (kebal SQL Injection).
   * Seluruh penayangan data dinamis di-escape menggunakan fungsi pembantu `e()` (kebal XSS).
   * Seluruh form mutasi data (POST & Fetch) dilindungi token **CSRF**.
   * Pengecekan otorisasi ketat di awal setiap file (`wajibPeran()`) untuk mencegah celah IDOR.
   * Penyimpanan kata sandi menggunakan algoritma aman `password_hash()` BCRYPT.

---

## 🔑 Akun Demo Pengujian

Semua akun contoh di bawah ini menggunakan kata sandi default: **`password123`**

| Peran | Alamat Email | Kata Sandi | Kegunaan Pengujian |
| :--- | :--- | :---: | :--- |
| **Admin** | `admin@dynaquiz.com` | `password123` | Mengakses dashboard admin, menyetujui kuis di antrian, mengelola mapel dan pengguna |
| **Pengajar** | `budi@guru.com` | `password123` | Mengelola kuis Pemrograman Web & Bahasa Inggris |
| **Pengajar** | `siti@guru.com` | `password123` | Mengelola kuis Matematika & IPA Sains |
| **Pelajar** | `ani@siswa.com` | `password123` | Menguji pengerjaan kuis, riwayat nilai, dan peringkat |
| **Pelajar** | `bima@siswa.com` | `password123` | Menguji akun pelajar kedua untuk simulasi kompetisi skor |
| **Guest** | *(Tanpa Login)* | - | Masuk melalui tombol **Mode Tamu** di halaman awal |

---

## 🚀 Panduan Setup & Instalasi Lokal

### 1. Prasyarat
* Web server lokal: **Laragon** (direkomendasikan) atau **XAMPP**
* PHP versi **8.1** atau **8.2**
* Database: **MySQL 5.7+** atau **MariaDB 10.4+**

### 2. Langkah Instalasi Database
1. Pastikan server Apache dan MySQL di Laragon/XAMPP sudah dalam kondisi **Running**.
2. Buka antarmuka phpMyAdmin di browser: `http://localhost/phpmyadmin/`.
3. Buat database baru bernama:
   ```sql
   CREATE DATABASE dynaquiz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
4. Buka tab **Import** pada database `dynaquiz`, lalu impor file skema tabel secara berurutan:
   * **Pertama:** [`database/schema.sql`](file:///C:/laragon/www/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_14/database/schema.sql)
   * **Kedua:** [`database/seed.sql`](file:///C:/laragon/www/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_14/database/seed.sql)  
     *(Atau jalankan `php database/seed.php` melalui terminal di folder kelompok 14)*.

### 3. Konfigurasi Koneksi Database
Konfigurasi database default di [`includes/config.php`](file:///C:/laragon/www/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_14/includes/config.php) sudah disesuaikan untuk konfigurasi standar Laragon/XAMPP:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dynaquiz');
define('DB_USER', 'root');
define('DB_PASS', '');
```
*(Opsional: Jika kredensial database lokal Anda berbeda, buat file `includes/config.local.php` untuk menimpa pengaturan di atas tanpa mengubah repositori).*

### 4. Menjalankan Aplikasi
Buka peramban (*web browser*), lalu akses alamat URL berikut:
```text
http://localhost/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_14/
```

---

## 📁 Struktur Direktori Aplikasi

```text
kelompok/kelompok_14/
├── admin/                    # Panel pengelolaan Admin (AD-01 s.d. AD-06)
│   ├── dashboard.php         # Ringkasan statistik pengguna dan kuis
│   ├── mapel.php             # Kelola mata pelajaran dan tingkat
│   ├── moderasi.php          # Moderasi kuis terbit
│   ├── pengguna.php          # Kelola status dan reset password pengguna
│   ├── persetujuan.php       # Antrian verifikasi kuis menunggu
│   └── tinjau.php            # Tinjauan butir soal sebelum persetujuan
├── api/                      # Endpoint asinkron pengerjaan kuis (AJAX/Fetch)
│   ├── jawab.php             # Validasi dan umpan balik jawaban per butir
│   └── selesai.php           # Finalisasi percobaan dan kalkulasi skor akhir
├── assets/                   # Aset statis antarmuka
│   ├── css/                  # dasar.css, komponen.css, tema.css, kerjakan.css
│   └── js/                   # tema.js, tema-init.js, kerjakan.js, ui.js
├── database/                 # Berkas SQL dan Seeder
│   ├── schema.sql            # Struktur tabel dan relasi foreign key
│   ├── seed.sql              # Data awal 20 kuis lengkap dengan soal & kunci
│   └── seed.php              # Skrip CLI import otomatis
├── guest/                    # Tampilan khusus pengguna tamu
│   └── dashboard.php         # Beranda mode tamu & riwayat sesi
├── includes/                 # Komponen inti backend dan partials layout
│   ├── auth.php              # Pengecekan otorisasi sesi dan peran
│   ├── config.php            # Konfigurasi konstanta, database, dan session
│   ├── csrf.php              # Pembuatan dan verifikasi token CSRF
│   ├── db.php                # Inisialisasi koneksi PDO ke MySQL
│   ├── helpers.php           # Fungsi utilitas pembersihan input & output (e)
│   ├── kata_terlarang.php    # Filter sensor kata tidak pantas
│   ├── fungsi/               # Logika bisnis (admin, kuis, soal, pelajar, dll.)
│   └── partials/             # Header, footer, navbar, sidebar, pemilih tema
├── kuis/                     # Alur pengerjaan kuis (Pelajar & Guest)
│   ├── katalog.php           # Daftar kuis terbit dengan filter & pencarian
│   ├── detail.php            # Rincian kuis dan akses kode kuis privat
│   ├── kerjakan.php          # Layar pengerjaan kuis mode fokus
│   └── hasil.php             # Rincian skor dan pembahasan pasca kuis
├── pelajar/                  # Halaman pengguna terdaftar Pelajar
│   ├── dashboard.php         # Beranda pelajar, streak belajar, dan rekomendasi
│   ├── peringkat.php         # Papan peringkat leaderboard
│   └── riwayat.php           # Riwayat evaluasi seluruh kuis
├── pengajar/                 # Halaman panel Pengajar (Guru)
│   ├── dashboard.php         # Beranda pengajar & ringkasan kuis aktif
│   ├── kuis.php              # Daftar kuis buatan pengajar & statusnya
│   ├── kuis-form.php         # Form pembuatan dan perubahan kuis
│   ├── kelola-soal.php       # Manajemen bank soal pilihan ganda & kunci
│   ├── pratinjau.php         # Simulasi mandiri pengerjaan kuis
│   └── hasil.php             # Analisis performa dan daftar nilai peserta
├── 403.php                   # Halaman respon HTTP 403 (Akses Ditolak)
├── 404.php                   # Halaman respon HTTP 404 (Tidak Ditemukan)
├── index.php                 # Halaman utama / Landing page publik
├── login.php                 # Form masuk akun terdaftar
├── logout.php                # Pembersihan sesi aman
├── profil.php                # Pembaruan nama dan penggantian kata sandi
├── register.php              # Pendaftaran akun baru (Pelajar/Pengajar)
└── README.md                 # Dokumentasi sistem Kelompok 14
```

---

## 🛡 Validasi CI/CD & Linter
Proyek ini mematuhi standar repositori monorepo LabKom Teknik Informatika Universitas Lampung:
* Seluruh berkas PHP lolos uji sintaks `php -l` tanpa kesalahan (*0 syntax errors detected*).
* Seluruh aset dan berkas berukuran jauh di bawah batas maksimum 5 MB.
* Seluruh modifikasi kode terisolasi secara ketat di dalam direktori `kelompok/kelompok_14/`.
