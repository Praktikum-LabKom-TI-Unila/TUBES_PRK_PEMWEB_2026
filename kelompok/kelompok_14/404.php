<?php
// 404.php
// Halaman Error 404 Tidak Ditemukan (PB-06) - DynaQuiz

// 1. Set kode status respon HTTP menjadi 404 Not Found
http_response_code(404);

// 2. Jalankan sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Muat konfigurasi dan fungsi pembantu
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/helpers.php';

// 4. Konfigurasi halaman dan template publik
$root = defined('BASE_URL') ? (rtrim(BASE_URL, '/') . '/') : './';
$judulHalaman = '404 Halaman Tidak Ditemukan - DynaQuiz';
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

<!-- Kartu Konten Error 404 -->
<div class="card error-card">
  <div class="error-ikon" aria-hidden="true">
    <i class="fa-solid fa-map-location-dot"></i>
  </div>

  <span class="badge badge-sedang error-badge">
    Error 404 &bull; Tidak Ditemukan
  </span>

  <h1 class="error-judul">
    Halaman Tidak Ditemukan
  </h1>

  <p class="error-deskripsi">
    Maaf, halaman atau kuis yang kamu cari tidak dapat ditemukan. Tautan yang kamu tuju mungkin salah ketik, sudah dipindahkan, atau kuis telah dihapus dari sistem.
  </p>

  <!-- Tombol Aksi Navigasi -->
  <div class="error-aksi">
    <a href="<?= e($linkKembali) ?>" class="btn btn-primary">
      <i class="fa-solid fa-arrow-left"></i> <?= e($labelKembali) ?>
    </a>

    <a href="<?= e($root) ?>index.php" class="btn btn-secondary">
      <i class="fa-solid fa-house"></i> Halaman Utama
    </a>
  </div>
</div>

<?php
// 6. Muat penutup footer layout publik
require_once __DIR__ . '/includes/partials/footer-publik.php';
?>
