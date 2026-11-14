<?php
// Koneksi ke database MySQL menggunakan PDO
require_once __DIR__ . '/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS);

    // Pengaturan standar PDO supaya aman dari SQL Injection
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // Samakan zona waktu MySQL dengan WIB (+07:00)
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    die("Koneksi ke database gagal. Pastikan MySQL di XAMPP sudah nyala dan database '" . DB_NAME . "' sudah dibuat.");
}
