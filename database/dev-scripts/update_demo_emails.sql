-- Đổi email tài khoản mẫu sang Gmail (mật khẩu không đổi).
-- Nếu vẫn không đăng nhập được, dùng thay thế: database/fix_demo_accounts.sql (cập nhật cả hash mật khẩu).
--
-- mysql -u root -p tour_dulich < database/update_demo_emails.sql

SET NAMES utf8mb4;
USE `tour_dulich`;

UPDATE `users` SET `email` = 'admin.dulichviet@gmail.com' WHERE `id` = 1 AND `email` = 'admin@dulichviet.test';
UPDATE `users` SET `email` = 'staff.dulichviet@gmail.com' WHERE `id` = 2 AND `email` = 'staff@dulichviet.test';
UPDATE `users` SET `email` = 'user1.dulichviet@gmail.com' WHERE `id` = 3 AND `email` = 'user1@dulichviet.test';
UPDATE `users` SET `email` = 'user2.dulichviet@gmail.com' WHERE `id` = 4 AND `email` = 'user2@dulichviet.test';
UPDATE `users` SET `email` = 'user3.dulichviet@gmail.com' WHERE `id` = 5 AND `email` = 'user3@dulichviet.test';
UPDATE `users` SET `email` = 'user4.dulichviet@gmail.com' WHERE `id` = 6 AND `email` = 'user4@dulichviet.test';
