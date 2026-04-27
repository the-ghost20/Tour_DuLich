<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/booking_pricing.php';
require_once __DIR__ . '/../includes/booking_slots.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để đặt tour.']);
    exit;
}

$userId   = (int) $_SESSION['user_id'];
$tourId   = (int) ($_POST['tour_id'] ?? 0);
$adults   = max(1, (int) ($_POST['adults'] ?? 1));
$children = max(0, (int) ($_POST['children'] ?? 0));
$couponNorm = booking_normalize_coupon_code($_POST['coupon_code'] ?? '');
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

if ($adults < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Số người lớn tối thiểu là 1.']);
    exit;
}

try {
    $guests = booking_guest_total($adults, $children);

    $pdo->beginTransaction();

    $stmtTour = $pdo->prepare(
        "SELECT id, tour_name, price, available_slots, status FROM tours WHERE id = :id LIMIT 1 FOR UPDATE"
    );
    $stmtTour->execute(['id' => $tourId]);
    $tour = $stmtTour->fetch(PDO::FETCH_ASSOC);

    if (!$tour) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Tour không tồn tại.']);
        exit;
    }

    if ((string) $tour['status'] !== 'hiện') {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tour hiện không khả dụng.']);
        exit;
    }

    if ((int) $tour['available_slots'] < $guests) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Tour chỉ còn {$tour['available_slots']} chỗ trống.",
        ]);
        exit;
    }

    $pricePerPerson = (float) $tour['price'];
    $baseSubtotal = booking_subtotal($pricePerPerson, $adults, $children);
    $h = booking_holiday_addon($baseSubtotal, $departureYmd);
    $grossSubtotal = $h['subtotal'];

    $discountAmount = 0.0;
    $couponStored = null;
    $couponIdApplied = null;

    if ($couponNorm !== '') {
        $stmtC = $pdo->prepare('SELECT * FROM coupons WHERE code = :c LIMIT 1 FOR UPDATE');
        $stmtC->execute(['c' => $couponNorm]);
        $cRow = $stmtC->fetch(PDO::FETCH_ASSOC);
        if (!$cRow) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mã khuyến mãi không tồn tại.']);
            exit;
        }
        $err = booking_coupon_is_valid_now($cRow)
            ?? booking_coupon_min_order_error($cRow, $grossSubtotal)
            ?? booking_coupon_uses_error($cRow);
        if ($err !== null) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $err]);
            exit;
        }
        $discountAmount = booking_discount_amount($grossSubtotal, $cRow);
        $couponStored = (string) $cRow['code'];
        $couponIdApplied = (int) $cRow['id'];
    }

    $totalAmount = max(0.0, round($grossSubtotal - $discountAmount, 2));
    $holidayPct = $h['holiday_percent'];
    $holidayAmt = $h['holiday_amount'];

    $stmtDup = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE user_id = :uid AND tour_id = :tid
           AND departure_date <=> :dep
           AND status IN ('chờ duyệt', 'đã xác nhận')
         LIMIT 1"
    );
    $stmtDup->execute(['uid' => $userId, 'tid' => $tourId, 'dep' => $departureYmd]);
    if ($stmtDup->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Bạn đã có đơn cùng tour và cùng ngày khởi hành đang chờ hoặc đã xác nhận.',
        ]);
        exit;
    }

    $stmtInsert = $pdo->prepare(
        "INSERT INTO bookings (user_id, tour_id, adults, children, departure_date, coupon_code, discount_amount, holiday_surcharge_percent, holiday_surcharge_amount, total_amount, status)
         VALUES (:uid, :tid, :adults, :children, :dep, :ccode, :disc, :hpct, :hamt, :total, 'chờ duyệt')"
    );
    $stmtInsert->execute([
        'uid'      => $userId,
        'tid'      => $tourId,
        'adults'   => $adults,
        'children' => $children,
        'dep'      => $departureYmd,
        'ccode'    => $couponStored,
        'disc'     => $discountAmount,
        'hpct'     => $holidayPct,
        'hamt'     => $holidayAmt,
        'total'    => $totalAmount,
    ]);

    if (!booking_consume_tour_slots($pdo, $tourId, $guests)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Hết chỗ trống cho tour (vui lòng chọn số khách nhỏ hơn hoặc tour khác).',
        ]);
        exit;
    }

    if ($couponIdApplied !== null) {
        $pdo->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id')
            ->execute(['id' => $couponIdApplied]);
    }

    $bookingId = (int) $pdo->lastInsertId();
    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'message'    => "Đặt tour thành công! Mã đơn #{$bookingId}. Khởi hành {$departure->format('d/m/Y')}. Chúng tôi sẽ liên hệ xác nhận sớm.",
        'booking_id' => $bookingId,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('booking.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Không lưu được đơn đặt tour. Vui lòng tải lại trang và thử lại.',
    ]);
}
