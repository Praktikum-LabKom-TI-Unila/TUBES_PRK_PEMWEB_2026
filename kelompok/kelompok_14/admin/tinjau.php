<?php
// Halaman Tinjau Kuis Admin (AD-03, ADM-03)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Hanya Admin yang boleh membuka halaman ini
wajibPeran(['admin']);

// Ambil id kuis dari URL
$kuisId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($kuisId <= 0) {
    http_response_code(404);
    die('Kuis tidak ditemukan.');
}

// Ambil detail kuis beserta semua soal, opsi jawaban (kunci terlihat), dan pembahasannya
$kuis = ambilDetailKuisUntukTinjau($kuisId);
if (!$kuis) {
    http_response_code(404);
    die('Kuis tidak ditemukan.');
}

$error = '';
$catatan = '';

// Proses form saat tombol Setujui atau Tolak ditekan (POST)
if (isPost()) {
    verifikasiCsrf();

    $keputusan = $_POST['keputusan'] ?? '';
    $catatan = $_POST['catatan'] ?? '';
    $adminSekarang = penggunaSaatIni();
    $adminId = (int)$adminSekarang['id'];

    if ($keputusan !== 'terbit' && $keputusan !== 'ditolak') {
        $error = 'Keputusan tidak valid.';
    } else {
        $hasil = keputusanKuisAdmin($kuisId, $keputusan, $catatan, $adminId);
        if ($hasil['berhasil']) {
            flash('sukses', $hasil['pesan']);
            redirect(BASE_URL . '/admin/persetujuan.php');
        } else {
            $error = $hasil['pesan'];
            // Segarkan data kuis terbaru kalau-kalau statusnya sudah diubah admin lain
            $kuis = ambilDetailKuisUntukTinjau($kuisId);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tinjau Kuis - <?= e(NAMA_APLIKASI) ?></title>
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
                <a href="<?= e(BASE_URL) ?>/admin/persetujuan.php" class="btn btn-outline btn-sm">
                    &larr; Kembali ke Antrian
                </a>
            </div>

            <!-- Informasi Kuis -->
            <div class="card">
                <span class="badge badge-<?= e($kuis['status']) ?>">
                    Status: <?= ucfirst(e($kuis['status'])) ?>
                </span>
                <h1 class="card-title"><?= e($kuis['judul']) ?></h1>
                <p class="form-helper"><?= e($kuis['deskripsi'] ?: 'Tidak ada deskripsi kuis.') ?></p>
                <br>
                <p><strong>Pengajar:</strong> <?= e($kuis['nama_pengajar']) ?></p>
                <p><strong>Mapel / Tingkat:</strong> <?= e($kuis['nama_topik']) ?> - <?= e($kuis['nama_tingkat']) ?></p>
                <p><strong>Durasi:</strong> <?= (int)$kuis['durasi_menit'] ?> menit | <strong>Akses:</strong> <?= ucfirst(e($kuis['akses'])) ?></p>
                <p><strong>Total Soal:</strong> <?= count($kuis['daftar_soal']) ?> soal</p>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert alert-galat" role="alert">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <!-- Form Keputusan Admin (hanya muncul kalau status kuis masih 'menunggu') -->
            <?php if ($kuis['status'] === 'menunggu'): ?>
                <div class="card">
                    <h2 class="card-title">Keputusan Persetujuan</h2>
                    <p class="form-helper">Pastikan seluruh soal, kunci jawaban, dan pembahasan sudah layak sebelum diterbitkan.</p>
                    <br>

                    <form action="" method="POST" novalidate>
                        <?= inputCsrf() ?>

                        <div class="form-group">
                            <label for="catatan" class="form-label">Catatan untuk Pengajar</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="3" placeholder="Opsional jika disetujui. Wajib diisi minimal 10 karakter jika kuis ditolak."><?= e($catatan) ?></textarea>
                        </div>

                        <div class="bento-grid">
                            <button type="submit" name="keputusan" value="terbit" class="btn btn-primary">
                                Setujui &amp; Terbitkan Kuis
                            </button>
                            <button type="submit" name="keputusan" value="ditolak" class="btn btn-danger">
                                Tolak Kuis
                            </button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Kuis ini sudah berstatus <strong><?= ucfirst(e($kuis['status'])) ?></strong> dan tidak lagi berada di antrian persetujuan.
                </div>
            <?php endif; ?>

            <!-- Daftar Soal, Kunci Jawaban & Pembahasan -->
            <h2 class="card-title">Daftar Soal dan Kunci Jawaban</h2>

            <?php if (empty($kuis['daftar_soal'])): ?>
                <div class="card">
                    <p class="form-helper">Kuis ini belum memiliki soal.</p>
                </div>
            <?php else: ?>
                <?php foreach ($kuis['daftar_soal'] as $index => $soal): ?>
                    <div class="card">
                        <h3 class="card-title">Soal <?= $index + 1 ?> (<?= (int)$soal['poin'] ?> poin)</h3>
                        <p><?= nl2br(e($soal['pertanyaan'])) ?></p>
                        <br>

                        <div class="form-group">
                            <?php foreach ($soal['opsi'] as $opsi): ?>
                                <?php if ((int)$opsi['benar'] === 1): ?>
                                    <div class="alert alert-sukses">
                                        <strong><?= e($opsi['label']) ?>.</strong> <?= e($opsi['teks']) ?>
                                        <span class="badge badge-terbit">Kunci Jawaban</span>
                                    </div>
                                <?php else: ?>
                                    <div class="card card-sm">
                                        <strong><?= e($opsi['label']) ?>.</strong> <?= e($opsi['teks']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-info">
                            <strong>Pembahasan:</strong>
                            <?= e($soal['pembahasan'] ?: 'Tidak ada pembahasan.') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </main>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
