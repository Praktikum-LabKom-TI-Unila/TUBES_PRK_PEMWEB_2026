<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | FEBMart</title>

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
            max-width: 1100px;
            margin: 0 auto;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        }

        .header-section {
            background: linear-gradient(145deg, #29245c, #5142a5);
            color: white;
            padding: 30px 35px;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .brand i {
            color: #ffad42;
            margin-right: 7px;
        }

        .content-section {
            padding: 35px;
        }

        .menu-card {
            border: 1px solid #e8e7f0;
            border-radius: 12px;
            padding: 24px;
            background: #fff;
            transition: all 0.2s ease-in-out;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: #5142a5;
        }

        .menu-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 16px;
        }

        .icon-pos { background: #eeeaff; color: #5142a5; }
        .icon-produk { background: #e0f2fe; color: #0284c7; }
        .icon-kategori { background: #fef3c7; color: #d97706; }
        .icon-users { background: #dcfce7; color: #16a34a; }

        .btn-open {
            background: #29245c;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
            border-radius: 8px;
            padding: 8px 16px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .btn-open:hover {
            background: #ffad42;
            color: #29245c;
        }

        .copyright {
            color: #999;
            font-size: 12px;
            text-align: center;
            padding: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>

    <main class="main-container">
        <header class="header-section">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="brand"><i class="bi bi-cart3"></i>FEBMart</div>
                <a href="auth/logout.php" class="btn btn-outline-light btn-sm fw-bold">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
            <h2 class="fw-bold mb-2">Selamat Datang, <?= htmlspecialchars($_SESSION['name']); ?>!</h2>
            <p class="text-white-50 mb-0">Role: <span class="badge bg-warning text-dark text-uppercase"><?= htmlspecialchars($_SESSION['role']); ?></span> | Username: <?= htmlspecialchars($_SESSION['username']); ?></p>
        </header>

        <section class="content-section">
            <div class="mb-4">
                <h4 class="fw-bold text-dark mb-1">Menu Utama</h4>
                <p class="text-muted small">Pilih menu operasional sistem FEBMart di bawah ini.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="menu-card border-primary">
                        <div>
                            <div class="menu-icon icon-pos">
                                <i class="bi bi-cash-register"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Transaksi Kasir</h5>
                            <p class="text-muted small mb-3">Layanan kasir POS, katalog produk, dan perhitungan struk.</p>
                        </div>
                        <a href="transaksi/index.php" class="btn-open">
                            Buka Kasir <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="menu-card">
                        <div>
                            <div class="menu-icon icon-produk">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Data Produk</h5>
                            <p class="text-muted small mb-3">Kelola data produk, harga, stok, dan gambar item.</p>
                        </div>
                        <a href="produk/index.php" class="btn-open">
                            Kelola Produk <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="menu-card">
                        <div>
                            <div class="menu-icon icon-kategori">
                                <i class="bi bi-tags"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Kategori Produk</h5>
                            <p class="text-muted small mb-3">Pengelompokan jenis dan kategori barang toko.</p>
                        </div>
                        <a href="kategori/index.php" class="btn-open">
                            Kelola Kategori <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="menu-card">
                        <div>
                            <div class="menu-icon icon-users">
                                <i class="bi bi-people"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">Pengguna</h5>
                            <p class="text-muted small mb-3">Manajemen akun administrator dan kasir FEBMart.</p>
                        </div>
                        <a href="users.php" class="btn-open">
                            Kelola Pengguna <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <footer class="copyright">© 2026 FEBMart. All Rights Reserved.</footer>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>