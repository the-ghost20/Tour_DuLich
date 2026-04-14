<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

$pdoOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, $pdoOptions);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Không thể kết nối đến database. Vui lòng thử lại sau.');
}

require_once __DIR__ . '/schema_migrations.php';
app_ensure_bookings_departure_coupon_columns($pdo, $dbName);
app_ensure_bookings_holiday_surcharge_columns($pdo, $dbName);
app_ensure_tours_itinerary_column($pdo, $dbName);
app_ensure_tours_journey_content_columns($pdo, $dbName);
app_ensure_tours_gallery_urls_column($pdo, $dbName);
app_ensure_blog_posts_extra_columns($pdo, $dbName);
app_seed_tour_itinerary_defaults($pdo);

require_once __DIR__ . '/functions.php';
