<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. Panggil Database (PDO) & Config
require_once "../config/database.php";
require_once "../config/config.php";

require_once "../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

$page_title = "Dashboard";

// ==========================================
// QUERY STATISTIK SESUAI NAMA TABEL DATABASE
// ==========================================

// 1. Total Produk (Tabel: products)
try {
    $total_produk = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_produk = 0;
}

// 2. Total Meja (Tabel: tables)
try {
    $total_meja = $pdo->query("SELECT COUNT(*) FROM tables")->fetchColumn() ?: 0;
} catch (PDOException $e) {
    $total_meja = 0;
}

// 3. Total Pesanan (Cek tabel pesanan / orders)
try {
    $total_pesanan = $pdo->query("SELECT COUNT(*) FROM pesanan WHERE LOWER(status) != 'batal'")->fetchColumn();
} catch (PDOException $e) {
    try {
        $total_pesanan = $pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) != 'batal'")->fetchColumn();
    } catch (PDOException $e2) {
        $total_pesanan = 0;
    }
}

// 4. Total Omzet
try {
    $stmt = $pdo->query("
        SELECT SUM(total) AS total_omzet 
        FROM orders 
        WHERE DATE(created_at) = CURDATE() 
        AND (status = 'selesai' OR status_pembayaran = 'lunas')
    ");
    $data = $stmt->fetch();
    $total_omzet = $data['total_omzet'] ?? 0;
} catch (PDOException $e) {
    try {
        $stmt = $pdo->query("
            SELECT SUM(total) AS total_omzet 
            FROM pesanan 
            WHERE DATE(created_at) = CURDATE() 
            AND (status = 'selesai' OR status_pembayaran = 'lunas')
        ");
        $data = $stmt->fetch();
        $total_omzet = $data['total_omzet'] ?? 0;
    } catch (PDOException $e2) {
        $total_omzet = 0;
    }
}

// 5. Pesanan Terbaru
$pesanan_terbaru = [];
try {
    $query_dashboard = "SELECT o.*, t.nomor_meja, 
                       GROUP_CONCAT(CONCAT(od.qty, 'x ', od.nama_produk) SEPARATOR ', ') AS daftar_menu 
                      FROM orders o 
                      LEFT JOIN tables t ON o.table_id = t.id 
                      LEFT JOIN order_details od ON o.id = od.order_id
                      GROUP BY o.id
                      ORDER BY o.id DESC 
                      LIMIT 5";
    $stmt_dashboard = $pdo->query($query_dashboard);
    $pesanan_terbaru = $stmt_dashboard->fetchAll();
} catch (PDOException $e) {
    $pesanan_terbaru = [];
}

require_once "../includes/header.php";
require_once "../includes/navbar.php";
?>

<style>
    /* Tambahan styling untuk dashboard lebih profesional */
    .stat-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        cursor: default;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.10);
    }

    .stat-value {
        font-size: 28px;
        font-weight: 700;
    }

    .stat-label {
        font-size: 14px;
        color: #6b7280;
        font-weight: 500;
    }

    .stat-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }

    .stat-icon.brown { background: #f5ebe3; color: #8b5e3c; }
    .stat-icon.green { background: #d1fae5; color: #059669; }
    .stat-icon.blue { background: #dbeafe; color: #2563eb; }
    .stat-icon.orange { background: #fed7aa; color: #ea580c; }

    .quick-item {
        transition: all 0.3s ease;
        border: 1px solid #f3f4f6;
    }

    .quick-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        border-color: #8b5e3c;
        background: #fdfaf7;
    }

    .quick-item i {
        font-size: 24px;
        color: #8b5e3c;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f5ebe3;
        border-radius: 12px;
    }

    .quick-item strong {
        color: #1f2937;
        font-size: 14px;
    }

    .quick-item span {
        color: #9ca3af;
        font-size: 12px;
    }

    .welcome-card {
        background: linear-gradient(135deg, #8b5e3c 0%, #6b4423 100%);
        border-radius: 16px;
        padding: 30px 35px;
        margin-bottom: 28px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .welcome-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .welcome-card::after {
        content: '☕';
        position: absolute;
        bottom: -20px;
        right: 30px;
        font-size: 120px;
        opacity: 0.08;
        transform: rotate(-10deg);
    }

    .welcome-title {
        font-size: 24px;
        margin: 0 0 8px;
        position: relative;
        z-index: 1;
    }

    .welcome-text {
        font-size: 14px;
        opacity: 0.85;
        margin: 0;
        position: relative;
        z-index: 1;
    }

    .data-table tbody tr {
        transition: background 0.2s ease;
    }

    .data-table tbody tr:hover {
        background: #faf8f6;
    }

    .badge {
        padding: 5px 14px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
    }

    .badge-pending { background: #fef3c7; color: #d97706; }
    .badge-diproses { background: #dbeafe; color: #2563eb; }
    .badge-siap { background: #d1fae5; color: #059669; }
    .badge-selesai { background: #10b981; color: white; }
    .badge-batal { background: #fee2e2; color: #dc2626; }
    .badge-dibatalkan { background: #fee2e2; color: #dc2626; }

    .panel {
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        border: 1px solid #f3f4f6;
        transition: box-shadow 0.3s ease;
    }

    .panel:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    }

    .panel-header {
        padding: 18px 24px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .panel-title {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .panel-link {
        color: #8b5e3c;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: color 0.2s;
    }

    .panel-link:hover {
        color: #6b4423;
        text-decoration: underline;
    }

    .panel-body {
        padding: 20px 24px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 28px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .quick-menu {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .quick-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px 18px;
        border-radius: 12px;
        background: white;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .quick-item > div {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    @media (max-width: 1024px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        .quick-menu {
            grid-template-columns: 1fr 1fr;
        }
    }

    @media (max-width: 640px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        .quick-menu {
            grid-template-columns: 1fr;
        }
        .welcome-card {
            padding: 20px;
        }
        .welcome-title {
            font-size: 20px;
        }
        .stat-value {
            font-size: 22px;
        }
    }
</style>

<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <button type="button" class="mobile-menu" id="mobileMenu">
                <i class="fa-solid fa-bars"></i>
            </button>

            <div>
                <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>
                <div class="page-breadcrumb">Café Order / <?= htmlspecialchars($page_title) ?></div>
            </div>
        </div>

        <div class="topbar-right">
            <div class="topbar-date">
                <i class="fa-regular fa-calendar"></i>
                <?= date('d M Y') ?>
            </div>

            <div class="topbar-user">
                <div class="topbar-avatar"><?= strtoupper(substr($_SESSION['user_nama'] ?? $_SESSION['username'] ?? 'A', 0, 1)) ?></div>
            </div>
        </div>
    </header>

    <main class="content">
        <!-- WELCOME CARD -->
        <div class="welcome-card">
            <h2 class="welcome-title">Selamat datang, <?= htmlspecialchars($_SESSION['user_nama'] ?? $_SESSION['username'] ?? 'Administrator') ?> 👋</h2>
            <p class="welcome-text">Kelola pesanan, menu, meja, dan aktivitas café dari satu dashboard.</p>
        </div>

        <!-- STATISTIK -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon brown"><i class="fa-solid fa-receipt"></i></div>
                <div>
                    <div class="stat-label">Pesanan Hari Ini</div>
                    <div class="stat-value"><?= number_format($total_pesanan ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon green"><i class="fa-solid fa-cash-register"></i></div>
                <div>
                    <div class="stat-label">Omzet Hari Ini</div>
                    <div class="stat-value">Rp <?= number_format($total_omzet ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon blue"><i class="fa-solid fa-utensils"></i></div>
                <div>
                    <div class="stat-label">Total Produk</div>
                    <div class="stat-value"><?= number_format($total_produk ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon orange"><i class="fa-solid fa-chair"></i></div>
                <div>
                    <div class="stat-label">Total Meja</div>
                    <div class="stat-value"><?= number_format($total_meja ?? 0, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- DASHBOARD GRID -->
        <div class="dashboard-grid">
            <!-- PESANAN TERBARU -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">Pesanan Terbaru</h3>
                    <a href="pesanan/" class="panel-link">Lihat Semua →</a>
                </div>
                <div class="panel-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan / Meja</th>
                                    <th>Menu Dipesan</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pesanan_terbaru)): ?>
                                    <?php foreach ($pesanan_terbaru as $row): ?>
                                        <tr>
                                            <td><strong>#<?= $row['id'] ?></strong></td>
                                            <td>
                                                <strong><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Guest') ?></strong>
                                                <br>
                                                <small style="color: #6b7280; font-size: 11px;">
                                                    Meja <?= htmlspecialchars($row['nomor_meja'] ?? $row['table_id'] ?? '-') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span style="font-size: 13px; color: #374151;">
                                                    <?= htmlspecialchars($row['daftar_menu'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= strtolower($row['status']) ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong style="color: #8b5e3c;">
                                                    Rp <?= number_format($row['total'] ?? $row['total_harga'] ?? 0, 0, ',', '.') ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center; padding: 40px; color: #9ca3af;">
                                            <i class="fa-solid fa-receipt" style="font-size: 32px; margin-bottom: 12px; display: block;"></i>
                                            Belum ada pesanan masuk.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- AKSES CEPAT -->
            <div class="panel">
                <div class="panel-header">
                    <h3 class="panel-title">Akses Cepat</h3>
                </div>
                <div class="panel-body">
                    <div class="quick-menu">
                        <a href="pesanan/" class="quick-item">
                            <i class="fa-solid fa-receipt"></i>
                            <div>
                                <strong>Kelola Pesanan</strong>
                                <span>Lihat & proses pesanan</span>
                            </div>
                        </a>
                        <a href="produk/" class="quick-item">
                            <i class="fa-solid fa-utensils"></i>
                            <div>
                                <strong>Kelola Produk</strong>
                                <span>Tambah/Edit menu</span>
                            </div>
                        </a>
                        <a href="kategori/" class="quick-item">
                            <i class="fa-solid fa-layer-group"></i>
                            <div>
                                <strong>Kelola Kategori</strong>
                                <span>Atur kategori produk</span>
                            </div>
                        </a>
                        <a href="meja/" class="quick-item">
                            <i class="fa-solid fa-qrcode"></i>
                            <div>
                                <strong>Cetak QR Meja</strong>
                                <span>Generate QR Code</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once "../includes/footer.php"; ?>