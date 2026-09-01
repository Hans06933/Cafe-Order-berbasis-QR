<?php
$user_name = $_SESSION['user_nama'] ?? $_SESSION['username'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fa-solid fa-mug-hot"></i>
        </div>
        <div>
            <div class="brand-text">Café Order</div>
            <div class="brand-subtitle">Management System</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>

        <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Dashboard</span>
        </a>

        <a href="<?= BASE_URL ?>admin/pesanan/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/pesanan/') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-receipt"></i>
            <span>Pesanan</span>
        </a>

        <div class="nav-section-title">Master Data</div>

        <a href="<?= BASE_URL ?>admin/produk/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/produk/') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-utensils"></i>
            <span>Produk</span>
        </a>

        <a href="<?= BASE_URL ?>admin/kategori/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/kategori/') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-layer-group"></i>
            <span>Kategori</span>
        </a>

        <a href="<?= BASE_URL ?>admin/meja/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/meja/') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-chair"></i>
            <span>Meja & QR Code</span>
        </a>

        <div class="nav-section-title">Sistem</div>

        <a href="<?= BASE_URL ?>index.php" class="nav-link">
            <i class="fa-solid fa-store"></i>
            <span>Halaman Café</span>
        </a>

        <a href="<?= BASE_URL ?>admin/logout.php" class="nav-link">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar"><?= strtoupper(substr($user_name, 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($user_name) ?></div>
                <div class="user-role"><?= htmlspecialchars($user_role) ?></div>
            </div>
        </div>
    </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>