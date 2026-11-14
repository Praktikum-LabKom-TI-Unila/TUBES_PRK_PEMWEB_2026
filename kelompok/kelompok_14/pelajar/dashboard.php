<?php
// pelajar/dashboard.php
// Halaman Dashboard Pelajar Lengkap (PL-01, HSL-05, HSL-06, KT-06) - DynaQuiz
// Menampilkan sapaan, sorotan kuis berjalan/terakhir, statistik, streak WIB, rekomendasi kuis, dan cuplikan peringkat

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

// 5. Ambil data dashboard dari fungsi pelajar
global $pdo;
$statistik = ambilStatistikDashboardPelajar($pdo, $pelajarId);
$sorotanKuis = ambilKuisBerjalanDanTerakhir($pdo, $pelajarId);
$rekomendasiKuis = ambilRekomendasiKuisPelajar($pdo, $pelajarId, 4);
$semuaPeringkat = ambilPeringkatPelajar($pdo, 0);

// Ambil 5 teratas dan cek posisi pelajar saat ini
$topLima = array_slice($semuaPeringkat, 0, 5);
$posisiSaya = null;
foreach ($semuaPeringkat as $baris) {
    if ($baris['pelajar_id'] === $pelajarId) {
        $posisiSaya = $baris;
        break;
    }
}
$masukTopLima = ($posisiSaya !== null && $posisiSaya['peringkat'] <= 5);

// 6. Variabel Halaman & Muat Header Pelajar
$root = '../';
$judulHalaman = 'Dashboard Pelajar - DynaQuiz';
$halamanAktif = 'beranda';
require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- 1. Sambutan Pengguna -->
<section class="dashboard-sambutan" aria-label="Sambutan Dashboard">
  <h1>Halo, <?= e($user['nama'] ?? 'Pelajar') ?>!</h1>
  <p>Selamat datang kembali di DynaQuiz. Pantau progres belajar, streak harian, dan rekomendasi kuis untukmu.</p>
</section>

<!-- 2. Sorotan Kuis Berjalan / Terakhir (HSL-05) -->
<?php if (!empty($sorotanKuis['berjalan'])): ?>
  <?php $kuisBerjalan = $sorotanKuis['berjalan']; ?>
  <section class="card kuis-sorotan-card" aria-label="Kuis Sedang Berlangsung">
    <div class="kuis-sorotan-header">
      <div>
        <span class="badge badge-sedang">Sedang Berlangsung</span>
        <h2 class="kuis-judul"><?= e($kuisBerjalan['judul']) ?></h2>
        <p class="kuis-meta">
          <span><?= e($kuisBerjalan['nama_topik']) ?></span>
          <span>&bull;</span>
          <span><?= e($kuisBerjalan['nama_tingkat']) ?></span>
          <span>&bull;</span>
          <span>Sisa waktu: <strong><?= e(formatDurasiSingkat($kuisBerjalan['sisa_detik'])) ?></strong></span>
        </p>
      </div>
      <div class="kuis-sorotan-aksi">
        <a href="<?= $root ?>kuis/kerjakan.php?id=<?= (int) $kuisBerjalan['kuis_id'] ?>" class="btn btn-primary">
          Lanjutkan Kuis
        </a>
      </div>
    </div>
  </section>
<?php elseif (!empty($sorotanKuis['terakhir'])): ?>
  <?php
  $kuisTerakhir = $sorotanKuis['terakhir'];
  $skor = (float) ($kuisTerakhir['skor'] ?? 0);
  $skorMaks = (float) ($kuisTerakhir['skor_maksimal'] ?? 0);
  $persenTerakhir = $skorMaks > 0 ? round(($skor / $skorMaks) * 100) : 0;
  $labelStatus = $kuisTerakhir['status'] === 'waktu_habis' ? 'Waktu Habis' : 'Selesai';
  $kelasStatus = $kuisTerakhir['status'] === 'waktu_habis' ? 'badge-sedang' : 'badge-mudah';
  $waktuTampil = $kuisTerakhir['waktu_selesai'] ?: $kuisTerakhir['waktu_mulai'];
  ?>
  <section class="card kuis-sorotan-card" aria-label="Kuis Terakhir Dikerjakan">
    <div class="kuis-sorotan-header">
      <div>
        <span class="badge <?= $kelasStatus ?>">Kuis Terakhir &bull; <?= e($labelStatus) ?></span>
        <h2 class="kuis-judul"><?= e($kuisTerakhir['judul']) ?></h2>
        <p class="kuis-meta">
          <span><?= e($kuisTerakhir['nama_topik']) ?></span>
          <span>&bull;</span>
          <span><?= e($kuisTerakhir['nama_tingkat']) ?></span>
          <span>&bull;</span>
          <span>Skor: <strong><?= (int) round($skor) ?> / <?= (int) round($skorMaks) ?> (<?= (int) $persenTerakhir ?>%)</strong></span>
          <span>&bull;</span>
          <span><?= e(formatTanggal($waktuTampil)) ?></span>
        </p>
      </div>
      <div class="kuis-sorotan-aksi">
        <a href="<?= $root ?>kuis/hasil.php?id=<?= (int) $kuisTerakhir['percobaan_id'] ?>" class="btn btn-primary">
          Lihat Hasil
        </a>
        <a href="<?= $root ?>kuis/detail.php?id=<?= (int) $kuisTerakhir['kuis_id'] ?>" class="btn btn-secondary">
          Detail Kuis
        </a>
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="card kuis-sorotan-card" aria-label="Mulai Kuis Pertama">
    <div class="kuis-sorotan-header">
      <div>
        <span class="badge badge-mudah">Mulai Belajar</span>
        <h2 class="kuis-judul">Belum Ada Riwayat Kuis</h2>
        <p class="kuis-meta">Pilih kuis publik pertamamu di katalog untuk mulai membangun skor dan streak harian.</p>
      </div>
      <div class="kuis-sorotan-aksi">
        <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">
          Jelajahi Katalog Kuis
        </a>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- 3. Statistik Utama & Streak Harian (HSL-05, HSL-06, KT-06) -->
<section class="stats-grid-4" aria-label="Statistik Belajar">
  <article class="card stat-card">
    <div class="stat-ikon" aria-hidden="true"><i class="fa-solid fa-bullseye"></i></div>
    <div class="stat-angka"><?= (int) $statistik['rata_rata_skor'] ?>%</div>
    <div class="stat-ket">Rata-rata Skor</div>
  </article>

  <article class="card stat-card">
    <div class="stat-ikon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></div>
    <div class="stat-angka"><?= (int) $statistik['total_selesai'] ?></div>
    <div class="stat-ket">Kuis Selesai</div>
  </article>

  <article class="card stat-card">
    <div class="stat-ikon" aria-hidden="true"><i class="fa-solid fa-pen-to-square"></i></div>
    <div class="stat-angka"><?= (int) $statistik['total_latihan'] ?></div>
    <div class="stat-ket">Total Percobaan</div>
  </article>

  <article class="card stat-card">
    <div class="stat-ikon" aria-hidden="true"><i class="fa-solid fa-fire"></i></div>
    <div class="stat-angka"><?= (int) $statistik['streak_hari'] ?> Hari</div>
    <div class="stat-ket">Streak Belajar (WIB)</div>
  </article>
</section>

<!-- 4. Rekomendasi Kuis Pintar (HSL-06, KT-06) -->
<section class="section-blok" aria-label="Rekomendasi Kuis Untukmu">
  <div class="section-header">
    <h2 class="section-judul">Rekomendasi Kuis Untukmu</h2>
    <a href="<?= $root ?>kuis/katalog.php" class="btn btn-secondary">Lihat Semua Kuis</a>
  </div>

  <?php if (empty($rekomendasiKuis)): ?>
    <div class="card empty-state">
      <div class="empty-state-ikon" aria-hidden="true"><i class="fa-solid fa-champagne-glasses"></i></div>
      <h3>Semua Kuis Publik Sudah Dicoba</h3>
      <p>Kamu sudah mencoba semua kuis publik yang tersedia saat ini. Kunjungi katalog untuk mengulang kuis favoritmu.</p>
      <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Buka Katalog Kuis</a>
    </div>
  <?php else: ?>
    <div class="rekomendasi-grid">
      <?php foreach ($rekomendasiKuis as $kuis): ?>
        <?php
        $kelasKesulitan = 'badge-mudah';
        if ($kuis['kesulitan'] === 'Sedang') {
            $kelasKesulitan = 'badge-sedang';
        } elseif ($kuis['kesulitan'] === 'Sulit') {
            $kelasKesulitan = 'badge-sulit';
        }
        ?>
        <article class="card kuis-card">
          <div>
            <div class="kuis-meta">
              <span class="badge badge-sedang"><?= e($kuis['nama_topik']) ?></span>
              <span class="badge <?= $kelasKesulitan ?>"><?= e($kuis['kesulitan']) ?></span>
            </div>
            <h3 class="kuis-judul"><?= e($kuis['judul']) ?></h3>
            <p class="kuis-meta">
              <span><?= (int) $kuis['jumlah_soal'] ?> Soal</span>
              <span>&bull;</span>
              <span><?= (int) $kuis['durasi_menit'] ?> Menit</span>
              <span>&bull;</span>
              <span><?= e($kuis['nama_tingkat']) ?></span>
            </p>
            <p class="kuis-meta">Pengajar: <?= e($kuis['nama_pengajar']) ?></p>
          </div>
          <div>
            <a href="<?= $root ?>kuis/detail.php?id=<?= (int) $kuis['id'] ?>" class="btn btn-primary">
              Lihat Detail &amp; Mulai
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- 5. Cuplikan Peringkat Top 5 + Posisi Saya (HSL-06, KT-06) -->
<section class="section-blok" aria-label="Cuplikan Peringkat">
  <div class="section-header">
    <h2 class="section-judul">Cuplikan Peringkat (Top 5)</h2>
    <a href="<?= $root ?>pelajar/peringkat.php" class="btn btn-secondary">Papan Peringkat Lengkap</a>
  </div>

  <?php if (empty($topLima)): ?>
    <div class="card empty-state">
      <div class="empty-state-ikon" aria-hidden="true"><i class="fa-solid fa-trophy"></i></div>
      <h3>Papan Peringkat Masih Kosong</h3>
      <p>Jadilah pelajar pertama yang menyelesaikan kuis untuk masuk ke jajaran peringkat teratas.</p>
      <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Mulai Kuis</a>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="table-responsive">
        <table class="tabel-clay">
          <thead>
            <tr>
              <th>Peringkat</th>
              <th>Nama Pelajar</th>
              <th class="teks-kanan">Total Poin</th>
              <th class="teks-kanan">Kuis</th>
              <th class="teks-kanan">Waktu</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topLima as $baris): ?>
              <?php $adalahSaya = ($baris['pelajar_id'] === $pelajarId); ?>
              <tr class="<?= $adalahSaya ? 'baris-saya' : '' ?>">
                <td>#<?= (int) $baris['peringkat'] ?></td>
                <td>
                  <?= e($baris['nama']) ?>
                  <?php if ($adalahSaya): ?>
                    <span class="badge badge-mudah">Kamu</span>
                  <?php endif; ?>
                </td>
                <td class="teks-kanan"><?= (int) $baris['total_poin'] ?></td>
                <td class="teks-kanan"><?= (int) $baris['jumlah_kuis'] ?></td>
                <td class="teks-kanan"><?= e(formatDurasiSingkat($baris['total_waktu_detik'])) ?></td>
              </tr>
            <?php endforeach; ?>

            <?php if ($posisiSaya !== null && !$masukTopLima): ?>
              <tr class="baris-saya">
                <td>#<?= (int) $posisiSaya['peringkat'] ?></td>
                <td>
                  <?= e($posisiSaya['nama']) ?>
                  <span class="badge badge-mudah">Posisi Kamu</span>
                </td>
                <td class="teks-kanan"><?= (int) $posisiSaya['total_poin'] ?></td>
                <td class="teks-kanan"><?= (int) $posisiSaya['jumlah_kuis'] ?></td>
                <td class="teks-kanan"><?= e(formatDurasiSingkat($posisiSaya['total_waktu_detik'])) ?></td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($posisiSaya === null): ?>
        <p class="stat-ket">Kamu belum masuk papan peringkat. Selesaikan minimal 1 kuis untuk mulai mengumpulkan poin.</p>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>

<!-- 6. Akses Cepat Menu Pelajar -->
<section class="section-blok" aria-label="Menu Cepat Pelajar">
  <div class="section-header">
    <h2 class="section-judul">Menu Cepat</h2>
  </div>
  <div class="menu-cepat-grid">
    <article class="card menu-cepat-card">
      <div>
        <h3>Katalog Kuis</h3>
        <p class="stat-ket">Cari dan kerjakan berbagai kuis publik berdasarkan topik dan tingkat kesulitan.</p>
      </div>
      <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Buka Katalog</a>
    </article>

    <article class="card menu-cepat-card">
      <div>
        <h3>Riwayat Percobaan</h3>
        <p class="stat-ket">Tinjau kembali semua kuis yang pernah kamu kerjakan beserta pembahasan hasilnya.</p>
      </div>
      <a href="<?= $root ?>pelajar/riwayat.php" class="btn btn-secondary">Lihat Riwayat</a>
    </article>

    <article class="card menu-cepat-card">
      <div>
        <h3>Papan Peringkat</h3>
        <p class="stat-ket">Bandingkan total poinmu dengan pelajar lain dan filter berdasarkan mata pelajaran.</p>
      </div>
      <a href="<?= $root ?>pelajar/peringkat.php" class="btn btn-accent">Cek Peringkat</a>
    </article>
  </div>
</section>

<?php
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
