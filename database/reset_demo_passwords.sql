-- Đặt lại mật khẩu mẫu = chữ thường: password
-- Dùng khi đăng nhập admin/staff/user mẫu báo sai mật khẩu (hash trong DB bị lệch).
--
-- mysql -u root -p -P 8889 tour_dulich < database/reset_demo_passwords.sql
-- (đổi port/user theo MAMP của bạn)

SET NAMES utf8mb4;
USE `tour_dulich`;

-- Hash bcrypt do PHP password_hash('password', PASSWORD_DEFAULT) — kiểm tra với password_verify
UPDATE `users`
SET `password` = '$2y$10$38S8lbqM25prVKtW.uh0Nu42qZnJT0F7dLyHq1aP6xMEfMn3.sZja'
WHERE `email` IN (
  'admin@dulichviet.test',
  'staff@dulichviet.test',
  'user1@dulichviet.test',
  'user2@dulichviet.test',
  'user3@dulichviet.test',
  'user4@dulichviet.test'
);
