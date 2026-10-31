<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Inisialisasi tabel transaksi jika belum ada
$conn->query("
    CREATE TABLE IF NOT EXISTS `transactions` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
        `user_id` INT NULL,
        `customer_name` VARCHAR(100) NOT NULL DEFAULT 'Pelanggan Umum',
        `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        `change_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$conn->query("
    CREATE TABLE IF NOT EXISTS `transaction_details` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `transaction_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `product_name` VARCHAR(255) NOT NULL,
        `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
        `qty` INT NOT NULL DEFAULT 1,
        `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$pesan_sukses = $_SESSION['flash_success'] ?? '';
$pesan_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($product_id > 0) {
        $stmt = $conn->prepare("SELECT id, sku, name, price, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $item = $res->fetch_assoc();

        if ($item) {
            $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['qty'] : 0;
            if ($item['stock'] > $current_qty) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['qty'] += 1;
                } else {
                    $_SESSION['cart'][$product_id] = [
                        'id' => $item['id'],
                        'sku' => $item['sku'],
                        'name' => $item['name'],
                        'price' => (float)$item['price'],
                        'stock' => (int)$item['stock'],
                        'qty' => 1
                    ];
                }
                $_SESSION['flash_success'] = "Produk berhasil ditambahkan ke keranjang.";
            } else {
                $_SESSION['flash_error'] = "Stok produk tidak mencukupi!";
            }
        }
    }
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $max_stock = $res ? (int)$res['stock'] : $_SESSION['cart'][$product_id]['stock'];

        if ($qty <= 0) {
            $_SESSION['flash_error'] = "Jumlah minimal 1.";
        } elseif ($qty > $max_stock) {
            $_SESSION['flash_error'] = "Jumlah melebihi stok yang tersedia (Stok: $max_stock)!";
        } else {
            $_SESSION['cart'][$product_id]['qty'] = $qty;
            $_SESSION['flash_success'] = "Jumlah produk berhasil diperbarui.";
        }
    }
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        $nama_item = $_SESSION['cart'][$product_id]['name'];
        unset($_SESSION['cart'][$product_id]);
        $_SESSION['flash_success'] = "Produk '$nama_item' berhasil dihapus dari keranjang.";
    }
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'kosongkan') {
    $_SESSION['cart'] = [];
    $_SESSION['flash_success'] = "Keranjang belanja berhasil dikosongkan.";
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (empty($_SESSION['cart'])) {
        $_SESSION['flash_error'] = "Keranjang belanja masih kosong!";
        header("Location: index.php");
        exit;
    }

    $customer_name = !empty($_POST['customer_name']) ? trim($_POST['customer_name']) : 'Pelanggan Umum';
    $paid_amount = isset($_POST['nominal_bayar']) ? (float)$_POST['nominal_bayar'] : 0.0;
    $user_id = $_SESSION['user_id'] ?? null;

    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += $item['price'] * $item['qty'];
    }

    // Validasi nominal pembayaran tidak boleh kurang dari total belanja
    if ($paid_amount < $total_amount) {
        $kurang = $total_amount - $paid_amount;
        $_SESSION['flash_error'] = "Nominal pembayaran kurang Rp " . number_format($kurang, 0, ',', '.') . "!";
        header("Location: index.php");
        exit;
    }

    $change_amount = $paid_amount - $total_amount;
    $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

    $conn->begin_transaction();
    try {
        // Validasi ketersediaan stok terbaru dari database untuk setiap produk
        $stmt_check_stock = $conn->prepare("SELECT id, name, stock FROM products WHERE id = ? FOR UPDATE");
        $stmt_potong_stok = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

        foreach ($_SESSION['cart'] as $item) {
            $stmt_check_stock->bind_param("i", $item['id']);
            $stmt_check_stock->execute();
            $db_product = $stmt_check_stock->get_result()->fetch_assoc();

            if (!$db_product) {
                throw new Exception("Produk '{$item['name']}' tidak ditemukan dalam database.");
            }

            if ($db_product['stock'] < $item['qty']) {
                throw new Exception("Stok untuk '{$db_product['name']}' tidak mencukupi! Sisa stok: {$db_product['stock']}, diminta: {$item['qty']}.");
            }

            // Pemotongan stok otomatis
            $stmt_potong_stok->bind_param("ii", $item['qty'], $item['id']);
            if (!$stmt_potong_stok->execute()) {
                throw new Exception("Gagal memotong stok untuk produk '{$db_product['name']}': " . $conn->error);
            }
        }

        // Simpan data transaksi utama
        $stmt_trans = $conn->prepare("
            INSERT INTO transactions (invoice_number, user_id, customer_name, total_amount, paid_amount, change_amount)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt_trans->bind_param("sisddd", $invoice_number, $user_id, $customer_name, $total_amount, $paid_amount, $change_amount);
        if (!$stmt_trans->execute()) {
            throw new Exception("Gagal menyimpan data transaksi: " . $conn->error);
        }
        $transaction_id = $conn->insert_id;

        // Simpan detail item transaksi
        $stmt_detail = $conn->prepare("
            INSERT INTO transaction_details (transaction_id, product_id, product_name, price, qty, subtotal)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($_SESSION['cart'] as $item) {
            $subtotal = $item['price'] * $item['qty'];
            $stmt_detail->bind_param("iisdid", $transaction_id, $item['id'], $item['name'], $item['price'], $item['qty'], $subtotal);
            if (!$stmt_detail->execute()) {
                throw new Exception("Gagal menyimpan detail transaksi: " . $conn->error);
            }
        }

        $conn->commit();

        $_SESSION['cart'] = [];
        $_SESSION['flash_success'] = "Transaksi $invoice_number berhasil diproses! Stok berhasil dipotong. Kembalian: Rp " . number_format($change_amount, 0, ',', '.') . " <a href='struk.php?invoice=$invoice_number' target='_blank' class='btn btn-sm btn-dark ms-2 fw-bold text-white'><i class='bi bi-printer me-1'></i>Cetak Struk</a>";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_error'] = "Transaksi dibatalkan: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
}

$search = isset($_GET['cari']) ? trim($_GET['cari']) : '';
if ($search !== '') {
    $stmt = $conn->prepare("
        SELECT products.*, categories.name AS nama_kategori 
        FROM products 
        LEFT JOIN categories ON products.category_id = categories.id 
        WHERE products.name LIKE ? OR products.sku LIKE ?
        ORDER BY products.name ASC
    ");
    $param = "%$search%";
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $produk = $stmt->get_result();
} else {
    $produk = $conn->query("
        SELECT products.*, categories.name AS nama_kategori 
        FROM products 
        LEFT JOIN categories ON products.category_id = categories.id 
        ORDER BY products.name ASC
    ");
}
$total_subtotal = 0;
$total_qty = 0;
foreach ($_SESSION['cart'] as $cart_item) {
    $total_subtotal += $cart_item['price'] * $cart_item['qty'];
    $total_qty += $cart_item['qty'];
}
$total_bayar = $total_subtotal;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Kasir | FEBMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
            padding: 35px 20px;
        }

        .main-container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            overflow: hidden;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .header-section {
            padding: 30px 35px;
            color: #fff;
            background: linear-gradient(145deg, #29245c, #5142a5);
        }

        .brand {
            margin-bottom: 25px;
            font-size: 23px;
            font-weight: bold;
        }

        .brand i {
            margin-right: 7px;
            color: #ffad42;
        }

        .header-section h2 {
            margin-bottom: 8px;
            font-size: 25px;
            font-weight: bold;
        }

        .header-section p {
            margin-bottom: 0;
            color: #d5d9e2;
            font-size: 14px;
        }

        .content-section {
            padding: 35px;
        }

        .section-card {
            height: 100%;
            padding: 24px;
            border: 1px solid #e8e7f0;
            border-radius: 12px;
            background: #fff;
        }

        .section-title {
            margin-bottom: 5px;
            color: #29245c;
            font-size: 19px;
            font-weight: bold;
        }

        .section-description {
            margin-bottom: 22px;
            color: #888;
            font-size: 13px;
        }

        .empty-state {
            min-height: 250px;
            padding: 60px 20px;
            border: 1px dashed #d8d5e8;
            border-radius: 10px;
            color: #888;
            text-align: center;
        }

        .empty-state i {
            display: block;
            margin-bottom: 12px;
            color: #5142a5;
            font-size: 42px;
        }

        .summary-box {
            margin-bottom: 20px;
            padding: 18px;
            border-radius: 10px;
            background: #f5f4fb;
        }

        .summary-label {
            color: #888;
            font-size: 13px;
        }

        .summary-value {
            margin-top: 5px;
            color: #29245c;
            font-size: 22px;
            font-weight: bold;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            padding: 10px 18px;
            border: 1px solid #ddd;
            border-radius: 7px;
            color: #5142a5;
            background: #f5f4fb;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
        }

        .btn-back:hover {
            color: #29245c;
            background: #e9e6f7;
        }

        .catalog-container {
            max-height: 520px;
            overflow-y: auto;
            padding-right: 5px;
        }

        .product-card {
            border: 1px solid #e8e7f0;
            border-radius: 10px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            background: #fff;
            transition: all 0.2s;
        }

        .product-card:hover {
            border-color: #5142a5;
            background: #fbfbfe;
        }

        .product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            background: #f0f0f4;
        }

        .product-info {
            flex: 1;
            min-width: 0;
        }

        .product-name {
            font-weight: bold;
            font-size: 14px;
            color: #29245c;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-meta {
            font-size: 12px;
            color: #777;
        }

        .product-price {
            font-weight: bold;
            font-size: 14px;
            color: #5142a5;
        }

        .btn-add-cart {
            background: #29245c;
            color: #fff;
            border: none;
            padding: 7px 14px;
            font-size: 12px;
            border-radius: 6px;
            font-weight: 600;
        }

        .btn-add-cart:hover:not(:disabled) {
            background: #ffad42;
            color: #29245c;
        }

        .copyright {
            padding: 20px;
            border-top: 1px solid #eee;
            color: #999;
            font-size: 12px;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="main-container">
        <header class="header-section">
            <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
            <h2><i class="bi bi-cash-register me-2"></i>Transaksi Kasir</h2>
            <p>Kelola transaksi penjualan FEBMart melalui halaman kasir.</p>
        </header>

        <section class="content-section">
            <?php if ($pesan_sukses): ?>
                <div class="alert alert-success alert-dismissible fade show mb-3 d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                    <div><i class="bi bi-check-circle me-1"></i><?= $pesan_sukses; ?></div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($pesan_error): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($pesan_error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="section-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h3 class="section-title">Katalog Produk</h3>
                                <p class="section-description mb-0">Pilih produk yang ingin ditambahkan ke transaksi.</p>
                            </div>
                        </div>

                        <form method="GET" class="mb-3">
                            <div class="input-group">
                                <input type="text" name="cari" class="form-control" placeholder="Cari nama produk atau SKU..." value="<?= htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
                                <?php if ($search !== ''): ?>
                                    <a href="index.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i></a>
                                <?php endif; ?>
                            </div>
                        </form>

                        <div class="catalog-container">
                            <?php if ($produk && $produk->num_rows > 0): ?>
                                <?php while ($item = $produk->fetch_assoc()): ?>
                                    <div class="product-card">
                                        <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../assets/img/' . $item['image'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($item['image']); ?>" class="product-img" alt="<?= htmlspecialchars($item['name']); ?>">
                                        <?php else: ?>
                                            <div class="product-img d-flex align-items-center justify-content-center text-muted">
                                                <i class="bi bi-image"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-info">
                                            <div class="product-name" title="<?= htmlspecialchars($item['name']); ?>">
                                                <?= htmlspecialchars($item['name']); ?>
                                            </div>
                                            <div class="product-meta">
                                                <span class="badge bg-light text-dark border"><?= htmlspecialchars($item['sku']); ?></span>
                                                <span class="ms-1">Stok: <strong><?= (int)$item['stock']; ?></strong></span>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <div class="product-price mb-1">
                                                Rp <?= number_format($item['price'], 0, ',', '.'); ?>
                                            </div>
                                            <form method="POST" action="index.php" class="m-0">
                                                <input type="hidden" name="action" value="tambah">
                                                <input type="hidden" name="product_id" value="<?= $item['id']; ?>">
                                                <button type="submit" class="btn-add-cart" <?= $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                                    <i class="bi bi-cart-plus me-1"></i><?= $item['stock'] <= 0 ? 'Habis' : 'Pilih'; ?>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="bi bi-box-seam"></i>
                                    <div>Produk tidak ditemukan.</div>
                                    <small class="text-muted">Tidak ada produk yang sesuai dengan kriteria pencarian.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="section-card">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h3 class="section-title mb-0">Keranjang Belanja</h3>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary"><?= count($_SESSION['cart']); ?> Item</span>
                                <?php if (!empty($_SESSION['cart'])): ?>
                                    <form method="POST" action="index.php" class="m-0" onsubmit="return confirm('Kosongkan semua item di keranjang?');">
                                        <input type="hidden" name="action" value="kosongkan">
                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px;">
                                            <i class="bi bi-trash me-1"></i>Kosongkan
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="section-description">Daftar barang yang dipilih untuk transaksi.</p>
                        <?php if (empty($_SESSION['cart'])): ?>
                            <div class="empty-state mb-4">
                                <i class="bi bi-cart"></i>
                                <div>Keranjang masih kosong.</div>
                                <small>Tambahkan produk dari katalog.</small>
                            </div>
                        <?php else: ?>
                            <div class="cart-items-container mb-3" style="max-height: 320px; overflow-y: auto;">
                                <?php foreach ($_SESSION['cart'] as $id => $cart_item): ?>
                                    <div class="d-flex justify-content-between align-items-center p-2 mb-2 border rounded bg-light">
                                        <div style="min-width: 0; flex: 1;">
                                            <div class="fw-bold text-truncate" style="font-size: 13px; color: #29245c;">
                                                <?= htmlspecialchars($cart_item['name']); ?>
                                            </div>
                                            <div class="text-muted" style="font-size: 11px;">
                                                <?= htmlspecialchars($cart_item['sku']); ?> | Rp <?= number_format($cart_item['price'], 0, ',', '.'); ?> × <?= $cart_item['qty']; ?> = <strong class="text-dark">Rp <?= number_format($cart_item['price'] * $cart_item['qty'], 0, ',', '.'); ?></strong>
                                            </div>
                                        </div>
                                        <div class="ms-2 d-flex align-items-center gap-1">
                                            <form method="POST" action="index.php" class="d-flex align-items-center gap-1 m-0">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="product_id" value="<?= $id; ?>">
                                                <input type="number" name="qty" value="<?= (int)$cart_item['qty']; ?>" min="1" max="<?= (int)$cart_item['stock']; ?>" class="form-control form-control-sm text-center px-1" style="width: 55px; height: 30px;">
                                                <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-2" style="height: 30px;" title="Update Jumlah">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="index.php" class="m-0">
                                                <input type="hidden" name="action" value="hapus">
                                                <input type="hidden" name="product_id" value="<?= $id; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" style="height: 30px;" title="Hapus item">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="summary-box">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="summary-label">Total Item / Kuantitas</span>
                                <strong style="font-size: 13px; color: #29245c;"><?= count($_SESSION['cart']); ?> jenis (<?= $total_qty; ?> pcs)</strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2 pb-2 border-bottom">
                                <span class="summary-label">Subtotal</span>
                                <strong style="font-size: 14px; color: #29245c;">Rp <?= number_format($total_subtotal, 0, ',', '.'); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="summary-label fw-bold" style="color: #29245c;">Total Bayar</div>
                                <div class="summary-value" id="display-total">Rp <?= number_format($total_bayar, 0, ',', '.'); ?></div>
                            </div>
                        </div>

                        <form method="POST" action="index.php" id="form-transaksi">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="total_bayar" id="input-total-bayar" value="<?= (int)$total_bayar; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 13px; color: #29245c;">Nama Pelanggan</label>
                                <input type="text" name="customer_name" class="form-control form-control-sm" placeholder="Nama Pelanggan (opsional)" value="Pelanggan Umum" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 13px; color: #29245c;">Nominal Pembayaran (Cash) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light fw-bold" style="font-size: 13px;">Rp</span>
                                    <input type="number" name="nominal_bayar" id="nominal-bayar" class="form-control" placeholder="0" min="0" step="1" required <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                </div>
                                <div class="mt-2 d-flex flex-wrap gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" onclick="setNominal(<?= (int)$total_bayar; ?>)" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>Uang Pas</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" onclick="setNominal(10000)" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>10.000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" onclick="setNominal(20000)" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>20.000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" onclick="setNominal(50000)" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>50.000</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size: 11px;" onclick="setNominal(100000)" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>100.000</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 11px;" onclick="setNominal(0)" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>Reset</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size: 13px; color: #29245c;">Kembalian</label>
                                <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                    <span class="text-muted" style="font-size: 13px;">Uang Kembali:</span>
                                    <span class="fw-bold text-success" id="display-kembalian" style="font-size: 16px;">Rp 0</span>
                                </div>
                            </div>

                            <button type="submit" id="btn-submit-transaksi" class="btn w-100 py-2 fw-bold" style="background: #29245c; color: white; border-radius: 8px;" <?= empty($_SESSION['cart']) ? 'disabled' : ''; ?>>
                                <i class="bi bi-check-circle me-1"></i>Selesaikan Transaksi
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <a href="../dashboard.php" class="btn-back mt-4">
                <i class="bi bi-arrow-left me-1"></i>Kembali ke Dashboard
            </a>
        </section>

        <footer class="copyright">© 2026 FEBMart. All Rights Reserved.</footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const inputBayar = document.getElementById('nominal-bayar');
        const displayKembalian = document.getElementById('display-kembalian');
        const totalBayar = <?= (int)$total_bayar; ?>;
        const btnSubmit = document.getElementById('btn-submit-transaksi');

        function hitungKembalian() {
            if (!inputBayar || !displayKembalian) return;
            const bayar = parseFloat(inputBayar.value) || 0;
            const kembalian = bayar - totalBayar;

            if (totalBayar === 0) {
                displayKembalian.textContent = 'Rp 0';
                displayKembalian.className = 'fw-bold text-muted';
                return;
            }

            if (kembalian >= 0) {
                displayKembalian.textContent = 'Rp ' + Math.floor(kembalian).toLocaleString('id-ID');
                displayKembalian.className = 'fw-bold text-success';
                if (btnSubmit) btnSubmit.disabled = false;
            } else {
                displayKembalian.textContent = 'Kurang Rp ' + Math.floor(Math.abs(kembalian)).toLocaleString('id-ID');
                displayKembalian.className = 'fw-bold text-danger';
                if (btnSubmit) btnSubmit.disabled = true;
            }
        }

        function setNominal(amount) {
            if (!inputBayar) return;
            inputBayar.value = amount > 0 ? amount : '';
            hitungKembalian();
            inputBayar.focus();
        }

        if (inputBayar) {
            inputBayar.addEventListener('input', hitungKembalian);
            hitungKembalian();
        }
    </script>
</body>
</html>
