<?php
// Halaman Dashboard Admin (AD-01, ADM-01)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/admin.php';

// Hanya Admin yang boleh membuka halaman ini
wajibPeran(['admin']);

$adminSekarang = penggunaSaatIni();
$statistik = hitungStatistikAdmin();
$aktivitasTerbaru = ambilAktivitasTerbaruAdmin(8);
$pesanFlash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - <?= e(NAMA_APLIKASI) ?></title>
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
                <h1>Halo, <?= e($adminSekarang['nama']) ?>!</h1>
                <p class="form-helper">Ringkasan pengguna, status kuis, antrian persetujuan, dan aktivitas terbaru platform.</p>
            </div>

            <?php if ($pesanFlash): ?>
                <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                    <?= e($pesanFlash['pesan']) ?>
                </div>
            <?php endif; ?>

            <!-- Sorotan Antrian Persetujuan -->
            <div class="card">
                <h2 class="card-title">Antrian Persetujuan Kuis</h2>
                <?php if ($statistik['kuis_menunggu'] > 0): ?>
                    <p>Ada <strong><?= (int)$statistik['kuis_menunggu'] ?> kuis</strong> yang sedang menunggu ditinjau sebelum terbit.</p>
                    <br>
                    <a href="<?= e(BASE_URL) ?>/admin/persetujuan.php" class="btn btn-accent">
                        Tinjau Antrian Sekarang (<?= (int)$statistik['kuis_menunggu'] ?>)
                    </a>
                <?php else: ?>
                    <p class="form-helper">Semua pengajuan kuis sudah ditinjau. Antrian saat ini kosong.</p>
                <?php endif; ?>
            </div>

            <!-- Bento Grid Statistik -->
            <div class="bento-grid">
                <div class="card">
                    <span class="badge badge-terbit">Pengguna Terdaftar</span>
                    <h2 class="card-title">Statistik Pengguna</h2>
                    <p><strong>Pelajar:</strong> <?= (int)$statistik['total_pelajar'] ?> akun</p>
                    <p><strong>Pengajar:</strong> <?= (int)$statistik['total_pengajar'] ?> akun</p>
                    <p><strong>Admin:</strong> <?= (int)$statistik['total_admin'] ?> akun</p>
                    <br>
                    <a href="<?= e(BASE_URL) ?>/admin/pengguna.php" class="btn btn-outline btn-sm btn-block">Kelola Pengguna</a>
                </div>

                <div class="card">
                    <span class="badge badge-menunggu">Kurasi Kuis</span>
                    <h2 class="card-title">Status Kuis</h2>
                    <p><strong>Terbit:</strong> <?= (int)$statistik['kuis_terbit'] ?> kuis</p>
                    <p><strong>Menunggu:</strong> <?= (int)$statistik['kuis_menunggu'] ?> kuis</p>
                    <p><strong>Draft:</strong> <?= (int)$statistik['kuis_draft'] ?> kuis</p>
                    <p><strong>Ditolak:</strong> <?= (int)$statistik['kuis_ditolak'] ?> kuis | <strong>Ditarik:</strong> <?= (int)$statistik['kuis_ditarik'] ?> kuis</p>
                    <br>
                    <a href="<?= e(BASE_URL) ?>/admin/moderasi.php" class="btn btn-outline btn-sm btn-block">Moderasi Kuis</a>
                </div>

                <div class="card">
                    <span class="badge badge-draft">Data Master</span>
                    <h2 class="card-title">Akses Cepat</h2>
                    <p class="form-helper">Tambah atau ubah kategori mata pelajaran dan jenjang tingkat pendidikan.</p>
                    <br>
                    <a href="<?= e(BASE_URL) ?>/admin/mapel.php" class="btn btn-primary btn-sm btn-block">Kelola Mapel &amp; Tingkat</a>
                </div>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="card">
                <h2 class="card-title">Aktivitas Terbaru</h2>
                <?php if (empty($aktivitasTerbaru)): ?>
                    <p class="form-helper">Belum ada catatan perubahan status kuis.</p>
                <?php else: ?>
                    <?php foreach ($aktivitasTerbaru as $log): ?>
                        <div class="card card-sm">
                            <span class="badge badge-<?= e($log['status_ke']) ?>">
                                <?= ucfirst(e($log['status_dari'])) ?> &rarr; <?= ucfirst(e($log['status_ke'])) ?>
                            </span>
                            <p>
                                <strong><?= e($log['judul_kuis']) ?></strong>
                                oleh <?= e($log['nama_pelaku']) ?> (<?= ucfirst(e($log['peran_pelaku'])) ?>)
                            </p>
                            <?php if (!empty($log['catatan'])): ?>
                                <p class="form-helper">Catatan: <?= e($log['catatan']) ?></p>
                            <?php endif; ?>
                            <p class="form-helper"><?= e(formatTanggal($log['dibuat_pada'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
