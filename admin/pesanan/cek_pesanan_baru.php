<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../config/database.php";

header('Content-Type: application/json');

$last_id = filter_input(INPUT_GET, 'last_id', FILTER_VALIDATE_INT) ?? 0;

try {
    // Jika last_id = 0, ambil ID pesanan paling baru saja sebagai acuan awal
    if ($last_id === 0) {
        $stmt = $pdo->query("SELECT id FROM orders ORDER BY id DESC LIMIT 1");
        $latest = $stmt->fetch(PDO_FETCH_ASSOC);
        echo json_encode([
            'status' => 'init',
            'order'  => ['id' => $latest['id'] ?? 0]
        ]);
        exit;
    }

    // Cek apakah ada order dengan ID lebih besar dari last_id
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id > ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$last_id]);
    $new_order = $stmt->fetch(PDO_FETCH_ASSOC);

    if ($new_order) {
        $total_nominal = $new_order['total'] ?? $new_order['total_harga'] ?? 0;

        echo json_encode([
            'status' => 'new_order',
            'order'  => [
                'id'             => $new_order['id'],
                'nama_pelanggan' => $new_order['nama_pelanggan'] ?? 'Pelanggan',
                'total'          => $total_nominal
            ]
        ]);
    } else {
        echo json_encode(['status' => 'no_change']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}