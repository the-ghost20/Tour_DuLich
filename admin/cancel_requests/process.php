<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/booking_slots.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . app_admin_url('cancel_requests/list.php'));
    exit;
}

$bid = (int) ($_POST['booking_id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');
$revert = (string) ($_POST['revert_status'] ?? 'đã xác nhận');
$ok = ['đã xác nhận', 'đã thanh toán'];

if ($bid > 0 && ($action === 'approve' || $action === 'reject')) {
    try {
        $pdo->beginTransaction();
        $chk = $pdo->prepare(
            'SELECT id, tour_id, adults, children, status FROM bookings WHERE id = :id LIMIT 1 FOR UPDATE'
        );
        $chk->execute(['id' => $bid]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        $st  = $row ? (string) $row['status'] : '';
        if ($st === 'yêu cầu hủy' && $row) {
            if ($action === 'approve') {
                $guests = booking_guest_total((int) $row['adults'], (int) $row['children']);
                booking_release_slots_if_cancelled(
                    $pdo,
                    $st,
                    'đã hủy',
                    (int) $row['tour_id'],
                    $guests
                );
                $pdo->prepare("UPDATE bookings SET status = 'đã hủy' WHERE id = :id")->execute(['id' => $bid]);
            } else {
                if (!in_array($revert, $ok, true)) {
                    $revert = 'đã xác nhận';
                }
                $pdo->prepare('UPDATE bookings SET status = :s WHERE id = :id')->execute(['s' => $revert, 'id' => $bid]);
            }
        }
        $pdo->commit();
    } catch (Throwable) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
}

header('Location: ' . app_admin_url('cancel_requests/list.php'), true, 302);
exit;
