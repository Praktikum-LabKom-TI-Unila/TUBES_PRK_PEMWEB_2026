<?php
// PJ-02: Kuis Saya (Milik Pengajar)

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Proteksi halaman: hanya pengajar yang boleh masuk (AUT-05)
wajibPeran(['pengajar']);

// Ambil ID pengajar yang sedang login
$pengajarId = (int) $_SESSION['user']['id'];

// Ambil parameter filter status dan kata kunci pencarian dari URL (GET)
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
$kataKunci    = isset($_GET['cari']) ? trim($_GET['cari']) : '';

// Validasi status filter agar sesuai dengan enum status kuis
$daftarStatusValid = ['draft', 'menunggu', 'terbit', 'ditolak', 'ditarik'];
if ($statusFilter !== '' && !in_array($statusFilter, $daftarStatusValid, true)) {
    $statusFilter = '';
}

// Ambil daftar kuis milik pengajar menggunakan fungsi yang sudah dibuat
$daftarKuis = daftarKuisPengajar($pengajarId, $statusFilter, $kataKunci);

// Pesan notifikasi error/sukses
$pesanError = '';

// Pemrosesan aksi saat ada form POST yang dikirim (Tarik ke Draft, Buka Revisi, Hapus)
if (isPost()) {
    verifikasiCsrf();

    $aksi   = isset($_POST['aksi']) ? trim($_POST['aksi']) : '';
    $kuisId = isset($_POST['kuis_id']) ? (int) $_POST['kuis_id'] : 0;

    // Pastikan kuis yang dipilih ada dan benar milik pengajar yang sedang login
    $kuis = ambilKuisById($kuisId, $pengajarId);

    if (!$kuis) {
        $pesanError = 'Kuis tidak ditemukan atau Anda tidak memiliki akses.';
    } else {
        $hasil = null;
        $pesanSukses = '';

        // Aksi 1: Tarik kuis yang sudah terbit kembali ke draft untuk diedit (PJR-06)
        if ($aksi === 'tarik') {
            $catatan = isset($_POST['catatan']) ? trim($_POST['catatan']) : null;
            $hasil = tarikKuis($kuisId, $pengajarId, $catatan);
            $pesanSukses = 'Kuis berhasil ditarik kembali ke Draft.';
        }
        // Aksi 2: Pindahkan kuis ditolak / ditarik ke draft agar bisa diedit (PJR-07 / BR-04)
        elseif ($aksi === 'revisi') {
            $hasil = bukaRevisiKuis($kuisId, $pengajarId);
            $pesanSukses = 'Kuis berhasil dipindahkan ke Draft untuk direvisi.';
        }
        // Aksi 3: Hapus kuis draft yang belum pernah dikerjakan (PJR-08 / BR-06)
        elseif ($aksi === 'hapus') {
            $hasil = hapusKuis($kuisId, $pengajarId);
            $pesanSukses = 'Draft kuis berhasil dihapus.';
        }

        // Cek hasil proses aksi
        if ($hasil !== null) {
            if ($hasil['berhasil'] ?? false) {
                flash('sukses', $pesanSukses);
                redirect('kuis.php' . ($statusFilter !== '' ? '?status=' . urlencode($statusFilter) : ''));
            } else {
                $pesanError = $hasil['pesan'] ?? 'Gagal memproses aksi pada kuis.';
            }
        }
    }
}

// Bagian Tampilan Halaman (HTML)
$judulHalaman = 'Kuis Saya';
$halamanAktif = 'kuis';
$root = '../';

// Token CSRF untuk form aksi
$tokenCsrf = ambilTokenCsrf();

// Muat template header panel pengajar
require_once __DIR__ . '/../includes/partials/header-panel.php';
?>

<link rel="stylesheet" href="<?= e($root) ?>assets/css/kuis-pengajar.css">

<div class="kuis-panel-header">
    <div>
        <h1 class="kuis-panel-judul">Kuis Saya</h1>
        <p class="form-helper">Kelola daftar kuis latihan soal yang telah Anda buat.</p>
    </div>
    <div>
        <a href="kuis-form.php" class="btn btn-primary">+ Buat Kuis Baru</a>
    </div>
</div>

<?php if ($pesanError !== ''): ?>
    <div class="alert alert-galat" role="alert">
        <?= e($pesanError) ?>
    </div>
<?php endif; ?>

<!-- Baris Filter Status dan Pencarian -->
<div class="card card-sm kuis-filter-card">
    <div class="kuis-filter-bar">

        <!-- Filter Status Kuis (Tab / Tombol Filter) -->
        <div class="kuis-filter-tabs">
            <?php
            $daftarTabStatus = [
                ''         => 'Semua',
                'draft'    => 'Draft',
                'menunggu' => 'Menunggu',
                'terbit'   => 'Terbit',
                'ditolak'  => 'Ditolak',
                'ditarik'  => 'Ditarik',
            ];
            foreach ($daftarTabStatus as $nilaiTab => $labelTab):
                $queryTab = [];
                if ($nilaiTab !== '') {
                    $queryTab['status'] = $nilaiTab;
                }
                if ($kataKunci !== '') {
                    $queryTab['cari'] = $kataKunci;
                }
                $urlTab = 'kuis.php' . (!empty($queryTab) ? '?' . http_build_query($queryTab) : '');
                $kelasTab = ($statusFilter === $nilaiTab) ? 'kuis-filter-tab-aktif' : 'badge-draft';
            ?>
                <a href="<?= e($urlTab) ?>" class="badge kuis-filter-tab <?= e($kelasTab) ?>">
                    <?= e($labelTab) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- Form Pencarian Judul Kuis -->
        <form method="get" action="kuis.php" class="kuis-cari-form">
            <?php if ($statusFilter !== ''): ?>
                <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
            <?php endif; ?>
            <input type="text" name="cari" class="form-input kuis-cari-input" placeholder="Cari judul kuis..."
                   value="<?= e($kataKunci) ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Cari</button>
            <?php if ($kataKunci !== ''): ?>
                <a href="kuis.php<?= $statusFilter !== '' ? '?status=' . urlencode($statusFilter) : '' ?>"
                   class="btn btn-batal btn-sm">Reset</a>
            <?php endif; ?>
        </form>

    </div>
</div>

<!-- Daftar Kartu Kuis Pengajar -->
<?php if (empty($daftarKuis)): ?>
    <div class="card kuis-kosong">
        <p class="kuis-kosong-teks">
            Belum ada kuis yang ditemukan<?= $statusFilter !== '' ? ' untuk status "' . e($statusFilter) . '"' : '' ?><?= $kataKunci !== '' ? ' dengan kata kunci "' . e($kataKunci) . '"' : '' ?>.
        </p>
        <a href="kuis-form.php" class="btn btn-primary">+ Buat Kuis Sekarang</a>
    </div>
<?php else: ?>
    <div class="kuis-grid">
        <?php foreach ($daftarKuis as $k): ?>
            <?php
            // Penentuan warna badge status sesuai komponen.css
            $kelasBadge = 'badge-draft';
            $labelStatus = ucfirst($k['status']);
            if ($k['status'] === 'terbit') {
                $kelasBadge = 'badge-terbit';
            } elseif ($k['status'] === 'ditolak') {
                $kelasBadge = 'badge-ditolak';
            } elseif ($k['status'] === 'menunggu') {
                $kelasBadge = 'badge-menunggu';
                $labelStatus = 'Menunggu Persetujuan';
            } elseif ($k['status'] === 'ditarik') {
                $kelasBadge = 'badge-ditarik';
                $labelStatus = 'Ditarik Admin';
            }
            ?>
            <div class="card kuis-kartu">
                <div>
                    <!-- Header Kartu: Status dan Jenis Akses -->
                    <div class="kuis-kartu-header">
                        <span class="badge <?= e($kelasBadge) ?>">
                            <?= e($labelStatus) ?>
                        </span>
                        <span class="badge <?= $k['akses'] === 'privat' ? 'badge-menunggu' : 'badge-draft' ?>">
                            <?= e(ucfirst($k['akses'])) ?>
                        </span>
                    </div>

                    <!-- Judul Kuis -->
                    <h3 class="kuis-kartu-judul">
                        <?= e($k['judul']) ?>
                    </h3>

                    <!-- Informasi Topik, Tingkat, Soal, Durasi -->
                    <p class="form-helper kuis-kartu-meta">
                        <span><?= e($k['nama_topik']) ?></span> &bull;
                        <span><?= e($k['nama_tingkat']) ?></span>
                    </p>

                    <div class="kuis-kartu-info">
                        <div><strong><?= e($k['jumlah_soal']) ?></strong> Soal</div>
                        <div><strong><?= e($k['durasi_menit']) ?></strong> Menit</div>
                        <?php if ($k['akses'] === 'privat' && !empty($k['kode'])): ?>
                            <div>Kode: <strong><?= e($k['kode']) ?></strong></div>
                        <?php endif; ?>
                    </div>

                    <!-- Tampilkan Catatan Alasan jika Ditolak atau Ditarik -->
                    <?php if (($k['status'] === 'ditolak' || $k['status'] === 'ditarik') && !empty($k['catatan_status_terakhir'])): ?>
                        <div class="alert alert-galat kuis-kartu-catatan">
                            <strong>Catatan Admin:</strong><br>
                            <?= e($k['catatan_status_terakhir']) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tombol Aksi Sesuai Status Kuis -->
                <div class="kuis-kartu-aksi">

                    <?php if ($k['status'] === 'draft'): ?>
                        <!-- Draft: Edit, Kelola Soal, Pratinjau, Hapus -->
                        <a href="kuis-form.php?id=<?= e($k['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                        <a href="kelola-soal.php?kuis_id=<?= e($k['id']) ?>" class="btn btn-primary btn-sm">Kelola Soal</a>
                        <a href="pratinjau.php?id=<?= e($k['id']) ?>" class="btn btn-batal btn-sm">Pratinjau</a>
                        <?php if ((int) $k['jumlah_percobaan'] === 0): ?>
                            <form method="post" action="kuis.php" class="kuis-form-inline form-konfirmasi"
                                  data-konfirmasi="Yakin ingin menghapus draft kuis ini?">
                                <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
                                <input type="hidden" name="kuis_id" value="<?= e($k['id']) ?>">
                                <input type="hidden" name="aksi" value="hapus">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        <?php endif; ?>

                    <?php elseif ($k['status'] === 'menunggu'): ?>
                        <!-- Menunggu Persetujuan: Hanya Pratinjau (kunci) -->
                        <a href="pratinjau.php?id=<?= e($k['id']) ?>" class="btn btn-batal btn-sm">Pratinjau</a>
                        <span class="form-helper">Sedang ditinjau admin</span>

                    <?php elseif ($k['status'] === 'terbit'): ?>
                        <!-- Terbit: Lihat Hasil, Pratinjau, Tarik ke Draft -->
                        <a href="hasil.php?kuis_id=<?= e($k['id']) ?>" class="btn btn-primary btn-sm">Lihat Hasil</a>
                        <a href="pratinjau.php?id=<?= e($k['id']) ?>" class="btn btn-batal btn-sm">Pratinjau</a>
                        <form method="post" action="kuis.php" class="kuis-form-inline form-konfirmasi"
                              data-konfirmasi="Tarik kuis ini kembali ke Draft untuk diedit? Pelajar tidak akan bisa mengerjakan kuis ini selama ditarik.">
                            <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
                            <input type="hidden" name="kuis_id" value="<?= e($k['id']) ?>">
                            <input type="hidden" name="aksi" value="tarik">
                            <button type="submit" class="btn btn-accent btn-sm">Tarik ke Draft</button>
                        </form>

                    <?php elseif ($k['status'] === 'ditolak' || $k['status'] === 'ditarik'): ?>
                        <!-- Ditolak / Ditarik: Pindahkan ke Draft untuk Revisi -->
                        <form method="post" action="kuis.php" class="kuis-form-inline">
                            <input type="hidden" name="csrf_token" value="<?= e($tokenCsrf) ?>">
                            <input type="hidden" name="kuis_id" value="<?= e($k['id']) ?>">
                            <input type="hidden" name="aksi" value="revisi">
                            <button type="submit" class="btn btn-primary btn-sm">Revisi Kuis</button>
                        </form>
                        <a href="pratinjau.php?id=<?= e($k['id']) ?>" class="btn btn-batal btn-sm">Pratinjau</a>

                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="<?= e($root) ?>assets/js/kuis-pengajar.js"></script>

<?php
// Muat template footer panel
require_once __DIR__ . '/../includes/partials/footer-panel.php';