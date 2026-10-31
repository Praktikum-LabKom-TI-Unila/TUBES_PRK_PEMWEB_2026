<?php
session_start();
if (!isset($_SESSION['user_id'])) { 
    header("Location: ../auth/login.php"); 
    exit; 
}

require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?status=hapus_berhasil");
    } else {
        header("Location: index.php?status=gagal_hapus");
    }
} else {
    header("Location: index.php");
}
?>