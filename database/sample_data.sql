-- =============================================================================
-- Dữ liệu mẫu — chạy SAU database/tour_management.sql (database tour_dulich trống)
-- mysql -u root -p tour_dulich < database/sample_data.sql
--
-- Tài khoản mẫu (mật khẩu giống nhau): password
--   admin.dulichviet@gmail.com   — admin
--   staff.dulichviet@gmail.com   — staff
--   user1.dulichviet@gmail.com … user4.dulichviet@gmail.com — khách
-- Hash bcrypt tương thích password_hash() PHP (PASSWORD_DEFAULT)
-- =============================================================================

SET NAMES utf8mb4;
USE `tour_dulich`;

-- Xóa dữ liệu cũ: dùng DELETE (theo thứ tự FK) thay vì TRUNCATE.
-- phpMyAdmin tách từng câu lệnh nên TRUNCATE `tours` hay lỗi #1701 (FK từ `bookings`).
DELETE FROM `blog_feedback`;
DELETE FROM `tour_reviews`;
DELETE FROM `bookings`;
DELETE FROM `blog_posts`;
DELETE FROM `coupons`;
DELETE FROM `tours`;
DELETE FROM `users`;
DELETE FROM `categories`;

-- Mật khẩu: password (chuỗi 8 ký tự, đủ điều kiện đăng ký)
-- Hash cho mật khẩu: password (đã kiểm tra password_verify trên PHP 8.x)
SET @pwd := '$2y$10$38S8lbqM25prVKtW.uh0Nu42qZnJT0F7dLyHq1aP6xMEfMn3.sZja';

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
(1, 'Quản trị viên', 'admin.dulichviet@gmail.com', @pwd, '0901000001', 'admin', 1),
(2, 'Nhân viên Hành chính', 'staff.dulichviet@gmail.com', @pwd, '0901000002', 'staff', 1),
(3, 'Nguyễn Minh An', 'user1.dulichviet@gmail.com', @pwd, '0912000001', 'user', 1),
(4, 'Trần Thu Hà', 'user2.dulichviet@gmail.com', @pwd, '0912000002', 'user', 1),
(5, 'Lê Quốc Huy', 'user3.dulichviet@gmail.com', @pwd, '0912000003', 'user', 1),
(6, 'Phạm Ngọc Lan', 'user4.dulichviet@gmail.com', @pwd, '0912000004', 'user', 1);

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
-- Lịch trình chi tiết theo ngày (cột itinerary — JSON)
-- -----------------------------------------------------------------------------
UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Hà Nội → Lào Cai, nhận phòng Sapa","body":"Đón khách theo điểm hẹn tại Hà Nội. Xe giường nằm hoặc limousine về Lào Cai, lên Sapa. Nhận phòng khách sạn, nghỉ ngơi. Chiều tự do dạo thị trấn, chợ tối, thưởng thức đặc sản vùng cao."},
{"title":"Ngày 2 — Fansipan và bản Cát Cát","body":"Sáng đi cáp treo Fansipan, chiêm ngưỡng biển mây và đỉnh núi. Trưa về nghỉ. Chiều thăm bản Cát Cát, thác nước, giao lưu văn nghệ, tìm hiểu văn hóa đồng bào vùng cao."},
{"title":"Ngày 3 — Thung lũng Mường Hoa — về Hà Nội","body":"Tham quan thung lũng Mường Hoa tùy điều kiện thời tiết. Trưa trả phòng. Xe đưa về Hà Nội, kết thúc chương trình."}
]' WHERE `id` = 1;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Đại Nội và di tích Huế","body":"Đón tại Huế, tham quan Đại Nội, điện Thái Hòa, Tử Cấm Thành. Chiều viếng lăng Khải Định hoặc Minh Mạng. Tối tự do thưởng thức cơm hến, chè Huế."},
{"title":"Ngày 2 — Sông Hương và tiễn đoàn","body":"Sáng du thuyền sông Hương, thăm chùa Thiên Mụ, nghe ca Huế trên thuyền. Trưa trả phòng, tiễn sân bay hoặc ga, kết thúc."}
]' WHERE `id` = 2;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Đến Phú Quốc, nhận phòng","body":"Đón sân bay Phú Quốc, về resort hoặc khách sạn nhận phòng. Chiều tắm biển Bãi Sao hoặc Bãi Dài, ngắm hoàng hôn."},
{"title":"Ngày 2 — Nam đảo, lặn ngắm san hô","body":"Cano tham quan Hòn Móng Tay, Hòn Mây Rút, tắm biển, lặn ngắm san hô theo gói dịch vụ. Trưa ăn hải sản tại nhà bè. Chiều về nghỉ ngơi."},
{"title":"Ngày 3 — Vinpearl Safari và chợ đêm","body":"Sáng tham quan Vinpearl Safari hoặc công viên giải trí tùy chọn. Chiều tự do mua sắm. Tối chợ đêm Dinh Cật, thưởng thức hải sản."},
{"title":"Ngày 4 — Trả phòng, tiễn bay","body":"Sáng tự do tắm biển hoặc spa. Trưa trả phòng, đưa ra sân bay, kết thúc hành trình."}
]' WHERE `id` = 3;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Hà Nội — Hạ Long, lên du thuyền","body":"Đón Hà Nội, di chuyển Hạ Long. Lên du thuyền, ăn trưa trên tàu. Chiều tham quan hang Sửng Sốt, chèo kayak. Tối tiệc trên tàu, nghỉ đêm trên vịnh."},
{"title":"Ngày 2 — Vịnh Hạ Long — về Hà Nội","body":"Sáng hoạt động nhẹ trên tàu, brunch. Thăm hang hoặc làng chài. Trưa trả phòng tàu, về Hà Nội, kết thúc chương trình."}
]' WHERE `id` = 4;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — TP.HCM — Singapore","body":"Tập trung sân bay, bay Singapore. Đón về khách sạn, nhận phòng. Tối tự do Marina Bay, xem nhạc nước hoặc ẩm thực hawker center."},
{"title":"Ngày 2 — Gardens by the Bay và Marina","body":"Sáng tham quan Gardens by the Bay: Flower Dome, Cloud Forest, Supertree Grove. Chiều Merlion Park, Esplanade. Tối Clarke Quay hoặc Orchard Road."},
{"title":"Ngày 3 — Universal Studios Sentosa","body":"Cả ngày vui chơi Universal Studios Singapore: phim trường, tàu lượn, show diễn. Tối về khách sạn hoặc dạo Sentosa buổi tối."},
{"title":"Ngày 4 — Mua sắm — về Việt Nam","body":"Sáng tự do Jewel Changi hoặc Bugis. Trưa trả phòng, ra sân bay Changi, bay về TP.HCM, kết thúc tour."}
]' WHERE `id` = 5;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Đà Lạt: Hồ Xuân Hương và chợ đêm","body":"Đón sân bay Liên Khương về trung tâm Đà Lạt, nhận phòng. Chiều dạo Hồ Xuân Hương, nhà thờ Con Gà. Tối chợ đêm, thử bánh tráng nướng, sữa đậu nành."},
{"title":"Ngày 2 — Hồ Tuyền Lâm và đồi chè","body":"Sáng tham quan Thiền viện Trúc Lâm, cáp treo qua hồ Tuyền Lâm. Chiều đồi chè Cầu Đất, check-in view đồi thông. Tối BBQ hoặc lẩu gà lá é."},
{"title":"Ngày 3 — Langbiang và tiễn sân bay","body":"Sáng xe lên đỉnh Langbiang ngắm toàn cảnh. Trưa trả phòng, mua đặc sản. Chiều tiễn sân bay Liên Khương, kết thúc."}
]' WHERE `id` = 6;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Đà Nẵng — Bà Nà Hills","body":"Đón Đà Nẵng, lên Bà Nà Hills bằng cáp treo. Tham quan Cầu Vàng, Làng Pháp, vườn hoa. Tối về khách sạn khu biển Mỹ Khê."},
{"title":"Ngày 2 — Hội An cổ kính","body":"Sáng phố cổ Hội An: chùa Cầu, nhà cổ, làm gốm hoặc lồng đèn. Trưa cao lầu, cơm gà. Chiều thuyền sông Hoài, thả đèn hoa đăng."},
{"title":"Ngày 3 — Tiễn đoàn","body":"Sáng tự do tắm biển hoặc mua sắm. Trưa trả phòng, tiễn sân bay Đà Nẵng, kết thúc."}
]' WHERE `id` = 7;

UPDATE `tours` SET `itinerary` = '[
{"title":"Ngày 1 — Quy Nhơn: Eo Gió và Ghềnh Ráng","body":"Đón ga hoặc sân bay Phù Cát, nhận phòng. Chiều thăm Eo Gió, Ghềnh Ráng Tiên Sa, khu tưởng niệm Hàn Mặc Tử. Tối hải sản chợ đêm."},
{"title":"Ngày 2 — Kỳ Co và Hòn Khô","body":"Cano hoặc thuyền ra Kỳ Co, tắm biển, tham quan bãi đá. Trưa ăn tại bãi. Chiều Hòn Khô lặn ngắm san hô tùy thủy triều."},
{"title":"Ngày 3 — Tháp Đôi và tiễn đoàn","body":"Sáng tham quan Tháp Đôi Chăm, có thể thêm bảo tàng Quang Trung. Trưa trả phòng, mua chả cá, bánh hỏi. Chiều tiễn sân bay hoặc ga, kết thúc."}
]' WHERE `id` = 8;

-- -----------------------------------------------------------------------------
-- Đặt tour — đủ trạng thái cho dashboard & lịch sử khách
-- total_amount = (tạm tính + phụ thu lễ nếu có) − discount (booking.php + booking_pricing.php)
-- -----------------------------------------------------------------------------
INSERT INTO `bookings` (`id`, `user_id`, `tour_id`, `adults`, `children`, `departure_date`, `coupon_code`, `discount_amount`, `holiday_surcharge_percent`, `holiday_surcharge_amount`, `total_amount`, `status`, `cancel_reason`, `created_at`) VALUES
-- chờ duyệt
(1, 3, 1, 2, 0, '2026-05-18', NULL, 0.00, 0, 0.00, 8580000.00, 'chờ duyệt', NULL, '2026-03-01 08:30:00'),
-- đã xác nhận
(2, 4, 2, 1, 1, '2026-06-02', NULL, 0.00, 0, 0.00, 4335000.00, 'đã xác nhận', NULL, '2026-02-20 11:00:00'),
-- đã thanh toán
(3, 5, 3, 2, 1, '2026-07-10', NULL, 0.00, 0, 0.00, 16475000.00, 'đã thanh toán', NULL, '2026-02-10 15:20:00'),
-- yêu cầu hủy (my_bookings)
(4, 6, 4, 2, 0, '2026-08-05', NULL, 0.00, 0, 0.00, 6380000.00, 'yêu cầu hủy', 'Đổi lịch công tác đột xuất.', '2026-02-28 09:00:00'),
-- đã hủy
(5, 3, 5, 1, 0, '2026-09-01', NULL, 0.00, 0, 0.00, 15990000.00, 'đã hủy', 'Không đủ người khởi hành.', '2026-01-15 14:00:00'),
-- thêm đơn cho biểu đồ 6 tháng (admin/index.php)
(6, 4, 6, 2, 0, '2025-11-20', NULL, 0.00, 0, 0.00, 4980000.00, 'đã xác nhận', NULL, '2025-11-05 10:00:00'),
(7, 5, 8, 1, 2, '2025-12-25', NULL, 0.00, 0, 0.00, 7180000.00, 'đã thanh toán', NULL, '2025-12-18 16:30:00'),
(8, 6, 2, 1, 0, '2026-04-12', NULL, 0.00, 0, 0.00, 2890000.00, 'chờ duyệt', NULL, '2026-03-10 13:45:00');

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

-- -----------------------------------------------------------------------------
-- Mã giảm giá & bài blog (admin)
-- -----------------------------------------------------------------------------
INSERT INTO `coupons` (`id`, `code`, `discount_type`, `discount_value`, `min_order_amount`, `max_uses`, `used_count`, `starts_at`, `expires_at`, `is_active`) VALUES
(1, 'SUMMER10', 'percent', 10.00, 2000000.00, 100, 0, '2026-01-01', '2026-12-31', 1),
(2, 'GIAM500K', 'fixed', 500000.00, 5000000.00, 50, 0, '2026-03-01', NULL, 1);

-- Bài blog (cần cột featured_image, category, tag_label, keywords — xem migrations/004 hoặc mở web để auto-migrate)
INSERT INTO `blog_posts` (`id`, `title`, `slug`, `excerpt`, `featured_image`, `category`, `tag_label`, `keywords`, `body`, `status`, `published_at`, `author_id`) VALUES
 (1, 'Kinh nghiệm du lịch Đà Lạt 3 ngày 2 đêm chi tiết từ A-Z', 'dalat-kinh-nghiem', 'Lịch trình thực tế, chi phí dự kiến, địa điểm check-in đẹp và gợi ý ăn uống.', 'https://images.unsplash.com/photo-1527631746610-bca00a040d60?auto=format&fit=crop&w=1400&q=80', 'cam-nang', 'Cẩm nang/Kinh nghiệm du lịch', 'đà lạt lịch trình 3 ngày trekking', '<p>Đà Lạt luôn là điểm đến được yêu thích nhờ khí hậu mát lành.</p><h3>Ngày 1</h3><p>Chiều đến nên ưu tiên nghỉ ngơi nhẹ, dạo Hồ Xuân Hương hoặc chợ đêm.</p><h3>Ngày 2</h3><p>Ghé Datanla, đồi chè Cầu Đất hoặc săn mây tùy mùa.</p><h3>Ngày 3</h3><p>Mua mứt, atiso, cà phê làm quà rồi trả phòng.</p>', 'published', '2026-04-08 10:00:00', 1),
 (2, 'Đi Sapa mùa nào đẹp nhất? Gợi ý theo từng tháng', 'sapa-mua-nao-dep', 'So sánh thời tiết, cảnh sắc và chi phí theo mùa để chọn thời điểm phù hợp.', 'https://images.unsplash.com/photo-1549880338-65ddcdfd017b?auto=format&fit=crop&w=900&q=80', 'cam-nang', 'Cẩm nang/Kinh nghiệm du lịch', 'sapa fansipan mùa lúa', '<p>Sapa thay đổi theo mùa: mùa lúa chín vàng thu hút đông đảo du khách.</p><h3>Mùa xuân</h3><p>Hoa đào, hoa mận nở rộ — phù hợp trekking nhẹ.</p><h3>Mùa thu</h3><p>Lúa chín khoảng tháng 9–10 là thời điểm vàng cho ảnh.</p>', 'published', '2026-04-05 14:00:00', 1),
 (3, 'Review resort ven biển Phú Quốc: Có đáng tiền?', 'review-phu-quoc-resort', 'Trải nghiệm thực tế phòng ở, bữa sáng, bãi biển riêng và lưu ý khi đặt phòng.', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=900&q=80', 'review', 'Review & Đánh giá', 'phú quốc resort bãi kem', '<p>Resort ven biển thường có bãi riêng và đưa đón — phù hợp gia đình.</p><p>Khi đặt phòng, xem kỹ chính sách bữa sáng và hoạt động cho trẻ.</p>', 'published', '2026-04-03 09:30:00', 1),
 (4, 'Lịch trình Đà Nẵng - Hội An 4 ngày 3 đêm tối ưu chi phí', 'lich-trinh-da-nang-hoi-an-4n3d', 'Giờ đi, điểm tham quan, phương tiện và ngân sách theo từng ngày.', 'https://images.unsplash.com/photo-1555899434-94d1368aa7af?auto=format&fit=crop&w=900&q=80', 'cam-nang', 'Cẩm nang/Kinh nghiệm du lịch', 'đà nẵng hội an cầu rồng', '<p>Ngày 1: Bán đảo Sơn Trà và bãi biển Mỹ Khê. Ngày 2–3: Phố cổ Hội An và làng gốm Thanh Hà.</p><p>Tối ưu chi phí bằng combo vé tham quan và xe máy.</p>', 'published', '2026-04-04 11:00:00', 1),
 (5, 'Review 5 quán hải sản Nha Trang ngon, giá hợp lý', 'review-hai-san-nha-trang', 'Thực đơn, mức giá và trải nghiệm phục vụ thực tế.', 'https://images.unsplash.com/photo-1559847844-d721426d6edc?auto=format&fit=crop&w=900&q=80', 'review', 'Review & Đánh giá', 'nha trang hải sản tôm hùm', '<p>Chọn quán có bể chứa hải sản tươi, hỏi giá trước khi chế biến.</p><p>Nên đi nhóm để chia món và giảm chi phí/người.</p>', 'published', '2026-03-31 16:00:00', 1),
 (6, 'Top món đặc sản Huế nhất định phải thử trong 2 ngày', 'top-mon-dac-san-hue', 'Danh sách quán, mức giá tham khảo và lịch ăn uống hợp lý.', 'https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=80', 'am-thuc', 'Văn hóa & Ẩm thực', 'huế bún bò bánh bèo', '<p>Bún bò, bánh bèo, nem lụi và chè là combo dễ ăn trong 48 giờ.</p><p>Ưu tiên quán địa phương đông khách thay vì khu du lịch đông đúc.</p>', 'published', '2026-04-01 08:00:00', 1),
 (7, 'Mẹo săn tour giá tốt mùa cao điểm', 'meo-san-tour-gia-tot', 'Chọn thời điểm đặt, so sánh gói và tận dụng mã giảm giá.', 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=900&q=80', 'tin-tuc', 'Tin tức & Khuyến mãi', 'flash sale tour giảm giá', '<p>Đặt sớm 2–3 tuần, theo dõi khuyến mãi cuối tuần và kết hợp mã giảm giá hợp lệ.</p><p>So sánh gói tour trước khi chốt để tránh phát sinh.</p>', 'published', '2026-02-01 09:00:00', 1),
 (8, 'Checklist hành lý đi biển 3 ngày', 'checklist-hanh-ly-bien', 'Gọn nhẹ mà đủ dùng cho chuyến nghỉ dưỡng.', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80', 'cam-nang', 'Cẩm nang/Kinh nghiệm du lịch', 'biển vali kem chống nắng', '<p>Kem chống nắng, dép, sạc dự phòng, bản photo giấy tờ.</p><p>Áo khoác gió nhẹ cho tối ven biển.</p>', 'published', '2026-03-20 12:00:00', 1),
 (9, 'Cập nhật quy định du lịch nội địa và lịch bay hè 2026', 'cap-nhat-quy-dinh-du-lich-2026', 'Thay đổi về giấy tờ, hành lý và lịch bay cao điểm.', 'https://images.unsplash.com/photo-1493558103817-58b2924bce98?auto=format&fit=crop&w=900&q=80', 'tin-tuc', 'Tin tức & Khuyến mãi', 'vé máy bay đà nẵng hành lý', '<p>Theo dõi quy định hành lý từng hãng bay và thời gian làm thủ tục dịp lễ.</p>', 'published', '2026-03-29 10:00:00', 1),
 (10, 'Gia đình chị Hạnh chia sẻ hành trình Ninh Bình 2N1Đ', 'gia-dinh-ninh-binh-2n1d', 'Cảm nhận về lịch trình, xe, bữa ăn và hướng dẫn viên.', 'https://images.unsplash.com/photo-1527004013197-933c4bb611b3?auto=format&fit=crop&w=900&q=80', 'testimonials', 'Câu chuyện khách hàng', 'ninh bình tràng an bái đính', '<p>Chuyến đi phù hợp gia đình có người lớn tuổi và trẻ nhỏ.</p><p>Lịch trình không quá dày, có thời gian nghỉ trưa hợp lý.</p>', 'published', '2026-03-28 15:00:00', 1),
 (11, 'Food tour phố cổ Hà Nội: ăn gì trong một buổi tối?', 'food-tour-pho-co-ha-noi', 'Bún chả, phở cuốn, chè — khung giờ và mức giá tham khảo.', 'https://images.unsplash.com/photo-1529692236671-f1dc2a44bf24?auto=format&fit=crop&w=900&q=80', 'am-thuc', 'Văn hóa & Ẩm thực', 'hà nội phố cổ bún chả', '<p>Bắt đầu từ khu phố cổ, đi bộ giữa các quán nhỏ.</p><p>Chia nhỏ khẩu phần để thử được nhiều món.</p>', 'published', '2026-03-27 18:00:00', 1),
 (12, 'Ưu đãi tour Hà Giang tháng 5: giảm nhóm và quà tặng kèm', 'uu-dai-tour-ha-giang-thang-5', 'Khung ngày ưu đãi, chính sách nhóm và combo lưu trú.', 'https://images.unsplash.com/photo-1464822759844-d150ad6d1d1b?auto=format&fit=crop&w=900&q=80', 'tin-tuc', 'Tin tức & Khuyến mãi', 'hà giang cao nguyên đá khuyến mãi', '<p>Áp dụng cho nhóm từ 6 khách trở lên trong khung ngày công bố trên web.</p>', 'published', '2026-03-26 09:00:00', 1);

-- Reset AUTO_INCREMENT (tuỳ chọn)
ALTER TABLE `categories` AUTO_INCREMENT = 10;
ALTER TABLE `users` AUTO_INCREMENT = 100;
ALTER TABLE `tours` AUTO_INCREMENT = 20;
ALTER TABLE `bookings` AUTO_INCREMENT = 50;
ALTER TABLE `tour_reviews` AUTO_INCREMENT = 100;
ALTER TABLE `blog_feedback` AUTO_INCREMENT = 50;
ALTER TABLE `coupons` AUTO_INCREMENT = 10;
ALTER TABLE `blog_posts` AUTO_INCREMENT = 20;
