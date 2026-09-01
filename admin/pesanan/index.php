<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";
require_once "../../config/config.php";

// ==========================================
// HANDLE UPDATE STATUS PESANAN & PEMBAYARAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

    if ($order_id) {
        try {
            // Update Status Pesanan
            if (isset($_POST['update_status'])) {
                $status = strtolower(trim($_POST['status'] ?? ''));
                $allowed_status = ['pending', 'diproses', 'siap', 'selesai', 'batal', 'dibatalkan'];
                
                if (in_array($status, $allowed_status)) {
                    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $order_id]);
                    $_SESSION['flash_success'] = "Status pesanan #" . $order_id . " berhasil diperbarui!";
                }
            }

            // Update Status Pembayaran
            if (isset($_POST['update_pembayaran'])) {
                $status_pembayaran = strtolower(trim($_POST['status_pembayaran'] ?? ''));
                $allowed_pembayaran = ['pending', 'lunas', 'batal', 'failed', 'success'];

                if (in_array($status_pembayaran, $allowed_pembayaran)) {
                    $stmt = $pdo->prepare("UPDATE orders SET status_pembayaran = ? WHERE id = ?");
                    $stmt->execute([$status_pembayaran, $order_id]);
                    $_SESSION['flash_success'] = "Status pembayaran pesanan #" . $order_id . " berhasil diperbarui!";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "Error Database: " . $e->getMessage();
        }
    }

    header("Location: index.php");
    exit;
}

// Fetch Data Pesanan + Join Meja & Ambil Daftar Menu via GROUP_CONCAT
try {
    $query = "SELECT o.*, t.nomor_meja, 
               GROUP_CONCAT(CONCAT(od.qty, 'x ', od.nama_produk) SEPARATOR ', ') AS daftar_menu 
              FROM orders o 
              LEFT JOIN tables t ON o.table_id = t.id 
              LEFT JOIN order_details od ON o.id = od.order_id
              GROUP BY o.id
              ORDER BY o.id DESC";
    $stmt = $pdo->query($query);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error = "Gagal memuat data: " . $e->getMessage();
}

$page_title = "Kelola Pesanan";
require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<!-- KUNCI UTAMA: Wajib dibungkus dengan class .main-wrapper agar tidak tertutup sidebar -->
<div class="main-wrapper">
    <div class="content">
        <!-- HEADER HALAMAN -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 15px; flex-wrap: wrap;">
            <div>
                <h2 style="margin: 0 0 5px; font-size: 20px;">Daftar Pesanan</h2>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Kelola status pesanan dan status pembayaran pelanggan secara real-time.</p>
            </div>
        </div>

        <!-- ALERT NOTIFIKASI -->
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div style="padding: 12px 16px; background: #d1fae5; color: #065f46; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i>
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error']) || isset($error)): ?>
            <?php 
                $err_msg = $_SESSION['flash_error'] ?? $error; 
                if (isset($_SESSION['flash_error'])) unset($_SESSION['flash_error']); 
            ?>
            <div style="padding: 12px 16px; background: #fee2e2; color: #dc2626; border-radius: 8px; margin-bottom: 20px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= htmlspecialchars($err_msg) ?>
            </div>
        <?php endif; ?>

        <!-- TABEL PESANAN -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Semua Transaksi</h3>
                <span style="color: #6b7280; font-size: 12px;"><?= count($orders) ?> Pesanan</span>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="min-width: 120px;">Pelanggan</th>
                                <th style="min-width: 200px;">Menu Dipesan</th>
                                <th style="min-width: 100px;">Total</th>
                                <th style="min-width: 140px;">Status Pesanan</th>
                                <th style="min-width: 140px;">Pembayaran</th>
                                <th style="min-width: 120px;">Waktu</th>
                                <th style="text-align: center; min-width: 90px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 45px; color: #9ca3af;">
                                        <i class="fa-solid fa-receipt" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                        Belum ada pesanan masuk.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <!-- PELANGGAN -->
                                        <td>
                                            <strong><?= htmlspecialchars($order['nama_pelanggan'] ?? 'Guest') ?></strong>
                                            <br>
                                            <small style="color: #6b7280; font-size: 11px;">
                                                Meja <?= htmlspecialchars($order['nomor_meja'] ?? $order['table_id'] ?? '-') ?>
                                            </small>
                                        </td>

                                        <!-- MENU -->
                                        <td>
                                            <span style="font-size: 13px; color: #374151;">
                                                <?= htmlspecialchars($order['daftar_menu'] ?? '-') ?>
                                            </span>
                                        </td>

                                        <!-- TOTAL -->
                                        <td>
                                            <strong style="color: #8b5e3c;">
                                                Rp <?= number_format($order['total'] ?? $order['total_harga'] ?? 0, 0, ',', '.') ?>
                                            </strong>
                                        </td>

                                        <!-- STATUS PESANAN -->
                                        <td>
                                            <form action="" method="POST" style="margin: 0;">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <select name="status" onchange="this.form.submit()" style="width: 100%; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #d1d5db; background: white; cursor: pointer;">
                                                    <option value="pending" <?= strtolower($order['status']) === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="diproses" <?= strtolower($order['status']) === 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                                    <option value="siap" <?= strtolower($order['status']) === 'siap' ? 'selected' : '' ?>>Siap</option>
                                                    <option value="selesai" <?= strtolower($order['status']) === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                                    <option value="batal" <?= in_array(strtolower($order['status']), ['batal', 'dibatalkan']) ? 'selected' : '' ?>>Batal</option>
                                                </select>
                                            </form>
                                        </td>

                                        <!-- STATUS PEMBAYARAN -->
                                        <td>
                                            <form action="" method="POST" style="margin: 0;">
                                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                <input type="hidden" name="update_pembayaran" value="1">
                                                <select name="status_pembayaran" onchange="this.form.submit()" style="width: 100%; padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid #d1d5db; background: white; cursor: pointer;">
                                                    <option value="pending" <?= strtolower($order['status_pembayaran'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                    <option value="lunas" <?= strtolower($order['status_pembayaran'] ?? '') === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                                                    <option value="batal" <?= strtolower($order['status_pembayaran'] ?? '') === 'batal' ? 'selected' : '' ?>>Batal</option>
                                                </select>
                                            </form>
                                        </td>

                                        <!-- WAKTU -->
                                        <td>
                                            <small style="color: #6b7280; font-size: 11px;">
                                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                            </small>
                                        </td>

                                        <!-- AKSI -->
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 7px; justify-content: center;">
                                                <a href="detail.php?id=<?= $order['id'] ?>" title="Detail Pesanan" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #e0e7ff; color: #4338ca; text-decoration: none; transition: all 0.2s;">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <a href="hapus.php?id=<?= $order['id'] ?>" title="Hapus" onclick="return confirm('Hapus riwayat pesanan ini?');" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fee2e2; color: #dc2626; text-decoration: none; transition: all 0.2s;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>