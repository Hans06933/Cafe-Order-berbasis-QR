<?php
session_start();
require_once "config/database.php";
require_once "config/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_id          = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);
    $nama_pelanggan    = trim($_POST['nama_pelanggan'] ?? '');
    $no_telepon        = trim($_POST['no_telepon'] ?? '');
    $catatan           = trim($_POST['catatan'] ?? '');
    $metode_pembayaran = $_POST['metode_pembayaran'] ?? 'kasir';
    $cart_data         = json_decode($_POST['cart_data'] ?? '[]', true);

    if ($table_id && !empty($nama_pelanggan) && !empty($cart_data)) {
        try {
            // Hitung total bayar
            $total_harga = 0;
            foreach ($cart_data as $item) {
                $total_harga += ($item['harga'] * $item['qty']);
            }

            // 1. Insert ke tabel orders
            $stmt = $pdo->prepare("
                INSERT INTO orders (table_id, nama_pelanggan, no_telepon, catatan, total, status, status_pembayaran, metode_pembayaran, created_at) 
                VALUES (?, ?, ?, ?, ?, 'pending', 'pending', ?, NOW())
            ");
            $stmt->execute([
                $table_id,
                $nama_pelanggan,
                $no_telepon !== '' ? $no_telepon : null,
                $catatan !== '' ? $catatan : null,
                $total_harga,
                $metode_pembayaran
            ]);

            $new_order_id = $pdo->lastInsertId();

            // 2. Insert detail rincian item ke order_details
            $stmt_detail = $pdo->prepare("
                INSERT INTO order_details (order_id, product_id, nama_produk, harga, qty, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            foreach ($cart_data as $item) {
                $subtotal = $item['harga'] * $item['qty'];
                $stmt_detail->execute([
                    $new_order_id,
                    $item['id'],
                    $item['nama'],
                    $item['harga'],
                    $item['qty'],
                    $subtotal
                ]);
            }

            // SIMPAN ID PESANAN KE SESSION
            $_SESSION['active_order_id'] = $new_order_id;

            // Redirect ke halaman utama
            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            die("Gagal menyimpan pesanan: " . $e->getMessage());
        }
    } else {
        header("Location: index.php");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}