<?php
// Halaman Kelola Pengguna Admin (AD-04, ADM-04, ADM-05, ADM-08)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/pengguna.php';

// Hanya Admin yang boleh membuka halaman ini
wajibPeran(['admin']);

$adminSekarang = penggunaSaatIni();
$adminId = (int)$adminSekarang['id'];

// Proses aksi POST (aktifkan, nonaktifkan, atau reset sandi)
if (isPost()) {
    verifikasiCsrf();

    $aksi = $_POST['aksi'] ?? '';
    $targetUserId = (int)($_POST['user_id'] ?? 0);

    if ($aksi === 'aktifkan') {
        $hasil = ubahStatusAktifPengguna($targetUserId, 1, $adminId);
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
        redirect(BASE_URL . '/admin/pengguna.php');
    } elseif ($aksi === 'nonaktifkan') {
        $hasil = ubahStatusAktifPengguna($targetUserId, 0, $adminId);
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
        redirect(BASE_URL . '/admin/pengguna.php');
    } elseif ($aksi === 'reset_sandi') {
        $hasil = resetSandiSementara($targetUserId);
        if ($hasil['berhasil']) {
            // Simpan sandi sementara di session agar hanya tampil 1 kali setelah redirect (ADM-05, AC-21)
            $_SESSION['sandi_sementara_baru'] = [
                'nama'  => $hasil['nama_pengguna'],
                'email' => $hasil['email_pengguna'],
                'sandi' => $hasil['sandi_sementara']
            ];
            flash('sukses', $hasil['pesan']);
        } else {
            flash('galat', $hasil['pesan']);
        }
        redirect(BASE_URL . '/admin/pengguna.php');
    }
}

// Ambil sandi sementara jika baru saja direset, lalu langsung hapus dari session (tampil 1x saja)
$infoSandiBaru = null;
if (isset($_SESSION['sandi_sementara_baru'])) {
    $infoSandiBaru = $_SESSION['sandi_sementara_baru'];
    unset($_SESSION['sandi_sementara_baru']);
}

// Ambil parameter filter dari URL (GET)
$filterPeran = $_GET['peran'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$cari = trim($_GET['cari'] ?? '');

$pesanFlash = flash();
$daftarPengguna = daftarSemuaPengguna($filterPeran, $filterStatus, $cari);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - <?= e(NAMA_APLIKASI) ?></title>
    <script src="<?= e(BASE_URL) ?>/assets/js/tema-init.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/tema.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/dasar.css">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/komponen.css">
</head>
<body class="layout-panel">

    <?php require __DIR__ . '/../includes/partials/sidebar-admin.php'; ?>

    <main class="panel-content">
        <div class="container">
            <div class="form-group">
                <h1>Kelola Pengguna</h1>
                <p class="form-helper">Aktifkan atau nonaktifkan akun, serta bantu reset sandi pengguna jika lupa password.</p>
            </div>

            <?php if ($pesanFlash): ?>
                <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                    <?= e($pesanFlash['pesan']) ?>
                </div>
            <?php endif; ?>

            <?php if ($infoSandiBaru): ?>
                <div class="card">
                    <h2 class="card-title">Sandi Sementara Berhasil Dibuat (Tampil Satu Kali)</h2>
                    <p>Silakan catat dan sampaikan sandi sementara ini kepada pengguna secara langsung:</p>
                    <br>
                    <p><strong>Nama:</strong> <?= e($infoSandiBaru['nama']) ?></p>
                    <p><strong>Email:</strong> <?= e($infoSandiBaru['email']) ?></p>
                    <div class="alert alert-info">
                        <strong>Sandi Sementara:</strong> <code><?= e($infoSandiBaru['sandi']) ?></code>
                    </div>
                    <p class="form-helper">Peringatan: Sandi ini akan hilang jika halaman dimuat ulang. Pengguna akan otomatis diminta mengganti sandi saat login berikutnya.</p>
                </div>
            <?php endif; ?>

            <!-- Form Filter & Pencarian -->
            <div class="card">
                <form action="" method="GET">
                    <div class="bento-grid">
                        <div class="form-group">
                            <label for="cari" class="form-label">Cari Nama / Email</label>
                            <input type="text" id="cari" name="cari" class="form-control" value="<?= e($cari) ?>" placeholder="Ketik nama atau email...">
                        </div>

                        <div class="form-group">
                            <label for="peran" class="form-label">Filter Peran</label>
                            <select id="peran" name="peran" class="form-select">
                                <option value="">Semua Peran</option>
                                <option value="pelajar" <?= $filterPeran === 'pelajar' ? 'selected' : '' ?>>Pelajar</option>
                                <option value="pengajar" <?= $filterPeran === 'pengajar' ? 'selected' : '' ?>>Pengajar</option>
                                <option value="admin" <?= $filterPeran === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="status" class="form-label">Filter Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                    <a href="<?= e(BASE_URL) ?>/admin/pengguna.php" class="btn btn-outline btn-sm">Reset Filter</a>
                </form>
            </div>

            <!-- Daftar Pengguna -->
            <?php if (empty($daftarPengguna)): ?>
                <div class="card">
                    <h2 class="card-title">Pengguna Tidak Ditemukan</h2>
                    <p class="form-helper">Belum ada pengguna yang sesuai dengan pencarian atau filter Anda.</p>
                </div>
            <?php else: ?>
                <div class="bento-grid">
                    <?php foreach ($daftarPengguna as $u): ?>
                        <div class="card">
                            <div>
                                <span class="badge <?= (int)$u['aktif'] === 1 ? 'badge-terbit' : 'badge-ditolak' ?>">
                                    <?= (int)$u['aktif'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                                <span class="badge badge-draft">
                                    <?= ucfirst(e($u['peran'])) ?>
                                </span>
                            </div>

                            <h2 class="card-title"><?= e($u['nama']) ?></h2>
                            <p class="form-helper"><?= e($u['email']) ?></p>
                            <p class="form-helper">Terdaftar: <?= e(formatTanggal($u['dibuat_pada'])) ?></p>

                            <?php if ((int)$u['wajib_ganti_sandi'] === 1): ?>
                                <p class="form-helper"><strong>Status:</strong> Menunggu ganti sandi</p>
                            <?php endif; ?>

                            <br>

                            <?php if ((int)$u['id'] === $adminId): ?>
                                <p class="form-helper"><em>(Akun Anda sendiri)</em></p>
                            <?php else: ?>
                                <!-- Form Aksi Pengguna -->
                                <form action="" method="POST" class="form-group">
                                    <?= inputCsrf() ?>
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">

                                    <?php if ((int)$u['aktif'] === 1): ?>
                                        <button type="submit" name="aksi" value="nonaktifkan" class="btn btn-danger btn-sm btn-block">
                                            Nonaktifkan Akun
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="aksi" value="aktifkan" class="btn btn-primary btn-sm btn-block">
                                            Aktifkan Akun
                                        </button>
                                    <?php endif; ?>
                                </form>

                                <form action="" method="POST">
                                    <?= inputCsrf() ?>
                                    <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" name="aksi" value="reset_sandi" class="btn btn-secondary btn-sm btn-block">
                                        Reset Sandi
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
