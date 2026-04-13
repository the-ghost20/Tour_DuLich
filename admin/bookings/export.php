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
    $stmt = $pdo->query(
        "SELECT b.id, b.status, b.adults, b.children, b.total_amount, b.created_at,
                u.full_name, u.email, u.phone,
                t.tour_name, t.destination
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN tours t ON t.id = b.tour_id
         ORDER BY b.created_at DESC"
    );
    $rows = $stmt->fetchAll();
} catch (Throwable) {
    $rows = [];
}

$fn = 'don-dat-tour-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $fn . '"');
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Trạng thái', 'Khách', 'Email', 'SĐT', 'Tour', 'Điểm đến', 'Người lớn', 'Trẻ em', 'Tổng tiền', 'Ngày đặt'], ';');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['status'],
        $r['full_name'],
        $r['email'],
        $r['phone'],
        $r['tour_name'],
        $r['destination'],
        $r['adults'],
        $r['children'],
        $r['total_amount'],
        $r['created_at'],
    ], ';');
}
fclose($out);
exit;
