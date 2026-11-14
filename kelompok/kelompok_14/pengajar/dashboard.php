<?php
// PJ-01: Dashboard Pengajar

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Hanya pengajar yang boleh mengakses halaman ini
wajibPeran(['pengajar']);

// Ambil ID dan nama pengajar yang sedang login dari session
$pengajarId   = (int) $_SESSION['user']['id'];
$namaPengajar = $_SESSION['user']['nama'] ?? 'Pengajar';

// Ambil semua data statistik dashboard dari fungsi ambilStatistikPengajar()
$statistik = ambilStatistikPengajar($pengajarId);

$jumlahStatus     = $statistik['jumlah_status'];
$riwayatKeputusan = $statistik['riwayat_keputusan'];
$totalPercobaan   = $statistik['total_percobaan'];
$rataRataPersen   = $statistik['rata_rata_persen'];

// Hitung total semua kuis milik pengajar ini
$totalKuis = $jumlahStatus['draft']
           + $jumlahStatus['menunggu']
           + $jumlahStatus['terbit']
           + $jumlahStatus['ditolak']
           + $jumlahStatus['ditarik'];


// Bagian Tampilan Halaman (HTML)
$judulHalaman = 'Dashboard Pengajar';
$halamanAktif = 'dashboard';
$root = '../';

// Muat template header panel
require_once __DIR__ . '/../includes/partials/header-panel.php';
?>

<div class="card">
    <h1 class="card-title">Halo, <?= e($namaPengajar) ?>! <i class="fa-solid fa-hand"></i></h1>
    <p class="form-helper">Selamat datang di panel pengajar DynaQuiz. Berikut ringkasan kuis Anda.</p>
    <div class="form-group">
        <a href="kuis-form.php" class="btn btn-primary">+ Buat Kuis Baru</a>
        <a href="kuis.php" class="btn btn-secondary">Kelola Kuis Saya</a>
    </div>
</div>

<!-- Ringkasan Statistik Percobaan Pelajar -->
<div class="bento-grid">
    <div class="card">
        <span class="badge badge-terbit">Kuis</span>
        <h2 class="card-title">Total Kuis Dibuat</h2>
        <p><strong><?= e($totalKuis) ?></strong> kuis terdaftar</p>
    </div>
    <div class="card">
        <span class="badge badge-menunggu">Pelajar</span>
        <h2 class="card-title">Total Percobaan Pelajar</h2>
        <p><strong><?= e($totalPercobaan) ?></strong> kali dikerjakan</p>
    </div>
    <div class="card">
        <span class="badge badge-draft">Hasil</span>
        <h2 class="card-title">Rata-rata Skor Pelajar</h2>
        <p><strong><?= e($rataRataPersen) ?>%</strong> rata-rata nilai</p>
    </div>
</div>

<!-- Ringkasan Jumlah Kuis per Status -->
<div class="card">
    <h2 class="card-title">Status Kuis Anda</h2>
    <div class="bento-grid">
        <div class="card card-sm">
            <span class="badge badge-draft">Draft</span>
            <p><strong><?= e($jumlahStatus['draft']) ?></strong> kuis</p>
            <a href="kuis.php?status=draft" class="btn btn-outline btn-sm">Lihat Draft</a>
        </div>
        <div class="card card-sm">
            <span class="badge badge-menunggu">Menunggu</span>
            <p><strong><?= e($jumlahStatus['menunggu']) ?></strong> kuis</p>
            <a href="kuis.php?status=menunggu" class="btn btn-outline btn-sm">Lihat Menunggu</a>
        </div>
        <div class="card card-sm">
            <span class="badge badge-terbit">Terbit</span>
            <p><strong><?= e($jumlahStatus['terbit']) ?></strong> kuis</p>
            <a href="kuis.php?status=terbit" class="btn btn-outline btn-sm">Lihat Terbit</a>
        </div>
        <div class="card card-sm">
            <span class="badge badge-ditolak">Ditolak</span>
            <p><strong><?= e($jumlahStatus['ditolak']) ?></strong> kuis</p>
            <a href="kuis.php?status=ditolak" class="btn btn-outline btn-sm">Lihat Ditolak</a>
        </div>
        <div class="card card-sm">
            <span class="badge badge-ditarik">Ditarik</span>
            <p><strong><?= e($jumlahStatus['ditarik']) ?></strong> kuis</p>
            <a href="kuis.php?status=ditarik" class="btn btn-outline btn-sm">Lihat Ditarik</a>
        </div>
    </div>
</div>

<!-- Daftar Hasil Persetujuan & Moderasi Terbaru dari Admin -->
<div class="card">
    <h2 class="card-title">Hasil Persetujuan &amp; Moderasi Terbaru</h2>

    <?php if (empty($riwayatKeputusan)): ?>
        <p class="form-helper">
            Belum ada keputusan persetujuan atau moderasi dari admin untuk kuis Anda.
        </p>
    <?php else: ?>
        <div class="form-group">
            <?php foreach ($riwayatKeputusan as $log): ?>
                <?php
                $badgeKelas = 'badge-draft';
                $labelKeputusan = ucfirst($log['status_ke']);
                if ($log['status_ke'] === 'terbit') {
                    $badgeKelas = 'badge-terbit';
                    $labelKeputusan = 'Disetujui (Terbit)';
                } elseif ($log['status_ke'] === 'ditolak') {
                    $badgeKelas = 'badge-ditolak';
                    $labelKeputusan = 'Ditolak';
                } elseif ($log['status_ke'] === 'ditarik') {
                    $badgeKelas = 'badge-ditarik';
                    $labelKeputusan = 'Ditarik Admin';
                }
                ?>
                <div class="card card-sm">
                    <span class="badge <?= e($badgeKelas) ?>">
                        <?= e($labelKeputusan) ?>
                    </span>
                    <h3 class="card-title"><?= e($log['judul_kuis']) ?></h3>
                    <p class="form-helper">Tanggal: <?= e(formatTanggal($log['dibuat_pada'])) ?></p>

                    <?php if (!empty($log['catatan'])): ?>
                        <div class="alert <?= $log['status_ke'] === 'terbit' ? 'alert-sukses' : 'alert-galat' ?>">
                            <strong>Catatan Admin:</strong> <?= e($log['catatan']) ?>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <?php if ($log['status_ke'] === 'terbit'): ?>
                            <a href="hasil.php?kuis_id=<?= e($log['kuis_id']) ?>" class="btn btn-primary btn-sm">Lihat Hasil Kuis &rarr;</a>
                        <?php else: ?>
                            <a href="kuis.php?status=<?= e($log['status_ke']) ?>" class="btn btn-secondary btn-sm">Buka di Kuis Saya untuk Revisi &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Muat template footer panel
require_once __DIR__ . '/../includes/partials/footer-panel.php';