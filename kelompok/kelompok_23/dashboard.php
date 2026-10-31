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
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3>Selamat Datang, <?= htmlspecialchars($_SESSION['name']); ?>!</h3>

                <p class="text-muted">
                    Anda berhasil login ke sistem manajemen FEBMart.
                </p>

                <p>
                    Username: <?= htmlspecialchars($_SESSION['username']); ?>
                </p>

                <p>
                    Role: <?= htmlspecialchars($_SESSION['role']); ?>
                </p>

                 <a href="auth/logout.php" class="btn btn-danger mt-3">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
                
            </div>
        </div>
    </div>

</body>
</html>