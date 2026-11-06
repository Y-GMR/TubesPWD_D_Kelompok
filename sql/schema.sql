-- =============================================================================
-- Tubes Pemrograman Web Dasar Gasal 2026/2027
-- Universitas Atma Jaya Yogyakarta - Fakultas Teknologi Industri
-- Kelas D - Kelompok TubesPWD_D_Kelompok
--
-- Definisi Skema Basis Data & Data Awal (Seed Data)
-- Mesin Basis Data: MySQL 8.0+ / MariaDB 10.4+ (Lingkungan XAMPP)
-- Mesin Penyimpanan: InnoDB
-- Set Karakter: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `tubespwd_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `tubespwd_db`;

-- -----------------------------------------------------------------------------
-- Hapus tabel yang ada dengan urutan dependensi terbalik (instalasi bersih)
-- -----------------------------------------------------------------------------
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `transaction_details`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Tabel: users
-- Tujuan: Mengelola autentikasi pengguna, data profil, peran akses, dan foto profil.
-- Batasan: Keunikan email dan username, penyimpanan hash kata sandi aman bcrypt.
-- =============================================================================
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `profile_image` VARCHAR(255) NOT NULL DEFAULT 'default_avatar.png',
  `role` ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `uq_users_username` UNIQUE (`username`),
  CONSTRAINT `uq_users_email` UNIQUE (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Tabel: categories
-- Tujuan: Klasifikasi master untuk katalog barang CRUD 1 (one-to-many dengan items).
-- =============================================================================
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `uq_categories_name` UNIQUE (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Tabel: items
-- Tujuan: Inventaris katalog master CRUD 1 (produk, perlengkapan kuliah).
-- Kunci Asing: category_id mereferensikan categories(id) dengan RESTRICT saat hapus.
-- =============================================================================
CREATE TABLE `items` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `stock` INT NOT NULL DEFAULT 0,
  `image_url` VARCHAR(255) NOT NULL DEFAULT 'default_item.png',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_items_category`
    FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_items_category_id` (`category_id`),
  INDEX `idx_items_name` (`name`),
  INDEX `idx_items_price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Tabel: transactions
-- Tujuan: Header transaksi pesanan CRUD 2. Menyimpan total belanja, status,
--         dan koordinat geolokasi pengguna (Bonus 2: Latitude / Longitude).
-- Kunci Asing: user_id mereferensikan users(id) dengan RESTRICT saat hapus.
-- =============================================================================
CREATE TABLE `transactions` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transaction_code` VARCHAR(32) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `latitude` DECIMAL(10, 8) NULL,
  `longitude` DECIMAL(11, 8) NULL,
  `status` ENUM('pending', 'paid', 'shipped', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `uq_transactions_code` UNIQUE (`transaction_code`),
  CONSTRAINT `fk_transactions_user`
    FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_transactions_user_id` (`user_id`),
  INDEX `idx_transactions_status` (`status`),
  INDEX `idx_transactions_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Tabel: transaction_details
-- Tujuan: Rincian item pesanan transaksi CRUD 2 (many-to-one dengan transactions,
--         many-to-one dengan items). Mencatat jumlah dan snapshot harga satuan.
-- =============================================================================
CREATE TABLE `transaction_details` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transaction_id` INT UNSIGNED NOT NULL,
  `item_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  CONSTRAINT `fk_tdetails_transaction`
    FOREIGN KEY (`transaction_id`)
    REFERENCES `transactions` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_tdetails_item`
    FOREIGN KEY (`item_id`)
    REFERENCES `items` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  INDEX `idx_tdetails_transaction_id` (`transaction_id`),
  INDEX `idx_tdetails_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DATA SEED AWAL
-- Kata Sandi Standar Pengujian:
-- Admin:    AdminPassword123! -> Hash Bcrypt di bawah
-- Customer: UserPassword123!  -> Hash Bcrypt di bawah
-- =============================================================================

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `profile_image`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@tubespwd.local', '$2y$10$wN1L9B72jHk1Qj2tUfBVOexmZ1mO3hG6N64/7Xk4cWsmWc.Z0kCee', 'Administrator Sistem', 'default_avatar.png', 'admin', NOW()),
(2, 'johndoe', 'john@example.com', '$2y$10$z7nUaZkJtPqA1t9N7wK7..iOqD3m4n5uV6s8y0x1z2a3b4c5d6e7f', 'John Doe Mahasiswa', 'default_avatar.png', 'customer', NOW());

INSERT INTO `categories` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Elektronik & Gadget', 'Perangkat komputer, laptop, smartphone, dan periferal perkuliahan', NOW()),
(2, 'Peralatan Kuliah & Buku', 'Buku referensi informatika, modul praktikum, dan alat tulis', NOW()),
(3, 'Aksesoris & Merchandise', 'Merchandise kampus, kaos angkatan, dan tas ransel laptop', NOW());

INSERT INTO `items` (`id`, `category_id`, `name`, `description`, `price`, `stock`, `image_url`, `created_at`) VALUES
(1, 1, 'Mouse Wireless Silent Click', 'Mouse nirkabel ergonomis 2.4GHz dengan switch hening cocok untuk ruang kuliah dan perpustakaan.', 125000.00, 25, 'default_item.png', NOW()),
(2, 1, 'Mechanical Keyboard TKL RGB', 'Keyboard mekanikal 87-key dengan switch tactile biru dan backlight RGB dapat diatur.', 450000.00, 15, 'default_item.png', NOW()),
(3, 1, 'USB Hub 4-Port Type-C', 'Adapter multiport USB 3.0 Type-C dengan kecepatan transfer data hingga 5Gbps.', 95000.00, 40, 'default_item.png', NOW()),
(4, 2, 'Buku Pemrograman Web Modern', 'Panduan komprehensif PHP murni, XHTML standar W3C, CSS grid & flexbox, serta JavaScript DOM.', 85000.00, 30, 'default_item.png', NOW()),
(5, 2, 'Notebook Grid A5 Hardcover', 'Buku catatan bersampul tebal dengan kertas grid 100gsm untuk sketsa arsitektur perangkat lunak.', 35000.00, 50, 'default_item.png', NOW()),
(6, 3, 'Backpack Laptop 15.6 Inch Waterproof', 'Tas ransel tahan air dengan kompartemen laptop busa tebal dan port USB charger eksternal.', 275000.00, 20, 'default_item.png', NOW());

INSERT INTO `transactions` (`id`, `transaction_code`, `user_id`, `total_amount`, `latitude`, `longitude`, `status`, `created_at`) VALUES
(1, 'TRX-20261106-0001', 2, 220000.00, -7.77970000, 110.41580000, 'completed', NOW()),
(2, 'TRX-20261106-0002', 2, 450000.00, -7.78010000, 110.41620000, 'pending', NOW());

INSERT INTO `transaction_details` (`id`, `transaction_id`, `item_id`, `quantity`, `unit_price`, `subtotal`) VALUES
(1, 1, 1, 1, 125000.00, 125000.00),
(2, 1, 3, 1, 95000.00, 95000.00),
(3, 2, 2, 1, 450000.00, 450000.00);
