<?php
// Proses Keluar / Logout (AUT-04)
// Menghapus seluruh sesi dan data pengguna, lalu kembali ke halaman Landing
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

// 1. Kosongkan semua data dalam session
$_SESSION = [];

// 2. Hapus cookie session dari browser jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Hancurkan sesi PHP secara total
session_destroy();

// 4. Arahkan pengguna kembali ke halaman utama (Landing / index.php)
redirect(BASE_URL . '/index.php');
