<?php
require_once "../../includes/auth.php";
if (function_exists('require_login')) {
    require_login();
}

require_once "../../config/database.php";
require_once "../../config/config.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM tables WHERE id = ?");
$stmt->execute([$id]);
$meja = $stmt->fetch();

if (!$meja) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomor_meja = trim($_POST['nomor_meja'] ?? '');
    $kode_meja  = trim($_POST['kode_meja'] ?? '');
    $status     = $_POST['status'] ?? 'tersedia';

    if (empty($nomor_meja) || empty($kode_meja)) {
        $error = 'Nomor meja dan kode meja wajib diisi!';
    } else {
        try {
            $orderUrl = BASE_URL . "index.php?meja=" . urlencode($kode_meja);
            $qr_code  = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($orderUrl);

            $stmt = $pdo->prepare("UPDATE tables SET nomor_meja = ?, kode_meja = ?, qr_code = ?, status = ? WHERE id = ?");
            $stmt->execute([$nomor_meja, $kode_meja, $qr_code, $status, $id]);

            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $error = 'Gagal memperbarui meja: ' . $e->getMessage();
        }
    }
}

$page_title = "Edit Meja";
require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div style="max-width: 500px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Edit Meja</h2>
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
                    <input type="text" name="nomor_meja" value="<?= htmlspecialchars($meja['nomor_meja']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kode Meja *</label>
                    <input type="text" name="kode_meja" value="<?= htmlspecialchars($meja['kode_meja']) ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status *</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <option value="tersedia" <?= $meja['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                        <option value="digunakan" <?= $meja['status'] === 'digunakan' ? 'selected' : '' ?>>Digunakan</option>
                        <option value="nonaktif" <?= $meja['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <button type="submit" style="padding: 12px; background: #8b5e3c; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Update Meja
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>