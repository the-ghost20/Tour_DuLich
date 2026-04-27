<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/booking_pricing.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

$tourId   = (int) ($_POST['tour_id'] ?? 0);
$adults   = max(1, (int) ($_POST['adults'] ?? 1));
$children = max(0, (int) ($_POST['children'] ?? 0));
$coupon   = booking_normalize_coupon_code($_POST['coupon_code'] ?? '');
$departureRaw = trim((string) ($_POST['departure_date'] ?? ''));

if ($tourId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tour không hợp lệ.']);
    exit;
}

if ($departureRaw === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ngày khởi hành.']);
    exit;
}

$departure = DateTimeImmutable::createFromFormat('Y-m-d', $departureRaw);
if (!$departure || $departure->format('Y-m-d') !== $departureRaw) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ngày khởi hành không hợp lệ.']);
    exit;
}

$today = new DateTimeImmutable('today');
if ($departure < $today) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ngày khởi hành không được trước hôm nay.']);
    exit;
}

$departureYmd = $departure->format('Y-m-d');

try {
    $stmtTour = $pdo->prepare(
        'SELECT id, price, status, available_slots FROM tours WHERE id = :id LIMIT 1'
    );
    $stmtTour->execute(['id' => $tourId]);
    $tour = $stmtTour->fetch(PDO::FETCH_ASSOC);
    if (!$tour) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tour không tồn tại.']);
        exit;
    }
    if (!tour_is_publicly_bookable($tour)) {
        http_response_code(400);
        $msg = ((string) $tour['status'] === 'hiện' && (int) $tour['available_slots'] <= 0)
            ? 'Tour đã hết chỗ.'
            : 'Tour hiện không khả dụng.';
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    $price = (float) $tour['price'];
    $out = booking_pricing_with_coupon($pdo, $price, $adults, $children, $departureYmd, $coupon);

    $payload = [
        'success'           => empty($out['error']),
        'base_subtotal'     => $out['base_subtotal'],
        'holiday_percent'   => $out['holiday_percent'],
        'holiday_amount'    => $out['holiday_amount'],
        'holiday_label'     => $out['holiday_label'],
        'subtotal'          => $out['subtotal'],
        'discount'          => $out['discount'],
        'total'             => $out['total'],
    ];
    if (!empty($out['error'])) {
        $payload['success'] = false;
        $payload['message'] = $out['error'];
    }

    echo json_encode($payload);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống.']);
}
