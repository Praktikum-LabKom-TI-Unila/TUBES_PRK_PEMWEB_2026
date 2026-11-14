<?php
// Halaman Masuk / Login (PB-02)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/fungsi/pengguna.php';

// Kalau pengguna sudah masuk akun terdaftar, arahkan ke dashboard (Guest diizinkan membuka login)
alihkanJikaSudahMasuk(true);

// Ambil pesan notifikasi flash jika ada (misal: sukses register)
$pesanFlash = flash();
$error = '';
$email = '';

// Proses ketika form dikirim (POST)
if (isPost()) {
    // 1. Verifikasi token CSRF demi keamanan form
    verifikasiCsrf();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 2. Verifikasi email dan password ke database
    $cekLogin = verifikasiLogin($email, $password);

    if ($cekLogin['berhasil']) {
        $user = $cekLogin['pengguna'];

        // Regenerasi session ID untuk mencegah session fixation (AUT-02, SEC-04)
        session_regenerate_id(true);

        // 3. Simpan data user ke dalam session
        $_SESSION['user'] = [
            'id'                => $user['id'],
            'nama'              => $user['nama'],
            'email'             => $user['email'],
            'peran'             => $user['peran'],
            'wajib_ganti_sandi' => isset($user['wajib_ganti_sandi']) ? (int)$user['wajib_ganti_sandi'] : 0
        ];

        // Hapus data guest jika sebelumnya ada di session
        unset($_SESSION['guest']);

        // Jika pengguna diwajibkan mengganti sandi sementara, arahkan ke profil.php (AUT-09, AC-21)
        if ($_SESSION['user']['wajib_ganti_sandi'] === 1) {
            flash('info', 'Anda login menggunakan sandi sementara. Silakan ganti password Anda terlebih dahulu.');
            redirect(BASE_URL . '/profil.php');
        }

        // 4. Arahkan pengguna ke dashboard sesuai perannya (AUT-02)
        redirect(urlDashboardPeran($user['peran']));
    } else {
        $error = $cekLogin['pesan'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk - <?= e(NAMA_APLIKASI) ?></title>
    <script src="<?= e(BASE_URL) ?>/assets/js/tema-init.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/tema.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/dasar.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/komponen.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth.css">
</head>
<body class="layout-publik">

    <header class="navbar-top">
        <div class="navbar-container">
            <a href="<?= e(BASE_URL) ?>/index.php" class="navbar-brand">
                <?= e(NAMA_APLIKASI) ?>
            </a>
            <ul class="navbar-menu">
                <li><a href="<?= e(BASE_URL) ?>/login.php" class="navbar-link active">Masuk</a></li>
                <li><a href="<?= e(BASE_URL) ?>/register.php" class="navbar-link">Daftar</a></li>
                <li><a href="<?= e(BASE_URL) ?>/guest.php" class="navbar-link">Coba Guest</a></li>
                <li><?php require __DIR__ . '/includes/partials/pemilih-tema.php'; ?></li>
            </ul>
        </div>
    </header>

    <main>
        <div class="container-sm">
            <div class="card">
                <h1 class="card-title">Masuk ke <?= e(NAMA_APLIKASI) ?></h1>
                <p class="form-helper">Masukkan email dan password untuk masuk ke akun Anda.</p>
                <br>

                <?php if ($pesanFlash): ?>
                    <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                        <?= e($pesanFlash['pesan']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <div class="alert alert-galat" role="alert">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" novalidate>
                    <?= inputCsrf() ?>

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="nama@email.com" required>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-sandi-wrap">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password Anda" required>
                            <button type="button" class="btn-toggle-sandi" data-target="password" aria-label="Tampilkan password" title="Tampilkan password">
                                <svg class="ikon-mata-buka" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="ikon-mata-tutup" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Masuk</button>
                </form>

                <br>
                <p class="form-helper">
                    Belum punya akun? <a href="<?= e(BASE_URL) ?>/register.php">Daftar di sini</a> atau <a href="<?= e(BASE_URL) ?>/guest.php">Masuk sebagai Guest</a>
                </p>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 <?= e(NAMA_APLIKASI) ?> - Kelompok 14</p>
    </footer>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
    <script src="<?= e(BASE_URL) ?>/assets/js/sandi-toggle.js"></script>
</body>
</html>
