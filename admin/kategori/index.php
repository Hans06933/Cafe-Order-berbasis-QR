<?php
require_once "../../includes/auth.php";
require_login();

require_once "../../config/database.php";
require_once "../../config/config.php";

$page_title = "Kategori";

require_once "../../includes/header.php";
require_once "../../includes/navbar.php";

// =====================================================
// AMBIL DATA KATEGORI
// =====================================================

$stmt = $pdo->query("
    SELECT
        c.id,
        c.nama_kategori,
        c.deskripsi,
        c.status,
        c.created_at,
        COUNT(p.id) AS jumlah_produk
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.nama_kategori, c.deskripsi, c.status, c.created_at
    ORDER BY c.id DESC
");

$categories = $stmt->fetchAll();

?>

<!-- KUNCI UTAMA: Wajib dibungkus .main-wrapper dan .content agar tidak tertutup sidebar -->
<div class="main-wrapper">
    <div class="content">

        <!-- HEADER HALAMAN -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 15px; flex-wrap: wrap;">
            <div>
                <h2 style="margin: 0 0 5px; font-size: 20px;">Daftar Kategori</h2>
                <p style="margin: 0; color: #6b7280; font-size: 13px;">Kelola kategori menu café.</p>
            </div>
            <a href="tambah.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 11px 16px; background: #8b5e3c; color: white; text-decoration: none; border-radius: 10px; font-size: 13px; font-weight: 600;">
                <i class="fa-solid fa-plus"></i> Tambah Kategori
            </a>
        </div>

        <!-- TABEL KATEGORI -->
        <div class="panel">
            <div class="panel-header">
                <h3 class="panel-title">Semua Kategori</h3>
                <span style="font-size: 12px; color: #6b7280;"><?= count($categories) ?> kategori</span>
            </div>

            <div class="panel-body">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th>Produk</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">
                                        <i class="fa-solid fa-layer-group" style="font-size: 30px; margin-bottom: 10px;"></i><br>
                                        Belum ada kategori.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $index => $category): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>

                                        <td>
                                            <strong><?= htmlspecialchars($category['nama_kategori']) ?></strong>
                                        </td>

                                        <td>
                                            <?php
                                            $deskripsi = $category['deskripsi'];
                                            if (empty($deskripsi)) {
                                                echo '<span style="color: #9ca3af;">Tidak ada deskripsi</span>';
                                            } else {
                                                echo htmlspecialchars(strlen($deskripsi) > 60 ? substr($deskripsi, 0, 60) . '...' : $deskripsi);
                                            }
                                            ?>
                                        </td>

                                        <td>
                                            <span class="badge badge-info"><?= $category['jumlah_produk'] ?> produk</span>
                                        </td>

                                        <td>
                                            <?php if ($category['status'] === 'aktif'): ?>
                                                <span class="badge badge-success">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Nonaktif</span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div style="display: flex; gap: 7px;">
                                                <a href="edit.php?id=<?= $category['id'] ?>" title="Edit" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #f3f4f6; color: #374151; text-decoration: none;">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                                <a href="hapus.php?id=<?= $category['id'] ?>" title="Hapus" onclick="return confirm('Yakin ingin menghapus kategori ini?');" style="width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fee2e2; color: #dc2626; text-decoration: none;">
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