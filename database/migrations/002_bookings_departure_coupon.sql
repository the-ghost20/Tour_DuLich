-- Ngày khởi hành + mã giảm giá trên đơn đặt tour
-- mysql -u root -p tour_dulich < database/migrations/002_bookings_departure_coupon.sql

ALTER TABLE `bookings`
  ADD COLUMN `departure_date` DATE NULL DEFAULT NULL AFTER `children`,
  ADD COLUMN `coupon_code` VARCHAR(40) NULL DEFAULT NULL AFTER `departure_date`,
  ADD COLUMN `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`;
