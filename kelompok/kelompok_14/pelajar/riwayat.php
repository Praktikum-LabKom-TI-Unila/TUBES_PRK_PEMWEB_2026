<?php
// pelajar/riwayat.php
// Halaman Riwayat Pengerjaan Kuis Pelajar (PL-07, HSL-03) - DynaQuiz
// Menampilkan daftar percobaan kuis, filter mapel & tanggal, pagination, serta tautan ke Hasil dan Detail Kuis

// 1. Muat konfigurasi, database, autentikasi, helper, dan fungsi
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';
require_once __DIR__ . '/../includes/fungsi/pelajar.php';

// 2. Penjaga Hak Akses (AUT-05): hanya peran 'pelajar' yang boleh masuk
wajibPeran(['pelajar']);

// 3. Tutup percobaan yang sudah melewati batas waktu
tutupPercobaanKedaluwarsa();

// 4. Data Pengguna Aktif
$user = penggunaSaatIni();
$pelajarId = (int) ($user['id'] ?? 0);

// 5. Ambil dan validasi filter dari query string (GET)
$filterTopikId = max(0, (int) ($_GET['topik_id'] ?? 0));

$tanggalMulai = trim($_GET['tanggal_mulai'] ?? '');
if ($tanggalMulai !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalMulai)) {
    $tanggalMulai = '';
}

$tanggalSelesai = trim($_GET['tanggal_selesai'] ?? '');
if ($tanggalSelesai !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSelesai)) {
    $tanggalSelesai = '';
}

// 6. Pengaturan Pagination
$perHalaman = 10;
$halamanAktifNomor = max(1, (int) ($_GET['halaman'] ?? 1));

// 7. Ambil data dari database melalui fungsi pembantu
global $pdo;
$daftarTopik = daftarSemuaTopik($pdo);
$totalData = hitungTotalRiwayatPelajar($pdo, $pelajarId, $filterTopikId, $tanggalMulai, $tanggalSelesai);
$totalHalaman = max(1, (int) ceil($totalData / $perHalaman));

if ($halamanAktifNomor > $totalHalaman) {
    $halamanAktifNomor = $totalHalaman;
}

$offset = ($halamanAktifNomor - 1) * $perHalaman;
$daftarRiwayat = ambilRiwayatPelajar($pdo, $pelajarId, $filterTopikId, $tanggalMulai, $tanggalSelesai, $perHalaman, $offset);

// Siapkan query dasar agar pagination mempertahankan filter aktif
$queryFilter = [];
if ($filterTopikId > 0) {
    $queryFilter['topik_id'] = $filterTopikId;
}
if ($tanggalMulai !== '') {
    $queryFilter['tanggal_mulai'] = $tanggalMulai;
}
if ($tanggalSelesai !== '') {
    $queryFilter['tanggal_selesai'] = $tanggalSelesai;
}

// 8. Konfigurasi halaman dan template
$root = '../';
$judulHalaman = 'Riwayat Pengerjaan - DynaQuiz';
$halamanAktif = 'riwayat';

// Muat header layout Pelajar
require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Judul Halaman -->
<div class="card page-header">
  <span class="badge badge-sedang">Riwayat Belajar</span>
  <h1>Riwayat Pengerjaan Kuis</h1>
  <p>Lihat seluruh hasil kuis yang pernah kamu kerjakan, pantau nilai, dan ulangi materi kapan saja.</p>
</div>

<!-- Kartu Form Filter Mapel & Tanggal -->
<div class="card">
  <form method="GET" action="riwayat.php" class="filter-grid">
    <div class="form-group">
      <label for="filterTopik" class="form-label">Mata Pelajaran</label>
      <select name="topik_id" id="filterTopik" class="form-select">
        <option value="0">Semua Mata Pelajaran</option>
        <?php foreach ($daftarTopik as $topik): ?>
          <option value="<?= (int) $topik['id'] ?>" <?= $filterTopikId === (int) $topik['id'] ? 'selected' : '' ?>>
            <?= e($topik['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="tanggalMulai" class="form-label">Dari Tanggal</label>
      <input type="date" name="tanggal_mulai" id="tanggalMulai" class="form-control" value="<?= e($tanggalMulai) ?>">
    </div>

    <div class="form-group">
      <label for="tanggalSelesai" class="form-label">Sampai Tanggal</label>
      <input type="date" name="tanggal_selesai" id="tanggalSelesai" class="form-control" value="<?= e($tanggalSelesai) ?>">
    </div>

    <div class="aksi-group">
      <button type="submit" class="btn btn-primary">Terapkan Filter</button>
      <?php if ($filterTopikId > 0 || $tanggalMulai !== '' || $tanggalSelesai !== ''): ?>
        <a href="riwayat.php" class="btn btn-batal">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Daftar Riwayat Percobaan Kuis -->
<?php if (!empty($daftarRiwayat)): ?>
  <div class="list-stack">
    <?php foreach ($daftarRiwayat as $item): ?>
      <?php
      // Hitung persentase nilai
      $persenNilai = 0;
      if ((int) $item['skor_maksimal'] > 0) {
          $persenNilai = (int) round(((int) $item['skor'] / (int) $item['skor_maksimal']) * 100);
      }

      // Tentukan label dan warna badge status percobaan
      $labelStatus = 'Selesai';
      $kelasStatus = 'badge-terbit';
      if ($item['status'] === 'waktu_habis') {
          $labelStatus = 'Waktu Habis';
          $kelasStatus = 'badge-ditolak';
      } elseif ($item['status'] === 'berjalan') {
          $labelStatus = 'Sedang Berjalan';
          $kelasStatus = 'badge-menunggu';
      }
      ?>

      <div class="card riwayat-card">
        <div class="riwayat-info">
          <div class="badge-group">
            <span class="badge badge-sedang"><?= e($item['nama_topik']) ?></span>
            <span class="badge badge-draft"><?= e($item['nama_tingkat']) ?></span>
            <span class="badge <?= $kelasStatus ?>"><?= e($labelStatus) ?></span>
          </div>

          <h2 class="riwayat-judul"><?= e($item['judul_kuis']) ?></h2>

          <p class="riwayat-meta">
            <i class="fa-solid fa-calendar-days"></i> <?= e(formatTanggal($item['waktu_mulai'])) ?>
            &bull; Benar: <strong><?= (int) $item['jumlah_benar'] ?></strong>
            &bull; Salah: <strong><?= (int) $item['jumlah_salah'] ?></strong>
          </p>
        </div>

        <div class="riwayat-kanan">
          <?php if ($item['status'] === 'berjalan'): ?>
            <div class="riwayat-skor">Belum Selesai</div>
            <div class="aksi-group">
              <a href="<?= $root ?>kuis/kerjakan.php?id=<?= (int) $item['kuis_id'] ?>" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-play"></i> Lanjutkan
              </a>
            </div>
          <?php else: ?>
            <div class="riwayat-skor">
              <?= $persenNilai ?>% (<?= (int) $item['skor'] ?>/<?= (int) $item['skor_maksimal'] ?> poin)
            </div>
            <div class="aksi-group">
              <a href="<?= $root ?>kuis/hasil.php?id=<?= (int) $item['id'] ?>" class="btn btn-sm btn-secondary">
                Lihat Hasil
              </a>
              <?php if ($item['status_kuis'] === 'terbit'): ?>
                <a href="<?= $root ?>kuis/detail.php?id=<?= (int) $item['kuis_id'] ?>" class="btn btn-sm btn-outline">
                  Ulangi Kuis
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($totalHalaman > 1): ?>
    <nav class="pagination" aria-label="Navigasi Halaman Riwayat">
      <?php if ($halamanAktifNomor > 1): ?>
        <?php $queryPrev = array_merge($queryFilter, ['halaman' => $halamanAktifNomor - 1]); ?>
        <a href="riwayat.php?<?= e(http_build_query($queryPrev)) ?>" class="btn btn-sm btn-outline">
          &laquo; Sebelumnya
        </a>
      <?php endif; ?>

      <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
        <?php $queryHal = array_merge($queryFilter, ['halaman' => $i]); ?>
        <a href="riwayat.php?<?= e(http_build_query($queryHal)) ?>"
           class="btn btn-sm <?= $i === $halamanAktifNomor ? 'btn-primary' : 'btn-outline' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <?php if ($halamanAktifNomor < $totalHalaman): ?>
        <?php $queryNext = array_merge($queryFilter, ['halaman' => $halamanAktifNomor + 1]); ?>
        <a href="riwayat.php?<?= e(http_build_query($queryNext)) ?>" class="btn btn-sm btn-outline">
          Berikutnya &raquo;
        </a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

<?php else: ?>
  <!-- Tampilan Kosong Jika Tidak Ada Riwayat -->
  <div class="card empty-state">
    <div class="empty-state-ikon" aria-hidden="true"><i class="fa-solid fa-clock-rotate-left"></i></div>
    <h2>Belum Ada Riwayat yang Ditemukan</h2>
    <?php if ($filterTopikId > 0 || $tanggalMulai !== '' || $tanggalSelesai !== ''): ?>
      <p>Tidak ada pengerjaan kuis yang cocok dengan filter yang kamu pilih. Coba ubah atau reset filter.</p>
      <a href="riwayat.php" class="btn btn-primary">Reset Filter</a>
    <?php else: ?>
      <p>Kamu belum pernah mengerjakan kuis apa pun. Yuk mulai latihan pertamamu sekarang!</p>
      <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Jelajahi Katalog Kuis &rarr;</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php
// Muat footer layout Pelajar
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
