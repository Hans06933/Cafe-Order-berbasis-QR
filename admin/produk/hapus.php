<?php
require_once "../../includes/auth.php";
require_login();

require_once "../../config/database.php";
require_once "../../config/config.php";

$id = $_GET['id'] ?? null;

if ($id && ctype_digit($id)) {
    // 1. Ambil info gambar lama untuk dihapus dari folder
    $stmt = $pdo->prepare("SELECT gambar FROM products WHERE id = ?");
    $stmt->execute([(int)$id]);
    $product = $stmt->fetch();

    if ($product) {
        if (!empty($product['gambar'])) {
            $filePath = '../../assets/uploads/produk/' . $product['gambar'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // 2. Hapus data dari database
        $deleteStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $deleteStmt->execute([(int)$id]);
    }
}

header("Location: index.php");
exit;