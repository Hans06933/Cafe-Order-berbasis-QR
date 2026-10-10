<?php
$user_name = $_SESSION['user_nama'] ?? $_SESSION['username'] ?? 'User';
$user_role =$_SESSION['user_role'] ?? 'admin';
$current_page = basename($_SERVER['PHP_SELF']);

// Ambil jumlah pesanan pending awal untuk badge
$pending_count = 0;
if (isset($pdo)) {
    try {
        $stmt_badge =$pdo->query("SELECT COUNT(*) FROM orders WHERE LOWER(status) = 'pending'");
        $pending_count = (int)$stmt_badge->fetchColumn();
    } catch (Exception $e) {$pending_count = 0;
    }
}
?>

<style>
/* CSS BADGE NOTIFIKASI DI NAVBAR */
.nav-badge {
    background: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    margin-left: auto;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.4);
    transition: all 0.3s ease;
}

.nav-badge.hidden {
    display: none;
}

.nav-badge.bounce {
    animation: badgePulse 0.4s ease;
}

@keyframes badgePulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.3); }
    100% { transform: scale(1); }
}

/* POPUP TOAST GLOBAL */
.global-order-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 14px 18px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    z-index: 99999;
    display: flex;
    align-items: center;
    gap: 12px;
    transform: translateX(120%);
    transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.27, 1.55);
    cursor: pointer;
}

.global-order-toast.show {
    transform: translateX(0);
}
</style>

<!-- POPUP TOAST NOTIFIKASI PESANAN BARU -->
<div id="global-order-toast" class="global-order-toast" onclick="window.location.href='<?= BASE_URL ?>admin/pesanan/'">
    <i class="fa-solid fa-bell fa-bounce" style="font-size: 20px;"></i>
    <div>
        <strong style="display: block; font-size: 13px;">🔔 Pesanan Baru Masuk!</strong>
        <span id="global-toast-info" style="font-size: 12px; opacity: 0.95;">--</span>
    </div>
</div>

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

        <!-- MENU PESANAN DENGAN BADGE NOTIFIKASI REALTIME -->
        <a href="<?= BASE_URL ?>admin/pesanan/" class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/pesanan/') !== false ? 'active' : '' ?>">
            <i class="fa-solid fa-receipt"></i>
            <span>Pesanan</span>
            <span id="nav-order-badge" class="nav-badge <?= $pending_count > 0 ? '' : 'hidden' ?>">
                <?= $pending_count ?>
            </span>
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

        <a href="<?= BASE_URL ?>index.php" class="nav-link" target="_blank">
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

<!-- SCRIPT POLLING NOTIFIKASI REAL-TIME -->
<script>
(function() {
    let lastCheckedOrderId = 0;

    // Ambil ID pesanan tertinggi saat pertama kali dimuat
    fetch('<?= BASE_URL ?>admin/pesanan/cek_pesanan_baru.php?last_id=0')
        .then(r => r.json())
        .then(d => {
            if (d.order) lastCheckedOrderId = d.order.id;
        })
        .catch(() => {});

    // Bunyikan Nada Lonceng
    function playBell() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + 0.6);
        } catch(e) {}
    }

    // Polling setiap 5 detik
    setInterval(() => {
        if (lastCheckedOrderId === 0) return;

        fetch(`<?= BASE_URL ?>admin/pesanan/cek_pesanan_baru.php?last_id=${lastCheckedOrderId}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'new_order' && data.order) {
                    lastCheckedOrderId = data.order.id;

                    // 1. Update & Animasi Badge Navbar
                    const badge = document.getElementById('nav-order-badge');
                    if (badge) {
                        let count = parseInt(badge.innerText) || 0;
                        badge.innerText = count + 1;
                        badge.classList.remove('hidden', 'bounce');
                        setTimeout(() => badge.classList.add('bounce'), 10);
                    }

                    // 2. Bunyikan Suara
                    playBell();

                    // 3. Tampilkan Toast Popup
                    const toast = document.getElementById('global-order-toast');
                    const toastInfo = document.getElementById('global-toast-info');
                    if (toast && toastInfo) {
                        const totalRp = parseInt(data.order.total || 0).toLocaleString('id-ID');
                        toastInfo.innerText = `${data.order.nama_pelanggan} - Rp ${totalRp}`;
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 4000);
                    }

                    // 4. Jika sedang membuka halaman kelola pesanan, auto-reload tabel
                    if (window.location.href.includes('/admin/pesanan/')) {
                        setTimeout(() => location.reload(), 1200);
                    }
                }
            })
            .catch(() => {});
    }, 5000);
})();
</script>