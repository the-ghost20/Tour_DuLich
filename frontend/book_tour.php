<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/notifications.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

// Phải đăng nhập
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đặt tour.']);
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$tourId  = (int) ($_POST['tour_id'] ?? 0);
$adults  = max(1, (int) ($_POST['adults']   ?? 1));
$children = max(0, (int) ($_POST['children'] ?? 0));

if ($tourId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tour không hợp lệ.']);
    exit;
}

if ($adults < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Số người lớn tối thiểu là 1.']);
    exit;
}

try {
    // Lấy thông tin tour
    $stmtTour = $pdo->prepare(
        "SELECT id, tour_name, price, available_slots, status FROM tours WHERE id = :id LIMIT 1"
    );
    $stmtTour->execute(['id' => $tourId]);
    $tour = $stmtTour->fetch();

    if (!$tour) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tour không tồn tại.']);
        exit;
    }

    if ((string) $tour['status'] !== 'hiện') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tour hiện không khả dụng.']);
        exit;
    }

    if ((int) $tour['available_slots'] < ($adults + $children)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Tour chỉ còn {$tour['available_slots']} chỗ trống."
        ]);
        exit;
    }

    $pricePerPerson = (float) $tour['price'];
    $totalAmount = $pricePerPerson * ($adults + $children * 0.5);

    // Kiểm tra đã đặt tour này chưa (chờ duyệt / đã xác nhận)
    $stmtDup = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE user_id = :uid AND tour_id = :tid
           AND status IN ('chờ duyệt', 'đã xác nhận')
         LIMIT 1"
    );
    $stmtDup->execute(['uid' => $userId, 'tid' => $tourId]);
    if ($stmtDup->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Bạn đã đặt tour này rồi. Vào "Lịch sử đặt tour" để xem chi tiết.']);
        exit;
    }

    // Tạo booking
    $stmtInsert = $pdo->prepare(
        "INSERT INTO bookings (user_id, tour_id, adults, children, total_amount, status)
         VALUES (:uid, :tid, :adults, :children, :total, 'chờ duyệt')"
    );
    $stmtInsert->execute([
        'uid'      => $userId,
        'tid'      => $tourId,
        'adults'   => $adults,
        'children' => $children,
        'total'    => $totalAmount,
    ]);

    $bookingId = (int) $pdo->lastInsertId();
    $userStmt = $pdo->prepare('SELECT full_name, email, phone FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => $userId]);
    $user = $userStmt->fetch();

    if ($user) {
        send_booking_notification(
            (string) $user['full_name'],
            (string) $user['email'],
            (string) $user['phone'],
            $bookingId,
            (string) $tour['tour_name'],
            $adults,
            $children,
            $totalAmount
        );
    }

    echo json_encode([
        'success'    => true,
        'message'    => "Đặt tour thành công! Mã đơn #{$bookingId}. Chúng tôi sẽ liên hệ xác nhận sớm.",
        'booking_id' => $bookingId,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
