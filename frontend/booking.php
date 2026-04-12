<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ============================================
//   KIỂM TRA ĐĂNG NHẬP
// ============================================
if (empty($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode('booking.php?' . http_build_query($_GET)));
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ============================================
//   LẤY THÔNG TIN TOUR TỪ DATABASE
// ============================================
$tourId = (int) ($_GET['id'] ?? 0);

if ($tourId <= 0) {
    header('Location: tours.php');
    exit;
}

$stmtTour = $pdo->prepare(
    "SELECT id, tour_name, description, destination, duration, price, image_url, available_slots, status
     FROM tours WHERE id = :id LIMIT 1"
);
$stmtTour->execute(['id' => $tourId]);
$tour = $stmtTour->fetch();

if (!$tour || (string) $tour['status'] !== 'hiện') {
    header('Location: tours.php');
    exit;
}

// Giá người lớn = giá tour, trẻ em = 50%
$adultPrice = (float) $tour['price'];
$childPrice = $adultPrice * 0.5;
$maxSlots = (int) $tour['available_slots'];
$maxPerType = min(20, $maxSlots);
$maxTotal = $maxSlots;

// ============================================
//   HÀM HELPER
// ============================================
function formatVND(float $amount): string
{
    return number_format($amount, 0, ',', '.') . ' đ';
}

function old(string $key, array $data, string $default = ''): string
{
    return htmlspecialchars((string) ($data[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

// ============================================
//   XỬ LÝ FORM KHI SUBMIT (POST)
// ============================================
$errors = [];
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adults = (int) ($_POST['adults'] ?? 0);
    $children = (int) ($_POST['children'] ?? 0);
    $departure_date = trim((string) ($_POST['departure_date'] ?? ''));
    $special_req = trim((string) ($_POST['special_request'] ?? ''));

    // Lưu lại để fill lại form khi có lỗi
    $form_data = [
        'adults' => $adults,
        'children' => $children,
        'departure_date' => $departure_date,
        'special_req' => $special_req,
    ];

    // --- Server-side validation ---
    $total_pax = $adults + $children;

    if ($adults < 1) {
        $errors[] = 'Phải có ít nhất 1 người lớn.';
    }
    if ($children < 0) {
        $errors[] = 'Số trẻ em không hợp lệ.';
    }
    if ($maxSlots > 0 && $total_pax > $maxTotal) {
        $errors[] = 'Tổng số hành khách vượt quá số chỗ còn lại (' . $maxTotal . ' chỗ).';
    }
    if (empty($departure_date)) {
        $errors[] = 'Vui lòng chọn ngày khởi hành.';
    } elseif (strtotime($departure_date) < strtotime(date('Y-m-d'))) {
        $errors[] = 'Ngày khởi hành không thể là ngày trong quá khứ.';
    }

    if (empty($errors)) {
        $adult_total = $adults * $adultPrice;
        $child_total = $children * $childPrice;
        $grand_total = $adult_total + $child_total;

        try {
            $ins = $pdo->prepare(
                "INSERT INTO bookings (user_id, tour_id, adults, children, total_amount, status)
                 VALUES (:uid, :tid, :adults, :children, :total, 'chờ duyệt')"
            );
            $ins->execute([
                'uid' => $userId,
                'tid' => $tourId,
                'adults' => $adults,
                'children' => $children,
                'total' => $grand_total,
            ]);

            // Lưu thêm thông báo flash vào session
            $_SESSION['booking_flash'] = 'Đặt tour thành công! Chúng tôi sẽ liên hệ xác nhận sớm.';
            header('Location: my_bookings.php');
            exit;
        } catch (\Throwable $e) {
            error_log('Booking insert error: ' . $e->getMessage());
            $errors[] = 'Không thể lưu đặt chỗ. Vui lòng thử lại sau.';
        }
    }
}

$today = date('Y-m-d');
$tourName = htmlspecialchars((string) $tour['tour_name'], ENT_QUOTES, 'UTF-8');
$imageUrl = !empty($tour['image_url'])
    ? htmlspecialchars((string) $tour['image_url'], ENT_QUOTES, 'UTF-8')
    : 'https://images.unsplash.com/photo-1537225228614-b4fad34a0b60?w=800&h=500&fit=crop';
$destination = htmlspecialchars((string) $tour['destination'], ENT_QUOTES, 'UTF-8');
$duration = htmlspecialchars((string) $tour['duration'], ENT_QUOTES, 'UTF-8');

// Lấy đánh giá trung bình
$avgRating = null;
$reviewCount = 0;
try {
    $rStmt = $pdo->prepare(
        'SELECT ROUND(AVG(rating), 1) AS avg_r, COUNT(*) AS c FROM tour_reviews WHERE tour_id = :tid'
    );
    $rStmt->execute(['tid' => $tourId]);
    $rRow = $rStmt->fetch();
    if ($rRow) {
        $avgRating = $rRow['avg_r'] !== null ? (float) $rRow['avg_r'] : null;
        $reviewCount = (int) $rRow['c'];
    }
} catch (\Throwable $e) {
    // bảng chưa tồn tại – bỏ qua
}
$displayAvg = ($avgRating !== null && $reviewCount > 0)
    ? number_format($avgRating, 1, ',', '.')
    : '—';
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Đặt Tour –
        <?= $tourName ?> - Du Lịch Việt
    </title>
    <meta name="description"
        content="Đặt tour du lịch «<?= $tourName ?>» trực tuyến tại Du Lịch Việt. Chọn số lượng hành khách và ngày khởi hành, xem tổng tiền ngay lập tức." />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
        /* =========================================
         BOOKING PAGE - SPECIFIC STYLES
         ========================================= */

        .booking-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 3rem 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .booking-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 60% 40%, rgba(0, 188, 212, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 20% 80%, rgba(33, 150, 243, 0.06) 0%, transparent 50%);
            animation: breathe 8s ease-in-out infinite;
        }

        @keyframes breathe {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .booking-hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .booking-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 1.5rem;
        }

        .booking-breadcrumb a {
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.3s ease;
            text-decoration: none;
        }

        .booking-breadcrumb a:hover {
            color: var(--accent-color);
        }

        .booking-breadcrumb i {
            font-size: 0.7rem;
        }

        .booking-breadcrumb span {
            color: var(--accent-color);
            font-weight: 600;
        }

        .booking-hero-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.4rem;
            background: linear-gradient(135deg, #fff 0%, #b2ebf2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.3;
        }

        .booking-hero-subtitle {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
        }

        /* =========================================
         BOOKING LAYOUT
         ========================================= */

        .booking-page {
            background: #f0f4f8;
            min-height: 100vh;
            padding: 2.5rem 0 4rem;
        }

        .booking-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
            align-items: start;
        }

        /* =========================================
         TOUR INFO CARD
         ========================================= */

        .tour-info-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
        }

        .tour-info-card-header {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .tour-info-card-header i {
            color: var(--accent-color);
            font-size: 1.2rem;
        }

        .tour-info-card-header h2 {
            color: white;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
        }

        .tour-info-card-body {
            padding: 2rem;
        }

        .tour-overview {
            display: flex;
            align-items: flex-start;
            gap: 1.5rem;
        }

        .tour-thumbnail {
            width: 140px;
            height: 100px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .tour-overview-details {
            flex: 1;
        }

        .tour-overview-details h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .tour-meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.8rem;
        }

        .meta-tag {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: #f0f4f8;
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.8rem;
            color: #555;
            font-weight: 500;
        }

        .meta-tag i {
            color: var(--primary-color);
            font-size: 0.75rem;
        }

        .tour-rating-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tour-rating-row .stars i {
            color: #ffc107;
            font-size: 0.85rem;
        }

        .tour-rating-row .rating-text {
            font-size: 0.85rem;
            color: #666;
        }

        .slots-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.7rem;
            border-radius: 20px;
            margin-left: 0.8rem;
        }

        .slots-badge.available {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .slots-badge.low {
            background: #fff3e0;
            color: #e65100;
        }

        .slots-badge.full {
            background: #ffebee;
            color: #c62828;
        }

        .price-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px dashed #e0e0e0;
        }

        .price-summary-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem 1.2rem;
            border-left: 3px solid var(--primary-color);
        }

        .price-summary-item.children {
            border-left-color: #4caf50;
        }

        .price-summary-item label {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #888;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }

        .price-summary-item label i {
            font-size: 0.75rem;
        }

        .price-summary-item .price-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a2e;
        }

        .price-summary-item .price-note {
            font-size: 0.75rem;
            color: #aaa;
            margin-top: 0.2rem;
        }

        /* =========================================
         BOOKING FORM CARD
         ========================================= */

        .booking-form-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .form-card-header {
            background: linear-gradient(135deg, #2196f3 0%, #00bcd4 100%);
            padding: 1.5rem 2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .form-card-header i {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
        }

        .form-card-header h2 {
            color: white;
            font-size: 1.1rem;
            margin: 0;
            font-weight: 600;
        }

        .form-card-body {
            padding: 2rem;
        }

        .form-section-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 1.2rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #f0f4f8;
        }

        .form-section-title i {
            color: var(--primary-color);
            font-size: 0.95rem;
        }

        /* =========================================
         PASSENGER COUNTER
         ========================================= */

        .passenger-section {
            margin-bottom: 2rem;
        }

        .passenger-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.2rem 1.4rem;
            background: #f8f9fa;
            border-radius: 12px;
            margin-bottom: 1rem;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .passenger-row:hover {
            border-color: #e3f2fd;
            background: #fafcff;
        }

        .passenger-row.children-row:hover {
            border-color: #e8f5e9;
            background: #fafffe;
        }

        .passenger-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .passenger-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .passenger-icon.adult {
            background: linear-gradient(135deg, #2196f3, #00bcd4);
            color: white;
        }

        .passenger-icon.child {
            background: linear-gradient(135deg, #4caf50, #81c784);
            color: white;
        }

        .passenger-details h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a2e;
            margin: 0 0 0.2rem 0;
        }

        .passenger-details .unit-price {
            font-size: 0.8rem;
            color: #888;
        }

        .passenger-details .unit-price strong {
            color: var(--primary-color);
            font-weight: 600;
        }

        .children-row .passenger-details .unit-price strong {
            color: #4caf50;
        }

        .counter {
            display: flex;
            align-items: center;
            gap: 0;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: border-color 0.3s ease;
        }

        .counter:hover {
            border-color: var(--primary-color);
        }

        .counter-btn {
            width: 38px;
            height: 38px;
            background: transparent;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: var(--primary-color);
            transition: all 0.2s ease;
            font-weight: 700;
        }

        .counter-btn:hover {
            background: #e3f2fd;
            color: var(--primary-dark);
        }

        .counter-btn:active {
            transform: scale(0.9);
        }

        .counter-btn:disabled {
            color: #ccc;
            cursor: not-allowed;
            background: transparent;
        }

        .counter-value {
            min-width: 40px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: #1a1a2e;
            padding: 0 0.4rem;
            border: none;
            outline: none;
            background: transparent;
            user-select: none;
        }

        /* =========================================
         DEPARTURE DATE SECTION
         ========================================= */

        .date-section {
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .form-group label i {
            color: var(--primary-color);
            font-size: 0.8rem;
        }

        .form-group label .required {
            color: #f44336;
            margin-left: 2px;
        }

        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid #e8e8e8;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #1a1a2e;
            background: white;
            transition: all 0.3s ease;
            font-family: inherit;
            outline: none;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.1);
        }

        .form-control::placeholder {
            color: #bbb;
        }

        .form-control.error {
            border-color: #f44336;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* =========================================
         ORDER SUMMARY (RIGHT SIDEBAR)
         ========================================= */

        .order-summary-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 90px;
        }

        .summary-header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            padding: 1.5rem 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .summary-header i {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
        }

        .summary-header h3 {
            color: white;
            font-size: 1rem;
            margin: 0;
            font-weight: 700;
        }

        .summary-body {
            padding: 1.8rem;
        }

        .summary-tour-name {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1a1a2e;
            line-height: 1.4;
            margin-bottom: 1.2rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px dashed #e0e0e0;
        }

        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .summary-line .label {
            color: #666;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .summary-line .label i {
            color: #aaa;
            font-size: 0.8rem;
            width: 14px;
        }

        .summary-line .amount {
            font-weight: 600;
            color: #1a1a2e;
        }

        .summary-line .amount.adult-total {
            color: var(--primary-color);
        }

        .summary-line .amount.child-total {
            color: #4caf50;
        }

        .summary-line .amount.zero {
            color: #ccc;
            font-weight: 400;
        }

        .summary-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 1rem 0;
        }

        .summary-total {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
            margin-top: 1.2rem;
        }

        .summary-total-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .summary-total-label i {
            font-size: 0.8rem;
        }

        .summary-total-amount {
            font-size: 1.8rem;
            font-weight: 800;
            color: white;
            letter-spacing: -0.5px;
            line-height: 1;
        }

        .summary-total-amount .currency {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            margin-left: 0.3rem;
            vertical-align: middle;
        }

        .total-note {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
            margin-top: 0.5rem;
        }

        @keyframes priceUpdate {
            0% {
                transform: scale(1);
            }

            40% {
                transform: scale(1.08);
                color: #00bcd4;
            }

            100% {
                transform: scale(1);
            }
        }

        .price-animate {
            animation: priceUpdate 0.4s ease;
        }

        /* =========================================
         SUBMIT BUTTON & ACTIONS
         ========================================= */

        .form-actions {
            padding: 1.5rem 1.8rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .btn-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #2196f3, #00bcd4);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
            letter-spacing: 0.3px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.5);
            background: linear-gradient(135deg, #1976d2, #0097a7);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back {
            width: 100%;
            padding: 0.8rem;
            background: transparent;
            color: #666;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-back:hover {
            border-color: #bbb;
            color: #333;
            background: #f8f9fa;
        }

        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            color: #aaa;
            padding: 0 1.8rem 1.2rem;
        }

        .security-badge i {
            color: #4caf50;
            font-size: 0.85rem;
        }

        /* =========================================
         VALIDATION & ALERTS
         ========================================= */

        .booking-alert {
            padding: 0.9rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .booking-alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .booking-alert ul {
            margin: 0.4rem 0 0 1rem;
            padding: 0;
        }

        .booking-alert ul li {
            margin-bottom: 0.2rem;
        }

        #no-passengers-warning {
            display: none;
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
            padding: 0.9rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-size: 0.9rem;
        }

        .no-passengers-warning-inner {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* =========================================
         PROGRESS STEPS
         ========================================= */

        .booking-steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            margin-top: 1.5rem;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
            position: relative;
        }

        .step-circle {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: var(--accent-color);
            color: white;
            box-shadow: 0 0 0 4px rgba(0, 188, 212, 0.2);
        }

        .step.pending .step-circle {
            background: rgba(255, 255, 255, 0.15);
            color: rgba(255, 255, 255, 0.5);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .step-label {
            font-size: 0.72rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .step.active .step-label {
            color: var(--accent-color);
        }

        .step.pending .step-label {
            color: rgba(255, 255, 255, 0.4);
        }

        .step-connector {
            width: 60px;
            height: 2px;
            background: rgba(255, 255, 255, 0.2);
            margin-bottom: 1.4rem;
        }

        /* =========================================
         RESPONSIVE
         ========================================= */

        @media (max-width: 900px) {
            .booking-layout {
                grid-template-columns: 1fr;
            }

            .order-summary-card {
                position: static;
                order: -1;
            }

            .booking-hero-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .tour-overview {
                flex-direction: column;
            }

            .tour-thumbnail {
                width: 100%;
                height: 160px;
            }

            .price-summary-grid {
                grid-template-columns: 1fr;
            }

            .booking-steps {
                gap: 0;
            }

            .step-connector {
                width: 40px;
            }
        }
    </style>
</head>

<body>
    <?php
    $activePage = 'tours';
    require __DIR__ . '/includes/header.php';
    ?>

    <!-- BOOKING HERO -->
    <section class="booking-hero">
        <div class="container">
            <div class="booking-hero-content">
                <nav class="booking-breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="tours.php">Tour du lịch</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="tour_detail.php?id=<?= $tourId ?>">
                        <?= $tourName ?>
                    </a>
                    <i class="fas fa-chevron-right"></i>
                    <span>Đặt tour</span>
                </nav>
                <h1 class="booking-hero-title">Đặt Tour Du Lịch</h1>
                <p class="booking-hero-subtitle">Điền thông tin để hoàn tất đặt chỗ của bạn</p>

                <!-- Progress Steps -->
                <div class="booking-steps">
                    <div class="step active">
                        <div class="step-circle"><i class="fas fa-users"></i></div>
                        <span class="step-label">Chọn hành khách</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step pending">
                        <div class="step-circle">2</div>
                        <span class="step-label">Xác nhận</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step pending">
                        <div class="step-circle">3</div>
                        <span class="step-label">Thanh toán</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- BOOKING PAGE CONTENT -->
    <div class="booking-page">
        <div class="container">
            <div class="booking-layout">

                <!-- LEFT COLUMN: Tour Info + Form -->
                <div class="booking-left-col">

                    <!-- SERVER-SIDE ERROR ALERT -->
                    <?php if (!empty($errors)): ?>
                        <div class="booking-alert error" role="alert">
                            <i class="fas fa-exclamation-circle" style="margin-top:.15rem;flex-shrink:0;"></i>
                            <div>
                                <strong>Vui lòng kiểm tra lại thông tin:</strong>
                                <ul>
                                    <?php foreach ($errors as $err): ?>
                                        <li>
                                            <?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- TOUR INFORMATION CARD -->
                    <div class="tour-info-card">
                        <div class="tour-info-card-header">
                            <i class="fas fa-info-circle"></i>
                            <h2>Thông tin chuyến tour</h2>
                        </div>
                        <div class="tour-info-card-body">
                            <div class="tour-overview">
                                <img src="<?= $imageUrl ?>" alt="<?= $tourName ?>" class="tour-thumbnail" />
                                <div class="tour-overview-details">
                                    <h3>
                                        <?= $tourName ?>
                                    </h3>
                                    <div class="tour-meta-tags">
                                        <span class="meta-tag"><i class="fas fa-clock"></i>
                                            <?= $duration ?>
                                        </span>
                                        <span class="meta-tag"><i class="fas fa-map-marker-alt"></i>
                                            <?= $destination ?>
                                        </span>
                                        <?php if ($maxSlots > 0): ?>
                                            <span class="meta-tag slots-badge <?= $maxSlots <= 5 ? 'low' : 'available' ?>">
                                                <i class="fas fa-users"></i> Còn
                                                <?= $maxSlots ?> chỗ
                                            </span>
                                        <?php else: ?>
                                            <span class="meta-tag slots-badge full">
                                                <i class="fas fa-ban"></i> Hết chỗ
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tour-rating-row">
                                        <div class="stars">
                                            <?php
                                            $starFull = $avgRating !== null ? floor($avgRating) : 0;
                                            $starHalf = $avgRating !== null && ($avgRating - $starFull) >= 0.5;
                                            for ($i = 1; $i <= 5; $i++):
                                                if ($i <= $starFull): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php elseif ($starHalf && $i === $starFull + 1): ?>
                                                    <i class="fas fa-star-half-alt"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star" style="color:#ddd;"></i>
                                                <?php endif;
                                            endfor; ?>
                                        </div>
                                        <span class="rating-text">
                                            <?= $displayAvg ?> (
                                            <?= $reviewCount ?> đánh
                                            giá)
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Price per person summary -->
                            <div class="price-summary-grid">
                                <div class="price-summary-item">
                                    <label><i class="fas fa-male"></i> Người lớn</label>
                                    <div class="price-value">
                                        <?= formatVND($adultPrice) ?>
                                    </div>
                                    <div class="price-note">/ 1 người lớn</div>
                                </div>
                                <div class="price-summary-item children">
                                    <label><i class="fas fa-child"></i> Trẻ em (dưới 12)</label>
                                    <div class="price-value">
                                        <?= formatVND($childPrice) ?>
                                    </div>
                                    <div class="price-note">/ 1 trẻ em (50% giá)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOOKING FORM CARD -->
                    <div class="booking-form-card">
                        <div class="form-card-header">
                            <i class="fas fa-edit"></i>
                            <h2>Thông tin đặt tour</h2>
                        </div>
                        <div class="form-card-body">

                            <form id="booking-form" action="booking.php?id=<?= $tourId ?>" method="POST" novalidate>

                                <!-- HIDDEN: passenger counts (synced by JS) -->
                                <input type="hidden" name="adults" id="input-adults"
                                    value="<?= (int) ($form_data['adults'] ?? 1) ?>" />
                                <input type="hidden" name="children" id="input-children"
                                    value="<?= (int) ($form_data['children'] ?? 0) ?>" />

                                <!-- PASSENGER COUNT SECTION -->
                                <div class="passenger-section">
                                    <div class="form-section-title">
                                        <i class="fas fa-users"></i>
                                        Số lượng hành khách
                                    </div>

                                    <!-- Warning when no adults -->
                                    <div id="no-passengers-warning">
                                        <div class="no-passengers-warning-inner">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            Phải có ít nhất 1 người lớn để đặt tour.
                                        </div>
                                    </div>

                                    <!-- Adult row -->
                                    <div class="passenger-row" id="adult-row">
                                        <div class="passenger-info">
                                            <div class="passenger-icon adult">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div class="passenger-details">
                                                <h4>Người lớn <span style="color:#f44336;font-size:0.8rem;">*</span>
                                                </h4>
                                                <div class="unit-price">
                                                    Đơn giá: <strong>
                                                        <?= formatVND($adultPrice) ?>
                                                    </strong>/người
                                                </div>
                                            </div>
                                        </div>
                                        <div class="counter" id="adult-counter">
                                            <button type="button" class="counter-btn" id="adult-decrease"
                                                onclick="updatePassenger('adult', -1)" aria-label="Giảm số người lớn"
                                                <?= ($form_data['adults'] ?? 1) <= 1 ? 'disabled' : '' ?>>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="counter-value" id="adult-count">
                                                <?= (int) ($form_data['adults'] ?? 1) ?>
                                            </span>
                                            <button type="button" class="counter-btn" id="adult-increase"
                                                onclick="updatePassenger('adult', 1)" aria-label="Tăng số người lớn">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Child row -->
                                    <div class="passenger-row children-row" id="child-row">
                                        <div class="passenger-info">
                                            <div class="passenger-icon child">
                                                <i class="fas fa-baby"></i>
                                            </div>
                                            <div class="passenger-details">
                                                <h4>Trẻ em <span style="font-weight:400; color:#aaa;">(dưới 12
                                                        tuổi)</span></h4>
                                                <div class="unit-price">
                                                    Đơn giá: <strong>
                                                        <?= formatVND($childPrice) ?>
                                                    </strong>/trẻ em
                                                </div>
                                            </div>
                                        </div>
                                        <div class="counter" id="child-counter">
                                            <button type="button" class="counter-btn" id="child-decrease"
                                                onclick="updatePassenger('child', -1)" aria-label="Giảm số trẻ em"
                                                <?= ($form_data['children'] ?? 0) == 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <span class="counter-value" id="child-count">
                                                <?= (int) ($form_data['children'] ?? 0) ?>
                                            </span>
                                            <button type="button" class="counter-btn" id="child-increase"
                                                onclick="updatePassenger('child', 1)" aria-label="Tăng số trẻ em">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- DATE SECTION -->
                                <div class="date-section">
                                    <div class="form-section-title">
                                        <i class="fas fa-calendar-alt"></i>
                                        Ngày khởi hành
                                    </div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label for="departure-date">
                                                <i class="fas fa-calendar"></i>
                                                Ngày đi <span class="required">*</span>
                                            </label>
                                            <input type="date" id="departure-date" name="departure_date"
                                                class="form-control <?= !empty($errors) && empty($form_data['departure_date']) ? 'error' : '' ?>"
                                                min="<?= $today ?>" value="<?= old('departure_date', $form_data) ?>"
                                                required />
                                        </div>
                                        <div class="form-group">
                                            <label for="tour-slot">
                                                <i class="fas fa-ship"></i>
                                                Lịch khởi hành
                                            </label>
                                            <select id="tour-slot" name="tour_slot" class="form-control">
                                                <option value="">-- Chọn lịch --</option>
                                                <option value="morning">Sáng sớm (06:00)</option>
                                                <option value="noon">Buổi trưa (12:00)</option>
                                                <option value="evening">Chiều tối (17:00)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- SPECIAL REQUEST SECTION -->
                                <div class="date-section">
                                    <div class="form-section-title">
                                        <i class="fas fa-comment-alt"></i>
                                        Yêu cầu đặc biệt
                                    </div>
                                    <div class="form-group">
                                        <textarea id="special-request" name="special_request" class="form-control"
                                            placeholder="Ví dụ: ăn chay, phòng riêng cho trẻ em, hỗ trợ xe lăn..."><?= old('special_req', $form_data) ?></textarea>
                                    </div>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Order Summary -->
                <div class="booking-right-col">
                    <div class="order-summary-card">
                        <div class="summary-header">
                            <i class="fas fa-receipt"></i>
                            <h3>Tổng tiền tạm tính</h3>
                        </div>
                        <div class="summary-body">
                            <div class="summary-tour-name">
                                <?= $tourName ?>
                            </div>

                            <!-- Người lớn line -->
                            <div class="summary-line">
                                <span class="label">
                                    <i class="fas fa-user"></i>
                                    Người lớn (<span id="summary-adult-count">
                                        <?= (int) ($form_data['adults'] ?? 1) ?>
                                    </span> người)
                                </span>
                                <span class="amount adult-total" id="summary-adult-total">—</span>
                            </div>

                            <!-- Trẻ em line -->
                            <div class="summary-line">
                                <span class="label">
                                    <i class="fas fa-child"></i>
                                    Trẻ em (<span id="summary-child-count">
                                        <?= (int) ($form_data['children'] ?? 0) ?>
                                    </span> trẻ)
                                </span>
                                <span class="amount child-total zero" id="summary-child-total">0 đ</span>
                            </div>

                            <div class="summary-divider"></div>

                            <div class="summary-line">
                                <span class="label">
                                    <i class="fas fa-users"></i>
                                    Tổng hành khách
                                </span>
                                <span class="amount" id="summary-total-pax">—</span>
                            </div>

                            <!-- Grand Total -->
                            <div class="summary-total">
                                <div class="summary-total-label">
                                    <i class="fas fa-calculator"></i>
                                    Tổng tiền tạm tính
                                </div>
                                <div class="summary-total-amount" id="grand-total">
                                    0<span class="currency">đ</span>
                                </div>
                                <div class="total-note">* Chưa bao gồm phí dịch vụ và khuyến mãi</div>
                            </div>
                        </div>

                        <!-- Submit Actions -->
                        <div class="form-actions">
                            <?php if ($maxSlots <= 0): ?>
                                <div class="booking-alert error" style="margin-bottom:0;">
                                    <i class="fas fa-ban"></i>
                                    <span>Tour này hiện đã hết chỗ.</span>
                                </div>
                            <?php else: ?>
                                <button type="submit" form="booking-form" class="btn-submit" id="btn-submit-booking"
                                    onclick="return validateBooking()">
                                    <i class="fas fa-paper-plane"></i>
                                    XÁC NHẬN ĐẶT TOUR
                                </button>
                            <?php endif; ?>
                            <a href="tour_detail.php?id=<?= $tourId ?>" class="btn-back">
                                <i class="fas fa-arrow-left"></i>
                                Quay lại chi tiết tour
                            </a>
                        </div>
                        <div class="security-badge">
                            <i class="fas fa-lock"></i>
                            Thông tin của bạn được bảo mật tuyệt đối
                        </div>
                    </div>
                </div>

            </div><!-- end booking-layout -->
        </div><!-- end container -->
    </div><!-- end booking-page -->

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script>
        // ============================================
        //   BOOKING FORM - REAL-TIME PRICE CALCULATOR
        // ============================================

        const TOUR_CONFIG = {
            adultPrice: <?= (float) $adultPrice ?>,
            childPrice: <?= (float) $childPrice ?>,
                maxSlots: <?= (int) $maxSlots ?>,
                    maxPerType: <?= (int) $maxPerType ?>,
                        maxTotal: <?= (int) max(1, $maxTotal) ?>
        };

        let state = {
            adults: <?= (int) ($form_data['adults'] ?? 1) ?>,
            children: <?= (int) ($form_data['children'] ?? 0) ?>
        };

        function formatVND(amount) {
            if (amount === 0) return "0";
            return amount.toLocaleString("vi-VN");
        }

        function formatCurrency(amount) {
            return formatVND(amount) + " đ";
        }

        function animateElement(el) {
            el.classList.remove("price-animate");
            void el.offsetWidth;
            el.classList.add("price-animate");
        }

        function updateSummary() {
            const adultTotal = state.adults * TOUR_CONFIG.adultPrice;
            const childTotal = state.children * TOUR_CONFIG.childPrice;
            const grandTotal = adultTotal + childTotal;
            const totalPax = state.adults + state.children;

            // Adult summary
            const adultTotalEl = document.getElementById("summary-adult-total");
            const prevAdult = adultTotalEl.textContent;
            adultTotalEl.textContent = formatCurrency(adultTotal);
            adultTotalEl.className = "amount adult-total" + (adultTotal === 0 ? " zero" : "");
            if (prevAdult !== adultTotalEl.textContent) animateElement(adultTotalEl);
            document.getElementById("summary-adult-count").textContent = state.adults;

            // Child summary
            const childTotalEl = document.getElementById("summary-child-total");
            const prevChild = childTotalEl.textContent;
            childTotalEl.textContent = formatCurrency(childTotal);
            childTotalEl.className = "amount child-total" + (childTotal === 0 ? " zero" : "");
            if (prevChild !== childTotalEl.textContent) animateElement(childTotalEl);
            document.getElementById("summary-child-count").textContent = state.children;

            // Total pax
            document.getElementById("summary-total-pax").textContent =
                totalPax > 0 ? totalPax + " người" : "0 người";

            // Grand total
            const grandTotalEl = document.getElementById("grand-total");
            const prevGrand = grandTotalEl.innerHTML;
            grandTotalEl.innerHTML = formatVND(grandTotal) + '<span class="currency">đ</span>';
            if (prevGrand !== grandTotalEl.innerHTML) animateElement(grandTotalEl);

            // Warning visibility (phải có ít nhất 1 người lớn)
            document.getElementById("no-passengers-warning").style.display =
                state.adults < 1 ? "block" : "none";

            // Submit button state
            const submitBtn = document.getElementById("btn-submit-booking");
            if (submitBtn) {
                const canSubmit = state.adults >= 1 && TOUR_CONFIG.maxSlots > 0;
                submitBtn.disabled = !canSubmit;
                submitBtn.style.opacity = canSubmit ? "1" : "0.6";
                submitBtn.style.cursor = canSubmit ? "pointer" : "not-allowed";
            }

            // Sync hidden inputs for PHP POST
            document.getElementById("input-adults").value = state.adults;
            document.getElementById("input-children").value = state.children;
        }

        function updatePassenger(type, delta) {
            const totalCurrent = state.adults + state.children;

            if (type === "adult") {
                const newVal = state.adults + delta;
                if (newVal < 1) return; // minimum 1 người lớn
                if (newVal > TOUR_CONFIG.maxPerType) return;
                if (delta > 0 && totalCurrent >= TOUR_CONFIG.maxTotal) return;
                state.adults = newVal;
                document.getElementById("adult-count").textContent = state.adults;
                document.getElementById("adult-decrease").disabled = state.adults <= 1;
            }

            if (type === "child") {
                const newVal = state.children + delta;
                if (newVal < 0) return;
                if (newVal > TOUR_CONFIG.maxPerType) return;
                if (delta > 0 && totalCurrent >= TOUR_CONFIG.maxTotal) return;
                state.children = newVal;
                document.getElementById("child-count").textContent = state.children;
                document.getElementById("child-decrease").disabled = state.children === 0;
            }

            updateSummary();
        }

        function validateBooking() {
            if (state.adults < 1) {
                document.getElementById("no-passengers-warning").style.display = "block";
                document.getElementById("adult-row").scrollIntoView({ behavior: "smooth", block: "center" });
                return false;
            }

            const departureDate = document.getElementById("departure-date").value;
            if (!departureDate) {
                document.getElementById("departure-date").classList.add("error");
                document.getElementById("departure-date").focus();
                return false;
            } else {
                document.getElementById("departure-date").classList.remove("error");
            }

            return true;
        }

        // Remove error class on focus
        document.querySelectorAll(".form-control").forEach((input) => {
            input.addEventListener("focus", () => input.classList.remove("error"));
        });

        // Initialize on page load
        document.addEventListener("DOMContentLoaded", () => {
            updateSummary();
        });
    </script>
</body>

</html>