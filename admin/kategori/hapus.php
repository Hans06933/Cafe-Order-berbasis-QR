<?php
ob_start();

require_once "../../includes/auth.php";
require_login();

// Cek role hanya jika fungsinya ada dan session terisi
if (function_exists('require_role')) {
    // require_role('admin'); // Un-comment ini jika role di database sudah dipastikan 'admin'
}

require_once "../../config/database.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: index.php");
    exit;
}

try {
    // 1. Cek produk terkait
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $jumlah_produk = (int) $stmt->fetchColumn();

    if ($jumlah_produk > 0) {
        echo "<script>
            alert('Kategori tidak dapat dihapus karena masih digunakan oleh {$jumlah_produk} produk.');
            window.location.href = 'index.php';
        </script>";
        exit;
    }

    // 2. Eksekusi Hapus Kategori
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: index.php");
    exit;

} catch (PDOException $e) {
    die("Terjadi kesalahan database: " . htmlspecialchars($e->getMessage()));
}