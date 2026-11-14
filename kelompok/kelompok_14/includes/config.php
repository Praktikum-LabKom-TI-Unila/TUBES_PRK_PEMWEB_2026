<?php
// Pengaturan umum aplikasi DynaQuiz

// 1. Atur zona waktu ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// 2. Nama aplikasi dan alamat dasar website (BASE_URL)
define('NAMA_APLIKASI', 'DynaQuiz');
define('BASE_URL', '/TUBES_PRK_PEMWEB_2026/kelompok/kelompok_14');

// 3. Panggil file config.local.php kalau filenya ada di laptop
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// 4. Kalau config.local.php belum dibuat, pakai pengaturan default XAMPP ini
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'dynaquiz');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

// 5. Pengaturan dan mulai session PHP
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);

    session_start();
}

// 6. Cek batas diam (idle timeout) selama 2 jam (7200 detik)
$batas_diam = 7200;
if (isset($_SESSION['waktu_aktif'])) {
    $lama_diam = time() - $_SESSION['waktu_aktif'];
    if ($lama_diam > $batas_diam) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['waktu_aktif'] = time();
