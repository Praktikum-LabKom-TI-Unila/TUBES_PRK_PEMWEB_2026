<?php
// Halaman Moderasi Kuis Admin (AD-06, ADM-07, AC-20)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';
require_once __DIR__ . '/../includes/fungsi/admin.php';

// Hanya Admin yang boleh membuka halaman ini
wajibPeran(['admin']);

$adminSekarang = penggunaSaatIni();
$adminId = (int)$adminSekarang['id'];

// Proses form POST Tarik Kuis (Moderasi)
if (isPost()) {
    verifikasiCsrf();

    $aksi = $_POST['aksi'] ?? '';
    if ($aksi === 'tarik_kuis') {
        $kuisId = (int)($_POST['kuis_id'] ?? 0);
        $alasan = $_POST['alasan'] ?? '';

        $hasil = tarikKuisModerasi($kuisId, $alasan, $adminId);
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    }

    redirect(BASE_URL . '/admin/moderasi.php?tab=terbit');
}

// Ambil tab dan filter dari URL (GET)
$tabStatus = ($_GET['tab'] ?? 'terbit') === 'ditarik' ? 'ditarik' : 'terbit';
$filterTopik = (int)($_GET['topik_id'] ?? 0);
$filterPengajar = (int)($_GET['pengajar_id'] ?? 0);

$pesanFlash = flash();
$daftarTopik = ambilSemuaTopik();
$daftarPengajar = daftarSemuaPengajar();
$daftarKuis = daftarKuisUntukModerasi($tabStatus, $filterTopik, $filterPengajar);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderasi Kuis - <?= e(NAMA_APLIKASI) ?></title>
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
                <h1>Moderasi Kuis</h1>
                <p class="form-helper">Pantau kuis yang sudah terbit dan tarik kuis bermasalah beserta alasan yang jelas untuk pengajar.</p>
            </div>

            <?php if ($pesanFlash): ?>
                <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                    <?= e($pesanFlash['pesan']) ?>
                </div>
            <?php endif; ?>

            <!-- Tab Status: Terbit vs Ditarik -->
            <div class="form-group">
                <a href="<?= e(BASE_URL) ?>/admin/moderasi.php?tab=terbit" class="btn <?= $tabStatus === 'terbit' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Kuis Terbit
                </a>
                <a href="<?= e(BASE_URL) ?>/admin/moderasi.php?tab=ditarik" class="btn <?= $tabStatus === 'ditarik' ? 'btn-primary' : 'btn-outline' ?> btn-sm">
                    Kuis Ditarik
                </a>
            </div>

            <!-- Filter Mapel dan Pengajar -->
            <div class="card">
                <form action="" method="GET">
                    <input type="hidden" name="tab" value="<?= e($tabStatus) ?>">
                    <div class="bento-grid">
                        <div class="form-group">
                            <label for="topik_id" class="form-label">Filter Mapel</label>
                            <select id="topik_id" name="topik_id" class="form-select">
                                <option value="0">Semua Mapel</option>
                                <?php foreach ($daftarTopik as $t): ?>
                                    <option value="<?= (int)$t['id'] ?>" <?= $filterTopik === (int)$t['id'] ? 'selected' : '' ?>>
                                        <?= e($t['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="pengajar_id" class="form-label">Filter Pengajar</label>
                            <select id="pengajar_id" name="pengajar_id" class="form-select">
                                <option value="0">Semua Pengajar</option>
                                <?php foreach ($daftarPengajar as $p): ?>
                                    <option value="<?= (int)$p['id'] ?>" <?= $filterPengajar === (int)$p['id'] ? 'selected' : '' ?>>
                                        <?= e($p['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm">Terapkan Filter</button>
                    <a href="<?= e(BASE_URL) ?>/admin/moderasi.php?tab=<?= e($tabStatus) ?>" class="btn btn-outline btn-sm">Reset Filter</a>
                </form>
            </div>

            <!-- Daftar Kuis -->
            <?php if (empty($daftarKuis)): ?>
                <div class="card">
                    <h2 class="card-title">Belum Ada Kuis</h2>
                    <p class="form-helper">
                        <?= $tabStatus === 'terbit' ? 'Belum ada kuis terbit yang cocok dengan filter.' : 'Belum ada kuis yang ditarik oleh Admin.' ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="bento-grid">
                    <?php foreach ($daftarKuis as $kuis): ?>
                        <div class="card">
                            <div>
                                <span class="badge <?= $kuis['status'] === 'terbit' ? 'badge-terbit' : 'badge-ditarik' ?>">
                                    <?= $kuis['status'] === 'terbit' ? 'Terbit' : 'Ditarik' ?>
                                </span>
                                <span class="badge badge-draft">
                                    <?= $kuis['akses'] === 'privat' ? 'Privat' : 'Publik' ?>
                                </span>
                            </div>

                            <h2 class="card-title"><?= e($kuis['judul']) ?></h2>
                            <p class="form-helper"><strong>Pengajar:</strong> <?= e($kuis['nama_pengajar']) ?></p>
                            <p class="form-helper"><strong>Mapel:</strong> <?= e($kuis['nama_topik']) ?> (<?= e($kuis['nama_tingkat']) ?>)</p>
                            <p class="form-helper"><strong>Jumlah Soal:</strong> <?= (int)$kuis['jumlah_soal'] ?> soal</p>

                            <?php if ($tabStatus === 'terbit'): ?>
                                <p class="form-helper"><strong>Terbit:</strong> <?= e(formatTanggal($kuis['diterbitkan_pada'])) ?></p>
                                <br>
                                <a href="<?= e(BASE_URL) ?>/admin/tinjau.php?id=<?= (int)$kuis['id'] ?>" class="btn btn-outline btn-sm btn-block">
                                    Lihat Isi Soal
                                </a>
                                <br>
                                <!-- Form Tarik Kuis (Moderasi) dengan alasan wajib -->
                                <form action="" method="POST">
                                    <?= inputCsrf() ?>
                                    <input type="hidden" name="aksi" value="tarik_kuis">
                                    <input type="hidden" name="kuis_id" value="<?= (int)$kuis['id'] ?>">

                                    <div class="form-group">
                                        <label for="alasan_<?= (int)$kuis['id'] ?>" class="form-label">Alasan Penarikan</label>
                                        <textarea id="alasan_<?= (int)$kuis['id'] ?>" name="alasan" class="form-control" rows="2" placeholder="Wajib diisi minimal 10 karakter..." required></textarea>
                                    </div>

                                    <button type="submit" class="btn btn-danger btn-sm btn-block">
                                        Tarik Kuis Ini
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-galat">
                                    <strong>Alasan Ditarik:</strong> <?= e($kuis['alasan_ditarik'] ?: '-') ?>
                                </div>
                                <br>
                                <a href="<?= e(BASE_URL) ?>/admin/tinjau.php?id=<?= (int)$kuis['id'] ?>" class="btn btn-outline btn-sm btn-block">
                                    Lihat Isi Soal
                                </a>
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
