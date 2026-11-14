<?php
// guest/dashboard.php
// Dashboard Mode Tamu / Guest (PL-02, HSL-07) - DynaQuiz
// Menampilkan sapaan tamu, pilihan kuis publik asli dari DB, banner ajakan daftar, dan catatan sesi sementara

// 1. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Muat konfigurasi, database, helper, dan fungsi
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/fungsi/percobaan.php';

// 3. Aturan Akses: Pengguna yang sudah login sebagai akun terdaftar diarahkan ke dashboard masing-masing
$sesiUser = $_SESSION['user'] ?? $_SESSION['pengguna'] ?? null;
if ($sesiUser && isset($sesiUser['peran'])) {
    $peran = $sesiUser['peran'];
    if ($peran === 'admin') {
        redirect('../admin/dashboard.php');
    } elseif ($peran === 'pengajar') {
        redirect('../pengajar/dashboard.php');
    } elseif ($peran === 'pelajar') {
        redirect('../pelajar/dashboard.php');
    }
}

// 4. Pastikan data sesi tamu tersedia (jika belum ada, buat nama default)
if (!isset($_SESSION['guest']['username'])) {
    $_SESSION['guest'] = [
        'username' => 'Tamu DynaQuiz'
    ];
}

$namaTamu = $_SESSION['guest']['username'];

// 5. Konfigurasi halaman dan template
$root = '../';
$judulHalaman = 'Dashboard Tamu - DynaQuiz';
$halamanAktif = 'beranda';

// 6. Data Pilihan Kuis Publik Asli dari Database
$daftarKuis = [];
if (function_exists('daftarKuisPublik')) {
    $daftarKuis = daftarKuisPublik();
}

// 7. Muat header layout Pelajar & Guest
require_once __DIR__ . '/../includes/partials/header-pelajar.php';
?>

<!-- Sapaan Pengguna Tamu -->
<section class="card guest-sapaan" aria-label="Sapaan Tamu">
  <span class="badge badge-sedang guest-badge">Mode Tamu (Guest)</span>
  <h1 class="guest-judul">
    Halo, <span><?= e($namaTamu) ?></span>! <i class="fa-solid fa-hand"></i>
  </h1>
  <p class="guest-deskripsi">
    Selamat datang di DynaQuiz. Kamu bisa langsung mencoba latihan soal pilihan ganda di bawah ini tanpa perlu login.
  </p>
</section>

<!-- Catatan Informasi Mode Tamu (HSL-07) -->
<div class="alert alert-info guest-alert">
  <span class="guest-alert-ikon" aria-hidden="true"><i class="fa-solid fa-circle-info"></i></span>
  <div>
    <strong>Catatan Mode Tamu:</strong> Skor dan riwayat pengerjaan hanya disimpan sementara di sesi browser ini. Jika ingin nilaimu tercatat permanen dan masuk ke papan peringkat, silakan daftarkan akun Pelajar.
  </div>
</div>

<!-- Banner Ajakan Mendaftar Akun Pelajar (HSL-07) -->
<section class="card guest-cta-card" aria-label="Ajakan Daftar Akun Pelajar">
  <div class="guest-cta-wrapper">
    <div class="guest-cta-teks">
      <h2 class="guest-cta-judul">Simpan Progres Belajarmu Secara Permanen!</h2>
      <p class="guest-cta-sub">
        Daftar akun Pelajar gratis untuk mencatat seluruh riwayat pengerjaan, mempertahankan streak belajar harian, dan bersaing di papan peringkat siswa.
      </p>
    </div>
    <div class="guest-cta-tombol">
      <a href="<?= $root ?>register.php" class="btn btn-primary">
        <i class="fa-solid fa-user-plus"></i> Daftar Akun Pelajar
      </a>
      <a href="<?= $root ?>login.php" class="btn btn-outline">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk Akun
      </a>
    </div>
  </div>
</section>

<!-- Bagian Pilihan Kuis Publik (HSL-07) -->
<section class="section-blok" aria-label="Pilihan Kuis Publik">
  <div class="guest-section-header">
    <div>
      <h2 class="guest-section-judul">Pilihan Kuis Publik</h2>
      <p class="guest-section-sub">Pilih kuis yang ingin kamu coba kerjakan hari ini:</p>
    </div>
    <a href="<?= $root ?>kuis/katalog.php" class="btn btn-sm btn-outline">
      Lihat Semua di Katalog &rarr;
    </a>
  </div>

  <?php if (empty($daftarKuis)): ?>
    <div class="card empty-state">
      <div class="empty-state-ikon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></div>
      <h3>Belum Ada Kuis Publik</h3>
      <p>Kuis publik yang telah disetujui akan muncul di sini. Silakan cek kembali nanti atau hubungi pengajar.</p>
      <a href="<?= $root ?>kuis/katalog.php" class="btn btn-primary">Buka Katalog Kuis</a>
    </div>
  <?php else: ?>
    <!-- Grid Bento Kartu Kuis Publik -->
    <div class="bento-grid">
      <?php foreach ($daftarKuis as $kuis): ?>
        <?php
        // Ambil data kuis dan tentukan badge kesulitan
        $durasiMenit = (int) ($kuis['durasi_menit'] ?? $kuis['durasi'] ?? 0);
        $jumlahSoal = (int) ($kuis['jumlah_soal'] ?? 0);
        $tingkatTeks = $kuis['tingkat'] ?? 'Umum';
        $topikTeks = $kuis['topik'] ?? 'Umum';

        $kesulitan = strtolower((string) ($kuis['kesulitan'] ?? ''));
        if ($kesulitan === 'mudah') {
            $kelasKesulitan = 'badge-mudah';
        } elseif ($kesulitan === 'sulit') {
            $kelasKesulitan = 'badge-sulit';
        } else {
            $kelasKesulitan = 'badge-sedang';
        }
        ?>
        <article class="card guest-kuis-card">
          <div>
            <!-- Kategori & Tingkat Kesulitan -->
            <div class="guest-kuis-top">
              <span class="badge badge-sedang"><?= e($topikTeks) ?></span>
              <span class="badge <?= $kelasKesulitan ?>"><?= ucfirst(e($kesulitan ?: 'sedang')) ?></span>
            </div>

            <!-- Judul Kuis -->
            <h3 class="card-title">
              <?= e($kuis['judul']) ?>
            </h3>

            <!-- Informasi Jumlah Soal dan Durasi -->
            <p class="guest-kuis-info">
              <i class="fa-solid fa-pen-to-square"></i> <?= $jumlahSoal ?> Soal &bull; <i class="fa-solid fa-stopwatch"></i> <?= $durasiMenit ?> Menit &bull; <?= e($tingkatTeks) ?>
            </p>
          </div>

          <!-- Tombol Aksi Mulai Kuis -->
          <div>
            <a href="<?= $root ?>kuis/detail.php?id=<?= (int)$kuis['id'] ?>" class="btn btn-block btn-primary">
              Mulai Kerjakan &rarr;
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php
// 8. Muat penutup footer layout Pelajar & Guest
require_once __DIR__ . '/../includes/partials/footer-pelajar.php';
?>
