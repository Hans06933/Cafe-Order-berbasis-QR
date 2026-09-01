<?php
ob_start();

require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        $pdo->beginTransaction();

        // 1. Hapus rincian item (Disesuaikan dari order_items ke order_details)
        $stmt1 = $pdo->prepare("DELETE FROM order_details WHERE order_id = ?");
        $stmt1->execute([$id]);

        // 2. Hapus header order
        $stmt2 = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt2->execute([$id]);

        $pdo->commit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Gagal menghapus pesanan: " . htmlspecialchars($e->getMessage()));
    }
}

header("Location: index.php");
exit;