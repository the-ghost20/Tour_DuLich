<?php
declare(strict_types=1);

/**
 * Tự động bổ sung cột đặt tour (ngày khởi hành + mã KM) nếu DB cũ chưa chạy migration.
 * Chạy tối đa một lần mỗi request; idempotent; bỏ qua lỗi trùng cột khi hai request song song.
 */
function app_ensure_bookings_departure_coupon_columns(PDO $pdo, string $schemaName): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $chk = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t AND COLUMN_NAME = :c'
        );
        $chk->execute([
            's' => $schemaName,
            't' => 'bookings',
            'c' => 'departure_date',
        ]);
        if ((int) $chk->fetchColumn() > 0) {
            return;
        }

        $pdo->exec(
            'ALTER TABLE `bookings`
             ADD COLUMN `departure_date` DATE NULL DEFAULT NULL AFTER `children`,
             ADD COLUMN `coupon_code` VARCHAR(40) NULL DEFAULT NULL AFTER `departure_date`,
             ADD COLUMN `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `coupon_code`'
        );
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '1060') || str_contains($msg, 'Duplicate column')) {
            return;
        }
        error_log('app_ensure_bookings_departure_coupon_columns: ' . $msg);
    }
}

function app_ensure_tours_itinerary_column(PDO $pdo, string $schemaName): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $chk = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t AND COLUMN_NAME = :c'
        );
        $chk->execute([
            's' => $schemaName,
            't' => 'tours',
            'c' => 'itinerary',
        ]);
        if ((int) $chk->fetchColumn() > 0) {
            return;
        }

        $pdo->exec(
            'ALTER TABLE `tours` ADD COLUMN `itinerary` MEDIUMTEXT NULL DEFAULT NULL AFTER `description`'
        );
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '1060') || str_contains($msg, 'Duplicate column')) {
            return;
        }
        error_log('app_ensure_tours_itinerary_column: ' . $msg);
    }
}

/**
 * Nạp lịch trình mẫu cho tour id 1–8 nếu cột itinerary đang trống.
 * (Sửa file sample_data.sql không tự cập nhật MySQL — lần đầu mở web sau khi có cột sẽ đủ dữ liệu.)
 */
function app_seed_tour_itinerary_defaults(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    require_once __DIR__ . '/tour_itinerary_defaults.php';

    try {
        $needStmt = $pdo->query(
            "SELECT COUNT(*) FROM tours WHERE id BETWEEN 1 AND 8 AND (
                itinerary IS NULL
                OR TRIM(itinerary) = ''
                OR TRIM(itinerary) = '[]'
            )"
        );
        if ($needStmt && (int) $needStmt->fetchColumn() === 0) {
            return;
        }

        $upd = $pdo->prepare(
            "UPDATE tours SET itinerary = :j WHERE id = :id AND (
                itinerary IS NULL
                OR TRIM(itinerary) = ''
                OR TRIM(itinerary) = '[]'
            )"
        );
        foreach (tour_itinerary_default_payloads() as $id => $days) {
            $json = json_encode($days, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                continue;
            }
            $upd->execute(['j' => $json, 'id' => (int) $id]);
        }
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (!str_contains($msg, 'itinerary') && !str_contains($msg, 'Unknown column')) {
            error_log('app_seed_tour_itinerary_defaults: ' . $msg);
        }
    }
}

/**
 * Cột phụ thu lễ/Tết (theo ngày khởi hành, lúc đặt).
 */
function app_ensure_bookings_holiday_surcharge_columns(PDO $pdo, string $schemaName): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $chk = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = :s AND TABLE_NAME = :t AND COLUMN_NAME = :c'
        );
        $chk->execute([
            's' => $schemaName,
            't' => 'bookings',
            'c' => 'holiday_surcharge_percent',
        ]);
        if ((int) $chk->fetchColumn() > 0) {
            return;
        }

        $pdo->exec(
            'ALTER TABLE `bookings`
             ADD COLUMN `holiday_surcharge_percent` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `discount_amount`,
             ADD COLUMN `holiday_surcharge_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `holiday_surcharge_percent`'
        );
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '1060') || str_contains($msg, 'Duplicate column')) {
            return;
        }
        error_log('app_ensure_bookings_holiday_surcharge_columns: ' . $msg);
    }
}
