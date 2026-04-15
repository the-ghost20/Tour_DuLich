-- Chạy một lần trên DB tour_dulich đã có schema tour_management.sql
-- mysql -u root -p -P 8889 tour_dulich < database/migrations/001_admin_coupons_blog.sql

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `coupons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(40) NOT NULL,
  `discount_type` ENUM('percent','fixed') NOT NULL DEFAULT 'percent',
  `discount_value` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `min_order_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `max_uses` INT UNSIGNED NULL DEFAULT NULL,
  `used_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `starts_at` DATE NULL DEFAULT NULL,
  `expires_at` DATE NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_coupons_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(280) NOT NULL,
  `excerpt` VARCHAR(500) NULL DEFAULT NULL,
  `body` MEDIUMTEXT NULL,
  `status` ENUM('draft','published') NOT NULL DEFAULT 'draft',
  `published_at` DATETIME NULL DEFAULT NULL,
  `author_id` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blog_posts_slug` (`slug`),
  KEY `idx_blog_posts_status` (`status`),
  CONSTRAINT `fk_blog_posts_author`
    FOREIGN KEY (`author_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_uses`, `used_count`, `starts_at`, `expires_at`, `is_active`) VALUES
(1, 'SUMMER10', 'percent', 10.00, 2000000.00, 100, 0, '2026-01-01', '2026-12-31', 1),
(2, 'GIAM500K', 'fixed', 500000.00, 5000000.00, 50, 0, '2026-03-01', NULL, 1);

INSERT IGNORE INTO `blog_posts` (`id`, `title`, `slug`, `excerpt`, `body`, `status`, `published_at`, `author_id`) VALUES
(1,
 'Mẹo săn tour giá tốt mùa cao điểm',
 'meo-san-tour-gia-tot',
 'Chọn thời điểm đặt, so sánh gói và tận dụng mã giảm giá.',
 '<p>Nội dung mẫu: đặt sớm 2–3 tuần, theo dõi khuyến mãi cuối tuần, kết hợp mã giảm giá hợp lệ.</p>',
 'published', '2026-02-01 09:00:00', 1),
(2,
 'Checklist hành lý đi biển 3 ngày',
 'checklist-hanh-ly-bien',
 'Gọn nhẹ mà đủ dùng cho chuyến nghỉ dưỡng.',
 '<p>Nội dung mẫu: kem chống nắng, dép, sạc dự phòng, giấy tờ photo.</p>',
 'draft', NULL, 1);
