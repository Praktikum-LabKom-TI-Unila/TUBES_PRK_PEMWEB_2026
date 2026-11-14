<?php
// Fungsi-fungsi untuk mengecek sesi login dan peran pengguna
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// 1. Ambil data pengguna yang sedang login dari session
function penggunaSaatIni() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

// 2. Ambil peran pengguna saat ini ('pelajar', 'pengajar', 'admin', 'guest', atau null)
function peranSaatIni() {
    if (isset($_SESSION['user']['peran'])) {
        return $_SESSION['user']['peran'];
    }

    if (isset($_SESSION['guest']['username'])) {
        return 'guest';
    }

    return null;
}

// 3. Cek apakah pengunjung sudah login (termasuk Guest)
function sudahMasuk() {
    if (peranSaatIni() !== null) {
        return true;
    }
    return false;
}

// 4. Ambil alamat halaman dashboard sesuai peran pengguna
function urlDashboardPeran($peran = null) {
    if ($peran === null) {
        $peran = peranSaatIni();
    }

    if ($peran === 'admin') {
        return BASE_URL . '/admin/dashboard.php';
    } elseif ($peran === 'pengajar') {
        return BASE_URL . '/pengajar/dashboard.php';
    } elseif ($peran === 'pelajar') {
        return BASE_URL . '/pelajar/dashboard.php';
    } elseif ($peran === 'guest') {
        return BASE_URL . '/guest/dashboard.php';
    }

    return BASE_URL . '/login.php';
}

// 5. Cek ke database apakah akun user yang sedang login masih ada dan aktif
function cekStatusAkunAktif() {
    global $pdo;

    if (!isset($_SESSION['user']['id'])) {
        return true;
    }

    $id_user = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$id_user]);
    $data_user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Kalau user sudah tidak ada di database, hapus session
    if (!$data_user) {
        unset($_SESSION['user']);
        return false;
    }

    // Kalau nanti ada kolom aktif = 0 (dinonaktifkan admin), keluarkan user
    if (isset($data_user['aktif']) && $data_user['aktif'] == 0) {
        unset($_SESSION['user']);
        $_SESSION['flash_pesan'] = [
            'tipe' => 'galat',
            'pesan' => 'Akun Anda telah dinonaktifkan oleh Admin.'
        ];
        return false;
    }

    // Perbarui session dengan data terbaru dari database
    $_SESSION['user']['nama'] = $data_user['nama'];
    $_SESSION['user']['email'] = $data_user['email'];
    $_SESSION['user']['peran'] = $data_user['peran'];

    if (isset($data_user['wajib_ganti_sandi'])) {
        $_SESSION['user']['wajib_ganti_sandi'] = $data_user['wajib_ganti_sandi'];
    }

    return true;
}

// 6. Fungsi wajibPeran() untuk membatasi akses halaman
// Contoh pakai: wajibPeran(['admin']) atau wajibPeran(['pelajar', 'guest'])
function wajibPeran($daftar_peran) {
    // Cek dulu apakah akun masih aktif di database
    if (isset($_SESSION['user']['id'])) {
        $masih_aktif = cekStatusAkunAktif();
        if (!$masih_aktif) {
            header("Location: " . BASE_URL . "/login.php");
            exit;
        }
    }

    $peran_sekarang = peranSaatIni();

    // Kalau belum login sama sekali, arahkan ke halaman login
    if ($peran_sekarang === null) {
        header("Location: " . BASE_URL . "/login.php");
        exit;
    }

    // Kalau perannya tidak ada di daftar yang diizinkan, tampilkan 403
    if (!in_array($peran_sekarang, $daftar_peran)) {
        http_response_code(403);
        $file_403 = dirname(__DIR__) . '/403.php';
        if (file_exists($file_403)) {
            require $file_403;
            exit;
        }
        die("403 Akses Ditolak: Anda tidak punya akses ke halaman ini.");
    }

    // Kalau user wajib ganti sandi sementara dari Admin, paksa ke profil.php
    if (isset($_SESSION['user']['wajib_ganti_sandi']) && $_SESSION['user']['wajib_ganti_sandi'] == 1) {
        $halaman_sekarang = basename($_SERVER['SCRIPT_NAME']);
        if ($halaman_sekarang !== 'profil.php' && $halaman_sekarang !== 'logout.php') {
            header("Location: " . BASE_URL . "/profil.php");
            exit;
        }
    }
}

// 7. Kalau user sudah login tapi membuka halaman login/register/landing, arahkan ke dashboard
// Parameter $izinkanGuest = true memungkinkan pengunjung mode tamu membuka login.php dan register.php
function alihkanJikaSudahMasuk($izinkanGuest = false) {
    $peran = peranSaatIni();
    if ($peran === null) {
        return;
    }

    if ($izinkanGuest && $peran === 'guest') {
        return;
    }

    if (sudahMasuk()) {
        header("Location: " . urlDashboardPeran());
        exit;
    }
}

