<?php
// ============================================================================
// Skrip PHP Seeder Database DynaQuiz
// PIC: Wulan (Sesi 1 - Database Seed)
// Cara pakai:
// Buka terminal / command prompt, masuk ke folder kelompok_14, lalu ketik:
// php database/seed.php
// ============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

echo "==========================================\n";
echo "   SEEKER DATABASE DYNAQUIZ (KELOMPOK 14) \n";
echo "==========================================\n\n";

// 1. Cek apakah file seed.sql ada
$file_sql = __DIR__ . '/seed.sql';
if (!file_exists($file_sql)) {
    die("Error: File seed.sql tidak ditemukan di folder database!\n");
}

echo "Membaca file seed.sql...\n";
$isi_sql = file_get_contents($file_sql);

try {
    // 2. Matikan pengecekan foreign key sementara agar truncate lancar
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 3. Pecah file SQL menjadi perintah-perintah terpisah berdasarkan titik koma (;)
    $daftar_perintah = explode(';', $isi_sql);
    $jumlah_berhasil = 0;

    foreach ($daftar_perintah as $perintah) {
        $perintah = trim($perintah);

        // Abaikan jika baris kosong
        if ($perintah === '') {
            continue;
        }

        // Jalankan perintah SQL
        $pdo->exec($perintah);
        $jumlah_berhasil++;
    }

    // 4. Hidupkan kembali foreign key
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "Berhasil menjalankan {$jumlah_berhasil} perintah SQL ke database '" . DB_NAME . "'!\n\n";
    echo "--------------------------------------------------------\n";
    echo "DATA AKUN UNTUK LOGIN & PENGUJIAN:\n";
    echo "--------------------------------------------------------\n";
    echo "1. Admin    : admin@dynaquiz.com | Password: password123\n";
    echo "2. Pengajar : budi@guru.com      | Password: password123\n";
    echo "3. Pengajar : siti@guru.com      | Password: password123\n";
    echo "4. Pelajar  : ani@siswa.com       | Password: password123\n";
    echo "5. Pelajar  : bima@siswa.com      | Password: password123\n";
    echo "6. Pelajar  : citra@siswa.com     | Password: password123\n";
    echo "--------------------------------------------------------\n";
    echo "Tersedia 20 kuis lengkap dengan bank soal & kunci jawaban!\n";
    echo "Semua akun siap digunakan untuk pengujian fitur kelompok!\n";

} catch (PDOException $e) {
    echo "\nTerjadi kesalahan saat memasukkan data:\n";
    echo $e->getMessage() . "\n";
    echo "\nTips: Pastikan kamu sudah mengimpor file 'schema.sql' terlebih dahulu di phpMyAdmin sebelum menjalankan seeder ini.\n";
}
