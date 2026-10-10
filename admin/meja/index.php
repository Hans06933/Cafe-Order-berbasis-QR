<?php
require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";
require_once "../../config/config.php";

$page_title = "Kelola Meja";

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

// Fetch data meja sekaligus hitung jumlah pesanan aktif (pending, diproses, siap)
$query = "SELECT t.*, 
            COUNT(CASE WHEN LOWER(o.status) IN ('pending', 'diproses', 'siap') THEN 1 END) AS active_orders
          FROM tables t
          LEFT JOIN orders o ON t.id = o.table_id
          GROUP BY t.id
          ORDER BY t.id DESC";

$stmt = $pdo->query($query);
$meja_list = $stmt->fetchAll();
?>

<!-- KUNCI UTAMA: Wajib dibungkus .main-wrapper dan .content agar tidak tertutup sidebar -->
<div class="main-wrapper">
    <div class="content">

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; gap: 15px; flex-wrap: wrap;">
            <div>
                <h2 style="margin: 0 0 5px; font-size: 20px;">Daftar Meja & QR Code</h2>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Kelola meja café dan cetak QR code pemesanan.</p>
            </div>
            <a href="tambah.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; background: #8b5e3c; color: white; text-decoration: none; border-radius: 10px; font-size: 13px; font-weight: 600;">
                <i class="fa-solid fa-plus"></i> Tambah Meja
            </a>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Semua Meja</h3>
                <span style="color: #6b7280; font-size: 12px;"><?= count($meja_list) ?> meja</span>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>QR Code</th>
                                <th>Nomor Meja</th>
                                <th>Kode Meja</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($meja_list)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 45px; color: #9ca3af;">
                                        <i class="fa-solid fa-chair" style="font-size: 32px; margin-bottom: 12px;"></i><br>
                                        Belum ada data meja.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($meja_list as $meja): ?>
                                    <?php 
                                        // Target URL QR Code
                                        $target_url = defined('DEV_TUNNEL_URL') ? DEV_TUNNEL_URL : BASE_URL;
                                        $base_url_fixed = rtrim($target_url, '/') . '/';
                                        $orderUrl = $base_url_fixed . "index.php?meja=" . urlencode($meja['kode_meja']);
                                        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($orderUrl);

                                        // LOGIKA DETEKSI STATUS MEJA DARI PESANAN
                                        $is_active = (int) ($meja['active_orders'] ?? 0) > 0;
                                        
                                        if ($meja['status'] === 'nonaktif') {
                                            $displayStatus = 'Nonaktif';
                                            $statusClass = 'badge-danger';
                                        } elseif ($is_active) {
                                            $displayStatus = 'Digunakan (' . $meja['active_orders'] . ' pesanan)';
                                            $statusClass = 'badge-warning';
                                        } else {
                                            $displayStatus = 'Tersedia';
                                            $statusClass = 'badge-success';
                                        }
                                    ?>
                                    <tr>
                                        <td>
                                            <img src="<?= $qrApiUrl ?>" alt="QR <?= htmlspecialchars($meja['nomor_meja']) ?>" style="width: 50px; height: 50px; border-radius: 6px; border: 1px solid #e5e7eb;">
                                        </td>
                                        <td><strong><?= htmlspecialchars($meja['nomor_meja']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($meja['kode_meja']) ?></code></td>
                                        <td>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($displayStatus) ?></span>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 7px;">
                                                <a href="cetak_qr.php?id=<?= $meja['id'] ?>" target="_blank" title="Cetak QR" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #e0e7ff; color: #4338ca; text-decoration: none;">
                                                    <i class="fa-solid fa-qrcode"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $meja['id'] ?>" title="Edit" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f3f4f6; color: #374151; text-decoration: none;">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="hapus.php?id=<?= $meja['id'] ?>" title="Hapus" onclick="return confirm('Yakin ingin menghapus meja ini?');" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fee2e2; color: #dc2626; text-decoration: none;">
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