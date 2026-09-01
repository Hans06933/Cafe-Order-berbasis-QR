-- ============================================================
-- DATABASE SISTEM PEMESANAN CAFE
-- Project : cafe-order
-- Database: db_cafe
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_cafe
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE db_cafe;

-- ============================================================
-- 1. TABEL USERS
-- ============================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. TABEL CATEGORIES
-- ============================================================

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. TABEL PRODUCTS
-- ============================================================

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    deskripsi TEXT NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    gambar VARCHAR(255) NULL,
    status ENUM('tersedia', 'habis', 'nonaktif') NOT NULL DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 4. TABEL TABLES / MEJA
-- ============================================================

CREATE TABLE tables (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nomor_meja VARCHAR(20) NOT NULL UNIQUE,
    kode_meja VARCHAR(100) NOT NULL UNIQUE,
    qr_code VARCHAR(255) NULL,
    status ENUM('tersedia', 'digunakan', 'nonaktif') NOT NULL DEFAULT 'tersedia',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 5. TABEL ORDERS / PESANAN
-- ============================================================

CREATE TABLE orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode_pesanan VARCHAR(50) NOT NULL UNIQUE,
    table_id INT UNSIGNED NOT NULL,
    nama_pelanggan VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20) NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM(
        'pending',
        'diproses',
        'siap',
        'selesai',
        'dibatalkan'
    ) NOT NULL DEFAULT 'pending',
    catatan TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_table
        FOREIGN KEY (table_id)
        REFERENCES tables(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 6. TABEL ORDER DETAILS
-- ============================================================

CREATE TABLE order_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    nama_produk VARCHAR(150) NOT NULL,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    qty INT UNSIGNED NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    catatan VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_details_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_order_details_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- 7. TABEL PAYMENTS / PEMBAYARAN
-- ============================================================

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    metode ENUM('cash', 'qris', 'transfer') NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM(
        'pending',
        'menunggu_konfirmasi',
        'dibayar',
        'gagal',
        'dibatalkan'
    ) NOT NULL DEFAULT 'pending',
    bukti_pembayaran VARCHAR(255) NULL,
    dibayar_oleh INT UNSIGNED NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_payments_user
        FOREIGN KEY (dibayar_oleh)
        REFERENCES users(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- INDEX
-- ============================================================

CREATE INDEX idx_products_category
ON products(category_id);

CREATE INDEX idx_products_status
ON products(status);

CREATE INDEX idx_orders_table
ON orders(table_id);

CREATE INDEX idx_orders_status
ON orders(status);

CREATE INDEX idx_orders_created
ON orders(created_at);

CREATE INDEX idx_order_details_order
ON order_details(order_id);

CREATE INDEX idx_order_details_product
ON order_details(product_id);

CREATE INDEX idx_payments_order
ON payments(order_id);

CREATE INDEX idx_payments_status
ON payments(status);

-- ============================================================
-- DATA AWAL
-- ============================================================

-- ============================================================
-- USERS
-- ============================================================
-- Password sementara:
-- admin  = password
-- kasir  = password
--
-- Password akan kita ubah menggunakan password_hash()
-- pada tahap pembuatan sistem login.
-- ============================================================

INSERT INTO users
(nama, username, password, role, status)
VALUES
('Administrator', 'admin', 'password', 'admin', 'aktif'),
('Kasir Café', 'kasir', 'password', 'kasir', 'aktif');

-- ============================================================
-- CATEGORIES
-- ============================================================

INSERT INTO categories
(nama_kategori, deskripsi, status)
VALUES
('Makanan', 'Berbagai macam makanan utama.', 'aktif'),
('Minuman', 'Berbagai macam minuman café.', 'aktif'),
('Dessert', 'Aneka makanan penutup.', 'aktif'),
('Snack', 'Aneka makanan ringan.', 'aktif');

-- ============================================================
-- PRODUCTS
-- ============================================================

INSERT INTO products
(category_id, nama_produk, deskripsi, harga, gambar, status)
VALUES
(
    1,
    'Nasi Goreng Spesial',
    'Nasi goreng spesial dengan telur, ayam, dan sayuran.',
    25000,
    NULL,
    'tersedia'
),
(
    1,
    'Chicken Steak',
    'Chicken steak dengan kentang dan sayuran.',
    35000,
    NULL,
    'tersedia'
),
(
    1,
    'Mie Goreng Spesial',
    'Mie goreng dengan telur, ayam, dan sayuran.',
    23000,
    NULL,
    'tersedia'
),
(
    2,
    'Café Latte',
    'Espresso dengan steamed milk yang lembut.',
    18000,
    NULL,
    'tersedia'
),
(
    2,
    'Cappuccino',
    'Espresso dengan susu dan foam yang lembut.',
    20000,
    NULL,
    'tersedia'
),
(
    2,
    'Es Kopi Susu',
    'Kopi susu dengan rasa creamy dan menyegarkan.',
    18000,
    NULL,
    'tersedia'
),
(
    2,
    'Matcha Latte',
    'Minuman matcha dengan susu yang creamy.',
    22000,
    NULL,
    'tersedia'
),
(
    3,
    'Cheesecake',
    'Cheesecake lembut dengan rasa keju yang khas.',
    22000,
    NULL,
    'tersedia'
),
(
    3,
    'Chocolate Cake',
    'Kue cokelat lembut dengan topping cokelat.',
    20000,
    NULL,
    'tersedia'
),
(
    4,
    'French Fries',
    'Kentang goreng renyah dengan saus.',
    15000,
    NULL,
    'tersedia'
),
(
    4,
    'Chicken Wings',
    'Chicken wings dengan saus pilihan.',
    25000,
    NULL,
    'tersedia'
);

-- ============================================================
-- TABLES / MEJA
-- ============================================================

INSERT INTO tables
(nomor_meja, kode_meja, qr_code, status)
VALUES
('01', 'MEJA-001', NULL, 'tersedia'),
('02', 'MEJA-002', NULL, 'tersedia'),
('03', 'MEJA-003', NULL, 'tersedia'),
('04', 'MEJA-004', NULL, 'tersedia'),
('05', 'MEJA-005', NULL, 'tersedia'),
('06', 'MEJA-006', NULL, 'tersedia'),
('07', 'MEJA-007', NULL, 'tersedia'),
('08', 'MEJA-008', NULL, 'tersedia'),
('09', 'MEJA-009', NULL, 'tersedia'),
('10', 'MEJA-010', NULL, 'tersedia');

-- ============================================================
-- SELESAI
-- ============================================================