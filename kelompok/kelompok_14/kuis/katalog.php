<?php
// ============================================================================
// Halaman Katalog Kuis Publik (PL-03 - kuis/katalog.php)
// Kelompok 14 - Tugas Besar Praktikum Pemrograman Web 2026
// PIC: Wulan (Sesi 4 - PL-03 Katalog Kuis)
//
// Aturan & Fitur (KAT-01, KAT-02, KAT-03, KAT-05, BR-12, BR-14):
// 1. Menampilkan kuis publik berstatus 'terbit' dari yang terbaru
// 2. Pencarian judul, filter mata pelajaran (topik), dan jenjang (tingkat)
// 3. Pagination 12 kartu per halaman
// 4. Input kode kuis privat untuk Pelajar (tidak tampil untuk Guest)
// ============================================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';
require_once __DIR__ . '/../includes/fungsi/kuis.php';

// Pastikan kuis yang sudah lewat batas waktu ditutup
tutupPercobaanKedaluwarsa();

// Jika pengunjung belum login, aktifkan sesi Guest default
if (!sudahMasuk()) {
    if (!isset($_SESSION['guest'])) {
        $_SESSION['guest'] = [
            'username' => 'Tamu DynaQuiz'
        ];
    }
}

$peran = peranSaatIni();
$isGuest = ($peran === 'guest');

// ----------------------------------------------------------------------------
// 1. Proses Form Masukkan Kode Kuis Privat (Khusus Pelajar, KAT-03 & BR-12)
// ----------------------------------------------------------------------------
$pesanGalatKode = '';
if (isPost() && isset($_POST['aksi_kode_privat'])) {
    verifikasiCsrf();

    if ($isGuest) {
        flash('galat', 'Pengguna tamu tidak dapat mengakses kuis privat. Silakan daftar atau login akun Pelajar.');
        redirect('katalog.php');
    }

    $kodeKuis = strtoupper(trim($_POST['kode_kuis'] ?? ''));

    if ($kodeKuis === '') {
        $pesanGalatKode = 'Silakan masukkan kode kuis.';
    } else {
        global $pdo;
        // Cari kuis privat berstatus terbit yang kodenya cocok
        $stmtCariKode = $pdo->prepare("SELECT id, judul FROM kuis WHERE kode = ? AND status = 'terbit' AND akses = 'privat' LIMIT 1");
        $stmtCariKode->execute([$kodeKuis]);
        $kuisPrivat = $stmtCariKode->fetch(PDO::FETCH_ASSOC);

        if ($kuisPrivat) {
            // Berikan izin akses kuis privat ini di dalam sesi (KT-04, BR-12)
            if (!isset($_SESSION['akses_kuis_privat'])) {
                $_SESSION['akses_kuis_privat'] = [];
            }
            $_SESSION['akses_kuis_privat'][(int)$kuisPrivat['id']] = true;

            flash('sukses', 'Kode kuis berhasil diverifikasi! Selamat mengerjakan.');
            redirect('detail.php?id=' . (int)$kuisPrivat['id']);
        } else {
            // Pesan umum sesuai ketentuan KAT-03
            $pesanGalatKode = 'Kode kuis tidak valid atau kuis belum dipublikasikan.';
        }
    }
}

// ----------------------------------------------------------------------------
// 2. Filter & Pencarian Kuis Publik (KAT-02)
// ----------------------------------------------------------------------------
$kataKunci = trim($_GET['q'] ?? '');
$filterTopikId = (int) ($_GET['topik'] ?? 0);
$filterTingkatId = (int) ($_GET['tingkat'] ?? 0);
$halamanSekarang = max(1, (int) ($_GET['page'] ?? 1));
$perHalaman = 12;

// Ambil data master untuk pilihan dropdown filter
$daftarSemuaTopik = ambilSemuaTopik();
$daftarSemuaTingkat = ambilSemuaTingkat();

// Ambil seluruh daftar kuis publik yang cocok
$semuaKuisPublik = daftarKuisPublik($filterTopikId, $filterTingkatId, $kataKunci);

// Hitung total dan lakukan pemotongan pagination
$totalKuis = count($semuaKuisPublik);
$totalHalaman = max(1, (int) ceil($totalKuis / $perHalaman));
if ($halamanSekarang > $totalHalaman) {
    $halamanSekarang = $totalHalaman;
}

$offset = ($halamanSekarang - 1) * $perHalaman;
$daftarKuisTampil = array_slice($semuaKuisPublik, $offset, $perHalaman);

// ----------------------------------------------------------------------------
// 3. Konfigurasi Template & Layout
// ----------------------------------------------------------------------------
$root = '../';
$judulHalaman = 'Katalog Kuis - DynaQuiz';
$halamanAktif = 'katalog';

require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Header Katalog & Sapaan -->
<div class="card" style="margin: 24px 0 32px 0; padding: 32px 28px;">
  <span class="badge badge-sedang" style="margin-bottom: 12px;">Eksplorasi Soal</span>
  <h1 style="font-size: 2.2rem; margin-bottom: 8px;">Katalog Kuis Interaktif <i class="fa-solid fa-book-open"></i></h1>
  <p style="color: var(--teks); font-size: 1.05rem; margin: 0; max-width: 720px;">
    Temukan beragam latihan soal pilihan ganda dari berbagai mata pelajaran dan jenjang pendidikan. Kerjakan kuis untuk mengasah kemampuan dan pantau skor terbaikmu!
  </p>
</div>

<?php if (!$isGuest): ?>
  <!-- Form Input Kode Kuis Privat (Khusus Pelajar, KAT-03 & BR-12) -->
  <div class="card" style="margin-bottom: 32px; padding: 24px; border: 2px dashed var(--primer-2);">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      <div style="max-width: 480px;">
        <h2 style="font-size: 1.25rem; margin-bottom: 4px;">Punya Kode Kuis dari Guru? <i class="fa-solid fa-key"></i></h2>
        <p style="color: var(--teks-2); font-size: 0.95rem; margin: 0;">
          Masukkan kode kuis privat untuk membuka latihan soal khusus kelasmu.
        </p>
      </div>

      <form action="katalog.php" method="POST" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: flex-start;">
        <?= inputCsrf() ?>
        <input type="hidden" name="aksi_kode_privat" value="1">
        <div>
          <input 
            type="text" 
            name="kode_kuis" 
            placeholder="Contoh: WEB2026" 
            maxlength="8" 
            style="text-transform: uppercase; font-weight: bold; letter-spacing: 1px; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--border);"
            required
          >
          <?php if (!empty($pesanGalatKode)): ?>
            <div style="color: var(--galat); font-size: 0.85rem; margin-top: 4px;"><?= e($pesanGalatKode) ?></div>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
          Buka Kuis &rarr;
        </button>
      </form>
    </div>
  </div>
<?php endif; ?>

<!-- Form Pencarian dan Filter Kuis (KAT-02) -->
<div class="card" style="margin-bottom: 32px; padding: 24px;">
  <form action="katalog.php" method="GET" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
    <!-- Input Pencarian Judul -->
    <div style="flex: 2; min-width: 220px;">
      <label for="inputCari" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px;">Cari Judul Kuis</label>
      <input 
        type="text" 
        id="inputCari" 
        name="q" 
        value="<?= e($kataKunci) ?>" 
        placeholder="Ketik kata kunci judul kuis..." 
        style="width: 100%; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--border);"
      >
    </div>

    <!-- Filter Topik / Mata Pelajaran -->
    <div style="flex: 1; min-width: 180px;">
      <label for="selectTopik" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px;">Mata Pelajaran</label>
      <select id="selectTopik" name="topik" style="width: 100%; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--border);">
        <option value="">Semua Mata Pelajaran</option>
        <?php foreach ($daftarSemuaTopik as $topik): ?>
          <option value="<?= (int)$topik['id'] ?>" <?= $filterTopikId === (int)$topik['id'] ? 'selected' : '' ?>>
            <?= e($topik['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Filter Tingkat / Jenjang -->
    <div style="flex: 1; min-width: 160px;">
      <label for="selectTingkat" style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 6px;">Jenjang Kelas</label>
      <select id="selectTingkat" name="tingkat" style="width: 100%; padding: 10px 14px; border-radius: 12px; border: 1px solid var(--border);">
        <option value="">Semua Jenjang</option>
        <?php foreach ($daftarSemuaTingkat as $tingkat): ?>
          <option value="<?= (int)$tingkat['id'] ?>" <?= $filterTingkatId === (int)$tingkat['id'] ? 'selected' : '' ?>>
            <?= e($tingkat['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Tombol Aksi Filter -->
    <div style="display: flex; gap: 8px;">
      <button type="submit" class="btn btn-primary" style="padding: 10px 18px;">
        <i class="fa-solid fa-magnifying-glass"></i> Cari
      </button>
      <?php if (!empty($kataKunci) || !empty($filterTopikId) || !empty($filterTingkatId)): ?>
        <a href="katalog.php" class="btn btn-outline" style="text-decoration: none; padding: 10px 14px;">
          Reset
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<!-- Informasi Hasil Pencarian -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
  <h2 style="font-size: 1.35rem; margin: 0;">
    Daftar Kuis Publik 
    <span style="font-size: 0.95rem; font-weight: normal; color: var(--teks-2);">
      (Menampilkan <?= count($daftarKuisTampil) ?> dari <?= $totalKuis ?> kuis)
    </span>
  </h2>
</div>

<!-- Grid Bento Daftar Kuis (KAT-01, KAT-02) -->
<?php if (empty($daftarKuisTampil)): ?>
  <div class="card" style="padding: 48px 24px; text-align: center; margin-bottom: 40px;">
    <div style="font-size: 3rem; margin-bottom: 12px;"><i class="fa-solid fa-magnifying-glass"></i></div>
    <h3 style="font-size: 1.3rem; margin-bottom: 8px;">Kuis Tidak Ditemukan</h3>
    <p style="color: var(--teks-2); max-width: 480px; margin: 0 auto 20px auto;">
      Tidak ada kuis publik yang cocok dengan kata kunci atau filter yang kamu pilih. Silakan ubah filter atau gunakan kata kunci lain.
    </p>
    <a href="katalog.php" class="btn btn-primary" style="text-decoration: none;">
      Tampilkan Semua Kuis
    </a>
  </div>
<?php else: ?>
  <div class="bento-grid" style="margin-bottom: 36px;">
    <?php foreach ($daftarKuisTampil as $kuis): ?>
      <?php
      $kelasKesulitan = 'badge-mudah';
      if ($kuis['kesulitan'] === 'sedang') {
          $kelasKesulitan = 'badge-sedang';
      } elseif ($kuis['kesulitan'] === 'sulit') {
          $kelasKesulitan = 'badge-sulit';
      }
      ?>
      <div class="card" style="display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s ease;">
        <div>
          <!-- Kategori & Tingkat Kesulitan -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; gap: 8px;">
            <span class="badge badge-sedang"><?= e($kuis['topik']) ?></span>
            <span class="badge <?= $kelasKesulitan ?>"><?= ucfirst(e($kuis['kesulitan'])) ?></span>
          </div>

          <!-- Judul Kuis -->
          <h3 class="card-title" style="margin-bottom: 10px; font-size: 1.25rem;">
            <?= e($kuis['judul']) ?>
          </h3>

          <!-- Deskripsi Singkat -->
          <?php if (!empty($kuis['deskripsi'])): ?>
            <p style="color: var(--teks); font-size: 0.95rem; margin-bottom: 16px; line-height: 1.4;">
              <?= e(mb_strimwidth($kuis['deskripsi'], 0, 110, '...')) ?>
            </p>
          <?php endif; ?>

          <!-- Metadata Kuis -->
          <div style="font-size: 0.88rem; color: var(--teks-2); margin-bottom: 20px;">
            <div style="margin-bottom: 4px;">
              <i class="fa-solid fa-list-check"></i> <strong><?= (int)$kuis['jumlah_soal'] ?></strong> Soal &bull; <i class="fa-solid fa-stopwatch"></i> <strong><?= (int)$kuis['durasi'] ?></strong> Menit
            </div>
            <div>
              <i class="fa-solid fa-graduation-cap"></i> <?= e($kuis['tingkat']) ?> &bull; <i class="fa-solid fa-chalkboard-user"></i> <?= e($kuis['pengajar']) ?>
            </div>
          </div>
        </div>

        <!-- Tombol Menuju Detail Kuis -->
        <div>
          <a href="detail.php?id=<?= (int)$kuis['id'] ?>" class="btn btn-block btn-primary" style="text-decoration: none; text-align: center;">
            Lihat Detail & Mulai &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination Kuis (KAT-02) -->
  <?php if ($totalHalaman > 1): ?>
    <div style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-bottom: 48px; flex-wrap: wrap;">
      <!-- Tombol Sebelumnya -->
      <?php if ($halamanSekarang > 1): ?>
        <?php
        $paramPrev = $_GET;
        $paramPrev['page'] = $halamanSekarang - 1;
        $linkPrev = 'katalog.php?' . http_build_query($paramPrev);
        ?>
        <a href="<?= e($linkPrev) ?>" class="btn btn-sm btn-outline" style="text-decoration: none; min-height: 44px; display: inline-flex; align-items: center;">&larr; Sebelumnya</a>
      <?php endif; ?>

      <!-- Tombol Angka Halaman -->
      <?php for ($i = 1; $i <= $totalHalaman; $i++): ?>
        <?php
        $paramPage = $_GET;
        $paramPage['page'] = $i;
        $linkPage = 'katalog.php?' . http_build_query($paramPage);
        $isActive = ($i === $halamanSekarang);
        ?>
        <a 
          href="<?= e($linkPage) ?>" 
          class="btn btn-sm <?= $isActive ? 'btn-primary' : 'btn-outline' ?>" 
          style="text-decoration: none; min-width: 44px; min-height: 44px; display: inline-flex; align-items: center; justify-content: center;"
        >
          <?= $i ?>
        </a>
      <?php endfor; ?>

      <!-- Tombol Selanjutnya -->
      <?php if ($halamanSekarang < $totalHalaman): ?>
        <?php
        $paramNext = $_GET;
        $paramNext['page'] = $halamanSekarang + 1;
        $linkNext = 'katalog.php?' . http_build_query($paramNext);
        ?>
        <a href="<?= e($linkNext) ?>" class="btn btn-sm btn-outline" style="text-decoration: none; min-height: 44px; display: inline-flex; align-items: center;">Selanjutnya &rarr;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
