<?php
// Halaman Pendaftaran Akun Baru (PB-03)
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/kata_terlarang.php';
require_once __DIR__ . '/includes/fungsi/pengguna.php';

// Kalau sudah login akun terdaftar, arahkan ke dashboard (Guest diizinkan membuka register)
alihkanJikaSudahMasuk(true);

// Variabel untuk menampung nilai input dan pesan error
$errors = [];
$nama = '';
$email = '';
$peran = 'pelajar';

// Proses ketika form dikirim (POST)
if (isPost()) {
    verifikasiCsrf();

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi_password'] ?? '';
    $peran = $_POST['peran'] ?? 'pelajar';

    // Cek validasi semua kolom
    $errors = validasiRegister([
        'nama' => $nama,
        'email' => $email,
        'password' => $password,
        'konfirmasi_password' => $konfirmasi,
        'peran' => $peran
    ]);

    // Kalau tidak ada error, simpan ke database lalu arahkan ke halaman login
    if (empty($errors)) {
        $berhasil = daftarPenggunaBaru($nama, $email, $password, $peran);

        if ($berhasil) {
            unset($_SESSION['guest']);
            flash('sukses', 'Pendaftaran berhasil! Silakan masuk menggunakan akun baru Anda.');
            redirect(BASE_URL . '/login.php');
        } else {
            $errors['umum'] = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - <?= e(NAMA_APLIKASI) ?></title>
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
                <li><a href="<?= e(BASE_URL) ?>/login.php" class="navbar-link">Masuk</a></li>
                <li><a href="<?= e(BASE_URL) ?>/register.php" class="navbar-link active">Daftar</a></li>
                <li><?php require __DIR__ . '/includes/partials/pemilih-tema.php'; ?></li>
            </ul>
        </div>
    </header>

    <main>
        <div class="container-sm">
            <div class="card">
                <h1 class="card-title">Daftar Akun Baru</h1>
                <p class="form-helper">Silakan isi data di bawah ini untuk membuat akun Pelajar atau Pengajar.</p>
                <br>

                <?php if (isset($errors['umum'])): ?>
                    <div class="alert alert-galat" role="alert">
                        <?= e($errors['umum']) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" novalidate>
                    <?= inputCsrf() ?>

                    <div class="form-group">
                        <label for="nama" class="form-label">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" class="form-control" value="<?= e($nama) ?>" placeholder="Contoh: Budi Santoso" required>
                        <?php if (isset($errors['nama'])): ?>
                            <div class="form-error" role="alert"><?= e($errors['nama']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Alamat Email</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="nama@email.com" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="form-error" role="alert"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-sandi-wrap">
                            <input type="password" id="password" name="password" class="form-control" placeholder="Minimal 8 karakter" required>
                            <button type="button" class="btn-toggle-sandi" data-target="password" aria-label="Tampilkan password" title="Tampilkan password">
                                <svg class="ikon-mata-buka" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="ikon-mata-tutup" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <div class="form-error" role="alert"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="konfirmasi_password" class="form-label">Konfirmasi Password</label>
                        <div class="input-sandi-wrap">
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" class="form-control" placeholder="Ulangi password" required>
                            <button type="button" class="btn-toggle-sandi" data-target="konfirmasi_password" aria-label="Tampilkan password" title="Tampilkan password">
                                <svg class="ikon-mata-buka" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="ikon-mata-tutup" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                            </button>
                        </div>
                        <?php if (isset($errors['konfirmasi_password'])): ?>
                            <div class="form-error" role="alert"><?= e($errors['konfirmasi_password']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="peran" class="form-label">Daftar Sebagai</label>
                        <select id="peran" name="peran" class="form-select" required>
                            <option value="pelajar" <?= $peran === 'pelajar' ? 'selected' : '' ?>>Pelajar (Mengerjakan Kuis)</option>
                            <option value="pengajar" <?= $peran === 'pengajar' ? 'selected' : '' ?>>Pengajar (Membuat Kuis)</option>
                        </select>
                        <?php if (isset($errors['peran'])): ?>
                            <div class="form-error" role="alert"><?= e($errors['peran']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang</button>
                </form>

                <br>
                <p class="form-helper">
                    Sudah punya akun? <a href="<?= e(BASE_URL) ?>/login.php">Masuk di sini</a>
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
