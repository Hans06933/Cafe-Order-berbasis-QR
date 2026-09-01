<?php
session_start();
require_once "config/database.php";
require_once "config/config.php";

$kode_meja = $_SESSION['kode_meja'] ?? '';

if (empty($kode_meja)) {
    header("Location: index.php");
    exit;
}

$stmt_meja = $pdo->prepare("SELECT * FROM tables WHERE kode_meja = ?");
$stmt_meja->execute([$kode_meja]);
$meja_info = $stmt_meja->fetch();

if (!$meja_info) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pesanan</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background: #f9fafb; margin: 0; padding: 15px; color: #1f2937; }
        .container { max-width: 500px; margin: 0 auto; }
        .card { background: white; padding: 15px; border-radius: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 15px; }
        .item-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f3f4f6; }
        .qty-btn { background: #e5e7eb; border: none; width: 28px; height: 28px; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .payment-option { display: flex; align-items: center; gap: 10px; padding: 12px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 8px; cursor: pointer; transition: 0.2s; }
        .payment-option:hover { background: #fdf8f5; }
        .payment-option input[type="radio"] { margin: 0; accent-color: #8b5e3c; }
        .submit-btn { width: 100%; padding: 12px; background: #8b5e3c; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 15px; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h2 style="margin: 0; font-size: 18px;">Konfirmasi Pesanan</h2>
        <a href="index.php" style="color: #6b7280; text-decoration: none; font-size: 14px;">&larr; Tambah Menu</a>
    </div>

    <div class="card" style="background: #8b5e3c; color: white;">
        <div style="font-size: 12px; opacity: 0.8;">Posisi Meja Kamu:</div>
        <div style="font-size: 18px; font-weight: 700;">MEJA <?= htmlspecialchars($meja_info['nomor_meja']) ?></div>
    </div>

    <!-- Items List -->
    <div class="card">
        <h3 style="margin: 0 0 10px; font-size: 15px;">Item Dipesan</h3>
        <div id="cart-items"></div>
        <div style="display: flex; justify-content: space-between; font-weight: 700; margin-top: 15px; padding-top: 10px; border-top: 2px solid #f3f4f6;">
            <span>Total Bayar:</span>
            <span id="grand-total" style="color: #8b5e3c;">Rp 0</span>
        </div>
    </div>

    <!-- Customer Form -->
    <form action="proses_order.php" method="POST" id="order-form">
        <input type="hidden" name="table_id" value="<?= $meja_info['id'] ?>">
        <input type="hidden" name="cart_data" id="cart_data">
        
        <div class="card">
            <h3 style="margin: 0 0 10px; font-size: 15px;">Data Pemesan</h3>
            <div class="form-group">
                <label>Nama Pemesan *</label>
                <input type="text" name="nama_pelanggan" required placeholder="Masukkan nama kamu">
            </div>
            <div class="form-group">
                <label>Nomor WhatsApp / HP</label>
                <input type="text" name="no_telepon" placeholder="Contoh: 08123456789">
            </div>
            <div class="form-group">
                <label>Catatan Pesanan</label>
                <textarea name="catatan" rows="2" placeholder="Contoh: Esnya dikit aja, gak pakai gula"></textarea>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="card">
            <h3 style="margin: 0 0 10px; font-size: 15px;">Metode Pembayaran</h3>
            
            <label class="payment-option">
                <input type="radio" name="metode_pembayaran" value="kasir" checked onclick="toggleQRIS(false)">
                <div>
                    <strong style="font-size: 14px;"><i class="fa-solid fa-cash-register"></i> Bayar Langsung di Kasir</strong>
                    <div style="font-size: 12px; color: #6b7280;">Bayar tunai atau EDC untuk proses peanana anda di kasir</div>
                </div>
            </label>

            <label class="payment-option">
                <input type="radio" name="metode_pembayaran" value="qris" onclick="toggleQRIS(true)">
                <div>
                    <strong style="font-size: 14px;"><i class="fa-solid fa-qrcode"></i> Bayar QRIS (E-Wallet / M-Banking)</strong>
                    <div style="font-size: 12px; color: #6b7280;">Scan kode QRIS menggunakan GoPay, OVO, Dana, ShopeePay, dll.</div>
                </div>
            </label>

            <!-- Box QRIS (Tersembunyi secara bawaan) -->
            <div id="qris-box" style="display: none; text-align: center; margin-top: 15px; padding: 15px; background: #fff8f0; border: 1px dashed #8b5e3c; border-radius: 8px;">
                <p style="margin: 0 0 8px; font-size: 13px; font-weight: 700; color: #8b5e3c;">Scan QRIS di bawah ini untuk membayar:</p>
                
                <!-- Path diperbarui menggunakan BASE_URL -->
                <img src="<?= BASE_URL ?>assets/images/qris.jpg" 
                    alt="QRIS Cafe" 
                    style="width: 200px; height: 200px; object-fit: contain; border-radius: 8px; border: 1px solid #ddd;" 
                    onerror="this.src='https://via.placeholder.com/200?text=Gambar+QRIS+Tidak+Ditemukan'">
                    
                <p style="margin: 8px 0 0; font-size: 11px; color: #6b7280;">Silakan selesaikan pembayaran sebelum atau setelah mengirimkan pesanan ini.</p>
            </div>

            <button type="submit" class="submit-btn" id="btn-submit">Kirim Pesanan Sekarang</button>
        </div>
    </form>
</div>

<script>
let cart = JSON.parse(localStorage.getItem('cafe_cart')) || [];

function toggleQRIS(show) {
    document.getElementById('qris-box').style.display = show ? 'block' : 'none';
}

function renderCheckout() {
    const container = document.getElementById('cart-items');
    if (cart.length === 0) {
        window.location.href = 'index.php';
        return;
    }

    let html = '';
    let total = 0;

    cart.forEach((item, index) => {
        let subtotal = item.harga * item.qty;
        total += subtotal;
        html += `
            <div class="item-row">
                <div style="flex: 1;">
                    <strong style="font-size: 14px;">${item.nama}</strong>
                    <div style="font-size: 12px; color: #6b7280;">Rp ${item.harga.toLocaleString('id-ID')}</div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button class="qty-btn" onclick="changeQty(${index}, -1)">-</button>
                    <span style="font-weight: 600; font-size: 14px;">${item.qty}</span>
                    <button class="qty-btn" onclick="changeQty(${index}, 1)">+</button>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
    document.getElementById('grand-total').innerText = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('cart_data').value = JSON.stringify(cart);
}

function changeQty(index, change) {
    cart[index].qty += change;
    if (cart[index].qty <= 0) {
        cart.splice(index, 1);
    }
    localStorage.setItem('cafe_cart', JSON.stringify(cart));
    renderCheckout();
}

document.getElementById('order-form').addEventListener('submit', function() {
    document.getElementById('btn-submit').disabled = true;
    document.getElementById('btn-submit').innerText = 'Memproses Pesanan...';
});

renderCheckout();
</script>
</body>
</html>