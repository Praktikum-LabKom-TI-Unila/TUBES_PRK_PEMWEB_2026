<?php
// pelajar/peringkat.php
// Halaman Papan Peringkat / Leaderboard Pelajar (PL-08, HSL-04, KT-03, BR-15) - DynaQuiz
// Menampilkan peringkat pelajar berdasarkan total poin dari skor terbaik tiap kuis, filter mapel, dan sorotan posisi pengguna

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

// 5. Ambil filter mapel (topik) dari query string
$filterTopikId = max(0, (int) ($_GET['topik_id'] ?? 0));

// 6. Ambil daftar mapel dan data papan peringkat dari database
global $pdo;
$daftarTopik = daftarSemuaTopik($pdo);
$daftarPeringkat = ambilPeringkatPelajar($pdo, $filterTopikId);

// Cari posisi pengguna saat ini di papan peringkat
$posisiSaya = null;
foreach ($daftarPeringkat as $baris) {
    if ($baris['pelajar_id'] === $pelajarId) {
        $posisiSaya = $baris;
        break;
    }
}

// Ambil Top 3 untuk tampilan Podium
$topTiga = array_slice($daftarPeringkat, 0, 3);

// Cari nama mapel yang sedang difilter untuk keterangan
$namaTopikAktif = 'Semua Mata Pelajaran';
if ($filterTopikId > 0) {
    foreach ($daftarTopik as $t) {
        if ((int) $t['id'] === $filterTopikId) {
            $namaTopikAktif = $t['nama'];
            break;
        }
    }
}

// 7. Konfigurasi halaman dan template
$root = '../';
$judulHalaman = 'Papan Peringkat - DynaQuiz';
$halamanAktif = 'peringkat';

// Muat header layout Pelajar
require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Header Halaman -->
<div class="card page-header">
  <span class="badge badge-sedang">Leaderboard Pelajar</span>
  <h1>Papan Peringkat</h1>
  <p>Peringkat dihitung dari <strong>total poin skor terbaik</strong> pada setiap kuis yang telah diselesaikan (imbang ditentukan oleh waktu tercepat).</p>
</div>

<!-- Kartu Filter Mata Pelajaran -->
<div class="card">
  <form method="GET" action="peringkat.php" class="filter-peringkat">
    <div class="form-group">
      <label for="filterTopik" class="form-label">Filter Mata Pelajaran</label>
      <select name="topik_id" id="filterTopik" class="form-select">
        <option value="0">Semua Mata Pelajaran (Keseluruhan)</option>
        <?php foreach ($daftarTopik as $topik): ?>
          <option value="<?= (int) $topik['id'] ?>" <?= $filterTopikId === (int) $topik['id'] ? 'selected' : '' ?>>
            <?= e($topik['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="aksi-group">
      <button type="submit" class="btn btn-primary">Tampilkan Peringkat</button>
      <?php if ($filterTopikId > 0): ?>
        <a href="peringkat.php" class="btn btn-batal">Reset</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Kartu Ringkasan Posisi Pengguna Saat Ini -->
<div class="card posisi-saya-card">
  <?php if ($posisiSaya !== null): ?>
    <div>
      <div class="badge-group">
        <span class="badge badge-terbit">Posisi Kamu</span>
        <span class="badge badge-sedang"><?= e($namaTopikAktif) ?></span>
      </div>
      <h2><?= e($posisiSaya['nama']) ?></h2>
      <p class="riwayat-meta">
        Menyelesaikan <strong><?= $posisiSaya['jumlah_kuis'] ?></strong> kuis &bull;
        Total Waktu: <strong><?= e(formatDurasiSingkat($posisiSaya['total_waktu_detik'])) ?></strong>
      </p>
    </div>
    <div class="riwayat-kanan">
      <div class="posisi-angka">#<?= $posisiSaya['peringkat'] ?></div>
      <div class="riwayat-skor"><?= number_format($posisiSaya['total_poin']) ?> Poin</div>
    </div>
  <?php else: ?>
    <div>
      <div class="badge-group">
        <span class="badge badge-draft">Belum Ada Peringkat</span>
        <span class="badge badge-sedang"><?= e($namaTopikAktif) ?></span>
      </div>
      <h2>Kamu Belum Masuk Peringkat</h2>
      <p class="riwayat-meta">
        Selesaikan minimal satu kuis pada kategori <strong><?= e($namaTopikAktif) ?></strong> untuk mencatatkan skormu di leaderboard.
      </p>
    </div>
    <div>
      <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Mulai Kerjakan Kuis &rarr;</a>
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($daftarPeringkat)): ?>
  <!-- Podium Juara 1, 2, 3 -->
  <div class="podium-grid">
    <?php
    $ikonMedali = [
        1 => '<i class="fa-solid fa-medal" style="color: #eab308;"></i>',
        2 => '<i class="fa-solid fa-medal" style="color: #94a3b8;"></i>',
        3 => '<i class="fa-solid fa-medal" style="color: #d97706;"></i>'
    ];
    $labelJuara = [1 => 'Peringkat 1', 2 => 'Peringkat 2', 3 => 'Peringkat 3'];
    ?>
    <?php foreach ($topTiga as $juara): ?>
      <?php
      $no = $juara['peringkat'];
      $kelasTambahan = ($no === 1) ? 'podium-juara-1' : '';
      $adalahSaya = ($juara['pelajar_id'] === $pelajarId);
      ?>
      <div class="card podium-card <?= $kelasTambahan ?>">
        <div class="podium-medali" aria-hidden="true"><?= $ikonMedali[$no] ?? '<i class="fa-solid fa-trophy"></i>' ?></div>
        <span class="badge <?= $no === 1 ? 'badge-menunggu' : 'badge-sedang' ?>">
          <?= e($labelJuara[$no] ?? ('Peringkat ' . $no)) ?>
        </span>
        <h3 class="podium-nama">
          <?= e($juara['nama']) ?>
          <?php if ($adalahSaya): ?>
            <span class="badge badge-terbit">Kamu</span>
          <?php endif; ?>
        </h3>
        <div class="podium-poin"><?= number_format($juara['total_poin']) ?> Poin</div>
        <p class="podium-meta">
          <?= $juara['jumlah_kuis'] ?> Kuis &bull; <?= e(formatDurasiSingkat($juara['total_waktu_detik'])) ?>
        </p>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Tabel Lengkap Papan Peringkat -->
  <div class="card">
    <h2 class="card-title">Daftar Peringkat Lengkap &mdash; <?= e($namaTopikAktif) ?></h2>
    <div class="table-responsive">
      <table class="tabel-clay">
        <thead>
          <tr>
            <th>Peringkat</th>
            <th>Nama Pelajar</th>
            <th>Kuis Selesai</th>
            <th>Total Waktu</th>
            <th class="teks-kanan">Total Poin</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($daftarPeringkat as $baris): ?>
            <?php $adalahSaya = ($baris['pelajar_id'] === $pelajarId); ?>
            <tr class="<?= $adalahSaya ? 'baris-saya' : '' ?>">
              <td>#<?= $baris['peringkat'] ?></td>
              <td>
                <?= e($baris['nama']) ?>
                <?php if ($adalahSaya): ?>
                  <span class="badge badge-terbit">Kamu</span>
                <?php endif; ?>
              </td>
              <td><?= $baris['jumlah_kuis'] ?> Kuis</td>
              <td><?= e(formatDurasiSingkat($baris['total_waktu_detik'])) ?></td>
              <td class="teks-kanan"><?= number_format($baris['total_poin']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <!-- Tampilan Kosong Jika Belum Ada Data Peringkat -->
  <div class="card empty-state">
    <div class="empty-state-ikon" aria-hidden="true"><i class="fa-solid fa-trophy"></i></div>
    <h2>Papan Peringkat Masih Kosong</h2>
    <p>Belum ada pelajar yang menyelesaikan kuis pada kategori <strong><?= e($namaTopikAktif) ?></strong>. Jadilah yang pertama meraih posisi #1!</p>
    <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Cari Kuis Sekarang &rarr;</a>
  </div>
<?php endif; ?>

<?php
// Muat footer layout Pelajar
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
