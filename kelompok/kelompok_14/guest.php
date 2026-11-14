<?php
// Halaman Masuk Mode Guest (PB-04, AUT-03)
// Mengizinkan pengunjung mencoba platform tanpa registrasi akun
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/kata_terlarang.php';

// Kalau pengguna sudah login akun biasa, arahkan ke dashboard masing-masing
alihkanJikaSudahMasuk();

$error = '';
$username = '';

// Proses form saat tombol "Mulai sebagai Guest" ditekan
if (isPost()) {
    verifikasiCsrf();

    $username = trim($_POST['username'] ?? '');

    // Validasi panjang username: 3 sampai 20 karakter (AUT-03)
    $panjang = strlen($username);
    if ($username === '') {
        $error = 'Nama panggilan (username) wajib diisi.';
    } elseif ($panjang < 3 || $panjang > 20) {
        $error = 'Username harus terdiri dari 3 sampai 20 karakter.';
    }
    // Karakter yang diizinkan hanya huruf, angka, spasi, dan underscore (AUT-03)
    elseif (!preg_match('/^[a-zA-Z0-9_ ]+$/', $username)) {
        $error = 'Username hanya boleh berisi huruf, angka, spasi, dan garis bawah (_).';
    }
    // Filter kata terlarang (SEC-13, AUT-03)
    elseif (cekKataTerlarang($username)) {
        $error = 'Username mengandung kata yang tidak diizinkan. Silakan pilih nama lain.';
    } else {
        // Regenerasi session ID untuk keamanan
        session_regenerate_id(true);

        // Data Guest hanya disimpan di session (tidak masuk ke tabel database user, BR-14)
        $_SESSION['guest'] = [
            'username' => $username,
            'masuk_pada' => date('Y-m-d H:i:s')
        ];

        // Pastikan session user biasa kosong
        unset($_SESSION['user']);

        // Arahkan ke dashboard Guest (PL-02)
        redirect(BASE_URL . '/guest/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mode Guest - <?= e(NAMA_APLIKASI) ?></title>
    <script src="<?= e(BASE_URL) ?>/assets/js/tema-init.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/tema.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/dasar.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/komponen.css">
</head>
<body class="layout-publik">

    <header class="navbar-top">
        <div class="navbar-container">
            <a href="<?= e(BASE_URL) ?>/index.php" class="navbar-brand">
                <?= e(NAMA_APLIKASI) ?>
            </a>
            <ul class="navbar-menu">
                <li><a href="<?= e(BASE_URL) ?>/login.php" class="navbar-link">Masuk</a></li>
                <li><a href="<?= e(BASE_URL) ?>/register.php" class="navbar-link">Daftar</a></li>
                <li><a href="<?= e(BASE_URL) ?>/guest.php" class="navbar-link active">Coba Guest</a></li>
                <li><?php require __DIR__ . '/includes/partials/pemilih-tema.php'; ?></li>
            </ul>
        </div>
    </header>

    <main>
        <div class="container-sm">
            <div class="card">
                <h1 class="card-title">Coba Tanpa Akun (Guest)</h1>
                <p class="form-helper">
                    Ingin langsung mencoba kuis publik? Masukkan nama panggilan Anda untuk memulai.
                </p>
                <br>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-galat" role="alert">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" novalidate>
                    <?= inputCsrf() ?>

                    <div class="form-group">
                        <label for="username" class="form-label">Nama Panggilan</label>
                        <input type="text" id="username" name="username" class="form-control" value="<?= e($username) ?>" placeholder="Contoh: Budi_01" required autofocus>
                        <p class="form-helper">Panjang 3-20 karakter. Boleh huruf, angka, spasi, dan underscore.</p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Mulai Mengerjakan</button>
                </form>

                <br>
                <div class="alert alert-info">
                    <strong>Catatan:</strong> Riwayat dan skor mode Guest hanya tersimpan sementara selama browser dibuka. Mau riwayat tersimpan permanen? <a href="<?= e(BASE_URL) ?>/register.php">Daftar akun gratis</a>.
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 <?= e(NAMA_APLIKASI) ?> - Kelompok 14</p>
    </footer>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
