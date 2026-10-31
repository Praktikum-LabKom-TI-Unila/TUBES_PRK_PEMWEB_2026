<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

if (isset($_POST['simpan'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (empty($name)) {
        $error = "Nama kategori tidak boleh kosong!";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        
        if ($stmt->execute()) {
            header("Location: index.php?status=berhasil");
            exit;
        } else {
            $error = "Gagal menyimpan kategori: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kategori | FEBMart</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body { min-height: 100vh; margin: 0; background: #f3f4f6; font-family: Arial, sans-serif; padding: 35px 20px; }
        .main-container { width: 100%; max-width: 800px; margin: 0 auto; background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08); }
        .header-section { background: linear-gradient(145deg, #29245c, #5142a5); color: white; padding: 30px 35px; }
        .brand { font-size: 23px; font-weight: bold; margin-bottom: 25px; }
        .brand i { color: #ffad42; margin-right: 7px; }
        .header-section h2 { font-size: 25px; font-weight: bold; margin-bottom: 8px; }
        .header-section p { color: #d5d9e2; font-size: 14px; margin-bottom: 0; }
        .content-section { padding: 35px; }
        .btn-save { background: #ffad42; color: #29245c; border: none; border-radius: 7px; padding: 10px 20px; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-save:hover { background: #f49a25; color: #29245c; }
        .btn-back { background: #f5f4fb; color: #5142a5; border: 1px solid #ddd; border-radius: 7px; padding: 10px 20px; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center;}
        .btn-back:hover { background: #e9e6f7; color: #29245c; }
        .copyright { color: #999; font-size: 12px; text-align: center; padding: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header-section">
        <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
        <h2><i class="bi bi-tags me-2"></i>Tambah Kategori</h2>
        <p>Tambahkan kategori baru ke dalam sistem FEBMart.</p>
    </div>

    <div class="content-section">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i> <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Minuman Dingin" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Deskripsi (Opsional)</label>
                <textarea name="description" class="form-control" rows="3" placeholder="Keterangan tambahan mengenai kategori ini..."></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="index.php" class="btn-back"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                <button type="submit" name="simpan" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Kategori</button>
            </div>
        </form>
    </div>
    <div class="copyright">© 2026 FEBMart. All Rights Reserved.</div>
</div>

</body>
</html>