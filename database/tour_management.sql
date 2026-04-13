-- =============================================================================
-- Tour Du Lịch — Tạo database + bảng (MySQL 8.x / MariaDB 10.5+, InnoDB, utf8mb4)
-- Import: mysql -u root -p < database/tour_management.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `tour_dulich`;
CREATE DATABASE `tour_dulich`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `tour_dulich`;

-- -----------------------------------------------------------------------------
-- Danh mục tour (admin/categories)
-- -----------------------------------------------------------------------------
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `slug` VARCHAR(160) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Người dùng (auth: user / staff / admin)
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL DEFAULT '',
  `role` ENUM('user','staff','admin') NOT NULL DEFAULT 'user',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tour (frontend: tours, tour_detail, booking; admin: tours/list)
-- -----------------------------------------------------------------------------
CREATE TABLE `tours` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL DEFAULT NULL,
  `tour_name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `itinerary` MEDIUMTEXT NULL DEFAULT NULL,
  `destination` VARCHAR(255) NOT NULL,
  `duration` VARCHAR(80) NOT NULL DEFAULT '',
  `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `image_url` VARCHAR(512) NULL DEFAULT NULL,
  `available_slots` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` ENUM('hiện','ẩn') NOT NULL DEFAULT 'hiện',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tours_category` (`category_id`),
  KEY `idx_tours_status` (`status`),
  CONSTRAINT `fk_tours_category`
    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Đặt tour (booking.php, my_bookings.php, admin dashboard)
-- Trạng thái khớp code PHP (match/case trong my_bookings.php)
-- -----------------------------------------------------------------------------
CREATE TABLE `bookings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `tour_id` INT UNSIGNED NOT NULL,
  `adults` INT UNSIGNED NOT NULL DEFAULT 1,
  `children` INT UNSIGNED NOT NULL DEFAULT 0,
  `departure_date` DATE NULL DEFAULT NULL,
  `coupon_code` VARCHAR(40) NULL DEFAULT NULL,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `holiday_surcharge_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `holiday_surcharge_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM(
    'chờ duyệt',
    'đã xác nhận',
    'đã thanh toán',
    'yêu cầu hủy',
    'đã hủy'
  ) NOT NULL DEFAULT 'chờ duyệt',
  `cancel_reason` TEXT NULL DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_tour` (`tour_id`),
  KEY `idx_bookings_status` (`status`),
  KEY `idx_bookings_created` (`created_at`),
  CONSTRAINT `fk_bookings_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_tour`
    FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Đánh giá tour (tour_detail.php — ON DUPLICATE KEY tour_id + user_id)
-- -----------------------------------------------------------------------------
CREATE TABLE `tour_reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tour_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tour_reviews_tour_user` (`tour_id`,`user_id`),
  KEY `idx_tour_reviews_tour` (`tour_id`),
  CONSTRAINT `fk_tour_reviews_tour`
    FOREIGN KEY (`tour_id`) REFERENCES `tours` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tour_reviews_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Phản hồi blog (blog.php)
-- -----------------------------------------------------------------------------
CREATE TABLE `blog_feedback` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL DEFAULT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_blog_feedback_user` (`user_id`),
  CONSTRAINT `fk_blog_feedback_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Mã giảm giá & bài blog (admin: coupons/*, blog/*) — có thể import thêm sample từ
-- database/migrations/001_admin_coupons_blog.sql
-- -----------------------------------------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;
