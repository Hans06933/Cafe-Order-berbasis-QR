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
    $kode_meja = $_SESSION['kode_meja'];
}

// Cek validasi meja dari database
$meja_info = null;
if (!empty($kode_meja)) {
    $stmt_meja = $pdo->prepare("SELECT * FROM tables WHERE kode_meja = ? AND status != 'nonaktif'");
    $stmt_meja->execute([$kode_meja]);
    $meja_info = $stmt_meja->fetch();
}

// Fetch Kategori
$stmt_kat = $pdo->query("SELECT * FROM categories ORDER BY nama_kategori ASC");
$kategori_list = $stmt_kat->fetchAll();

// Fetch Produk (Filter berdasarkan kategori jika ada)
$cat_id = filter_input(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
if ($cat_id) {
    $stmt_prod = $pdo->prepare("SELECT * FROM products WHERE category_id = ? AND status = 'tersedia' ORDER BY nama_produk ASC");
    $stmt_prod->execute([$cat_id]);
} else {
    $stmt_prod = $pdo->query("SELECT * FROM products WHERE status = 'tersedia' ORDER BY nama_produk ASC");
}
$produk_list = $stmt_prod->fetchAll();
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        body {
            background: #f8f6f3;
            margin: 0;
            padding-bottom: 100px;
            color: #1f2937;
            min-height: 100vh;
        }

        /* ===== HEADER ===== */
        header {
            background: linear-gradient(135deg, #8b5e3c 0%, #6b4423 100%);
            color: white;
            padding: 18px 20px 14px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(139, 94, 60, 0.3);
        }

        header h1 {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }

        header .sub-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 6px;
        }

        .table-info {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .table-info i {
            font-size: 11px;
        }

        .table-info.danger {
            background: rgba(220, 38, 38, 0.8);
            border-color: rgba(220, 38, 38, 0.3);
        }

        .header-cart {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            cursor: pointer;
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
            font-size: 10px;
            font-weight: 700;
        }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 16px 16px 100px;
        }

        /* ===== CATEGORIES ===== */
        .categories-scroll {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding: 4px 0 14px;
            margin-bottom: 4px;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .categories-scroll::-webkit-scrollbar {
            display: none;
        }

        .cat-pill {
            padding: 8px 18px;
            background: white;
            border: 1.5px solid #e5e7eb;
            border-radius: 24px;
            text-decoration: none;
            color: #4b5563;
            font-size: 13px;
            white-space: nowrap;
            font-weight: 600;
            transition: all 0.25s ease;
            flex-shrink: 0;
        }

        .cat-pill:active {
            transform: scale(0.95);
        }

        .cat-pill.active {
            background: #8b5e3c;
            color: white;
            border-color: #8b5e3c;
            box-shadow: 0 4px 12px rgba(139, 94, 60, 0.35);
        }

        .cat-pill i {
            margin-right: 4px;
            font-size: 12px;
        }

        /* ===== SECTION TITLE ===== */
        .section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 16px 0 12px;
        }

        .section-title h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }

        .section-title span {
            font-size: 12px;
            color: #9ca3af;
        }

        /* ===== PRODUCT CARDS ===== */
        .product-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .product-card {
            background: white;
            border-radius: 16px;
            padding: 14px;
            display: flex;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
            position: relative;
        }

        .product-card:active {
            transform: scale(0.98);
        }

        .product-img {
            width: 85px;
            height: 85px;
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
            margin: 0 0 2px;
            color: #1f2937;
        }

        .product-desc {
            font-size: 12px;
            color: #9ca3af;
            margin: 0 0 6px;
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
            margin-top: 4px;
        }

        .product-price {
            font-size: 16px;
            font-weight: 700;
            color: #8b5e3c;
            margin: 0;
        }

        .add-btn {
            background: #8b5e3c;
            color: white;
            border: none;
            padding: 7px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 4px;
            flex-shrink: 0;
        }

        .add-btn:active {
            transform: scale(0.92);
        }

        .add-btn i {
            font-size: 11px;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            display: block;
            opacity: 0.5;
        }

        .empty-state p {
            font-size: 14px;
        }

        /* ===== CART BAR ===== */
        .cart-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            padding: 12px 20px 16px;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.06);
            z-index: 200;
        }

        .cart-inner {
            max-width: 600px;
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
            color: #9ca3af;
            font-weight: 500;
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
            padding: 10px 22px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }

        .checkout-btn:active {
            transform: scale(0.95);
        }

        .checkout-btn.disabled {
            pointer-events: none;
            opacity: 0.5;
            background: #d1d5db;
        }

        .checkout-btn .badge {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
        }

        /* ===== ANIMASI TOAST ===== */
        .toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: #1f2937;
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 500;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 999;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .toast i {
            margin-right: 8px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            header h1 {
                font-size: 17px;
            }

            .product-img {
                width: 72px;
                height: 72px;
            }

            .product-title {
                font-size: 14px;
            }

            .product-price {
                font-size: 14px;
            }

            .add-btn {
                padding: 6px 12px;
                font-size: 11px;
            }

            .cat-pill {
                font-size: 12px;
                padding: 6px 14px;
            }

            .cart-total {
                font-size: 16px;
            }

            .checkout-btn {
                font-size: 13px;
                padding: 8px 16px;
            }

            .container {
                padding: 12px 12px 100px;
            }
        }

        @media (max-width: 380px) {
            .product-card {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .product-img {
                width: 100px;
                height: 100px;
            }

            .product-bottom {
                flex-direction: column;
                gap: 8px;
                width: 100%;
            }

            .add-btn {
                width: 100%;
                justify-content: center;
            }

            .cart-inner {
                flex-direction: column;
                gap: 8px;
                align-items: stretch;
            }

            .cart-info {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .checkout-btn {
                justify-content: center;
            }
        }

        /* ===== CART ANIMATION ===== */
        @keyframes bounceIn {
            0% { transform: scale(0.8); }
            60% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .cart-bump {
            animation: bounceIn 0.3s ease;
        }
    </style>
</head>
<body>

<!-- ===== TOAST NOTIFICATION ===== -->
<div class="toast" id="toast">
    <i class="fa-solid fa-check-circle"></i>
    <span id="toast-message">Produk ditambahkan!</span>
</div>

<!-- ===== HEADER ===== -->
<header>
    <h1>☕ Café Order</h1>
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
        <div class="header-cart" onclick="window.location.href='checkout.php'">
            <i class="fa-solid fa-cart-shopping"></i>
            <span id="header-cart-count">0</span>
        </div>
    </div>
</header>

<div class="container">
    <!-- ===== KATEGORI ===== -->
    <div class="categories-scroll">
        <a href="index.php" class="cat-pill <?= !$cat_id ? 'active' : '' ?>">
            <i class="fa-solid fa-grid-2"></i> Semua
        </a>
        <?php foreach ($kategori_list as $cat): ?>
            <a href="index.php?cat=<?= $cat['id'] ?>" class="cat-pill <?= $cat_id == $cat['id'] ? 'active' : '' ?>">
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
                    foreach ($kategori_list as $c) {
                        if ($c['id'] == $cat_id) { $cat_name = $c['nama_kategori']; break; }
                    }
                    echo htmlspecialchars($cat_name);
                ?>
            <?php else: ?>
                Semua Menu
            <?php endif; ?>
        </h2>
        <span><?= count($produk_list) ?> menu</span>
    </div>

    <!-- ===== DAFTAR PRODUK ===== -->
    <div class="product-list">
        <?php if (empty($produk_list)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-utensils"></i>
                <p>Menu tidak ditemukan untuk kategori ini.</p>
            </div>
        <?php else: ?>
            <?php foreach ($produk_list as $prod): ?>
                <div class="product-card" data-id="<?= $prod['id'] ?>">
                    <img src="assets/uploads/produk/<?= !empty($prod['gambar']) ? htmlspecialchars($prod['gambar']) : 'default.jpg' ?>" 
                        alt="<?= htmlspecialchars($prod['nama_produk']) ?>" 
                        class="product-img" 
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect width=%2280%22 height=%2280%22 fill=%22%23f3f4f6%22/%3E%3Ctext x=%2240%22 y=%2244%22 text-anchor=%22middle%22 font-size=%2224%22 fill=%22%239ca3af%22%3E🍽%3C/text%3E%3C/svg%3E'">

                    <div class="product-details">
                        <div>
                            <h3 class="product-title"><?= htmlspecialchars($prod['nama_produk']) ?></h3>
                            <?php if (!empty($prod['deskripsi'])): ?>
                                <p class="product-desc"><?= htmlspecialchars($prod['deskripsi']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="product-bottom">
                            <span class="product-price">Rp <?= number_format($prod['harga'], 0, ',', '.') ?></span>
                            <button class="add-btn" onclick="addToCart(<?= $prod['id'] ?>, '<?= htmlspecialchars($prod['nama_produk'], ENT_QUOTES) ?>', <?= $prod['harga'] ?>)">
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

// ===== UPDATE UI =====
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
    showToast('✅ ' + nama + ' ditambahkan!');
    
    // Bump animation on cart badge
    const badge = document.getElementById('header-cart-count');
    badge.classList.remove('cart-bump');
    setTimeout(() => badge.classList.add('cart-bump'), 10);
}

// ===== INIT =====
updateCartUI();
</script>

</body>
</html>