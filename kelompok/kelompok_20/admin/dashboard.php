<?php

require_once '../config/database.php';
require_once '../includes/functions.php';

wajibAdmin();

$q_total = $pdo->query("SELECT COUNT(*) FROM laporan");
$total_laporan = $q_total->fetchColumn();

$q_menunggu = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'menunggu'");
$total_menunggu = $q_menunggu->fetchColumn();

$q_diproses = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'diproses'");
$total_diproses = $q_diproses->fetchColumn();

$q_selesai = $pdo->query("SELECT COUNT(*) FROM laporan WHERE status = 'selesai'");
$total_selesai = $q_selesai->fetchColumn();

$q_terbaru = $pdo->query("SELECT laporan.*, users.nama AS nama_pelapor 
                          FROM laporan 
                          JOIN users ON laporan.user_id = users.id 
                          ORDER BY laporan.created_at DESC LIMIT 5");
$list_terbaru = $q_terbaru->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Aspiraku</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <?php include '../includes/header.php'; ?>

    <main class="container" style="padding: 30px 20px;">
        <div style="margin-bottom: 30px;">
            <h1 style="color: #1E3B2F; font-size: 28px; margin-bottom: 8px;">Dashboard Admin</h1>
            <p style="color: #5F6B4E; font-size: 16px;">
                Selamat datang kembali, <strong><?= sanitize($_SESSION['nama'] ?? 'Admin') ?></strong>! Berikut adalah ringkasan pengaduan dan aspirasi kampus saat ini.
            </p>
        </div>

        <h3 style="color: #1E3B2F; margin-bottom: 15px; font-size: 20px;">Statistik Penanganan</h3>
        
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 35px;">
            
            <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 25px 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="font-size: 32px; font-weight: bold; color: #1E3B2F; margin-bottom: 8px;">
                    <?= $total_laporan ?>
                </div>
                <div style="font-size: 13px; font-weight: bold; color: #5F6B4E; letter-spacing: 0.5px;">
                    TOTAL LAPORAN
                </div>
            </div>

            <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 25px 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="font-size: 32px; font-weight: bold; color: #b78103; margin-bottom: 8px;">
                    <?= $total_menunggu ?>
                </div>
                <div style="font-size: 13px; font-weight: bold; color: #5F6B4E; letter-spacing: 0.5px;">
                    MENUNGGU
                </div>
            </div>

            <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 25px 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="font-size: 32px; font-weight: bold; color: #34402a; margin-bottom: 8px;">
                    <?= $total_diproses ?>
                </div>
                <div style="font-size: 13px; font-weight: bold; color: #5F6B4E; letter-spacing: 0.5px;">
                    DIPROSES
                </div>
            </div>

            <div class="card" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 25px 20px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                <div style="font-size: 32px; font-weight: bold; color: #1E3B2F; margin-bottom: 8px;">
                    <?= $total_selesai ?>
                </div>
                <div style="font-size: 13px; font-weight: bold; color: #5F6B4E; letter-spacing: 0.5px;">
                    SELESAI
                </div>
            </div>

        </div>

        <div style="margin-bottom: 35px;">
            <a href="kelola.php" class="btn" style="background: #1E3B2F; color: #F5F0E6; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-block; margin-right: 10px;">Kelola Semua Laporan &rarr;</a>
            <a href="kategori.php" class="btn btn-secondary" style="background: transparent; color: #1E3B2F; border: 1px solid #7F8C6A; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500; display: inline-block;">Kelola Kategori</a>
        </div>

        <!-- Tabel 5 Laporan Terbaru -->
        <h3 style="color: #1E3B2F; margin-bottom: 15px; font-size: 20px;">5 Laporan Terbaru</h3>
        
        <div class="table-responsive" style="background: #FFFFFF; border: 1px solid #DDD5C0; border-radius: 8px; padding: 15px; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #DDD5C0; color: #1E3B2F;">
                        <th style="padding: 10px;">No</th>
                        <th style="padding: 10px;">Pelapor</th>
                        <th style="padding: 10px;">Judul Laporan</th>
                        <th style="padding: 10px;">Jenis</th>
                        <th style="padding: 10px;">Status</th>
                        <th style="padding: 10px;">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_terbaru)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px; color: #5F6B4E;">Belum ada laporan yang masuk.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($list_terbaru as $i => $row): ?>
                            <tr style="border-bottom: 1px solid #EEE5D0;">
                                <td style="padding: 10px;"><?= $i + 1 ?></td>
                                <td style="padding: 10px;"><?= sanitize($row['nama_pelapor']) ?></td>
                                <td style="padding: 10px;"><?= sanitize($row['judul']) ?></td>
                                <td style="padding: 10px;"><?= ucfirst($row['jenis']) ?></td>
                                <td style="padding: 10px;">
                                    <span class="badge badge-<?= $row['status'] ?>" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: #DCE2D0; color: #1E3B2F;">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td style="padding: 10px; font-size: 14px; color: #5F6B4E;"><?= sanitize($row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>