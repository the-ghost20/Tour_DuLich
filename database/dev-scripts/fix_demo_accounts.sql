-- =============================================================================
-- Sửa nhanh tài khoản DEMO (id 1–6): email Gmail + mật khẩu chữ thường: password
-- Chạy khi đăng nhập báo sai dù đã nhập đúng (DB chưa import lại sample_data /
-- email trong MySQL vẫn là @dulichviet.test).
--
-- mysql -u root -p -h 127.0.0.1 -P 8889 tour_dulich < database/fix_demo_accounts.sql
-- (đổi -P / user / pass theo MAMP hoặc XAMPP của bạn)
-- =============================================================================

SET NAMES utf8mb4;
USE `tour_dulich`;

SET @pwd := '$2y$10$38S8lbqM25prVKtW.uh0Nu42qZnJT0F7dLyHq1aP6xMEfMn3.sZja';

UPDATE `users` SET `email` = 'admin.dulichviet@gmail.com', `password` = @pwd WHERE `id` = 1;
UPDATE `users` SET `email` = 'staff.dulichviet@gmail.com', `password` = @pwd WHERE `id` = 2;
UPDATE `users` SET `email` = 'user1.dulichviet@gmail.com', `password` = @pwd WHERE `id` = 3;
UPDATE `users` SET `email` = 'user2.dulichviet@gmail.com', `password` = @pwd WHERE `id` = 4;
UPDATE `users` SET `email` = 'user3.dulichviet@gmail.com', `password` = @pwd WHERE `id` = 5;
UPDATE `users` SET `email` = 'user4.dulichviet@gmail.com', `password` = @pwd WHERE `id` = 6;
