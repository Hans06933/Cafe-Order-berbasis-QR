<?php
require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";
require_once "../../config/config.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    die("ID Meja tidak valid.");
}

$stmt = $pdo->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->execute([$id]);
$meja = $stmt->fetch();

if (!$meja) {
    die("Data meja tidak ditemukan.");
}

// Buat URL Pelanggan berdasar BASE_URL dinamis
$url_order = BASE_URL . "index.php?meja=" . urlencode($meja['kode_meja']);

// API Generator QR Code
$qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($url_order);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR - Meja <?= htmlspecialchars($meja['nomor_meja']) ?></title>
    <style>
        body { font-family: sans-serif; text-align: center; padding: 20px; background: #f9fafb; }
        .card { background: white; padding: 20px; border-radius: 12px; display: inline-block; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .url-preview { font-size: 11px; color: #6b7280; margin-top: 10px; word-break: break-all; max-width: 250px; }
    </style>
</head>
<body>

<div class="card">
    <h2>MEJA <?= htmlspecialchars($meja['nomor_meja']) ?></h2>
    <img src="<?= $qr_api_url ?>" alt="QR Code" style="width: 200px; height: 200px;">
    
    <!-- Teks pembantu untuk cek isi URL QR Code -->
    <div class="url-preview">
        <strong>Target Link:</strong><br>
        <?= htmlspecialchars($url_order) ?>
    </div>
</div>

</body>
</html>