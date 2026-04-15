<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$rows = [];
try {
    $rows = $pdo->query(
        "SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
                SUM(total_amount) AS revenue,
                COUNT(*) AS orders
         FROM bookings
         WHERE status != 'đã hủy'
         GROUP BY ym
         ORDER BY ym ASC"
    )->fetchAll();
} catch (Throwable) {
    $rows = [];
}

$fn = 'bao-cao-doanh-thu-theo-thang-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fn . '"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['Tháng', 'Doanh thu', 'Số đơn'], ';');
foreach ($rows as $r) {
    fputcsv($out, [$r['ym'], $r['revenue'], $r['orders']], ';');
}
fclose($out);
exit;
