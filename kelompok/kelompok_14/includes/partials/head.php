<?php
// includes/partials/head.php
// Bagian pembuka HTML dan tag <head> untuk semua halaman

// 1. Pastikan session dimulai sebelum output HTML jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Muat fungsi pembantu jika belum ada
if (!function_exists('e')) {
    require_once __DIR__ . '/../helpers.php';
}

// 3. Pastikan variabel root dan judul sudah disiapkan
$root = $root ?? '';
$judulHalaman = $judulHalaman ?? 'DynaQuiz';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($judulHalaman) ?></title>

  <!-- Pustaka Ikon Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <!-- Berkas CSS Tema dan Komponen Claymorphism -->
  <link rel="stylesheet" href="<?= $root ?>assets/css/tema.css">
  <link rel="stylesheet" href="<?= $root ?>assets/css/dasar.css">
  <link rel="stylesheet" href="<?= $root ?>assets/css/komponen.css">

  <!-- Skrip Pencegah FOUC (agar tema tidak berkedip saat dimuat) -->
  <script src="<?= $root ?>assets/js/tema-init.js"></script>
</head>
<body>
