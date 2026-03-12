-- ============================================
-- Database Structure untuk Sistem Kasir Modern
-- Import file ini ke phpMyAdmin di InfinityFree
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('kasir','manajer') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `products`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_produk` varchar(255) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT '0',
  `stok_minimum` int(11) NOT NULL DEFAULT '10',
  `gambar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_transaksi` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `jumlah_bayar` decimal(10,2) NOT NULL,
  `kembalian` decimal(10,2) NOT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `status` enum('completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `transaction_items`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `transaction_id` (`transaction_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Dumping data for table `users`
-- Password untuk semua user: "password"
-- Hash bcrypt: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'manajer'),
('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir Satu', 'kasir');

-- --------------------------------------------------------
-- Sample data untuk table `products` (opsional)
-- Hapus bagian ini jika tidak ingin data sample
-- --------------------------------------------------------

INSERT INTO `products` (`nama_produk`, `barcode`, `harga`, `stok`, `stok_minimum`, `status`) VALUES
('Produk Sample 1', '1234567890123', 10000.00, 50, 10, 'active'),
('Produk Sample 2', '1234567890124', 15000.00, 30, 10, 'active'),
('Produk Sample 3', '1234567890125', 20000.00, 25, 10, 'active');

