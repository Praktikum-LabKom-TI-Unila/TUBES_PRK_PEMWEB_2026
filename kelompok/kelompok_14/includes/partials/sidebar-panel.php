<?php
// includes/partials/sidebar-panel.php
// Panel samping (sidebar) untuk peran Pengajar dan Admin

$root = $root ?? '';
$halamanAktif = $halamanAktif ?? '';

// Ambil data peran dan nama dari sesi (mendukung kunci 'user' dan 'pengguna')
$sesiUser = $_SESSION['user'] ?? $_SESSION['pengguna'] ?? null;
$peran = $sesiUser['peran'] ?? 'pengajar';
$namaPengguna = $sesiUser['nama'] ?? 'Pengguna';

// Tautan dashboard sesuai peran
$linkDashboard = ($peran === 'admin') ? ($root . 'admin/dashboard.php') : ($root . 'pengajar/dashboard.php');
?>
<aside class="sidebar-panel" aria-label="Navigasi Panel Samping">
  <div class="sidebar-atas">
    <!-- Logo dan Judul Panel -->
    <a href="<?= $linkDashboard ?>" class="navbar-brand">
      Dyna<span>Quiz</span>
    </a>
    <span class="badge badge-sedang">
      Panel <?= ($peran === 'admin') ? 'Admin' : 'Pengajar' ?>
    </span>

    <!-- Menu Navigasi Samping -->
    <ul class="sidebar-menu" id="menuSidebar">
      <?php if ($peran === 'admin'): ?>
        <!-- Menu Khusus Admin -->
        <li>
          <a href="<?= $root ?>admin/dashboard.php" class="sidebar-link <?= $halamanAktif === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/persetujuan.php" class="sidebar-link <?= $halamanAktif === 'persetujuan' ? 'active' : '' ?>">
            <i class="fa-solid fa-clipboard-check"></i> Persetujuan Kuis
            <?php if (!empty($jumlahAntrianKuis)): ?>
              <span class="sidebar-badge"><?= (int)$jumlahAntrianKuis ?></span>
            <?php endif; ?>
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/pengguna.php" class="sidebar-link <?= $halamanAktif === 'pengguna' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Kelola Pengguna
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/mapel.php" class="sidebar-link <?= $halamanAktif === 'mapel' ? 'active' : '' ?>">
            <i class="fa-solid fa-book-open"></i> Kelola Mapel
          </a>
        </li>
        <li>
          <a href="<?= $root ?>admin/moderasi.php" class="sidebar-link <?= $halamanAktif === 'moderasi' ? 'active' : '' ?>">
            <i class="fa-solid fa-shield-halved"></i> Moderasi Kuis
          </a>
        </li>
      <?php else: ?>
        <!-- Menu Khusus Pengajar -->
        <li>
          <a href="<?= $root ?>pengajar/dashboard.php" class="sidebar-link <?= $halamanAktif === 'dashboard' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i> Dashboard
          </a>
        </li>
        <li>
          <a href="<?= $root ?>pengajar/kuis.php" class="sidebar-link <?= $halamanAktif === 'kuis' ? 'active' : '' ?>">
            <i class="fa-solid fa-pen-to-square"></i> Kuis Saya
          </a>
        </li>
      <?php endif; ?>

      <!-- Menu Bersama (Profil & Keluar) -->
      <li>
        <a href="<?= $root ?>profil.php" class="sidebar-link <?= $halamanAktif === 'profil' ? 'active' : '' ?>">
          <i class="fa-solid fa-user"></i> Profil Saya
        </a>
      </li>
      <li>
        <a href="<?= $root ?>logout.php" class="sidebar-link">
          <i class="fa-solid fa-right-from-bracket"></i> Keluar
        </a>
      </li>
    </ul>
  </div>

  <!-- Bagian Bawah Sidebar (Info User & Pemilih Tema) -->
  <div class="sidebar-bawah">
    <p class="card-sm">
      Halo, <strong><?= e($namaPengguna) ?></strong>
    </p>

    <!-- Widget Pemilih Tema Claymorphism -->
    <?php include __DIR__ . '/pemilih-tema.php'; ?>
  </div>
</aside>
