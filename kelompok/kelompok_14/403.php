<?php
// 403.php
// Halaman Error 403 Akses Ditolak (PB-05) - DynaQuiz

// 1. Set kode status respon HTTP menjadi 403 Forbidden
http_response_code(403);

// 2. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Muat konfigurasi dan fungsi pembantu
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

// 4. Konfigurasi halaman dan template publik
$root = defined('BASE_URL') ? (rtrim(BASE_URL, '/') . '/') : './';
$judulHalaman = '403 Akses Ditolak - DynaQuiz';
$halamanAktif = '';

// Tentukan tautan kembali yang cerdas berdasarkan peran pengguna saat ini
$baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
$linkKembali = $baseUrl ? ($baseUrl . '/index.php') : 'index.php';
$labelKembali = 'Kembali ke Beranda';

$sesiUser = $_SESSION['user'] ?? $_SESSION['pengguna'] ?? null;
if ($sesiUser && isset($sesiUser['peran'])) {
    $peran = $sesiUser['peran'];
    if ($peran === 'admin') {
        $linkKembali = $baseUrl ? ($baseUrl . '/admin/dashboard.php') : 'admin/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Admin';
    } elseif ($peran === 'pengajar') {
        $linkKembali = $baseUrl ? ($baseUrl . '/pengajar/dashboard.php') : 'pengajar/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Pengajar';
    } elseif ($peran === 'pelajar') {
        $linkKembali = $baseUrl ? ($baseUrl . '/pelajar/dashboard.php') : 'pelajar/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Pelajar';
    } elseif ($peran === 'guest') {
        $linkKembali = $baseUrl ? ($baseUrl . '/guest/dashboard.php') : 'guest/dashboard.php';
        $labelKembali = 'Kembali ke Dashboard Tamu';
    }
}

// 5. Muat header layout publik
require_once __DIR__ . '/includes/partials/header-publik.php';
?>

<!-- Kartu Konten Error 403 -->
<div class="card error-card">
  <div class="error-ikon" aria-hidden="true">
    <i class="fa-solid fa-ban"></i>
  </div>

  <span class="badge badge-sulit error-badge">
    Error 403 &bull; Akses Ditolak
  </span>

  <h1 class="error-judul">
    Ups! Kamu Tidak Memiliki Akses
  </h1>

  <p class="error-deskripsi">
    Maaf, akun atau peranmu saat ini tidak memiliki izin untuk membuka halaman yang dituju. Silakan kembali ke dashboard atau masuk menggunakan akun yang sesuai.
  </p>

  <!-- Tombol Aksi Navigasi -->
  <div class="error-aksi">
    <a href="<?= e($linkKembali) ?>" class="btn btn-primary">
      <i class="fa-solid fa-arrow-left"></i> <?= e($labelKembali) ?>
    </a>

    <?php if ($sesiUser): ?>
      <a href="<?= e($root) ?>logout.php" class="btn btn-secondary">
        <i class="fa-solid fa-right-from-bracket"></i> Keluar & Ganti Akun
      </a>
    <?php else: ?>
      <a href="<?= e($root) ?>login.php" class="btn btn-secondary">
        <i class="fa-solid fa-right-to-bracket"></i> Masuk Akun
      </a>
    <?php endif; ?>
  </div>
</div>

<?php
// 6. Muat penutup footer layout publik
require_once __DIR__ . '/includes/partials/footer-publik.php';
?>
