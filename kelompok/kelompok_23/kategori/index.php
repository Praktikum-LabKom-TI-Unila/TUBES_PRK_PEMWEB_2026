<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

$query = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kategori | FEBMart</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #f3f4f6; font-family: Arial, sans-serif; padding: 35px 20px; }
        .main-container { width: 100%; max-width: 1000px; margin: 0 auto; background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08); }
        
        .header-section { background: linear-gradient(145deg, #29245c, #5142a5); color: white; padding: 30px 35px; }
        .brand { font-size: 23px; font-weight: bold; margin-bottom: 25px; }
        .brand i { color: #ffad42; margin-right: 7px; }
        .header-section h2 { font-size: 25px; font-weight: bold; margin-bottom: 8px; }
        .header-section p { color: #d5d9e2; font-size: 14px; margin-bottom: 0; }
        
        .content-section { padding: 35px; }
        .content-section h4 { color: #29245c; font-size: 20px; font-weight: bold; }
        .content-description { color: #888; font-size: 13px; }

        .total-data { 
            background: #f5f4fb; 
            color: #5142a5; 
            padding: 10px 15px; 
            border-radius: 7px; 
            font-size: 13px; 
            font-weight: bold; 
        }
        
        .table { font-size: 13px; margin-bottom: 0; }
        .table thead th { background: #29245c; color: white; padding: 14px 12px; font-weight: 600; white-space: nowrap; }
        .table tbody td { padding: 13px 12px; vertical-align: middle; }
        .table tbody tr:hover { background: #f5f4fb; }
        
        .btn-save { background: #ffad42; color: #29245c; border: none; border-radius: 7px; padding: 10px 15px; font-size: 13px; font-weight: bold; transition: 0.2s; text-decoration: none; }
        .btn-save:hover { background: #f49a25; color: #29245c; }
        .btn-back { background: #f5f4fb; color: #5142a5; border: 1px solid #ddd; border-radius: 7px; padding: 11px 18px; font-size: 14px; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-block; margin-top: 20px;}
        .btn-back:hover { background: #e9e6f7; color: #29245c; }
        .copyright { color: #999; font-size: 12px; text-align: center; padding: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header-section">
        <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
        <h2><i class="bi bi-tags me-2"></i>Daftar Kategori</h2>
        <p>Kelola dan pantau seluruh kategori produk FEBMart.</p>
    </div>

    <div class="content-section">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <div>
                <h4>Data Kategori</h4>
                <p class="content-description mb-0">Informasi kategori produk FEBMart.</p>
            </div>
            
            <div class="d-flex align-items-center flex-wrap gap-2">
                <div class="total-data">
                    <i class="bi bi-tags me-1"></i> Total: <?= $query->num_rows; ?> Kategori
                </div>
                <a href="tambah.php" class="btn-save"><i class="bi bi-plus-lg me-1"></i> Tambah Kategori</a>
            </div>
        </div>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'berhasil'): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>Kategori baru berhasil ditambahkan!</div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'edit_berhasil'): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>Kategori berhasil diperbarui!</div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] === 'hapus_berhasil'): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>Kategori berhasil dihapus!</div>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kategori</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($query->num_rows > 0): ?>
                        <?php $no = 1; while($data = $query->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($data['name']); ?></td>
                            <td><?= htmlspecialchars($data['description'] ?? '-'); ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="edit.php?id=<?= $data['id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Edit</a>
                                    <a href="hapus.php?id=<?= $data['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus kategori ini?')"><i class="bi bi-trash"></i> Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted py-4"><i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data kategori.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <a href="../dashboard.php" class="btn-back"><i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard</a>
    </div>
    <div class="copyright">© 2026 FEBMart. All Rights Reserved.</div>
</div>

</body>
</html>