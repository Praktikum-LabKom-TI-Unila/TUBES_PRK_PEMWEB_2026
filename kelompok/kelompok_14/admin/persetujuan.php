<?php
// Halaman Antrian Persetujuan Kuis Admin (AD-02, ADM-02)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Hanya Admin yang boleh membuka halaman ini
wajibPeran(['admin']);

// Ambil daftar kuis berstatus 'menunggu' (terlama di atas)
$daftarAntrian = daftarAntrianKuis();
$pesanFlash = flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Antrian Persetujuan - <?= e(NAMA_APLIKASI) ?></title>
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
                <h1>Antrian Persetujuan Kuis</h1>
                <p class="form-helper">Tinjau soal, kunci jawaban, dan pembahasan sebelum kuis diterbitkan. Pengajuan terlama ditampilkan paling atas.</p>
            </div>

            <?php if ($pesanFlash): ?>
                <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                    <?= e($pesanFlash['pesan']) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($daftarAntrian)): ?>
                <div class="card">
                    <h2 class="card-title">Antrian Kosong</h2>
                    <p class="form-helper">Belum ada pengajuan kuis baru dari pengajar yang perlu ditinjau saat ini.</p>
                </div>
            <?php else: ?>
                <div class="bento-grid">
                    <?php foreach ($daftarAntrian as $kuis): ?>
                        <div class="card">
                            <span class="badge badge-menunggu">
                                <?= $kuis['akses'] === 'privat' ? 'Privat' : 'Publik' ?>
                            </span>
                            <h2 class="card-title"><?= e($kuis['judul']) ?></h2>
                            <p class="form-helper"><strong>Pengajar:</strong> <?= e($kuis['nama_pengajar']) ?></p>
                            <p class="form-helper"><strong>Mapel:</strong> <?= e($kuis['nama_topik']) ?> (<?= e($kuis['nama_tingkat']) ?>)</p>
                            <p class="form-helper"><strong>Jumlah Soal:</strong> <?= (int)$kuis['jumlah_soal'] ?> soal</p>
                            <p class="form-helper"><strong>Diajukan:</strong> <?= e(formatTanggal($kuis['diperbarui_pada'])) ?></p>
                            <br>
                            <a href="<?= e(BASE_URL) ?>/admin/tinjau.php?id=<?= (int)$kuis['id'] ?>" class="btn btn-primary btn-block">
                                Tinjau Kuis
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
