<?php

require_once "../../includes/auth.php";
require_login();

require_once "../../config/database.php";
require_once "../../config/config.php";

$page_title = "Tambah Kategori";
$error = "";

// =====================================================
// PROSES FORM
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status    = $_POST['status'] ?? 'aktif';

    // VALIDASI
    if ($nama === '') {
        $error = "Nama kategori wajib diisi.";
    } elseif (!in_array($status, ['aktif', 'nonaktif'])) {
        $error = "Status tidak valid.";
    } else {
        // CEK DUPLIKAT
        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE nama_kategori = ?
            LIMIT 1
        ");
        $stmt->execute([$nama]);

        if ($stmt->fetch()) {
            $error = "Kategori tersebut sudah ada.";
        } else {
            // INSERT
            $stmt = $pdo->prepare("
                INSERT INTO categories (nama_kategori, deskripsi, status)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $nama,
                $deskripsi !== '' ? $deskripsi : null,
                $status
            ]);

            header("Location: index.php");
            exit;
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

?>

<div style="max-width: 700px; margin: auto;">
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">Tambah Kategori</h3>
        </div>

        <div class="panel-body">
            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 13px; border-radius: 10px; margin-bottom: 20px; font-size: 13px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Nama Kategori</label>
                    <input type="text" name="nama_kategori" placeholder="Contoh: Makanan" value="<?= htmlspecialchars($_POST['nama_kategori'] ?? '') ?>" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px; outline: none;">
                </div>

                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="4" placeholder="Deskripsi kategori..." style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px; resize: vertical; font-family: inherit;"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px; background: white;">
                        <option value="aktif" <?= (($_POST['status'] ?? 'aktif') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= (($_POST['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                    <a href="index.php" style="padding: 11px 17px; border-radius: 9px; background: #f3f4f6; color: #374151; text-decoration: none; font-size: 13px;">Batal</a>
                    <button type="submit" style="padding: 11px 17px; border: 0; border-radius: 9px; background: #8b5e3c; color: white; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-save"></i> Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once "../../includes/footer.php";
?>