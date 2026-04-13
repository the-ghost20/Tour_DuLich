-- =============================================================================
-- Dữ liệu mẫu — chạy SAU database/tour_management.sql (database tour_dulich trống)
-- mysql -u root -p tour_dulich < database/sample_data.sql
--
-- Tài khoản mẫu (mật khẩu giống nhau): password
--   admin@dulichviet.test   — admin
--   staff@dulichviet.test   — staff
--   user1@dulichviet.test … user4@dulichviet.test — khách
-- Hash bcrypt tương thích password_hash() PHP (PASSWORD_DEFAULT)
-- =============================================================================

SET NAMES utf8mb4;
USE `tour_dulich`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `blog_feedback`;
TRUNCATE TABLE `tour_reviews`;
TRUNCATE TABLE `bookings`;
TRUNCATE TABLE `tours`;
TRUNCATE TABLE `users`;
TRUNCATE TABLE `categories`;
SET FOREIGN_KEY_CHECKS = 1;

-- Mật khẩu: password (chuỗi 8 ký tự, đủ điều kiện đăng ký)
SET @pwd := '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- -----------------------------------------------------------------------------
-- Danh mục
-- -----------------------------------------------------------------------------
INSERT INTO `categories` (`id`, `name`, `slug`, `sort_order`) VALUES
(1, 'Miền Bắc', 'mien-bac', 1),
(2, 'Miền Trung', 'mien-trung', 2),
(3, 'Miền Nam', 'mien-nam', 3),
(4, 'Quốc tế', 'quoc-te', 4);

-- -----------------------------------------------------------------------------
-- Người dùng
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `role`, `is_active`) VALUES
(1, 'Quản trị viên', 'admin@dulichviet.test', @pwd, '0901000001', 'admin', 1),
(2, 'Nhân viên Hành chính', 'staff@dulichviet.test', @pwd, '0901000002', 'staff', 1),
(3, 'Nguyễn Minh An', 'user1@dulichviet.test', @pwd, '0912000001', 'user', 1),
(4, 'Trần Thu Hà', 'user2@dulichviet.test', @pwd, '0912000002', 'user', 1),
(5, 'Lê Quốc Huy', 'user3@dulichviet.test', @pwd, '0912000003', 'user', 1),
(6, 'Phạm Ngọc Lan', 'user4@dulichviet.test', @pwd, '0912000004', 'user', 1);

-- -----------------------------------------------------------------------------
-- Tour (mô tả rút gọn; ảnh minh họa)
-- -----------------------------------------------------------------------------
INSERT INTO `tours` (`id`, `category_id`, `tour_name`, `description`, `destination`, `duration`, `price`, `image_url`, `available_slots`, `status`, `created_at`) VALUES
(1, 1,
 'Sapa — Fansipan & bản Cát Cát',
 'Tham quan thung lũng Mường Hoa, cáp treo Fansipan, trải nghiệm văn hoá vùng cao.',
 'Lào Cai — Sapa', '3 ngày 2 đêm', 4290000.00,
 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&q=80',
 24, 'hiện', '2025-10-05 09:00:00'),

(2, 2,
 'Huế — Đại Nội & sông Hương',
 'Tham quan Đại Nội, lăng tẩm, du thuyền nghe ca Huế trên sông Hương.',
 'Thừa Thiên Huế', '2 ngày 1 đêm', 2890000.00,
 'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=1200&q=80',
 30, 'hiện', '2025-10-12 10:30:00'),

(3, 3,
 'Phú Quốc — Biển đảo Nam đảo',
 'Lặn ngắm san hô, tham quan Vinpearl Safari, tắm biển Bãi Sao.',
 'Kiên Giang — Phú Quốc', '4 ngày 3 đêm', 6590000.00,
 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&q=80',
 18, 'hiện', '2025-11-01 08:15:00'),

(4, 1,
 'Hà Nội — Hạ Long vịnh di sản',
 'Du thuyền vịnh Hạ Long, hang Sửng Sốt, chèo kayak.',
 'Quảng Ninh — Hạ Long', '2 ngày 1 đêm', 3190000.00,
 'https://images.unsplash.com/photo-1528127269322-539801943592?w=1200&q=80',
 40, 'hiện', '2025-11-20 14:00:00'),

(5, 4,
 'Singapore — Gardens by the Bay',
 'Universal Studios, Gardens by the Bay, Orchard Road.',
 'Singapore', '4 ngày 3 đêm', 15990000.00,
 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=1200&q=80',
 12, 'hiện', '2025-12-03 11:20:00'),

(6, 3,
 'Đà Lạt — Thành phố ngàn hoa',
 'Tham quan Hồ Tuyền Lâm, đồi chè Cầu Đất, chợ đêm.',
 'Lâm Đồng — Đà Lạt', '3 ngày 2 đêm', 2490000.00,
 'https://images.unsplash.com/photo-1596422846543-75c6fc197f07?w=1200&q=80',
 35, 'hiện', '2026-01-10 09:45:00'),

(7, 2,
 'Đà Nẵng — Bà Nà Hills & Hội An',
 'Cầu Vàng, phố cổ Hội An, biển Mỹ Khê.',
 'Đà Nẵng — Hội An', '3 ngày 2 đêm', 3990000.00,
 'https://images.unsplash.com/photo-1559592413-7cec4d0cae2b?w=1200&q=80',
 22, 'ẩn', '2026-01-25 16:00:00'),

(8, 2,
 'Quy Nhơn — Eo Gió & Kỳ Co',
 'Eo Gió, bãi Kỳ Co, ẩm thực hải sản địa phương.',
 'Bình Định — Quy Nhơn', '3 ngày 2 đêm', 3590000.00,
 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?w=1200&q=80',
 28, 'hiện', '2026-02-14 10:00:00');

-- -----------------------------------------------------------------------------
-- Đặt tour — đủ trạng thái cho dashboard & lịch sử khách
-- total_amount = price * (adults + children * 0.5) như booking.php
-- -----------------------------------------------------------------------------
INSERT INTO `bookings` (`id`, `user_id`, `tour_id`, `adults`, `children`, `total_amount`, `status`, `cancel_reason`, `created_at`) VALUES
-- chờ duyệt
(1, 3, 1, 2, 0, 8580000.00, 'chờ duyệt', NULL, '2026-03-01 08:30:00'),
-- đã xác nhận
(2, 4, 2, 1, 1, 4335000.00, 'đã xác nhận', NULL, '2026-02-20 11:00:00'),
-- đã thanh toán
(3, 5, 3, 2, 1, 16475000.00, 'đã thanh toán', NULL, '2026-02-10 15:20:00'),
-- yêu cầu hủy (my_bookings)
(4, 6, 4, 2, 0, 6380000.00, 'yêu cầu hủy', 'Đổi lịch công tác đột xuất.', '2026-02-28 09:00:00'),
-- đã hủy
(5, 3, 5, 1, 0, 15990000.00, 'đã hủy', 'Không đủ người khởi hành.', '2026-01-15 14:00:00'),
-- thêm đơn cho biểu đồ 6 tháng (admin/index.php)
(6, 4, 6, 2, 0, 4980000.00, 'đã xác nhận', NULL, '2025-11-05 10:00:00'),
(7, 5, 8, 1, 2, 7180000.00, 'đã thanh toán', NULL, '2025-12-18 16:30:00'),
(8, 6, 2, 1, 0, 2890000.00, 'chờ duyệt', NULL, '2026-03-10 13:45:00');

-- -----------------------------------------------------------------------------
-- Đánh giá tour (mỗi cặp user+tour tối đa 1 bản ghi)
-- -----------------------------------------------------------------------------
INSERT INTO `tour_reviews` (`tour_id`, `user_id`, `rating`, `comment`, `created_at`) VALUES
(1, 3, 5, 'Cảnh đẹp, hướng dẫn nhiệt tình. Sẽ quay lại!', '2026-02-01 12:00:00'),
(2, 4, 4, 'Huế rất đẹp, ăn ngon. Hơi gấp lịch trình ngày 2.', '2026-02-22 18:00:00'),
(3, 5, 5, 'Biển trong, resort ổn. Nên mang thêm kem chống nắng.', '2026-02-12 09:30:00'),
(6, 6, 4, 'Đà Lạt mát mẻ, điểm check-in đẹp.', '2026-02-25 20:15:00');

-- -----------------------------------------------------------------------------
-- Phản hồi blog (blog.php — không bắt buộc đăng nhập nhưng có user_id)
-- -----------------------------------------------------------------------------
INSERT INTO `blog_feedback` (`user_id`, `rating`, `comment`, `created_at`) VALUES
(3, 5, 'Bài viết hữu ích, mong thêm review tour miền Trung.', '2026-02-05 08:00:00'),
(4, 4, 'Hình ảnh đẹp, nội dung dễ đọc.', '2026-02-08 19:20:00'),
(NULL, 5, 'Khách vãng lai — blog rất hay!', '2026-02-11 11:00:00');

-- Reset AUTO_INCREMENT (tuỳ chọn)
ALTER TABLE `categories` AUTO_INCREMENT = 10;
ALTER TABLE `users` AUTO_INCREMENT = 100;
ALTER TABLE `tours` AUTO_INCREMENT = 20;
ALTER TABLE `bookings` AUTO_INCREMENT = 50;
ALTER TABLE `tour_reviews` AUTO_INCREMENT = 100;
ALTER TABLE `blog_feedback` AUTO_INCREMENT = 50;
