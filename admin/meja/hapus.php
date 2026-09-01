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
        $stmt = $pdo->prepare("DELETE FROM tables WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        die("Gagal menghapus meja: " . htmlspecialchars($e->getMessage()));
    }
}

header("Location: index.php");
exit;