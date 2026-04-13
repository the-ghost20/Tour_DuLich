-- Phụ thu ngày lễ/Tết (theo ngày khởi hành)
-- mysql -u root -p tour_dulich < database/migrations/003_bookings_holiday_surcharge.sql

ALTER TABLE `bookings`
  ADD COLUMN `holiday_surcharge_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `discount_amount`,
  ADD COLUMN `holiday_surcharge_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `holiday_surcharge_percent`;
