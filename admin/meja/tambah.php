<?php
require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";
require_once "../../config/config.php";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_meja = trim($_POST['nomor_meja'] ?? '');
    $kode_meja  = trim($_POST['kode_meja'] ?? '');
    $status     = $_POST['status'] ?? 'tersedia';

    // Jika kode_meja dikosongkan, buat otomatis (contoh: MEJA-01)
    if (empty($kode_meja) && !empty($nomor_meja)) {
        $kode_meja = 'MEJA-' . strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $nomor_meja));
    }

    if (empty($nomor_meja) || empty($kode_meja)) {
        $error = 'Nomor meja dan kode meja wajib diisi!';
    } else {
        try {
            // Memastikan BASE_URL memiliki slash (/) di akhir
            $base_url_fixed = rtrim(BASE_URL, '/') . '/';

            // Generate URL lengkap menuju halaman pemesanan pelanggan
            $orderUrl = $base_url_fixed . "index.php?meja=" . urlencode($kode_meja);

            // Generate link image dari QRServer API dengan URL lengkap
            $qr_code  = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($orderUrl);

            $stmt = $pdo->prepare("INSERT INTO tables (nomor_meja, kode_meja, qr_code, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nomor_meja, $kode_meja, $qr_code, $status]);

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = 'Nomor meja atau Kode meja sudah ada di database!';
            } else {
                $error = 'Gagal menyimpan meja: ' . $e->getMessage();
            }
        }
    }
}

$page_title = "Tambah Meja";
require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div style="max-width: 500px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Tambah Meja Baru</h2>
        <a href="index.php" style="color: #6b7280; text-decoration: none;">&larr; Kembali</a>
    </div>

    <?php if (!empty($error)): ?>
        <div style="padding: 12px; background: #fee2e2; color: #dc2626; border-radius: 8px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-body">
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nomor / Nama Meja *</label>
                    <input type="text" name="nomor_meja" placeholder="Contoh: Meja 01 atau VIP 1" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kode Meja (Unik)</label>
                    <input type="text" name="kode_meja" placeholder="Contoh: M01 (Biarkan kosong untuk auto-generate)" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status *</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <option value="tersedia">Tersedia</option>
                        <option value="digunakan">Digunakan</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <button type="submit" style="padding: 12px; background: #8b5e3c; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Simpan Meja
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>