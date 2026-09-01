<?php

require_once "../../includes/auth.php";
require_login();

require_once "../../config/database.php";
require_once "../../config/config.php";

$page_title = "Tambah Produk";
$error = "";

// =====================================================
// AMBIL KATEGORI
// =====================================================

$stmt = $pdo->query("
    SELECT id, nama_kategori
    FROM categories
    WHERE status = 'aktif'
    ORDER BY nama_kategori ASC
");

$categories = $stmt->fetchAll();

// =====================================================
// PROSES FORM
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama        = trim($_POST['nama_produk'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $harga       = $_POST['harga'] ?? '';
    $status      = $_POST['status'] ?? 'tersedia';

    // -------------------------------------------------
    // VALIDASI
    // -------------------------------------------------

    if ($nama === '') {
        $error = "Nama produk wajib diisi.";
    } elseif (!ctype_digit((string) $category_id)) {
        $error = "Kategori produk wajib dipilih.";
    } elseif ($harga === '' || !is_numeric($harga) || $harga < 0) {
        $error = "Harga produk tidak valid.";
    } elseif (!in_array($status, ['tersedia', 'habis', 'nonaktif'])) {
        $error = "Status produk tidak valid.";
    }

    // -------------------------------------------------
    // VALIDASI KATEGORI
    // -------------------------------------------------

    if ($error === '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ? AND status = 'aktif'
            LIMIT 1
        ");
        $stmt->execute([(int) $category_id]);

        if (!$stmt->fetch()) {
            $error = "Kategori tidak ditemukan.";
        }
    }

    // -------------------------------------------------
    // CEK NAMA PRODUK
    // -------------------------------------------------

    if ($error === '') {
        $stmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE nama_produk = ?
            LIMIT 1
        ");
        $stmt->execute([$nama]);

        if ($stmt->fetch()) {
            $error = "Nama produk tersebut sudah digunakan.";
        }
    }

    // -------------------------------------------------
    // UPLOAD FOTO
    // -------------------------------------------------

    $nama_file = null;

    if ($error === '' && isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = "Gagal mengupload gambar.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $error = "Ukuran gambar maksimal 2 MB.";
        } else {
            $mime = mime_content_type($file['tmp_name']);

            if (!in_array($mime, $allowed_types)) {
                $error = "Format gambar harus JPG, PNG, atau WEBP.";
            } else {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $nama_file = 'produk_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $upload_dir = "../../assets/uploads/produk/";

                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (!move_uploaded_file($file['tmp_name'], $upload_dir . $nama_file)) {
                    $error = "Gambar gagal disimpan.";
                    $nama_file = null;
                }
            }
        }
    }

    // -------------------------------------------------
    // INSERT
    // -------------------------------------------------

    if ($error === '') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (category_id, nama_produk, deskripsi, harga, foto, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                (int) $category_id,
                $nama,
                $deskripsi !== '' ? $deskripsi : null,
                $harga,
                $nama_file,
                $status
            ]);

            header("Location: index.php");
            exit;

        } catch (PDOException $e) {
            if ($nama_file && file_exists("../../assets/uploads/produk/" . $nama_file)) {
                unlink("../../assets/uploads/produk/" . $nama_file);
            }

            $error = "Produk gagal disimpan ke database.";
        }
    }
}

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

?>

<div style="max-width: 750px; margin: auto;">
    <div class="panel">
        <div class="panel-header">
            <h3 class="panel-title">Tambah Produk</h3>
        </div>

        <div class="panel-body">
            <?php if ($error): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 13px; border-radius: 10px; margin-bottom: 20px; font-size: 13px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- FOTO -->
                <div class="form-group">
                    <label>Foto Produk</label>
                    <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 9px; background: white;">
                    <small style="display: block; margin-top: 6px; color: #9ca3af;">JPG, PNG, WEBP. Maksimal 2 MB.</small>
                </div>

                <!-- NAMA -->
                <div class="form-group">
                    <label>Nama Produk *</label>
                    <input type="text" name="nama_produk" value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>" placeholder="Contoh: Cappuccino" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px;">
                </div>

                <!-- KATEGORI -->
                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="category_id" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px; background: white;">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" <?= ((string) ($_POST['category_id'] ?? '') === (string) $category['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- HARGA -->
                <div class="form-group">
                    <label>Harga *</label>
                    <div style="position: relative;">
                        <span style="position: absolute; left: 12px; top: 12px; color: #6b7280;">Rp</span>
                        <input type="number" name="harga" min="0" step="100" value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" placeholder="25000" required style="width: 100%; padding: 12px 12px 12px 38px; border: 1px solid #d1d5db; border-radius: 9px;">
                    </div>
                </div>

                <!-- DESKRIPSI -->
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" rows="5" placeholder="Deskripsi makanan atau minuman..." style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px; resize: vertical; font-family: inherit;"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
                </div>

                <!-- STATUS -->
                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" required style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 9px; background: white;">
                        <option value="tersedia" <?= (($_POST['status'] ?? 'tersedia') === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
                        <option value="habis" <?= (($_POST['status'] ?? '') === 'habis') ? 'selected' : '' ?>>Habis</option>
                        <option value="nonaktif" <?= (($_POST['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <!-- BUTTON -->
                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                    <a href="index.php" style="padding: 11px 17px; border-radius: 9px; background: #f3f4f6; color: #374151; text-decoration: none; font-size: 13px;">Batal</a>
                    <button type="submit" style="padding: 11px 17px; border: 0; border-radius: 9px; background: #8b5e3c; color: white; font-weight: 600; cursor: pointer;">
                        <i class="fa-solid fa-save"></i> Simpan Produk
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once "../../includes/footer.php";
?>