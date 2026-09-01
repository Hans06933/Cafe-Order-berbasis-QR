<?php
require_once "../../includes/auth.php";
require_login();

require_once "../../config/database.php";
require_once "../../config/config.php";

$error = '';

// Ambil data kategori untuk dropdown
$stmt = $pdo->query("SELECT id, nama_kategori FROM categories ORDER BY nama_kategori ASC");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $harga       = $_POST['harga'] ?? 0;
    $deskripsi   = trim($_POST['deskripsi'] ?? '');
    $status      = $_POST['status'] ?? 'tersedia';

    // Validasi
    if (empty($nama_produk) || empty($category_id) || empty($harga)) {
        $error = 'Nama produk, kategori, dan harga wajib diisi!';
    } else {
        $filename = null;

        // Proses Upload Gambar
        if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['gambar']['tmp_name'];
            $fileName    = $_FILES['gambar']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = time() . '_' . uniqid() . '.' . $fileExtension;
                $uploadFileDir = '../../assets/uploads/produk/';

                // Buat folder jika belum ada
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }

                $dest_path = $uploadFileDir . $newFileName;
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $filename = $newFileName;
                } else {
                    $error = 'Gagal mengupload gambar produk.';
                }
            } else {
                $error = 'Format file gambar harus JPG, JPEG, PNG, atau WEBP.';
            }
        }

        // Simpan ke DB jika tidak ada error upload
        if (empty($error)) {
            $sql = "INSERT INTO products (category_id, nama_produk, deskripsi, harga, gambar, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $inserted = $stmt->execute([
                (int)$category_id,
                $nama_produk,
                $deskripsi !== '' ? $deskripsi : null,
                (float)$harga,
                $filename,
                $status
            ]);

            if ($inserted) {
                header("Location: index.php");
                exit;
            } else {
                $error = 'Gagal menyimpan data produk ke database.';
            }
        }
    }
}

$page_title = "Tambah Produk";
require_once "../../includes/header.php";
require_once "../../includes/navbar.php";
?>

<div style="max-width: 650px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Tambah Produk Baru</h2>
        <a href="index.php" style="color: #6b7280; text-decoration: none;">&larr; Kembali</a>
    </div>

    <?php if (!empty($error)): ?>
        <div style="padding: 12px; background: #fee2e2; color: #dc2626; border-radius: 8px; margin-bottom: 20px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="panel">
        <div class="panel-body">
            <form action="" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; gap: 15px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Nama Produk *</label>
                    <input type="text" name="nama_produk" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Kategori *</label>
                    <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Harga (Rp) *</label>
                    <input type="number" step="0.01" name="harga" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Status *</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white;">
                        <option value="tersedia">Tersedia</option>
                        <option value="habis">Habis</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Foto Gambar Produk</label>
                    <input type="file" name="gambar" accept="image/*" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 8px;">
                </div>

                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: 600;">Deskripsi</label>
                    <textarea name="deskripsi" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;"></textarea>
                </div>

                <button type="submit" style="padding: 12px; background: #8b5e3c; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                    Simpan Produk
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>