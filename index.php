<?php
session_start();
require_once "config/database.php";
require_once "config/config.php";

// Fungsi sanitasi cadangan jika belum terdefinisi di helper
if (!function_exists('clean')) {
    function clean($data) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// Tangkap kode meja dari URL (misal: index.php?meja=MEJA-01)
$kode_meja = $_GET['meja'] ?? $_SESSION['kode_meja'] ?? '';

// Simpan kode meja ke session jika ada
if (!empty($_GET['meja'])) {
    $_SESSION['kode_meja'] = clean($_GET['meja']);
    $kode_meja =$_SESSION['kode_meja'];
}

// Cek validasi meja dari database
$meja_info = null;
if (!empty($kode_meja)) {
    $stmt_meja =$pdo->prepare("SELECT * FROM tables WHERE kode_meja = ? AND status != 'nonaktif'");
    $stmt_meja->execute([$kode_meja]);
    $meja_info =$stmt_meja->fetch();
}

// Fetch Kategori
$stmt_kat =$pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$kategori_list =$stmt_kat->fetchAll();

// Fetch Produk (Filter berdasarkan kategori jika ada)
$cat_id = filter_input(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
if ($cat_id) {
    $stmt_prod =$pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'tersedia' ORDER BY nama_produk ASC");
    $stmt_prod->execute([$cat_id]);
} else {
    $stmt_prod =$pdo->query("SELECT * FROM products WHERE status = 'tersedia' ORDER BY nama_produk ASC");
}
$produk_list =$stmt_prod->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pesan Menu - Cafe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', system-ui, -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f8f6f3;
            margin: 0;
            padding-bottom: 120px;
            color: #1f2937;
            min-height: 100vh;
        }

        /* ===== HEADER ===== */
        header {
            background: linear-gradient(135deg, #8b5e3c 0%, #6b4423 100%);
            color: white;
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(139, 94, 60, 0.25);
        }

        .header-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* TANGGAL & WAKTU REALTIME */
        .realtime-box {
            text-align: right;
            line-height: 1.2;
        }

        .realtime-date {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 500;
        }

        .realtime-clock {
            font-size: 13px;
            font-weight: 700;
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 0.5px;
            color: #ffffff;
        }

        header .sub-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .table-info.danger {
            background: rgba(220, 38, 38, 0.85);
            border-color: rgba(220, 38, 38, 0.3);
        }

        .header-cart {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 5px 14px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
            transition: background 0.2s;
        }

        .header-cart:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .header-cart span {
            background: white;
            color: #8b5e3c;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        /* ===== CATEGORIES ===== */
        .categories-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 4px 2px 14px;
            margin-bottom: 8px;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .categories-scroll::-webkit-scrollbar {
            display: none;
        }

        .cat-pill {
            padding: 8px 20px;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 24px;
            text-decoration: none;
            color: #4b5563;
            font-size: 13px;
            white-space: nowrap;
            font-weight: 600;
            transition: all 0.2s ease;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .cat-pill:hover {
            border-color: #8b5e3c;
            color: #8b5e3c;
        }

        .cat-pill.active {
            background: #8b5e3c;
            color: white;
            border-color: #8b5e3c;
            box-shadow: 0 4px 12px rgba(139, 94, 60, 0.25);
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 12px 0 16px;
        }

        .section-title h2 {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
        }

        .section-title span {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        /* ===== PRODUCT GRID (LAPTOP: 3 KESAMPING, TABLET: 2, HP: 1 KOLOM) ===== */
        .product-list {
            display: grid;
            grid-template-columns: 1fr; /* Default HP */
            gap: 16px;
        }

        /* Tablet (>= 640px): 2 Kolom */
        @media (min-width: 640px) {
            .product-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* Laptop/Desktop (>= 992px): 3 Kolom */
        @media (min-width: 992px) {
            .product-list {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        .product-card {
            background: white;
            border-radius: 16px;
            padding: 14px;
            display: flex;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #f3f4f6;
            transition: all 0.2s ease;
            position: relative;
            align-items: stretch;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: #e5e7eb;
        }

        .product-img {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            object-fit: cover;
            background: #f3f4f6;
            flex-shrink: 0;
        }

        .product-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .product-title {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 4px;
            color: #1f2937;
            line-height: 1.3;
        }

        .product-desc {
            font-size: 12px;
            color: #6b7280;
            margin: 0 0 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .product-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 4px;
        }

        .product-price {
            font-size: 15px;
            font-weight: 700;
            color: #8b5e3c;
            margin: 0;
        }

        .add-btn {
            background: #8b5e3c;
            color: white;
            border: none;
            padding: 7px 14px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            flex-shrink: 0;
        }

        .add-btn:hover {
            background: #734c30;
        }

        .add-btn:active {
            transform: scale(0.94);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
            background: white;
            border-radius: 16px;
            border: 1.5px dashed #e5e7eb;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
            color: #d1d5db;
        }

        .empty-state p {
            font-size: 14px;
            color: #6b7280;
        }

        /* ===== CART BAR ===== */
        .cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #e5e7eb;
            padding: 14px 20px;
            box-shadow: 0 -6px 20px rgba(0, 0, 0, 0.08);
            z-index: 200;
        }

        .cart-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-info {
            display: flex;
            flex-direction: column;
        }

        .cart-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cart-total {
            font-weight: 700;
            font-size: 18px;
            color: #8b5e3c;
        }

        .checkout-btn {
            background: #8b5e3c;
            color: white;
            padding: 10px 24px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(139, 94, 60, 0.25);
        }

        .checkout-btn:hover {
            background: #734c30;
        }

        .checkout-btn:active {
            transform: scale(0.96);
        }

        .checkout-btn.disabled {
            pointer-events: none;
            opacity: 0.5;
            background: #d1d5db;
            box-shadow: none;
        }

        .checkout-btn .badge {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        /* ===== TOAST ===== */
        .toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: #1f2937;
            color: white;
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ===== ANIMATION ===== */
        @keyframes bounceIn {
            0% { transform: scale(0.8); }
            60% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

        .cart-bump {
            animation: bounceIn 0.3s ease;
        }

        /* Mobile Adjustments untuk Waktu Header */
        @media (max-width: 480px) {
            .realtime-date {
                font-size: 10px;
            }
            .realtime-clock {
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<!-- ===== TOAST NOTIFICATION ===== -->
<div class="toast" id="toast">
    <i class="fa-solid fa-circle-check" style="color: #10b981;"></i>
    <span id="toast-message">Produk ditambahkan!</span>
</div>

<!-- ===== HEADER ===== -->
<header>
    <div class="header-inner">
        <div class="header-top">
            <h1>☕ Café Order</h1>
            <div class="header-right">
                <!-- TANGGAL & WAKTU REALTIME -->
                <div class="realtime-box">
                    <div id="realtime-date" class="realtime-date">-- -- ----</div>
                    <div id="realtime-clock" class="realtime-clock">00:00:00 WIB</div>
                </div>

                <!-- KERANJANG HEADER -->
                <div class="header-cart" onclick="window.location.href='checkout.php'">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <span id="header-cart-count">0</span>
                </div>
            </div>
        </div>
        <div class="sub-header">
            <?php if ($meja_info): ?>
                <div class="table-info">
                    <i class="fa-solid fa-chair"></i> Meja <?= htmlspecialchars($meja_info['nomor_meja']) ?>
                </div>
            <?php else: ?>
                <div class="table-info danger">
                    <i class="fa-solid fa-triangle-exclamation"></i> Scan QR ulang
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="container">
    <!-- ===== KATEGORI ===== -->
    <div class="categories-scroll">
        <a href="index.php" class="cat-pill <?= !$cat_id ? 'active' : '' ?>">
            <i class="fa-solid fa-border-all"></i> Semua
        </a>
        <?php foreach ($kategori_list as$cat): ?>
            <a href="index.php?cat=<?= $cat['id'] ?>" class="cat-pill <?= $cat_id ==$cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['nama_kategori']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ===== SECTION TITLE ===== -->
    <div class="section-title">
        <h2>
            <?php if ($cat_id): ?>
                <?php 
                    $cat_name = '';
                    foreach ($kategori_list as$c) {
                        if ($c['id'] ==$cat_id) { $cat_name =$c['nama_kategori']; break; }
                    }
                    echo htmlspecialchars($cat_name);
                ?>
            <?php else: ?>
                Semua Menu
            <?php endif; ?>
        </h2>
        <span><?= count($produk_list) ?> menu tersedia</span>
    </div>

    <!-- ===== DAFTAR PRODUK (GRID RESPONSIVE) ===== -->
    <div class="product-list">
        <?php if (empty($produk_list)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-utensils"></i>
                <p>Menu tidak ditemukan untuk kategori ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($produk_list as$prod): ?>
                <div class="product-card" data-id="<?= $prod['id'] ?>">
                    <img src="assets/uploads/produk/<?= !empty($prod['gambar']) ? htmlspecialchars($prod['gambar']) : 'default.jpg' ?>" 
                         alt="<?= htmlspecialchars($prod['nama_produk']) ?>" 
                         class="product-img" 
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2290%22 height=%2290%22%3E%3Crect width=%2290%22 height=%2290%22 fill=%22%23f3f4f6%22/%3E%3Ctext x=%2245%22 y=%2250%22 text-anchor=%22middle%22 font-size=%2226%22 fill=%22%239ca3af%22%3E☕%3C/text%3E%3C/svg%3E'">

                    <div class="product-details">
                        <div>
                            <h3 class="product-title"><?= htmlspecialchars($prod['nama_produk']) ?></h3>
                            <?php if (!empty($prod['deskripsi'])): ?>
                                <p class="product-desc"><?= htmlspecialchars($prod['deskripsi']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="product-bottom">
                            <span class="product-price">Rp <?= number_format($prod['harga'], 0, ',', '.') ?></span>
                            <button class="add-btn" onclick="addToCart(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['nama_produk'], ENT_QUOTES) ?>', <?=$prod['harga'] ?>)">
                                <i class="fa-solid fa-plus"></i> Tambah
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===== FLOATING CART BAR ===== -->
<div class="cart-bar">
    <div class="cart-inner">
        <div class="cart-info">
            <span class="cart-label">Total Pesanan</span>
            <span class="cart-total" id="cart-total">Rp 0</span>
        </div>
        <a href="checkout.php" class="checkout-btn" id="btn-checkout">
            <i class="fa-solid fa-cart-shopping"></i>
            Checkout
            <span class="badge" id="cart-count">0</span>
        </a>
    </div>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('cafe_cart')) || [];

// ===== WAKTU REALTIME =====
function updateRealtimeClock() {
    const now = new Date();

    const options = {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric'
    };
    const dateStr = now.toLocaleDateString('id-ID', options);

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const timeStr = `${hours}:${minutes}:${seconds} WIB`;

    const dateElem = document.getElementById('realtime-date');
    const clockElem = document.getElementById('realtime-clock');

    if (dateElem) dateElem.innerText = dateStr;
    if (clockElem) clockElem.innerText = timeStr;
}

// Jalankan timer waktu realtime
setInterval(updateRealtimeClock, 1000);
updateRealtimeClock();

// ===== UPDATE UI KERANJANG =====
function updateCartUI() {
    let count = 0;
    let total = 0;
    cart.forEach(item => {
        count += item.qty;
        total += item.harga * item.qty;
    });

    document.getElementById('cart-count').innerText = count;
    document.getElementById('header-cart-count').innerText = count;
    document.getElementById('cart-total').innerText = 'Rp ' + total.toLocaleString('id-ID');
    
    const checkoutBtn = document.getElementById('btn-checkout');
    const mejaValid = <?= $meja_info ? 'true' : 'false' ?>;
    
    if (count > 0 && mejaValid) {
        checkoutBtn.classList.remove('disabled');
    } else {
        checkoutBtn.classList.add('disabled');
    }
}

// ===== TOAST =====
function showToast(message) {
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toast-message');
    toastMsg.innerText = message;
    toast.classList.add('show');
    clearTimeout(toast._timeout);
    toast._timeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 1500);
}

// ===== ADD TO CART =====
function addToCart(id, nama, harga) {
    let item = cart.find(i => i.id === id);
    if (item) {
        item.qty += 1;
    } else {
        cart.push({ id, nama, harga, qty: 1, catatan: '' });
    }
    localStorage.setItem('cafe_cart', JSON.stringify(cart));
    updateCartUI();
    showToast(nama + ' ditambahkan!');
    
    // Bump animation pada badge keranjang
    const badge = document.getElementById('header-cart-count');
    badge.classList.remove('cart-bump');
    setTimeout(() => badge.classList.add('cart-bump'), 10);
}

// ===== INIT =====
updateCartUI();
</script>

</body>
</html>