-- Thời điểm khách xác nhận thanh toán QR (đồng bộ với app_ensure_bookings_paid_at_column).
ALTER TABLE `bookings`
  ADD COLUMN `paid_at` DATETIME NULL DEFAULT NULL AFTER `updated_at`;
