<?php
session_start();
require_once "config/database.php";

$kode_pesanan = filter_input(INPUT_GET, 'kode', FILTER_SANITIZE_SPECIAL_CHARS);

if (!$kode_pesanan) {
    header("Location: index.php");
    exit;
}

// Fetch Data Pesanan
$stmt = $pdo->prepare("SELECT o.*, t.nomor_meja 
                       FROM orders o 
                       LEFT JOIN tables t ON o.table_id = t.id 
                       WHERE o.kode_pesanan = ?");
$stmt->execute([$kode_pesanan]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Fetch Items
$stmt_items = $pdo->prepare("SELECT * FROM order_details WHERE order_id = ?");
$stmt_items->execute([$order['id']]);
$items = $stmt_items->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f9fafb; margin: 0; padding: 20px; color: #1f2937; }
        .card { max-width: 450px; margin: 0 auto; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); text-align: center; }
        .success-icon { font-size: 45px; color: #10b981; margin-bottom: 10px; }
        .order-code { background: #f3f4f6; padding: 8px 12px; border-radius: 6px; font-family: monospace; font-size: 16px; font-weight: 700; margin: 10px 0; }
        .details-table { width: 100%; text-align: left; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .details-table th, .details-table td { padding: 8px 0; border-bottom: 1px solid #f3f4f6; }
    </style>
</head>
<body>

<div class="card">
    <i class="fa-solid fa-circle-check success-icon"></i>
    <h2 style="margin: 0; font-size: 20px;">Pesanan Terkirim!</h2>
    <p style="margin: 5px 0 15px; color: #6b7280; font-size: 13px;">Silakan melakukan pembayaran di kasir atau tunggu pelayanan kami.</p>

    <div class="order-code"><?= htmlspecialchars($order['kode_pesanan']) ?></div>

    <div style="text-align: left; font-size: 13px; margin-top: 15px; color: #374151;">
        <div><strong>Meja:</strong> <?= htmlspecialchars($order['nomor_meja']) ?></div>
        <div><strong>Atas Nama:</strong> <?= htmlspecialchars($order['nama_pelanggan']) ?></div>
        <div><strong>Status:</strong> <span style="color: #d97706; font-weight: 700;"><?= strtoupper(htmlspecialchars($order['status'])) ?></span></div>
    </div>

    <table class="details-table">
        <thead>
            <tr>
                <th>Menu</th>
                <th>Qty</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nama_produk']) ?></td>
                    <td>x<?= $item['qty'] ?></td>
                    <td style="text-align: right;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2" style="padding-top: 10px;">Total Bayar:</th>
                <th style="text-align: right; padding-top: 10px; font-size: 15px; color: #8b5e3c;">
                    Rp <?= number_format($order['total'], 0, ',', '.') ?>
                </th>
            </tr>
        </tfoot>
    </table>

    <a href="index.php" style="display: block; margin-top: 20px; padding: 10px; background: #8b5e3c; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px;">
        Kembali ke Menu Utama
    </a>
</div>

<script>
// Bersihkan LocalStorage cart setelah pesanan berhasil
localStorage.removeItem('cafe_cart');
</script>
</body>
</html>