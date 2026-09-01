<?php
require_once "../../includes/auth.php";
require_login();

require_once "../../config/database.php";
require_once "../../config/config.php";

$page_title = "Produk";

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

// =====================================================
// FILTER
// =====================================================
$keyword     = trim($_GET['keyword'] ?? '');
$category_id = $_GET['category_id'] ?? '';
$status      = $_GET['status'] ?? '';

// =====================================================
// QUERY PRODUK (Sudah disesuaikan: p.gambar)
// =====================================================
$sql = "
    SELECT
        p.id,
        p.nama_produk,
        p.deskripsi,
        p.harga,
        p.gambar,
        p.status,
        p.created_at,
        c.nama_kategori
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE 1=1
";

$params = [];

// SEARCH
if ($keyword !== '') {
    $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

// FILTER KATEGORI
if ($category_id !== '' && ctype_digit($category_id)) {
    $sql .= " AND p.category_id = ?";
    $params[] = (int) $category_id;
}

// FILTER STATUS
if ($status !== '' && in_array($status, ['tersedia', 'habis', 'nonaktif'])) {
    $sql .= " AND p.status = ?";
    $params[] = $status;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// =====================================================
// KATEGORI
// =====================================================
$stmt = $pdo->query("
    SELECT id, nama_kategori
    FROM categories
    ORDER BY nama_kategori ASC
");
$categories = $stmt->fetchAll();
?>

<!-- KUNCI UTAMA: Wajib dibungkus .main-wrapper dan .content agar tidak tertutup sidebar -->
<div class="main-wrapper">
    <div class="content">

        <!-- HEADER HALAMAN -->
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap; margin-bottom: 22px;">
            <div>
                <h2 style="margin: 0 0 5px; font-size: 20px;">Daftar Produk</h2>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Kelola makanan dan minuman yang tersedia di café.</p>
            </div>
            <a href="tambah.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; background: #8b5e3c; color: white; text-decoration: none; border-radius: 10px; font-size: 13px; font-weight: 600;">
                <i class="fa-solid fa-plus"></i> Tambah Produk
            </a>
        </div>

        <!-- FILTER -->
        <div class="panel" style="margin-bottom: 20px;">
            <div class="panel-body">
                <form method="GET" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto auto; gap: 10px; align-items: end;">
                    <div class="form-group" style="margin: 0;">
                        <label>Cari Produk</label>
                        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>" placeholder="Cari nama produk..." style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 9px;">
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label>Kategori</label>
                        <select name="category_id" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 9px; background: white;">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (string) $category_id === (string) $category['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin: 0;">
                        <label>Status</label>
                        <select name="status" style="width: 100%; padding: 11px 12px; border: 1px solid #d1d5db; border-radius: 9px; background: white;">
                            <option value="">Semua Status</option>
                            <option value="tersedia" <?= $status === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="habis" <?= $status === 'habis' ? 'selected' : '' ?>>Habis</option>
                            <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                    <button type="submit" style="height: 40px; padding: 0 16px; border: 0; border-radius: 9px; background: #8b5e3c; color: white; cursor: pointer; font-weight: 600;">
                        <i class="fa-solid fa-filter"></i> Filter
                    </button>

                    <a href="index.php" style="height: 40px; display: flex; align-items: center; justify-content: center; padding: 0 15px; border-radius: 9px; background: #f3f4f6; color: #374151; text-decoration: none; font-size: 13px;">
                        Reset
                    </a>
                </form>
            </div>
        </div>

        <!-- TABEL PRODUK -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Semua Produk</h3>
                <span style="color: #6b7280; font-size: 12px;"><?= count($products) ?> produk</span>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 45px; color: #9ca3af;">
                                        <i class="fa-solid fa-utensils" style="font-size: 32px; margin-bottom: 12px;"></i><br>
                                        Tidak ada produk ditemukan.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?php if (!empty($product['gambar'])): ?>
                                                    <img src="<?= BASE_URL ?>assets/uploads/produk/<?= htmlspecialchars($product['gambar']) ?>" alt="<?= htmlspecialchars($product['nama_produk']) ?>" style="width: 52px; height: 52px; object-fit: cover; border-radius: 10px; border: 1px solid #e5e7eb;">
                                                <?php else: ?>
                                                    <div style="width: 52px; height: 52px; border-radius: 10px; background: #f5ebe3; color: #8b5e3c; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fa-solid fa-utensils"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($product['nama_produk']) ?></strong>
                                                    <?php if (!empty($product['deskripsi'])): ?>
                                                        <div style="color: #9ca3af; font-size: 11px; margin-top: 3px;">
                                                            <?= htmlspecialchars(strlen($product['deskripsi']) > 45 ? substr($product['deskripsi'], 0, 45) . '...' : $product['deskripsi']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($product['nama_kategori'] ?? 'Tanpa kategori') ?></td>
                                        <td><strong style="color: #8b5e3c;">Rp <?= number_format($product['harga'], 0, ',', '.') ?></strong></td>
                                        <td>
                                            <?php
                                            $statusClass = 'badge-info';
                                            if ($product['status'] === 'tersedia') {
                                                $statusClass = 'badge-success';
                                            } elseif ($product['status'] === 'habis') {
                                                $statusClass = 'badge-warning';
                                            } elseif ($product['status'] === 'nonaktif') {
                                                $statusClass = 'badge-danger';
                                            }
                                            ?>
                                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars(ucfirst($product['status'])) ?></span>
                                        </td>
                                        <td><?= date('d M Y', strtotime($product['created_at'])) ?></td>
                                        <td>
                                            <div style="display: flex; gap: 7px;">
                                                <a href="edit.php?id=<?= $product['id'] ?>" title="Edit" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f3f4f6; color: #374151; text-decoration: none;">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="hapus.php?id=<?= $product['id'] ?>" title="Hapus" onclick="return confirm('Yakin ingin menghapus produk ini?');" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fee2e2; color: #dc2626; text-decoration: none;">
                                                    <i class="fa-solid fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once "../../includes/footer.php";
?>