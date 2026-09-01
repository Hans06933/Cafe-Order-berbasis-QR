<?php
session_start();
require_once "config/database.php";
require_once "config/config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$table_id          = filter_input(INPUT_POST, 'table_id', FILTER_VALIDATE_INT);
$nama_pelanggan    = trim($_POST['nama_pelanggan'] ?? '');
$no_telepon        = trim($_POST['no_telepon'] ?? '');
$catatan           = trim($_POST['catatan'] ?? '');
$metode_pembayaran = trim($_POST['metode_pembayaran'] ?? 'kasir'); // Opsi: 'kasir' atau 'qris'
$cart_raw          = $_POST['cart_data'] ?? '';

$cart = json_decode($cart_raw, true);

if (!$table_id || empty($nama_pelanggan) || empty($cart)) {
    die("Data pesanan tidak valid. Sila periksa kembali.");
}

try {
    $pdo->beginTransaction();

    // 1. Hitung total bayar
    $total_bayar = 0;
    foreach ($cart as $item) {
        $total_bayar += $item['harga'] * $item['qty'];
    }

    // 2. Generate Kode Pesanan unik (Contoh: ORD-20260901-X8Y)
    $kode_pesanan = "ORD-" . date('Ymd') . "-" . strtoupper(substr(uniqid(), -4));

    // 3. Simpan ke tabel `orders` (Termasuk kolom metode_pembayaran dan status_pembayaran)
    $stmt_order = $pdo->prepare("INSERT INTO orders 
        (kode_pesanan, table_id, nama_pelanggan, no_telepon, total, status, catatan, metode_pembayaran, status_pembayaran) 
        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, 'pending')");
        
    $stmt_order->execute([
        $kode_pesanan, 
        $table_id, 
        $nama_pelanggan, 
        $no_telepon, 
        $total_bayar, 
        $catatan, 
        $metode_pembayaran
    ]);
    
    $order_id = $pdo->lastInsertId();

    // 4. Simpan rincian ke tabel `order_details`
    $stmt_detail = $pdo->prepare("INSERT INTO order_details 
        (order_id, product_id, nama_produk, harga, qty, subtotal, catatan) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($cart as $item) {
        $subtotal = $item['harga'] * $item['qty'];
        $item_note = $item['catatan'] ?? NULL;
        $stmt_detail->execute([$order_id, $item['id'], $item['nama'], $item['harga'], $item['qty'], $subtotal, $item_note]);
    }

    // 5. Commit Transaksi DB
    $pdo->commit();

    // Redirect ke halaman sukses
    header("Location: sukses.php?kode=" . urlencode($kode_pesanan));
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Terjadi kesalahan saat memproses pesanan: " . htmlspecialchars($e->getMessage()));
}