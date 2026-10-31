-- Skema Tabel Transaksi Kasir (Point of Sales) FEBMart
-- Kelompok 23 - Anggota 3: Transaksi Kasir / Point of Sales

CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `invoice_number` VARCHAR(50) NOT NULL UNIQUE,
    `user_id` INT NULL,
    `customer_name` VARCHAR(100) NOT NULL DEFAULT 'Pelanggan Umum',
    `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `paid_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `change_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_transactions_user_id` (`user_id`),
    KEY `idx_transactions_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `transaction_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `transaction_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `product_name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `qty` INT NOT NULL DEFAULT 1,
    `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    KEY `idx_details_transaction_id` (`transaction_id`),
    KEY `idx_details_product_id` (`product_id`),
    CONSTRAINT `fk_transaction_details_trans` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
