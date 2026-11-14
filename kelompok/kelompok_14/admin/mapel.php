<?php
// Halaman Kelola Mapel (Topik) dan Tingkat Jenjang Admin (AD-05, ADM-06, BR-18)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';
require_once __DIR__ . '/../includes/fungsi/master.php';

// Hanya Admin yang boleh membuka halaman ini
wajibPeran(['admin']);

// Proses aksi CRUD POST (tambah, ubah, hapus topik atau tingkat)
if (isPost()) {
    verifikasiCsrf();
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_topik') {
        $hasil = tambahTopik($_POST['nama'] ?? '', $_POST['deskripsi'] ?? '');
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    } elseif ($aksi === 'ubah_topik') {
        $hasil = ubahTopik((int)($_POST['id'] ?? 0), $_POST['nama'] ?? '', $_POST['deskripsi'] ?? '');
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    } elseif ($aksi === 'hapus_topik') {
        $hasil = hapusTopik((int)($_POST['id'] ?? 0));
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    } elseif ($aksi === 'tambah_tingkat') {
        $hasil = tambahTingkat($_POST['nama'] ?? '', $_POST['urutan'] ?? 1);
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    } elseif ($aksi === 'ubah_tingkat') {
        $hasil = ubahTingkat((int)($_POST['id'] ?? 0), $_POST['nama'] ?? '', $_POST['urutan'] ?? 1);
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    } elseif ($aksi === 'hapus_tingkat') {
        $hasil = hapusTingkat((int)($_POST['id'] ?? 0));
        flash($hasil['berhasil'] ? 'sukses' : 'galat', $hasil['pesan']);
    }

    redirect(BASE_URL . '/admin/mapel.php');
}

$pesanFlash = flash();
$daftarTopik = daftarTopikDenganJumlahKuis();
$daftarTingkat = daftarTingkatDenganJumlahKuis();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mapel &amp; Tingkat - <?= e(NAMA_APLIKASI) ?></title>
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
                <h1>Kelola Mapel &amp; Tingkat</h1>
                <p class="form-helper">Atur daftar mata pelajaran (topik) dan jenjang tingkat pendidikan untuk pengelompokan kuis.</p>
            </div>

            <?php if ($pesanFlash): ?>
                <div class="alert alert-<?= $pesanFlash['tipe'] === 'sukses' ? 'sukses' : 'galat' ?>" role="alert">
                    <?= e($pesanFlash['pesan']) ?>
                </div>
            <?php endif; ?>

            <div class="bento-grid">
                <!-- BAGIAN 1: TAMBAH MAPEL -->
                <div class="card">
                    <h2 class="card-title">Tambah Mapel Baru</h2>
                    <form action="" method="POST">
                        <?= inputCsrf() ?>
                        <input type="hidden" name="aksi" value="tambah_topik">

                        <div class="form-group">
                            <label for="nama_topik" class="form-label">Nama Mapel</label>
                            <input type="text" id="nama_topik" name="nama" class="form-control" placeholder="Contoh: Matematika" required>
                        </div>

                        <div class="form-group">
                            <label for="deskripsi_topik" class="form-label">Deskripsi Singkat (Opsional)</label>
                            <input type="text" id="deskripsi_topik" name="deskripsi" class="form-control" placeholder="Keterangan singkat mapel">
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">Tambah Mapel</button>
                    </form>
                </div>

                <!-- BAGIAN 2: TAMBAH TINGKAT -->
                <div class="card">
                    <h2 class="card-title">Tambah Tingkat Jenjang</h2>
                    <form action="" method="POST">
                        <?= inputCsrf() ?>
                        <input type="hidden" name="aksi" value="tambah_tingkat">

                        <div class="form-group">
                            <label for="nama_tingkat" class="form-label">Nama Tingkat</label>
                            <input type="text" id="nama_tingkat" name="nama" class="form-control" placeholder="Contoh: SMA" required>
                        </div>

                        <div class="form-group">
                            <label for="urutan_tingkat" class="form-label">Urutan Tampil</label>
                            <input type="number" id="urutan_tingkat" name="urutan" class="form-control" value="1" min="1" required>
                            <p class="form-helper">Angka kecil tampil paling awal (misal: SD=1, SMP=2, SMA=3).</p>
                        </div>

                        <button type="submit" class="btn btn-secondary btn-block">Tambah Tingkat</button>
                    </form>
                </div>
            </div>

            <!-- DAFTAR MAPEL -->
            <h2 class="card-title">Daftar Mata Pelajaran (<?= count($daftarTopik) ?>)</h2>
            <?php if (empty($daftarTopik)): ?>
                <div class="card">
                    <p class="form-helper">Belum ada data mapel.</p>
                </div>
            <?php else: ?>
                <div class="bento-grid">
                    <?php foreach ($daftarTopik as $t): ?>
                        <div class="card">
                            <span class="badge badge-draft"><?= (int)$t['jumlah_kuis'] ?> Kuis</span>
                            <h3 class="card-title"><?= e($t['nama']) ?></h3>
                            <p class="form-helper"><?= e($t['deskripsi'] ?: 'Tanpa deskripsi') ?></p>
                            <br>

                            <form action="" method="POST" class="form-group">
                                <?= inputCsrf() ?>
                                <input type="hidden" name="aksi" value="ubah_topik">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">

                                <div class="form-group">
                                    <label class="form-label">Ubah Nama</label>
                                    <input type="text" name="nama" class="form-control" value="<?= e($t['nama']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Ubah Deskripsi</label>
                                    <input type="text" name="deskripsi" class="form-control" value="<?= e($t['deskripsi'] ?? '') ?>">
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm btn-block">Simpan Perubahan</button>
                            </form>

                            <form action="" method="POST">
                                <?= inputCsrf() ?>
                                <input type="hidden" name="aksi" value="hapus_topik">
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-block">Hapus Mapel</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <br>

            <!-- DAFTAR TINGKAT -->
            <h2 class="card-title">Daftar Tingkat Pendidikan (<?= count($daftarTingkat) ?>)</h2>
            <?php if (empty($daftarTingkat)): ?>
                <div class="card">
                    <p class="form-helper">Belum ada data tingkat.</p>
                </div>
            <?php else: ?>
                <div class="bento-grid">
                    <?php foreach ($daftarTingkat as $tg): ?>
                        <div class="card">
                            <span class="badge badge-menunggu">Urutan #<?= (int)$tg['urutan'] ?></span>
                            <span class="badge badge-draft"><?= (int)$tg['jumlah_kuis'] ?> Kuis</span>
                            <h3 class="card-title"><?= e($tg['nama']) ?></h3>
                            <br>

                            <form action="" method="POST" class="form-group">
                                <?= inputCsrf() ?>
                                <input type="hidden" name="aksi" value="ubah_tingkat">
                                <input type="hidden" name="id" value="<?= (int)$tg['id'] ?>">

                                <div class="form-group">
                                    <label class="form-label">Ubah Nama</label>
                                    <input type="text" name="nama" class="form-control" value="<?= e($tg['nama']) ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Ubah Urutan</label>
                                    <input type="number" name="urutan" class="form-control" value="<?= (int)$tg['urutan'] ?>" min="1" required>
                                </div>

                                <button type="submit" class="btn btn-secondary btn-sm btn-block">Simpan Perubahan</button>
                            </form>

                            <form action="" method="POST">
                                <?= inputCsrf() ?>
                                <input type="hidden" name="aksi" value="hapus_tingkat">
                                <input type="hidden" name="id" value="<?= (int)$tg['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm btn-block">Hapus Tingkat</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?= e(BASE_URL) ?>/assets/js/tema.js"></script>
</body>
</html>
