-- ==========================================================
-- قاعدة بيانات متجر المنتجات الذكي (Store Products Database)
-- جاهزة للاستيراد المباشر في phpMyAdmin أو MySQL
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `store_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `store_db`;

-- إنشاء جدول المنتجات مع دعم الصور والباركود
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `price` DECIMAL(10, 2) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بيانات تجريبية (اختياري)
INSERT INTO `products` (`name`, `price`, `image`) VALUES
('قهوة مختصة كولومبية', 18.50, NULL),
('كوب سيراميك يدوي', 12.00, NULL),
('سماعات رأس لاسلكية', 45.00, NULL);
