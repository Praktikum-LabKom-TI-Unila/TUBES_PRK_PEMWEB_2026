<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = $_GET['id'];

$query = $conn->query("SELECT * FROM categories WHERE id = '$id'");
$data = $query->fetch_assoc();

if (!$data) {
    die("Kategori tidak ditemukan!");
}

$error = '';

if (isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);

    if (empty($name)) {
        $error = "Nama kategori tidak boleh kosong!";
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE id=?");
        $stmt->bind_param("ssi", $name, $description, $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?status=edit_berhasil");
            exit;
        } else {
            $error = "Gagal mengupdate kategori: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori | FEBMart</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; }
        body { min-height: 100vh; margin: 0; background: #f3f4f6; font-family: Arial, sans-serif; padding: 35px 20px; }
        .main-container { width: 100%; max-width: 800px; margin: 0 auto; background: white; border-radius: 14px; overflow: hidden; box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08); }
        
        .header-section { background: linear-gradient(145deg, #29245c, #5142a5); color: white; padding: 30px 35px; }
        .brand { font-size: 23px; font-weight: bold; margin-bottom: 25px; }
        .brand i { color: #ffad42; margin-right: 7px; }
        .header-section h2 { font-size: 25px; font-weight: bold; margin-bottom: 8px; }
        .header-section p { color: #d5d9e2; font-size: 14px; margin-bottom: 0; }
        
        .content-section { padding: 35px; }
        
        .btn-save { background: #ffad42; color: #29245c; border: none; border-radius: 7px; padding: 10px 20px; font-size: 14px; font-weight: bold; transition: 0.2s; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-save:hover { background: #f49a25; color: #29245c; }
        
        .btn-back { background: #f5f4fb; color: #5142a5; border: 1px solid #ddd; border-radius: 7px; padding: 10px 20px; font-size: 14px; font-weight: bold; text-decoration: none; transition: 0.2s; display: inline-flex; align-items: center;}
        .btn-back:hover { background: #e9e6f7; color: #29245c; }
        
        .copyright { color: #999; font-size: 12px; text-align: center; padding: 20px; border-top: 1px solid #eee; }
    </style>
</head>
<body>

<div class="main-container">
    <div class="header-section">
        <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
        <h2><i class="bi bi-pencil-square me-2"></i>Edit Kategori</h2>
        <p>Perbarui informasi kategori produk FEBMart.</p>
    </div>

    <div class="content-section">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i> <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($data['name']); ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Deskripsi (Opsional)</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($data['description'] ?? ''); ?></textarea>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="index.php" class="btn-back">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <button type="submit" name="update" class="btn-save">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

    <div class="copyright">© 2026 FEBMart. All Rights Reserved.</div>
</div>

</body>
</html>