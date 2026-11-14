<?php
// Sidebar Navigasi Khusus Panel Admin (Dipakai di semua halaman admin/)
// Membutuhkan fungsi jumlahAntrian() dari includes/fungsi/kuis.php

$halamanAktif = basename($_SERVER['SCRIPT_NAME']);
$totalAntrian = function_exists('jumlahAntrian') ? jumlahAntrian() : 0;
?>
<aside class="sidebar-panel">
    <div>
        <div class="form-group">
            <a href="<?= e(BASE_URL) ?>/admin/dashboard.php" class="navbar-brand">
                <?= e(NAMA_APLIKASI) ?> <span>Admin</span>
            </a>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="<?= e(BASE_URL) ?>/admin/dashboard.php" class="sidebar-link <?= $halamanAktif === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="<?= e(BASE_URL) ?>/admin/persetujuan.php" class="sidebar-link <?= ($halamanAktif === 'persetujuan.php' || $halamanAktif === 'tinjau.php') ? 'active' : '' ?>">
                    <i class="fa-solid fa-clipboard-check"></i> Persetujuan
                    <?php if ($totalAntrian > 0): ?>
                        <span class="sidebar-badge"><?= (int)$totalAntrian ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= e(BASE_URL) ?>/admin/pengguna.php" class="sidebar-link <?= $halamanAktif === 'pengguna.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-users"></i> Pengguna
                </a>
            </li>
            <li>
                <a href="<?= e(BASE_URL) ?>/admin/mapel.php" class="sidebar-link <?= $halamanAktif === 'mapel.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-book-open"></i> Mapel &amp; Tingkat
                </a>
            </li>
            <li>
                <a href="<?= e(BASE_URL) ?>/admin/moderasi.php" class="sidebar-link <?= $halamanAktif === 'moderasi.php' ? 'active' : '' ?>">
                    <i class="fa-solid fa-shield-halved"></i> Moderasi
                </a>
            </li>
            <li>
                <a href="<?= e(BASE_URL) ?>/profil.php" class="sidebar-link">
                    <i class="fa-solid fa-user"></i> Profil Saya
                </a>
            </li>
            <li>
                <a href="<?= e(BASE_URL) ?>/logout.php" class="sidebar-link">
                    <i class="fa-solid fa-right-from-bracket"></i> Keluar
                </a>
            </li>
        </ul>
    </div>

    <div>
        <p class="form-helper">Pilih Tema:</p>
        <?php require __DIR__ . '/pemilih-tema.php'; ?>
    </div>
</aside>
