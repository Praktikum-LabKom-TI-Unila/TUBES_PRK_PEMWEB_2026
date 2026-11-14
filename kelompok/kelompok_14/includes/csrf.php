<?php
// Fungsi untuk membuat dan mengecek token CSRF pada form POST

// 1. Buat atau ambil token CSRF dari session
function ambilTokenCsrf() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

// 2. Buat tag input hidden CSRF untuk dipasang di dalam <form>
function inputCsrf() {
    $token = ambilTokenCsrf();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// 3. Cek apakah token CSRF yang dikirim pengguna cocok dengan yang ada di session
function verifikasiCsrf() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token_sesi = $_SESSION['csrf_token'] ?? '';
    $token_kirim = '';

    // Ambil token dari form biasa ($_POST) atau dari header fetch API
    if (isset($_POST['csrf_token'])) {
        $token_kirim = $_POST['csrf_token'];
    } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $token_kirim = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    // Kalau token kosong atau tidak sama, tolak dengan error 403
    if ($token_sesi === '' || $token_kirim === '' || !hash_equals($token_sesi, $token_kirim)) {
        http_response_code(403);
        die('403 Akses Ditolak: Token keamanan (CSRF) tidak valid.');
    }

    return true;
}
