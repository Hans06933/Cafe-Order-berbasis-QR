<?php
require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";
require_once "../../config/config.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

// Fetch Header Order
$stmt = $pdo->prepare("SELECT o.*, t.nomor_meja 
                       FROM orders o 
                       LEFT JOIN tables t ON o.table_id = t.id 
                       WHERE o.id = ?");
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Fetch Items dari tabel order_details (langsung tanpa JOIN)
$stmt_items = $pdo->prepare("SELECT * FROM order_details WHERE order_id = ?");
$stmt_items->execute([$id]);
$items = $stmt_items->fetchAll();

$page_title = "Detail Pesanan " . $order['kode_pesanan'];
require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div style="max-width: 700px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Detail Pesanan: <?= htmlspecialchars($order['kode_pesanan']) ?></h2>
        <a href="index.php" style="color: #6b7280; text-decoration: none;">&larr; Kembali</a>
    </div>

    <div class="panel" style="margin-bottom: 20px;">
        <div class="panel-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <span style="color: #6b7280; font-size: 13px;">Pelanggan</span>
                    <p style="margin: 3px 0 0; font-weight: 600;"><?= htmlspecialchars($order['nama_pelanggan']) ?></p>
                </div>
                <div>
                    <span style="color: #6b7280; font-size: 13px;">No. Telepon</span>
                    <p style="margin: 3px 0 0; font-weight: 600;"><?= htmlspecialchars($order['no_telepon'] ?? '-') ?></p>
                </div>
                <div>
                    <span style="color: #6b7280; font-size: 13px;">Meja</span>
                    <p style="margin: 3px 0 0; font-weight: 600;"><?= htmlspecialchars($order['nomor_meja'] ?? 'Meja #' . $order['table_id']) ?></p>
                </div>
                <div>
                    <span style="color: #6b7280; font-size: 13px;">Status</span>
                    <p style="margin: 3px 0 0;"><span class="badge badge-info"><?= strtoupper(htmlspecialchars($order['status'])) ?></span></p>
                </div>
            </div>

            <?php if (!empty($order['catatan'])): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #f3f4f6;">
                    <span style="color: #6b7280; font-size: 13px;">Catatan Pesanan:</span>
                    <p style="margin: 3px 0 0; font-style: italic; color: #374151;"><?= htmlspecialchars($order['catatan']) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">Rincian Item Menu</h3>
        </div>
        <div class="panel-body">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Catatan Item</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($item['nama_produk']) ?></strong></td>
                            <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                            <td>x <?= $item['qty'] ?></td>
                            <td><small style="color: #6b7280;"><?= htmlspecialchars($item['catatan'] ?? '-') ?></small></td>
                            <td style="text-align: right;">Rp <?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" style="text-align: right; padding-top: 15px;">Total Keseluruhan:</th>
                        <th style="text-align: right; padding-top: 15px; font-size: 16px; color: #8b5e3c;">
                            Rp <?= number_format($order['total'], 0, ',', '.') ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>