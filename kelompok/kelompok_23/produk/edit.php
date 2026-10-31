<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../auth/login.php"); exit; }
require_once __DIR__ . '/../config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$id = $_GET['id'];

$query = $conn->query("SELECT * FROM products WHERE id = '$id'");
$data = $query->fetch_assoc();

if (!$data) {
    die("Produk tidak ditemukan!");
}

$kategori = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$error = '';

if (isset($_POST['update'])) {
    $category_id = $_POST['category_id'];
    $sku         = trim($_POST['sku']);
    $name        = trim($_POST['name']);
    $price       = $_POST['price'];
    $stock       = $_POST['stock'];
    $min_stock   = $_POST['min_stock'];
    
    $image = $_FILES['image']['name'];
    $tmp   = $_FILES['image']['tmp_name'];
    
    if ($image != "") {
        if (file_exists("../assets/img/" . $data['image'])) {
            unlink("../assets/img/" . $data['image']);
        }
        move_uploaded_file($tmp, "../assets/img/" . $image);
        $update_gambar = ", image='$image'";
    } else {
        $update_gambar = "";
    }

    if (empty($category_id) || empty($sku) || empty($name) || empty($price)) {
        $error = "Semua kolom bertanda (*) wajib diisi!";
    } else {
        $stmt = $conn->prepare("UPDATE products SET category_id=?, sku=?, name=?, price=?, stock=?, min_stock=? $update_gambar WHERE id=?");
        $stmt->bind_param("issdiis", $category_id, $sku, $name, $price, $stock, $min_stock, $id);
        
        if ($stmt->execute()) {
            header("Location: index.php?status=edit_berhasil");
            exit;
        } else {
            $error = "Gagal mengupdate produk: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Produk | FEBMart</title>
    
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
        <h2><i class="bi bi-pencil-square me-2"></i>Edit Produk</h2>
        <p>Perbarui informasi produk FEBMart.</p>
    </div>

    <div class="content-section">
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-1"></i> <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <?php while($kat = $kategori->fetch_assoc()): ?>
                            <option value="<?= $kat['id']; ?>" <?= ($kat['id'] == $data['category_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($kat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">SKU <span class="text-danger">*</span></label>
                    <input type="text" name="sku" class="form-control" value="<?= htmlspecialchars($data['sku']); ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Nama Produk <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($data['name']); ?>" required>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Harga <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" value="<?= $data['price']; ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Stok <span class="text-danger">*</span></label>
                    <input type="number" name="stock" class="form-control" value="<?= $data['stock']; ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Minimal Stok</label>
                    <input type="number" name="min_stock" class="form-control" value="<?= $data['min_stock']; ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold">Gambar Saat Ini</label><br>
                <?php if($data['image']): ?>
                    <img src="../assets/img/<?= $data['image']; ?>" width="100" style="border-radius: 8px; margin-bottom: 10px;">
                <?php else: ?>
                    <p class="text-muted">Belum ada gambar.</p>
                <?php endif; ?>
                
                <label class="form-label fw-bold mt-2">Ganti Gambar (Opsional)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="text-muted">Biarkan kosong jika tidak ingin mengganti gambar.</small>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                <a href="index.php" class="btn-back"><i class="bi bi-arrow-left me-1"></i> Kembali</a>
                <button type="submit" name="update" class="btn-save"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
            </div>
        </form>
    </div>
    <div class="copyright">© 2026 FEBMart. All Rights Reserved.</div>
</div>

</body>
</html>