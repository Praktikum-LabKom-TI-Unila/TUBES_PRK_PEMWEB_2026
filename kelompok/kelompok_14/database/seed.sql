-- ============================================================================
-- DynaQuiz - Database Seed (Data Awal / Seeder)
-- Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
-- PIC: Wulan (Sesi 1 - Database Seed / seed.sql)
--
-- CATATAN UNTUK TEMAN-TEMAN KELOMPOK 14:
-- 1. Jalankan dulu file schema.sql sebelum menjalankan file ini.
-- 2. File ini berisi data awal: akun user (admin, guru, siswa), topik mapel,
--    tingkat kelas, kuis contoh (terbit, draft, menunggu, privat), soal-soal,
--    opsi jawaban, serta contoh riwayat pengerjaan kuis.
-- 3. Password default untuk SEMUA akun di bawah ini adalah: password123
-- ============================================================================

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Bersihkan data lama jika ingin reset ulang database
-- ----------------------------------------------------------------------------
TRUNCATE TABLE `jawaban_percobaan`;
TRUNCATE TABLE `percobaan_kuis`;
TRUNCATE TABLE `opsi_jawaban`;
TRUNCATE TABLE `soal`;
TRUNCATE TABLE `log_status_kuis`;
TRUNCATE TABLE `log_login`;
TRUNCATE TABLE `kuis`;
TRUNCATE TABLE `tingkat`;
TRUNCATE TABLE `topik`;
TRUNCATE TABLE `user`;

-- ----------------------------------------------------------------------------
-- 1. Data Pengguna (user)
-- Catatan:
-- Password semua akun di-hash dengan password_hash('password123', PASSWORD_DEFAULT)
-- Hash: $2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC
-- ----------------------------------------------------------------------------

-- Akun 1: Admin
INSERT INTO `user` (`id`, `nama`, `email`, `password`, `peran`, `dibuat_pada`) VALUES
(1, 'Admin DynaQuiz', 'admin@dynaquiz.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'admin', NOW());

-- Akun 2 & 3: Pengajar / Guru
INSERT INTO `user` (`id`, `nama`, `email`, `password`, `peran`, `dibuat_pada`) VALUES
(2, 'Budi Santoso, S.Kom.', 'budi@guru.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pengajar', NOW()),
(3, 'Siti Aminah, M.Pd.', 'siti@guru.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pengajar', NOW());

-- Akun 4, 5, 6: Pelajar / Siswa
INSERT INTO `user` (`id`, `nama`, `email`, `password`, `peran`, `dibuat_pada`) VALUES
(4, 'Ani Wijaya', 'ani@siswa.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pelajar', NOW()),
(5, 'Bima Pratama', 'bima@siswa.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pelajar', NOW()),
(6, 'Citra Lestari', 'citra@siswa.com', '$2y$10$VSrxwue21OAXt/S57.j7oe5hx1JQ.3Fq3LVCn5QIfkHJiQ17zlncC', 'pelajar', NOW());


-- ----------------------------------------------------------------------------
-- 2. Data Topik / Mata Pelajaran (topik)
-- ----------------------------------------------------------------------------
INSERT INTO `topik` (`id`, `nama`, `deskripsi`, `dibuat_pada`) VALUES
(1, 'Matematika', 'Kumpulan kuis logika matematika, aljabar, geometri, dan hitung dasar.', NOW()),
(2, 'Ilmu Pengetahuan Alam', 'Kuis seputar biologi, sains alam, fisika dasar, dan tata surya.', NOW()),
(3, 'Bahasa Indonesia', 'Kuis tata bahasa, ejaan EYD, pemahaman bacaan, dan kosa kata.', NOW()),
(4, 'Bahasa Inggris', 'Latihan grammar, reading comprehension, vocabulary, dan tenses.', NOW()),
(5, 'Pemrograman Web', 'Konsep dasar pembuatan web: HTML, CSS, JavaScript, PHP, dan database MySQL.', NOW());


-- ----------------------------------------------------------------------------
-- 3. Data Tingkat Pendidikan (tingkat)
-- Memiliki kolom urutan dari yang paling rendah sampai umum
-- ----------------------------------------------------------------------------
INSERT INTO `tingkat` (`id`, `nama`, `urutan`, `dibuat_pada`) VALUES
(1, 'SD / Sederajat', 1, NOW()),
(2, 'SMP / Sederajat', 2, NOW()),
(3, 'SMA / Sederajat', 3, NOW()),
(4, 'Perguruan Tinggi', 4, NOW()),
(5, 'Umum', 5, NOW());


-- ----------------------------------------------------------------------------
-- 4. Data Kuis (kuis)
-- Berisi berbagai jenis status untuk keperluan testing fitur kelompok:
-- - Kuis 1: Terbit & Publik (Pemrograman Web, dibuat Pak Budi)
-- - Kuis 2: Terbit & Publik (Matematika SMP, dibuat Bu Siti)
-- - Kuis 3: Terbit & Privat dengan kode kuis (PHP Native, dibuat Pak Budi)
-- - Kuis 4: Menunggu Persetujuan Admin (IPA SD, dibuat Bu Siti)
-- - Kuis 5: Draft Pengajar (Bahasa Inggris SMP, dibuat Pak Budi)
-- ----------------------------------------------------------------------------
INSERT INTO `kuis` (`id`, `pengajar_id`, `topik_id`, `tingkat_id`, `judul`, `deskripsi`, `durasi_menit`, `akses`, `kode`, `status`, `diterbitkan_pada`, `dibuat_pada`) VALUES
(1, 2, 5, 3, 'Dasar-Dasar HTML & Pemrograman Web', 'Uji pemahaman dasar seputar tag HTML, elemen web, dan cara kerja website modern.', 15, 'publik', NULL, 'terbit', '2026-11-01 09:00:00', '2026-11-01 08:00:00'),
(2, 3, 1, 2, 'Matematika SMP: Operasi Hitung & Aljabar', 'Latihan soal berhitung cepat, operasi bilangan bulat, pecahan, dan persamaan aljabar.', 10, 'publik', NULL, 'terbit', '2026-11-02 10:00:00', '2026-11-02 09:30:00'),
(3, 2, 5, 3, 'Kuis Privat Logika PHP (Kelas Khusus)', 'Kuis privat untuk menguji dasar percabangan, fungsi, dan variabel pada PHP native.', 20, 'privat', 'WEB2026', 'terbit', '2026-11-03 13:00:00', '2026-11-03 11:00:00'),
(4, 3, 2, 1, 'Mengenal Tata Surya dan Planet Kita', 'Kuis interaktif sains seputar planet, matahari, dan benda langit untuk siswa SD.', 15, 'publik', NULL, 'menunggu', NULL, '2026-11-04 14:00:00'),
(5, 2, 4, 2, 'English Grammar: Simple Present & Past Tense', 'Latihan tata bahasa Inggris seputar waktu dan penggunaan kata kerja bentuk 1 & 2.', 15, 'publik', NULL, 'draft', NULL, '2026-11-05 16:00:00'),
(6, 3, 3, 2, 'Bahasa Indonesia: Ejaan, Tanda Baca & Kaidah EYD', 'Uji kemampuan tata bahasa Indonesia mengenai penulisan huruf kapital, kata baku, dan tanda baca.', 10, 'publik', NULL, 'terbit', '2026-11-06 09:00:00', '2026-11-06 08:00:00'),
(7, 2, 5, 3, 'Pemrograman Web: Dasar Tata Letak CSS Flexbox & Grid', 'Memahami konsep modern web layout menggunakan flexbox, grid, dan responsive box model.', 15, 'publik', NULL, 'terbit', '2026-11-06 13:00:00', '2026-11-06 11:30:00'),
(8, 3, 2, 2, 'Fisika Dasar: Gerak Lurus & Hukum Newton', 'Latihan soal seputar kecepatan, percepatan, gaya, dan penerapan Hukum Newton I, II, dan III.', 12, 'publik', NULL, 'terbit', '2026-11-07 10:00:00', '2026-11-07 09:00:00'),
(9, 2, 4, 2, 'English Vocabulary: School, Hobbies & Daily Life', 'Test your English vocabulary about everyday activities, school subjects, and leisure hobbies.', 10, 'publik', NULL, 'terbit', '2026-11-07 14:00:00', '2026-11-07 13:00:00'),
(10, 3, 1, 3, 'Matematika SMA: Persamaan & Fungsi Kuadrat', 'Menguji pemahaman mencari akar-akar persamaan kuadrat, diskriminan, dan titik puncak grafik fungsi.', 15, 'publik', NULL, 'terbit', '2026-11-08 09:30:00', '2026-11-08 08:30:00'),
(11, 2, 5, 3, 'Pemrograman Web: Logika Percabangan & Loop JavaScript', 'Latihan dasar logika pemrograman web: if-else, switch case, perulangan for, dan array.', 15, 'publik', NULL, 'terbit', '2026-11-08 14:00:00', '2026-11-08 12:00:00'),
(12, 3, 2, 2, 'Biologi SMP: Organ Tubuh & Sistem Pencernaan', 'Mengenal organ pencernaan manusia, enzim pencernaan, dan proses penyerapan sari makanan.', 10, 'publik', NULL, 'terbit', '2026-11-09 10:00:00', '2026-11-09 09:00:00'),
(13, 3, 3, 1, 'Bahasa Indonesia: Membaca Pemahaman & Ide Pokok', 'Latihan membaca teks bacaan pendek, menemukan gagasan pokok, dan menentukan simpulan paragraf.', 10, 'publik', NULL, 'terbit', '2026-11-09 13:00:00', '2026-11-09 11:00:00'),
(14, 2, 5, 4, 'Kuis Privat: Algoritma Pencarian & Pengurutan Data', 'Kuis evaluasi mahasiswa seputar Linear Search, Binary Search, Bubble Sort, dan Quicksort.', 20, 'privat', 'ALGO2026', 'terbit', '2026-11-10 09:00:00', '2026-11-10 08:00:00'),
(15, 3, 1, 1, 'Matematika SD: Pecahan Biasa, Campuran & Desimal', 'Latihan seru menyederhanakan pecahan, mengubah pecahan ke persen, dan operasi tambah pecahan.', 10, 'publik', NULL, 'terbit', '2026-11-10 11:00:00', '2026-11-10 10:00:00'),
(16, 3, 2, 3, 'Kimia Dasar: Struktur Atom & Tabel Periodik Unsur', 'Pemahaman seputar proton, neutron, elektron, nomor massa, dan konfigurasi elektron unsur.', 15, 'publik', NULL, 'terbit', '2026-11-11 10:00:00', '2026-11-11 09:00:00'),
(17, 2, 4, 3, 'English Grammar: Conditional Sentences (Type 1 & 2)', 'Deepen your understanding of future possible conditions and imaginary present situations.', 12, 'publik', NULL, 'terbit', '2026-11-11 14:00:00', '2026-11-11 13:00:00'),
(18, 2, 5, 4, 'Basis Data: Perancangan Relasi & Sintaks SQL SELECT', 'Dasar database relasional: Primary Key, Foreign Key, dan perintah query SELECT dengan WHERE.', 15, 'publik', NULL, 'terbit', '2026-11-12 10:00:00', '2026-11-12 08:30:00'),
(19, 3, 3, 3, 'Bahasa Indonesia: Struktur Teks Eksplanasi & Argumentasi', 'Kuis seputar struktur pernyataan umum, deretan penjelas, tesis, serta kaidah kebahasaan teks.', 12, 'publik', NULL, 'menunggu', NULL, '2026-11-12 14:00:00'),
(20, 3, 1, 3, 'Matematika Lanjut: Konsep Dasar Trigonometri', 'Latihan dasar perbandingan trigonometri sinus, cosinus, dan tangen pada segitiga siku-siku.', 15, 'publik', NULL, 'draft', NULL, '2026-11-13 09:00:00');


-- ----------------------------------------------------------------------------
-- 5. Data Soal (soal)
-- Menyimpan pertanyaan, pembahasan, dan poin untuk tiap kuis
-- ----------------------------------------------------------------------------

-- Soal untuk Kuis 1: Dasar-Dasar HTML (5 Soal, masing-masing 20 poin = Total 100)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(1, 1, 1, 'Apakah kepanjangan dari HTML?', 'HTML merupakan singkatan dari HyperText Markup Language, yaitu bahasa markup standar untuk membuat halaman web.', 20),
(2, 1, 2, 'Tag HTML manakah yang digunakan untuk membuat judul utama atau heading dengan ukuran paling besar?', 'Tag <h1> adalah level heading tertinggi (terbesar) dalam hierarki dokumen HTML dari h1 sampai h6.', 20),
(3, 1, 3, 'Atribut apa yang wajib ada pada tag <a> untuk menentukan alamat tujuan tautan (link)?', 'Atribut href (Hypertext REFerence) digunakan untuk menentukan URL tujuan pada elemen hyperlink <a>.', 20),
(4, 1, 4, 'Tag HTML yang benar untuk menampilkan gambar pada halaman web adalah...', 'Tag <img> digunakan untuk menyisipkan gambar dengan atribut src yang menentukan sumber file gambar.', 20),
(5, 1, 5, 'Elemen HTML apa yang digunakan untuk membuat daftar berurutan menggunakan angka (1, 2, 3)?', 'Tag <ol> adalah singkatan dari Ordered List, digunakan untuk daftar item bernomor.', 20);

-- Soal untuk Kuis 2: Matematika SMP (5 Soal, masing-masing 20 poin = Total 100)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(6, 2, 1, 'Berapakah hasil dari perhitungan: 15 + 25 : 5 ?', 'Dalam aturan matematika, pembagian dikerjakan terlebih dahulu: 25 : 5 = 5. Kemudian 15 + 5 = 20.', 20),
(7, 2, 2, 'Jika 2x + 6 = 16, maka nilai x adalah...', 'Pindahkan konstanta: 2x = 16 - 6 => 2x = 10 => x = 10 : 2 = 5.', 20),
(8, 2, 3, 'Sebuah persegi memiliki panjang sisi 8 cm. Berapakah luas persegi tersebut?', 'Rumus luas persegi adalah sisi x sisi. Jadi 8 cm x 8 cm = 64 cm².', 20),
(9, 2, 4, 'Bentuk pecahan paling sederhana dari 12/16 adalah...', 'Bagi pembilang dan penyebut dengan FPB-nya (4): 12:4 = 3 dan 16:4 = 4. Hasilnya 3/4.', 20),
(10, 2, 5, 'Berapakah hasil dari (-7) x 8 + 10 ?', 'Operasi perkalian dulu: (-7) x 8 = -56. Lalu -56 + 10 = -46.', 20);

-- Soal untuk Kuis 3: Kuis Privat Logika PHP (3 Soal, total 100 poin)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(11, 3, 1, 'Simbol apa yang wajib digunakan untuk mengawali nama variabel dalam bahasa PHP?', 'Di PHP, setiap penulisan variabel selalu diawali dengan simbol tanda dolar ($), misalnya $nama atau $total.', 30),
(12, 3, 2, 'Perintah bawaan PHP manakah yang paling sering digunakan untuk menampilkan teks ke layar browser?', 'Perintah echo digunakan untuk mencetak satu atau lebih string ke layar secara langsung.', 35),
(13, 3, 3, 'Fungsi bawaan PHP yang direkomendasikan untuk mengenkripsi/meng-hash password pengguna secara aman adalah...', 'Fungsi password_hash() adalah fungsi standar PHP modern untuk hashing password dengan algoritma aman seperti BCRYPT.', 35);

-- Soal untuk Kuis 4: Mengenal Tata Surya (Menunggu persetujuan, 3 Soal)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(14, 4, 1, 'Planet apakah yang letaknya paling dekat dengan matahari?', 'Merkurius adalah planet yang posisinya paling dekat dengan matahari dalam sistem tata surya kita.', 30),
(15, 4, 2, 'Planet yang sering dijuluki sebagai "Planet Merah" adalah...', 'Mars sering disebut Planet Merah karena kandungan besi oksida di permukaannya membuatnya tampak kemerahan.', 35),
(16, 4, 3, 'Benda langit yang menjadi pusat peredaran seluruh planet di tata surya adalah...', 'Matahari adalah bintang induk yang massanya sangat besar sehingga menjadi pusat gravitasi tata surya.', 35);

-- Soal untuk Kuis 5: English Grammar (Draft, 2 Soal)
INSERT INTO `soal` (`id`, `kuis_id`, `urutan`, `pertanyaan`, `pembahasan`, `poin`) VALUES
(17, 5, 1, 'She ___ to school every morning by bicycle.', 'Untuk subjek orang ketiga tunggal (She/He/It) pada Simple Present Tense, kata kerja ditambah akhiran -s/-es, yaitu "goes".', 50),
(18, 5, 2, 'They ___ football in the field yesterday afternoon.', 'Kata keterangan "yesterday" menandakan Simple Past Tense (lampau), sehingga menggunakan kata kerja bentuk kedua (V2): "played".', 50),
(19, 6, 1, 'Penulisan huruf kapital yang tepat menurut kaidah bahasa Indonesia terdapat pada kalimat...', 'Nama pulau, kota, dan unsur geografi yang diikuti nama diri ditulis menggunakan huruf kapital (misal: Pulau Bali, Kota Bandar Lampung).', 35),
(20, 6, 2, 'Manakah kelompok kata di bawah ini yang seluruhnya merupakan kata baku?', 'Bentuk baku yang tercatat di KBBI adalah ''izin'', ''antre'', dan ''kualitas'' (bukan ijin, antri, kwalitas).', 35),
(21, 6, 3, 'Tanda baca yang tepat untuk memisahkan unsur-unsur dalam suatu pemerincian atau pembilangan adalah...', 'Tanda koma (,) dipakai di antara unsur-unsur dalam suatu perincian atau pembilangan.', 30),
(22, 7, 1, 'Properti CSS apa yang digunakan untuk mengubah kontainer menjadi layout flexbox?', 'Properti ''display: flex'' digunakan untuk mengubah elemen pembungkus menjadi flex container.', 35),
(23, 7, 2, 'Dalam CSS Flexbox, properti apa yang mengatur perataan item di sepanjang sumbu utama (main axis)?', '''justify-content'' mengatur penjajaran elemen anak di sepanjang sumbu utama (secara horizontal jika row).', 35),
(24, 7, 3, 'Properti CSS Grid manakah yang mendefinisikan jumlah serta lebar kolom-kolom grid?', '''grid-template-columns'' digunakan untuk menentukan ukuran dan pembagian kolom dalam CSS Grid.', 30),
(25, 8, 1, 'Sebuah mobil menempuh jarak 120 km dalam waktu 2 jam. Berapakah kecepatan rata-rata mobil tersebut?', 'Kecepatan rata-rata dihitung dari jarak dibagi waktu: 120 km : 2 jam = 60 km/jam.', 35),
(26, 8, 2, 'Hukum Newton manakah yang menyatakan bahwa resultan gaya berbanding lurus dengan percepatan (F = m . a)?', 'Hukum II Newton menjelaskan hubungan antara gaya resultan (F), massa benda (m), dan percepatan (a).', 35),
(27, 8, 3, 'Satuan Standar Internasional (SI) untuk mengukur gaya adalah...', 'Satuan Internasional untuk gaya adalah Newton (N), dinamai dari fisikawan Sir Isaac Newton.', 30),
(28, 9, 1, 'What is the English word for a room in a school where students perform science experiments?', 'A laboratory (or science lab) is a workplace for the conduct of scientific research and experiments.', 35),
(29, 9, 2, 'Choose the word that best describes someone who enjoys painting and drawing pictures:', 'An artist or painter is a person who creates drawings and paintings.', 35),
(30, 9, 3, 'Which of the following is the opposite meaning (antonym) of the word ''generous''?', '''Stingy'' or ''selfish'' means unwilling to spend or give, which is the antonym of generous.', 30),
(31, 10, 1, 'Akar-akar dari persamaan kuadrat x² - 5x + 6 = 0 adalah...', 'Faktorkan persamaan: (x - 2)(x - 3) = 0, sehingga diperoleh x = 2 atau x = 3.', 35),
(32, 10, 2, 'Rumus untuk menghitung diskriminan (D) dari persamaan kuadrat ax² + bx + c = 0 adalah...', 'Nilai diskriminan dirumuskan sebagai D = b² - 4ac.', 35),
(33, 10, 3, 'Jika nilai diskriminan D > 0, maka persamaan kuadrat memiliki...', 'Jika D > 0, persamaan kuadrat memiliki dua akar real yang berbeda/berlainan.', 30),
(34, 11, 1, 'Kata kunci JavaScript modern manakah yang digunakan untuk mendeklarasikan variabel bernilai tetap (konstan)?', '''const'' digunakan untuk variabel yang nilainya tidak dapat di-reassign setelah inisialisasi awal.', 35),
(35, 11, 2, 'Metode array JavaScript apa yang digunakan untuk menambahkan elemen baru di urutan paling belakang?', 'Metode push() menambahkan satu atau lebih elemen ke akhir array dan mengembalikan panjang array baru.', 35),
(36, 11, 3, 'Operator apakah yang digunakan untuk membandingkan kesamaan nilai DAN tipe data sekaligus di JavaScript?', 'Operator ''==='' (strict equality) memeriksa apakah kedua operand sama baik nilai maupun tipe datanya.', 30),
(37, 12, 1, 'Organ pencernaan manakah yang pertama kali melakukan pencernaan mekanik dan kimiawi terhadap makanan?', 'Mulut melakukan pencernaan mekanik dengan gigi dan kimiawi dengan bantuan enzim ptialin (amilase saliva).', 35),
(38, 12, 2, 'Enzim yang dihasilkan oleh lambung dan berfungsi mengendapkan protein susu (kasein) adalah...', 'Enzim renin di dalam getah lambung berfungsi menggumpalkan atau mengendapkan kasein susu.', 35),
(39, 12, 3, 'Penyerapan sebagian besar sari-sari makanan oleh tubuh manusia terjadi pada bagian...', 'Usus halus (khususnya jejunum dan ileum) merupakan tempat utama penyerapan sari makanan ke aliran darah.', 30),
(40, 13, 1, 'Gagasan yang menjadi dasar pengembangan atau pokok bahasan dalam sebuah paragraf disebut...', 'Gagasan utama atau ide pokok merupakan inti permasalahan yang dibahas dalam paragraf.', 35),
(41, 13, 2, 'Paragraf yang kalimat utamanya terletak di awal paragraf dinamakan paragraf...', 'Paragraf deduktif memiliki kalimat utama di awal paragraf, lalu diikuti kalimat-kalimat penjelas.', 35),
(42, 13, 3, 'Kata yang memiliki makna atau arti yang serupa dengan kata lain disebut...', 'Sinonim adalah hubungan semantik yang menyatakan kesamaan makna antara satu ujaran dengan ujaran lain.', 30),
(43, 14, 1, 'Berapakah kompleksitas waktu rata-rata (average time complexity) dari algoritma Binary Search?', 'Binary Search membagi ruang pencarian menjadi dua pada tiap iterasi, sehingga kompleksitasnya O(log n).', 35),
(44, 14, 2, 'Syarat utama agar algoritma Binary Search dapat dijalankan pada sebuah array data adalah...', 'Binary Search hanya dapat bekerja pada himpunan data yang sudah terurut (sorted array).', 35),
(45, 14, 3, 'Algoritma pengurutan manakah yang bekerja dengan cara membandingkan elemen berdekatan dan menukarnya jika urutan salah?', 'Bubble Sort secara berulang membandingkan pasangan elemen yang bersebelahan dan menukarnya bila tidak urut.', 30),
(46, 15, 1, 'Bentuk paling sederhana dari pecahan 18/24 adalah...', 'Bagi pembilang dan penyebut dengan FPB-nya (6): 18 : 6 = 3, dan 24 : 6 = 4. Hasilnya 3/4.', 35),
(47, 15, 2, 'Bentuk persen (%) dari pecahan 3/5 adalah...', 'Ubah ke per seratus: 3/5 x 100% = 300 : 5 = 60%.', 35),
(48, 15, 3, 'Hasil penjumlahan pecahan: 1/4 + 2/4 adalah...', 'Karena penyebutnya sudah sama (4), jumlahkan pembilang: 1 + 2 = 3. Jadi hasilnya 3/4.', 30),
(49, 16, 1, 'Partikel penyusun atom yang berada di inti atom dan bermuatan listrik positif adalah...', 'Proton adalah partikel subatomik bermuatan positif yang terletak di dalam inti atom (nukleus).', 35),
(50, 16, 2, 'Lambang kimia untuk unsur Emas pada tabel periodik unsur adalah...', 'Emas memiliki lambang kimia Au, yang berasal dari bahasa Latin ''Aurum'' (berkilau).', 35),
(51, 16, 3, 'Gas mulia yang memiliki nomor atom 2 dan sering digunakan untuk mengisi balon udara adalah...', 'Helium (He) adalah gas mulia ringan, tidak mudah terbakar, dan nomor atomnya 2.', 30),
(52, 17, 1, 'Complete the sentence: ''If it rains tomorrow, we ___ at home.'' (First Conditional)', 'First conditional uses: If + simple present, will + infinitive (will stay).', 35),
(53, 17, 2, 'Complete the sentence: ''If I ___ a million dollars, I would travel around the world.'' (Second Conditional)', 'Second conditional for hypothetical situations uses: If + past simple (had), would + infinitive.', 35),
(54, 17, 3, 'Which sentence expresses an imaginary situation in the present (Second Conditional)?', '''If she studied harder, she would pass the exam'' is a second conditional sentence.', 30),
(55, 18, 1, 'Sebuah kolom dalam tabel basis data yang nilainya unik dan menjadi pembeda utama setiap baris data disebut...', 'Primary Key adalah kolom atau kumpulan kolom yang secara unik mengidentifikasi setiap baris pada tabel.', 35),
(56, 18, 2, 'Perintah SQL manakah yang digunakan untuk mengambil data dari tabel basis data?', 'Perintah SELECT digunakan untuk memilih dan mengambil data dari satu atau lebih tabel dalam basis data.', 35),
(57, 18, 3, 'Klausa SQL apakah yang digunakan untuk memfilter atau menyaring data berdasarkan kondisi tertentu?', 'Klausa WHERE digunakan bersama SELECT, UPDATE, atau DELETE untuk menyaring baris data yang memenuhi syarat.', 30),
(58, 19, 1, 'Bagian awal dari teks eksplanasi yang berisi pengenalan atau gambaran umum fenomena yang akan dibahas disebut...', 'Pernyataan umum (identifikasi fenomena) adalah struktur pembuka dalam teks eksplanasi.', 35),
(59, 19, 2, 'Konjungsi yang menyatakan hubungan sebab-akibat (kausalitas) dalam teks eksplanasi adalah...', '''Oleh karena itu'' dan ''karena'' merupakan konjungsi kausalitas yang menjelaskan sebab-akibat terjadinya peristiwa.', 35),
(60, 19, 3, 'Tujuan utama penulisan teks eksplanasi adalah untuk...', 'Teks eksplanasi bertujuan menjelaskan proses terjadinya suatu fenomena alam, sosial, atau budaya secara ilmiah.', 30),
(61, 20, 1, 'Pada segitiga siku-siku, perbandingan antara sisi depan sudut dengan sisi miring disebut...', 'Sinus (sin) dari suatu sudut pada segitiga siku-siku adalah perbandingan panjang sisi depan terhadap sisi miring.', 35),
(62, 20, 2, 'Berapakah nilai perbandingan trigonometri dari sin 30°?', 'Nilai sudut istimewa untuk sinus 30 derajat adalah 1/2 (setengah).', 35),
(63, 20, 3, 'Rumus identitas trigonometri dasar yang selalu bernilai 1 adalah...', 'Identitas Pythagoras dalam trigonometri: sin²(x) + cos²(x) = 1.', 30);


-- ----------------------------------------------------------------------------
-- 6. Data Opsi Jawaban (opsi_jawaban)
-- Setiap soal memiliki 4 pilihan (A, B, C, D).
-- Kolom `benar` bernilai 1 jika jawaban benar, dan 0 jika jawaban salah.
-- Tepat satu jawaban benar per soal!
-- ----------------------------------------------------------------------------

-- Opsi Soal 1: Kepanjangan HTML (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(1, 1, 'A', 'HyperText Markup Language', 1),
(2, 1, 'B', 'HighText Machine Language', 0),
(3, 1, 'C', 'Hyperlinks Text Multi Language', 0),
(4, 1, 'D', 'Home Tool Markup Language', 0);

-- Opsi Soal 2: Heading terbesar (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(5, 2, 'A', '<head>', 0),
(6, 2, 'B', '<h1>', 1),
(7, 2, 'C', '<h6>', 0),
(8, 2, 'D', '<header>', 0);

-- Opsi Soal 3: Atribut link (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(9, 3, 'A', 'src', 0),
(10, 3, 'B', 'link', 0),
(11, 3, 'C', 'href', 1),
(12, 3, 'D', 'target', 0);

-- Opsi Soal 4: Tag gambar (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(13, 4, 'A', '<img>', 1),
(14, 4, 'B', '<image>', 0),
(15, 4, 'C', '<picture>', 0),
(16, 4, 'D', '<pic>', 0);

-- Opsi Soal 5: Daftar berurutan angka (Kunci: D)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(17, 5, 'A', '<ul>', 0),
(18, 5, 'B', '<li>', 0),
(19, 5, 'C', '<dl>', 0),
(20, 5, 'D', '<ol>', 1);

-- Opsi Soal 6: 15 + 25 : 5 (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(21, 6, 'A', '8', 0),
(22, 6, 'B', '15', 0),
(23, 6, 'C', '20', 1),
(24, 6, 'D', '25', 0);

-- Opsi Soal 7: 2x + 6 = 16 (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(25, 7, 'A', '4', 0),
(26, 7, 'B', '5', 1),
(27, 7, 'C', '6', 0),
(28, 7, 'D', '10', 0);

-- Opsi Soal 8: Luas persegi sisi 8 cm (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(29, 8, 'A', '64 cm²', 1),
(30, 8, 'B', '32 cm²', 0),
(31, 8, 'C', '16 cm²', 0),
(32, 8, 'D', '48 cm²', 0);

-- Opsi Soal 9: Pecahan 12/16 (Kunci: D)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(33, 9, 'A', '1/2', 0),
(34, 9, 'B', '2/3', 0),
(35, 9, 'C', '3/5', 0),
(36, 9, 'D', '3/4', 1);

-- Opsi Soal 10: (-7) x 8 + 10 (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(37, 10, 'A', '-56', 0),
(38, 10, 'B', '-46', 1),
(39, 10, 'C', '46', 0),
(40, 10, 'D', '-66', 0);

-- Opsi Soal 11: Simbol variabel PHP (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(41, 11, 'A', '$', 1),
(42, 11, 'B', '#', 0),
(43, 11, 'C', '@', 0),
(44, 11, 'D', '&', 0);

-- Opsi Soal 12: Menampilkan teks PHP (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(45, 12, 'A', 'write()', 0),
(46, 12, 'B', 'display()', 0),
(47, 12, 'C', 'echo', 1),
(48, 12, 'D', 'console.log()', 0);

-- Opsi Soal 13: Enkripsi sandi PHP (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(49, 13, 'A', 'md5()', 0),
(50, 13, 'B', 'password_hash()', 1),
(51, 13, 'C', 'sha1()', 0),
(52, 13, 'D', 'encrypt()', 0);

-- Opsi Soal 14: Planet terdekat matahari (Kunci: A)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(53, 14, 'A', 'Merkurius', 1),
(54, 14, 'B', 'Venus', 0),
(55, 14, 'C', 'Bumi', 0),
(56, 14, 'D', 'Mars', 0);

-- Opsi Soal 15: Planet merah (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(57, 15, 'A', 'Jupiter', 0),
(58, 15, 'B', 'Saturnus', 0),
(59, 15, 'C', 'Mars', 1),
(60, 15, 'D', 'Uranus', 0);

-- Opsi Soal 16: Pusat tata surya (Kunci: D)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(61, 16, 'A', 'Bulan', 0),
(62, 16, 'B', 'Bumi', 0),
(63, 16, 'C', 'Komet', 0),
(64, 16, 'D', 'Matahari', 1);

-- Opsi Soal 17: English She ___ to school (Kunci: B)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(65, 17, 'A', 'go', 0),
(66, 17, 'B', 'goes', 1),
(67, 17, 'C', 'went', 0),
(68, 17, 'D', 'going', 0);

-- Opsi Soal 18: English They ___ football yesterday (Kunci: C)
INSERT INTO `opsi_jawaban` (`id`, `soal_id`, `label`, `teks`, `benar`) VALUES
(69, 18, 'A', 'plays', 0),
(70, 18, 'B', 'play', 0),
(71, 18, 'C', 'played', 1),
(72, 18, 'D', 'playing', 0),
(73, 19, 'A', 'Kami sedang berlibur di pulau Bali.', 0),
(74, 19, 'B', 'Kami sedang berlibur di Pulau Bali.', 1),
(75, 19, 'C', 'kami sedang berlibur di Pulau bali.', 0),
(76, 19, 'D', 'Kami sedang Berlibur di pulau bali.', 0),
(77, 20, 'A', 'Ijin, antri, kwalitas', 0),
(78, 20, 'B', 'Izin, antre, kualitas', 1),
(79, 20, 'C', 'Ijin, antre, kwalitas', 0),
(80, 20, 'D', 'Izin, antri, kwalitas', 0),
(81, 21, 'A', 'Tanda titik (.)', 0),
(82, 21, 'B', 'Tanda hubung (-)', 0),
(83, 21, 'C', 'Tanda titik koma (;)', 0),
(84, 21, 'D', 'Tanda koma (,)', 1),
(85, 22, 'A', 'display: flex;', 1),
(86, 22, 'B', 'flex-direction: row;', 0),
(87, 22, 'C', 'align-items: center;', 0),
(88, 22, 'D', 'display: inline-block;', 0),
(89, 23, 'A', 'align-items', 0),
(90, 23, 'B', 'justify-content', 1),
(91, 23, 'C', 'flex-wrap', 0),
(92, 23, 'D', 'align-content', 0),
(93, 24, 'A', 'grid-gap', 0),
(94, 24, 'B', 'grid-template-rows', 0),
(95, 24, 'C', 'grid-template-columns', 1),
(96, 24, 'D', 'grid-column-gap', 0),
(97, 25, 'A', '40 km/jam', 0),
(98, 25, 'B', '50 km/jam', 0),
(99, 25, 'C', '60 km/jam', 1),
(100, 25, 'D', '80 km/jam', 0),
(101, 26, 'A', 'Hukum I Newton', 0),
(102, 26, 'B', 'Hukum II Newton', 1),
(103, 26, 'C', 'Hukum III Newton', 0),
(104, 26, 'D', 'Hukum Kekekalan Energi', 0),
(105, 27, 'A', 'Joule', 0),
(106, 27, 'B', 'Watt', 0),
(107, 27, 'C', 'Pascal', 0),
(108, 27, 'D', 'Newton', 1),
(109, 28, 'A', 'Library', 0),
(110, 28, 'B', 'Canteen', 0),
(111, 28, 'C', 'Laboratory', 1),
(112, 28, 'D', 'Gymnasium', 0),
(113, 29, 'A', 'Painter', 1),
(114, 29, 'B', 'Musician', 0),
(115, 29, 'C', 'Scientist', 0),
(116, 29, 'D', 'Architect', 0),
(117, 30, 'A', 'Kind', 0),
(118, 30, 'B', 'Stingy', 1),
(119, 30, 'C', 'Polite', 0),
(120, 30, 'D', 'Honest', 0),
(121, 31, 'A', 'x = -2 atau x = -3', 0),
(122, 31, 'B', 'x = 2 atau x = 3', 1),
(123, 31, 'C', 'x = 1 atau x = 6', 0),
(124, 31, 'D', 'x = -1 atau x = -6', 0),
(125, 32, 'A', 'D = b² + 4ac', 0),
(126, 32, 'B', 'D = 2b - 4ac', 0),
(127, 32, 'C', 'D = b² - 4ac', 1),
(128, 32, 'D', 'D = 4ac - b²', 0),
(129, 33, 'A', 'Dua akar real yang kembar (sama)', 0),
(130, 33, 'B', 'Dua akar real yang berbeda', 1),
(131, 33, 'C', 'Tidak mempunyai akar real (imajiner)', 0),
(132, 33, 'D', 'Akar selalu bernilai nol', 0),
(133, 34, 'A', 'var', 0),
(134, 34, 'B', 'let', 0),
(135, 34, 'C', 'const', 1),
(136, 34, 'D', 'constant', 0),
(137, 35, 'A', 'pop()', 0),
(138, 35, 'B', 'push()', 1),
(139, 35, 'C', 'shift()', 0),
(140, 35, 'D', 'unshift()', 0),
(141, 36, 'A', '==', 0),
(142, 36, 'B', '=', 0),
(143, 36, 'C', '===', 1),
(144, 36, 'D', '!=', 0),
(145, 37, 'A', 'Mulut', 1),
(146, 37, 'B', 'Lambung', 0),
(147, 37, 'C', 'Kerongkongan', 0),
(148, 37, 'D', 'Usus Halus', 0),
(149, 38, 'A', 'Pepsin', 0),
(150, 38, 'B', 'Lipase', 0),
(151, 38, 'C', 'Renin', 1),
(152, 38, 'D', 'Amilase', 0),
(153, 39, 'A', 'Usus besar', 0),
(154, 39, 'B', 'Usus halus', 1),
(155, 39, 'C', 'Lambung', 0),
(156, 39, 'D', 'Pankreas', 0),
(157, 40, 'A', 'Kalimat penjelas', 0),
(158, 40, 'B', 'Ide pokok', 1),
(159, 40, 'C', 'Kata kunci', 0),
(160, 40, 'D', 'Kesimpulan', 0),
(161, 41, 'A', 'Induktif', 0),
(162, 41, 'B', 'Campuran', 0),
(163, 41, 'C', 'Deduktif', 1),
(164, 41, 'D', 'Naratif', 0),
(165, 42, 'A', 'Antonim', 0),
(166, 42, 'B', 'Homonim', 0),
(167, 42, 'C', 'Akronim', 0),
(168, 42, 'D', 'Sinonim', 1),
(169, 43, 'A', 'O(n)', 0),
(170, 43, 'B', 'O(1)', 0),
(171, 43, 'C', 'O(log n)', 1),
(172, 43, 'D', 'O(n²)', 0),
(173, 44, 'A', 'Data harus bertipe bilangan bulat', 0),
(174, 44, 'B', 'Data harus sudah dalam keadaan terurut', 1),
(175, 44, 'C', 'Jumlah data harus genap', 0),
(176, 44, 'D', 'Tidak boleh ada data bernilai nol', 0),
(177, 45, 'A', 'Merge Sort', 0),
(178, 45, 'B', 'Bubble Sort', 1),
(179, 45, 'C', 'Insertion Sort', 0),
(180, 45, 'D', 'Heap Sort', 0),
(181, 46, 'A', '2/3', 0),
(182, 46, 'B', '3/4', 1),
(183, 46, 'C', '4/5', 0),
(184, 46, 'D', '6/8', 0),
(185, 47, 'A', '30%', 0),
(186, 47, 'B', '50%', 0),
(187, 47, 'C', '60%', 1),
(188, 47, 'D', '75%', 0),
(189, 48, 'A', '3/8', 0),
(190, 48, 'B', '2/8', 0),
(191, 48, 'C', '3/4', 1),
(192, 48, 'D', '1/2', 0),
(193, 49, 'A', 'Elektron', 0),
(194, 49, 'B', 'Neutron', 0),
(195, 49, 'C', 'Proton', 1),
(196, 49, 'D', 'Foton', 0),
(197, 50, 'A', 'Ag', 0),
(198, 50, 'B', 'Fe', 0),
(199, 50, 'C', 'Cu', 0),
(200, 50, 'D', 'Au', 1),
(201, 51, 'A', 'Hidrogen', 0),
(202, 51, 'B', 'Helium', 1),
(203, 51, 'C', 'Neon', 0),
(204, 51, 'D', 'Argon', 0),
(205, 52, 'A', 'stay', 0),
(206, 52, 'B', 'will stay', 1),
(207, 52, 'C', 'would stay', 0),
(208, 52, 'D', 'stayed', 0),
(209, 53, 'A', 'have', 0),
(210, 53, 'B', 'had', 1),
(211, 53, 'C', 'will have', 0),
(212, 53, 'D', 'having', 0),
(213, 54, 'A', 'If you heat ice, it melts.', 0),
(214, 54, 'B', 'If you call me, I will answer.', 0),
(215, 54, 'C', 'If she studied harder, she would pass the exam.', 1),
(216, 54, 'D', 'If I had seen you, I would have waved.', 0),
(217, 55, 'A', 'Foreign Key', 0),
(218, 55, 'B', 'Primary Key', 1),
(219, 55, 'C', 'Index Key', 0),
(220, 55, 'D', 'Secondary Key', 0),
(221, 56, 'A', 'INSERT', 0),
(222, 56, 'B', 'UPDATE', 0),
(223, 56, 'C', 'SELECT', 1),
(224, 56, 'D', 'FETCH', 0),
(225, 57, 'A', 'ORDER BY', 0),
(226, 57, 'B', 'GROUP BY', 0),
(227, 57, 'C', 'HAVING', 0),
(228, 57, 'D', 'WHERE', 1),
(229, 58, 'A', 'Deretan penjelas', 0),
(230, 58, 'B', 'Pernyataan umum', 1),
(231, 58, 'C', 'Ulasan / Interpretasi', 0),
(232, 58, 'D', 'Penegasan ulang', 0),
(233, 59, 'A', 'Kemudian', 0),
(234, 59, 'B', 'Setelah itu', 0),
(235, 59, 'C', 'Oleh karena itu', 1),
(236, 59, 'D', 'Selanjutnya', 0),
(237, 60, 'A', 'Menghibur pembaca dengan cerita fiksi', 0),
(238, 60, 'B', 'Menjelaskan proses terjadinya fenomena secara ilmiah', 1),
(239, 60, 'C', 'Mempromosikan suatu produk kepada masyarakat', 0),
(240, 60, 'D', 'Menggambarkan objek secara mendalam', 0),
(241, 61, 'A', 'Sinus (sin)', 1),
(242, 61, 'B', 'Cosinus (cos)', 0),
(243, 61, 'C', 'Tangen (tan)', 0),
(244, 61, 'D', 'Cotangen (cot)', 0),
(245, 62, 'A', '0', 0),
(246, 62, 'B', '1/2', 1),
(247, 62, 'C', '1/2 √2', 0),
(248, 62, 'D', '1', 0),
(249, 63, 'A', 'sin²(x) - cos²(x) = 1', 0),
(250, 63, 'B', 'sin²(x) + cos²(x) = 1', 1),
(251, 63, 'C', 'tan²(x) + 1 = sin²(x)', 0),
(252, 63, 'D', 'cos²(x) - sin²(x) = 1', 0);


-- ----------------------------------------------------------------------------
-- 7. Data Contoh Percobaan Kuis (percobaan_kuis)
-- Bermanfaat untuk mengetes halaman:
-- - Riwayat Pelajar (PL-07)
-- - Peringkat Nilai (PL-08)
-- - Hasil Kuis Pengajar (PJ-06)
-- ----------------------------------------------------------------------------

-- Percobaan 1: Ani Wijaya (id 4) mengerjakan Kuis 1 (Skor 80 dari 100, benar 4, salah 1)
INSERT INTO `percobaan_kuis` (`id`, `kuis_id`, `pelajar_id`, `waktu_mulai`, `waktu_selesai`, `durasi_menit`, `skor`, `skor_maksimal`, `jumlah_benar`, `jumlah_salah`, `status`, `catatan`) VALUES
(1, 1, 4, '2026-11-05 09:15:00', '2026-11-05 09:23:45', 15, 80, 100, 4, 1, 'selesai', 'Selesai tepat waktu');

-- Percobaan 2: Bima Pratama (id 5) mengerjakan Kuis 1 (Skor 100 dari 100, benar 5, salah 0)
INSERT INTO `percobaan_kuis` (`id`, `kuis_id`, `pelajar_id`, `waktu_mulai`, `waktu_selesai`, `durasi_menit`, `skor`, `skor_maksimal`, `jumlah_benar`, `jumlah_salah`, `status`, `catatan`) VALUES
(2, 1, 5, '2026-11-05 10:00:00', '2026-11-05 10:08:12', 15, 100, 100, 5, 0, 'selesai', 'Sempurna semua benar');

-- Percobaan 3: Ani Wijaya (id 4) mengerjakan Kuis 2 (Skor 100 dari 100, benar 5, salah 0)
INSERT INTO `percobaan_kuis` (`id`, `kuis_id`, `pelajar_id`, `waktu_mulai`, `waktu_selesai`, `durasi_menit`, `skor`, `skor_maksimal`, `jumlah_benar`, `jumlah_salah`, `status`, `catatan`) VALUES
(3, 2, 4, '2026-11-06 14:00:00', '2026-11-06 14:06:50', 10, 100, 100, 5, 0, 'selesai', 'Lulus matematika dengan nilai sempurna'),
(4, 6, 6, '2026-11-07 11:00:00', '2026-11-07 11:05:30', 10, 100, 100, 3, 0, 'selesai', 'Nilai sempurna Bahasa Indonesia'),
(5, 7, 4, '2026-11-08 14:00:00', '2026-11-08 14:09:15', 15, 65, 100, 2, 1, 'selesai', 'Cukup memahami CSS Flexbox'),
(6, 8, 5, '2026-11-09 10:15:00', '2026-11-09 10:22:45', 12, 100, 100, 3, 0, 'selesai', 'Lulus ujian Fisika Newton');


-- ----------------------------------------------------------------------------
-- 8. Data Jawaban Percobaan (jawaban_percobaan)
-- Menyimpan jawaban yang dipilih per soal beserta snapshot benar/salah & poin
-- ----------------------------------------------------------------------------

-- Jawaban untuk Percobaan 1 (Ani pada Kuis 1):
-- Soal 1: Opsi A (Benar, 20 poin)
-- Soal 2: Opsi B (Benar, 20 poin)
-- Soal 3: Opsi B (Salah, kunci adalah C href, Ani pilih link, 0 poin)
-- Soal 4: Opsi A (Benar, 20 poin)
-- Soal 5: Opsi D (Benar, 20 poin)
INSERT INTO `jawaban_percobaan` (`id`, `percobaan_id`, `soal_id`, `opsi_id`, `benar`, `poin`, `dijawab_pada`) VALUES
(1, 1, 1, 1, 1, 20, '2026-11-05 09:16:10'),
(2, 1, 2, 6, 1, 20, '2026-11-05 09:17:30'),
(3, 1, 3, 10, 0, 0, '2026-11-05 09:19:05'),
(4, 1, 4, 13, 1, 20, '2026-11-05 09:21:40'),
(5, 1, 5, 20, 1, 20, '2026-11-05 09:23:40');

-- Jawaban untuk Percobaan 2 (Bima pada Kuis 1 - Semua Benar):
INSERT INTO `jawaban_percobaan` (`id`, `percobaan_id`, `soal_id`, `opsi_id`, `benar`, `poin`, `dijawab_pada`) VALUES
(6, 2, 1, 1, 1, 20, '2026-11-05 10:01:05'),
(7, 2, 2, 6, 1, 20, '2026-11-05 10:02:40'),
(8, 2, 3, 11, 1, 20, '2026-11-05 10:04:15'),
(9, 2, 4, 13, 1, 20, '2026-11-05 10:06:00'),
(10, 2, 5, 20, 1, 20, '2026-11-05 10:08:10');

-- Jawaban untuk Percobaan 3 (Ani pada Kuis 2 - Semua Benar):
INSERT INTO `jawaban_percobaan` (`id`, `percobaan_id`, `soal_id`, `opsi_id`, `benar`, `poin`, `dijawab_pada`) VALUES
(11, 3, 6, 23, 1, 20, '2026-11-06 14:01:10'),
(12, 3, 7, 26, 1, 20, '2026-11-06 14:02:30'),
(13, 3, 8, 29, 1, 20, '2026-11-06 14:03:55'),
(14, 3, 9, 36, 1, 20, '2026-11-06 14:05:15'),
(15, 3, 10, 38, 1, 20, '2026-11-06 14:06:45');


-- ----------------------------------------------------------------------------
-- 9. Data Riwayat Status Kuis (log_status_kuis)
-- Mencatat jejak audit pengajuan dan persetujuan oleh Admin
-- ----------------------------------------------------------------------------
INSERT INTO `log_status_kuis` (`id`, `kuis_id`, `diubah_oleh`, `status_dari`, `status_ke`, `catatan`, `dibuat_pada`) VALUES
(1, 1, 2, 'draft', 'menunggu', 'Mengajukan kuis HTML untuk ditinjau oleh Admin.', '2026-11-01 08:30:00'),
(2, 1, 1, 'menunggu', 'terbit', 'Kuis disetujui. Soal dan pembahasan sudah sangat bagus dan sesuai standar.', '2026-11-01 09:00:00'),
(3, 2, 3, 'draft', 'menunggu', 'Kuis Matematika SMP siap dipublikasikan.', '2026-11-02 09:45:00'),
(4, 2, 1, 'menunggu', 'terbit', 'Disetujui. Pembahasan rumus sudah jelas.', '2026-11-02 10:00:00'),
(5, 3, 2, 'draft', 'menunggu', 'Kuis privat logika PHP untuk bahan evaluasi kelas.', '2026-11-03 12:00:00'),
(6, 3, 1, 'menunggu', 'terbit', 'Kuis privat disetujui.', '2026-11-03 13:00:00'),
(7, 4, 3, 'draft', 'menunggu', 'Mohon Admin meninjau kuis sains tata surya untuk siswa SD.', '2026-11-04 14:00:00'),
(8, 6, 3, 'draft', 'menunggu', 'Kuis EYD siap untuk diverifikasi.', '2026-11-06 08:30:00'),
(9, 6, 1, 'menunggu', 'terbit', 'Kuis disetujui.', '2026-11-06 09:00:00'),
(10, 7, 2, 'draft', 'menunggu', 'Kuis CSS Layout untuk siswa SMK/SMA.', '2026-11-06 12:00:00'),
(11, 7, 1, 'menunggu', 'terbit', 'Disetujui. Soal sangat relevan.', '2026-11-06 13:00:00'),
(12, 19, 3, 'draft', 'menunggu', 'Mohon tinjau kuis teks eksplanasi.', '2026-11-12 14:00:00');


-- ----------------------------------------------------------------------------
-- 10. Data Contoh Log Login (log_login)
-- ----------------------------------------------------------------------------
INSERT INTO `log_login` (`id`, `email`, `alamat_ip`, `waktu`) VALUES
(1, 'admin@dynaquiz.com', '127.0.0.1', '2026-11-07 08:00:00'),
(2, 'budi@guru.com', '127.0.0.1', '2026-11-07 08:15:00'),
(3, 'ani@siswa.com', '127.0.0.1', '2026-11-07 08:30:00');

SET FOREIGN_KEY_CHECKS = 1;
