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
    header('Location: login.php?redirect=' . urlencode('cancellation_refund.php?' . http_build_query($_GET)));
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ============================================
//   ĐẢM BẢO CỘT cancel_reason TỒN TẠI
// ============================================
try {
    $pdo->exec(
        "ALTER TABLE bookings
         ADD COLUMN IF NOT EXISTS cancel_reason TEXT NULL AFTER status"
    );
} catch (\Throwable $e) {
    // MySQL < 8.0 không có IF NOT EXISTS, bỏ qua
}

// ============================================
//   LẤY THÔNG TIN ĐƠN ĐẶT TOUR
// ============================================
$bookingId = (int) ($_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    header('Location: my_bookings.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT b.id, b.user_id, b.adults, b.children, b.total_amount, b.status,
            b.cancel_reason, b.created_at,
            t.tour_name, t.destination, t.duration, t.image_url, t.price
     FROM bookings b
     INNER JOIN tours t ON t.id = b.tour_id
     WHERE b.id = :id AND b.user_id = :uid
     LIMIT 1"
);
$stmt->execute(['id' => $bookingId, 'uid' => $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

$status = (string) $booking['status'];
$cancelReason = (string) ($booking['cancel_reason'] ?? '');

// Chỉ cho phép hủy khi đang ở trạng thái "chờ duyệt" hoặc "đã xác nhận"
$canCancel = in_array($status, ['chờ duyệt', 'đã xác nhận'], true);

// ============================================
//   XỬ LÝ FORM HỦY TOUR (POST)
// ============================================
$errors = [];
$flashOk = null;
$justCancelled = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canCancel) {
    $reason = trim((string) ($_POST['cancel_reason_select'] ?? ''));
    $reasonText = trim((string) ($_POST['cancel_reason_detail'] ?? ''));
    $agreed = !empty($_POST['confirm_cancel']);

    if (empty($reason)) {
        $errors[] = 'Vui lòng chọn lý do hủy tour.';
    }
    if (!$agreed) {
        $errors[] = 'Vui lòng đồng ý với chính sách hủy tour.';
    }

    if (empty($errors)) {
        $fullReason = $reason;
        if ($reasonText !== '') {
            $fullReason .= ' – ' . $reasonText;
        }

        try {
            $upd = $pdo->prepare(
                "UPDATE bookings
                 SET status = 'yêu cầu hủy', cancel_reason = :reason
                 WHERE id = :id AND user_id = :uid"
            );
            $upd->execute([
                'reason' => $fullReason,
                'id' => $bookingId,
                'uid' => $userId,
            ]);

            $status = 'yêu cầu hủy';
            $cancelReason = $fullReason;
            $canCancel = false;
            $justCancelled = true;

            // Cập nhật biến $booking để hiển thị đúng trạng thái
            $booking['status'] = $status;
            $booking['cancel_reason'] = $cancelReason;
        } catch (\Throwable $e) {
            error_log('Cancel booking error: ' . $e->getMessage());
            $errors[] = 'Không thể cập nhật trạng thái. Vui lòng thử lại sau.';
        }
    }
}

// ============================================
//   HELPER – Format
// ============================================
$tourName = htmlspecialchars((string) $booking['tour_name'], ENT_QUOTES, 'UTF-8');
$destination = htmlspecialchars((string) $booking['destination'], ENT_QUOTES, 'UTF-8');
$duration = htmlspecialchars((string) $booking['duration'], ENT_QUOTES, 'UTF-8');
$totalFmt = number_format((float) $booking['total_amount'], 0, ',', '.') . ' đ';
$bookedDate = date('d/m/Y', strtotime((string) $booking['created_at']));
$imageUrl = !empty($booking['image_url'])
    ? htmlspecialchars((string) $booking['image_url'], ENT_QUOTES, 'UTF-8')
    : 'https://images.unsplash.com/photo-1528127269322-539801943592?w=400&h=300&fit=crop';

$adultPrice = (float) $booking['price'];
$childPrice = $adultPrice * 0.5;
$adultTotal = (int) $booking['adults'] * $adultPrice;
$childTotal = (int) $booking['children'] * $childPrice;
$adultTotalFmt = number_format($adultTotal, 0, ',', '.') . ' đ';
$childTotalFmt = number_format($childTotal, 0, ',', '.') . ' đ';

// Badge helper
function statusBadge(string $status): array
{
    return match ($status) {
        'chờ duyệt' => ['badge-pending', 'fa-clock', 'Chờ duyệt'],
        'đã xác nhận' => ['badge-confirmed', 'fa-check-circle', 'Đã xác nhận'],
        'đã thanh toán' => ['badge-confirmed', 'fa-money-bill-wave', 'Đã thanh toán'],
        'yêu cầu hủy' => ['badge-refunding', 'fa-sync-alt', 'Yêu cầu hủy'],
        'đã hủy' => ['badge-cancelled', 'fa-times-circle', 'Đã hủy'],
        default => ['badge-pending', 'fa-question-circle', $status],
    };
}

[$badgeClass, $badgeIcon, $badgeLabel] = statusBadge($status);
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hủy Tour & Hoàn Tiền –
        <?= $tourName ?> - Du Lịch Việt
    </title>
    <meta name="description"
        content="Gửi yêu cầu hủy tour và theo dõi tiến trình hoàn tiền tại Du Lịch Việt. Hỗ trợ nhanh chóng và minh bạch." />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
        /* =============================================
           CANCELLATION & REFUND PAGE – STYLES
           ============================================= */

        /* ---------- Hero ---------- */
        .cr-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 3rem 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .cr-hero::before {
            content: '';
            position: absolute;
            inset: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 60% 25%, rgba(255, 107, 107, .07) 0%, transparent 55%),
                radial-gradient(circle at 20% 80%, rgba(0, 188, 212, .06) 0%, transparent 50%);
            animation: heroGlow 10s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes heroGlow {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.04);
            }
        }

        .cr-hero-inner {
            position: relative;
            z-index: 2;
            text-align: center;
            color: #fff;
        }

        .cr-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: .9rem;
            color: rgba(255, 255, 255, .55);
            margin-bottom: 1.5rem;
        }

        .cr-breadcrumb a {
            color: rgba(255, 255, 255, .55);
            transition: color .3s;
            text-decoration: none;
        }

        .cr-breadcrumb a:hover {
            color: var(--accent-color);
        }

        .cr-breadcrumb i {
            font-size: .7rem;
        }

        .cr-breadcrumb .current {
            color: var(--accent-color);
            font-weight: 600;
        }

        .cr-hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: .5rem;
            background: linear-gradient(135deg, #fff 0%, #ffcdd2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cr-hero-sub {
            color: rgba(255, 255, 255, .65);
            font-size: 1rem;
        }

        /* ---------- Page body ---------- */
        .cr-page {
            background: #f0f4f8;
            min-height: 100vh;
            padding: 2.5rem 0 4rem;
        }

        .cr-layout {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* ---------- Generic card ---------- */
        .cr-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
            margin-bottom: 1.5rem;
        }

        .cr-card-header {
            padding: 1.3rem 1.8rem;
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .cr-card-header i {
            font-size: 1.15rem;
        }

        .cr-card-header h2 {
            font-size: 1.05rem;
            margin: 0;
            font-weight: 700;
        }

        .cr-card-body {
            padding: 1.8rem;
        }

        /* Header color variants */
        .cr-header-dark {
            background: linear-gradient(135deg, #0f3460, #16213e);
        }

        .cr-header-dark i,
        .cr-header-dark h2 {
            color: #fff;
        }

        .cr-header-dark i {
            color: var(--accent-color);
        }

        .cr-header-red {
            background: linear-gradient(135deg, #c62828, #e53935);
        }

        .cr-header-red i,
        .cr-header-red h2 {
            color: #fff;
        }

        .cr-header-blue {
            background: linear-gradient(135deg, #2196f3, #00bcd4);
        }

        .cr-header-blue i,
        .cr-header-blue h2 {
            color: #fff;
        }

        .cr-header-green {
            background: linear-gradient(135deg, #2e7d32, #4caf50);
        }

        .cr-header-green i,
        .cr-header-green h2 {
            color: #fff;
        }

        .cr-header-orange {
            background: linear-gradient(135deg, #e65100, #ff9800);
        }

        .cr-header-orange i,
        .cr-header-orange h2 {
            color: #fff;
        }

        /* ---------- Order Detail ---------- */
        .order-detail-tour {
            display: flex;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px dashed #e0e0e0;
        }

        .order-detail-thumb {
            width: 110px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .15);
        }

        .order-detail-info h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1a1a2e;
            margin: 0 0 .5rem;
            line-height: 1.4;
        }

        .order-detail-meta {
            display: flex;
            flex-direction: column;
            gap: .3rem;
            font-size: .85rem;
            color: #888;
        }

        .order-detail-meta span {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .order-detail-meta i {
            color: var(--primary-color);
            width: 16px;
            font-size: .8rem;
        }

        .order-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: .55rem 0;
            font-size: .9rem;
        }

        .order-row .lbl {
            color: #666;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .order-row .lbl i {
            color: #aaa;
            font-size: .8rem;
            width: 16px;
        }

        .order-row .val {
            font-weight: 600;
            color: #1a1a2e;
        }

        .order-row .val.blue {
            color: var(--primary-color);
        }

        .order-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: .6rem 0;
        }

        .order-total-box {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border-radius: 12px;
            padding: 1.2rem 1.4rem;
            margin-top: 1.2rem;
        }

        .order-total-label {
            font-size: .82rem;
            color: rgba(255, 255, 255, .55);
            margin-bottom: .35rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .order-total-amount {
            font-size: 1.8rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -.5px;
        }

        .order-total-amount .cur {
            font-size: 1rem;
            font-weight: 600;
            color: rgba(255, 255, 255, .65);
            margin-left: .3rem;
        }

        /* Status badges */
        .order-status-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .85rem;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .order-status-badge i {
            font-size: .7rem;
        }

        .badge-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .badge-pending {
            background: #fff8e1;
            color: #f57f17;
            border: 1px solid #ffe082;
        }

        .badge-cancelled {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .badge-refunding {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .badge-refunded {
            background: #e0f2f1;
            color: #00695c;
            border: 1px solid #80cbc4;
        }

        /* ---------- Cancel-reason info box ---------- */
        .cancel-reason-note {
            background: #fff5f5;
            border-left: 3px solid #f87171;
            border-radius: 8px;
            padding: .9rem 1.1rem;
            font-size: .88rem;
            color: #7a1a1a;
            margin-top: 1rem;
            font-style: italic;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
        }

        .cancel-reason-note i {
            flex-shrink: 0;
            margin-top: .2rem;
        }

        /* ---------- Alerts ---------- */
        .cr-alert {
            padding: .9rem 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.2rem;
            font-size: .9rem;
            display: flex;
            align-items: flex-start;
            gap: .6rem;
        }

        .cr-alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .cr-alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .cr-alert.info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .cr-alert ul {
            margin: .4rem 0 0 1.2rem;
            padding: 0;
        }

        .cr-alert ul li {
            margin-bottom: .2rem;
        }

        /* ---------- Form ---------- */
        .cr-form-group {
            margin-bottom: 1.5rem;
        }

        .cr-form-group label {
            display: block;
            font-size: .88rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: .5rem;
        }

        .cr-form-group label .required {
            color: #e53935;
            margin-left: .2rem;
        }

        .cr-select {
            width: 100%;
            padding: .8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: .92rem;
            font-family: inherit;
            outline: none;
            appearance: none;
            -webkit-appearance: none;
            background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23999'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E") no-repeat right 12px center;
            background-size: 20px;
            cursor: pointer;
            transition: border-color .3s, box-shadow .3s;
            color: #333;
        }

        .cr-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, .1);
        }

        .cr-select.error {
            border-color: #e53935;
        }

        .cr-textarea {
            width: 100%;
            min-height: 120px;
            padding: .8rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: .92rem;
            font-family: inherit;
            outline: none;
            resize: vertical;
            transition: border-color .3s, box-shadow .3s;
            line-height: 1.6;
            color: #333;
            box-sizing: border-box;
        }

        .cr-textarea:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, .1);
        }

        .cr-textarea::placeholder {
            color: #bbb;
        }

        .cr-char-count {
            text-align: right;
            font-size: .78rem;
            color: #aaa;
            margin-top: .3rem;
        }

        .cr-policy-box {
            background: linear-gradient(135deg, #fff3e0, #fff8e1);
            border: 1px solid #ffe0b2;
            border-radius: 12px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
        }

        .cr-policy-box h4 {
            font-size: .9rem;
            color: #e65100;
            margin: 0 0 .6rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .cr-policy-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .cr-policy-list li {
            font-size: .82rem;
            color: #8d6e00;
            padding: .3rem 0;
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            line-height: 1.5;
        }

        .cr-policy-list li i {
            color: #e65100;
            margin-top: .2rem;
            font-size: .7rem;
            flex-shrink: 0;
        }

        .cr-confirm-check {
            display: flex;
            align-items: flex-start;
            gap: .6rem;
            margin-bottom: 1.5rem;
            cursor: pointer;
        }

        .cr-confirm-check input {
            width: 20px;
            height: 20px;
            accent-color: #e53935;
            cursor: pointer;
            flex-shrink: 0;
            margin-top: .1rem;
        }

        .cr-confirm-check span {
            font-size: .88rem;
            color: #555;
            line-height: 1.5;
        }

        .btn-cancel-submit {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #e53935, #c62828);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .6rem;
            box-shadow: 0 4px 15px rgba(229, 57, 53, .4);
            letter-spacing: .3px;
            font-family: inherit;
        }

        .btn-cancel-submit:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(229, 57, 53, .5);
        }

        .btn-cancel-submit:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-back-link {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-top: 1rem;
            color: #666;
            font-size: .9rem;
            font-weight: 600;
            transition: color .3s;
            text-decoration: none;
        }

        .btn-back-link:hover {
            color: var(--primary-color);
        }

        /* ---------- Refund Tracker ---------- */
        .refund-tracker {
            padding: 2rem 1.5rem;
        }

        .refund-steps {
            position: relative;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .refund-progress-line {
            position: absolute;
            top: 28px;
            left: 28px;
            right: 28px;
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            z-index: 1;
        }

        .refund-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4caf50, #00bcd4);
            border-radius: 2px;
            width: 0%;
            transition: width .8s cubic-bezier(.4, 0, .2, 1);
            position: relative;
        }

        .refund-progress-fill::after {
            content: '';
            position: absolute;
            right: -4px;
            top: -3px;
            width: 10px;
            height: 10px;
            background: #00bcd4;
            border-radius: 50%;
            box-shadow: 0 0 12px rgba(0, 188, 212, .6);
            opacity: 0;
            transition: opacity .4s;
        }

        .refund-progress-fill.moving::after {
            opacity: 1;
            animation: glowPulse 1.5s ease-in-out infinite;
        }

        @keyframes glowPulse {

            0%,
            100% {
                box-shadow: 0 0 8px rgba(0, 188, 212, .4);
            }

            50% {
                box-shadow: 0 0 20px rgba(0, 188, 212, .8);
            }
        }

        .refund-step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            text-align: center;
        }

        .refund-step-circle {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all .5s ease;
            background: #fff;
            border: 3px solid #e0e0e0;
            color: #ccc;
        }

        .refund-step.completed .refund-step-circle {
            background: linear-gradient(135deg, #4caf50, #66bb6a);
            border-color: #4caf50;
            color: #fff;
            box-shadow: 0 4px 15px rgba(76, 175, 80, .35);
        }

        .refund-step.active .refund-step-circle {
            background: linear-gradient(135deg, #2196f3, #00bcd4);
            border-color: #2196f3;
            color: #fff;
            box-shadow: 0 0 0 5px rgba(33, 150, 243, .15), 0 4px 15px rgba(33, 150, 243, .35);
            animation: activeRipple 2s ease-in-out infinite;
        }

        @keyframes activeRipple {

            0%,
            100% {
                box-shadow: 0 0 0 5px rgba(33, 150, 243, .15), 0 4px 15px rgba(33, 150, 243, .35);
            }

            50% {
                box-shadow: 0 0 0 10px rgba(33, 150, 243, .08), 0 4px 20px rgba(33, 150, 243, .45);
            }
        }

        .refund-step.pending .refund-step-circle {
            background: #f5f5f5;
            border-color: #e0e0e0;
            color: #ccc;
        }

        .refund-step-label {
            margin-top: .8rem;
            font-size: .78rem;
            font-weight: 600;
            color: #aaa;
            max-width: 100px;
            line-height: 1.4;
            transition: color .4s;
        }

        .refund-step.completed .refund-step-label {
            color: #2e7d32;
        }

        .refund-step.active .refund-step-label {
            color: var(--primary-color);
            font-weight: 700;
        }

        .refund-step-time {
            font-size: .7rem;
            color: #bbb;
            margin-top: .3rem;
            transition: color .4s;
        }

        .refund-step.completed .refund-step-time {
            color: #81c784;
        }

        .refund-step.active .refund-step-time {
            color: #90caf9;
        }

        .refund-summary {
            margin-top: 2rem;
            padding: 1.2rem 1.4rem;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all .5s ease;
        }

        .refund-summary.status-submitted {
            background: linear-gradient(135deg, rgba(255, 152, 0, .08), rgba(255, 183, 77, .08));
            border: 1px solid rgba(255, 152, 0, .2);
        }

        .refund-summary.status-processing {
            background: linear-gradient(135deg, rgba(33, 150, 243, .08), rgba(0, 188, 212, .08));
            border: 1px solid rgba(33, 150, 243, .2);
        }

        .refund-summary.status-approved {
            background: linear-gradient(135deg, rgba(76, 175, 80, .08), rgba(129, 199, 132, .08));
            border: 1px solid rgba(76, 175, 80, .2);
        }

        .refund-summary.status-completed {
            background: linear-gradient(135deg, rgba(0, 105, 92, .08), rgba(0, 188, 212, .08));
            border: 1px solid rgba(0, 105, 92, .2);
        }

        .refund-summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .refund-summary-icon.submitted {
            background: linear-gradient(135deg, #ff9800, #ffb74d);
            color: #fff;
        }

        .refund-summary-icon.processing {
            background: linear-gradient(135deg, #2196f3, #00bcd4);
            color: #fff;
        }

        .refund-summary-icon.approved {
            background: linear-gradient(135deg, #4caf50, #81c784);
            color: #fff;
        }

        .refund-summary-icon.completed {
            background: linear-gradient(135deg, #00695c, #00bcd4);
            color: #fff;
        }

        .refund-summary-text h4 {
            font-size: .95rem;
            font-weight: 700;
            margin: 0 0 .25rem;
            color: #1a1a2e;
        }

        .refund-summary-text p {
            font-size: .82rem;
            color: #888;
            margin: 0;
            line-height: 1.5;
        }

        /* Sim controls */
        .sim-controls {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px dashed #e0e0e0;
        }

        .sim-controls-title {
            font-size: .82rem;
            font-weight: 700;
            color: #888;
            margin-bottom: .8rem;
            display: flex;
            align-items: center;
            gap: .4rem;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .sim-controls-title i {
            color: var(--accent-color);
        }

        .sim-btn-group {
            display: flex;
            gap: .6rem;
            flex-wrap: wrap;
        }

        .sim-btn {
            padding: .55rem 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #fff;
            font-family: inherit;
            font-size: .8rem;
            font-weight: 600;
            color: #555;
            cursor: pointer;
            transition: all .3s;
            display: flex;
            align-items: center;
            gap: .35rem;
            white-space: nowrap;
        }

        .sim-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .sim-btn.active {
            border-color: var(--primary-color);
            background: #e3f2fd;
            color: var(--primary-color);
        }

        .sim-btn i {
            font-size: .72rem;
        }

        .sim-btn-auto {
            padding: .55rem 1.2rem;
            border: none;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            font-family: inherit;
            font-size: .8rem;
            font-weight: 700;
            color: #fff;
            cursor: pointer;
            transition: all .3s;
            display: flex;
            align-items: center;
            gap: .35rem;
            box-shadow: 0 4px 12px rgba(33, 150, 243, .3);
        }

        .sim-btn-auto:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(33, 150, 243, .4);
        }

        .sim-btn-auto:disabled {
            opacity: .5;
            cursor: not-allowed;
            transform: none;
        }

        /* ---------- Success overlay ---------- */
        .cr-success-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .cr-success-overlay.show {
            display: flex;
        }

        .cr-success-content {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            max-width: 420px;
            width: 90%;
            animation: modalPop .4s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .2);
        }

        @keyframes modalPop {
            from {
                opacity: 0;
                transform: scale(.85) translateY(20px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .cr-success-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e53935, #c62828);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 1.8rem;
            color: #fff;
            box-shadow: 0 8px 25px rgba(229, 57, 53, .3);
        }

        .cr-success-content h3 {
            font-size: 1.3rem;
            font-weight: 800;
            color: #1a1a2e;
            margin-bottom: .6rem;
        }

        .cr-success-content p {
            font-size: .92rem;
            color: #888;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }

        .btn-back-orders {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .8rem 2rem;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: #fff;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            transition: all .3s;
            font-family: inherit;
            font-size: .92rem;
            box-shadow: 0 4px 15px rgba(33, 150, 243, .3);
            text-decoration: none;
        }

        .btn-back-orders:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, .4);
        }

        /* ---------- Status info box ---------- */
        .status-info-box {
            padding: 1.2rem 1.4rem;
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: .8rem;
            margin-bottom: 1.2rem;
        }

        .status-info-box.info-orange {
            background: #fff3e0;
            border: 1px solid #ffe0b2;
            color: #8d4a00;
        }

        .status-info-box.info-red {
            background: #ffebee;
            border: 1px solid #ef9a9a;
            color: #7a1a1a;
        }

        .status-info-box i {
            flex-shrink: 0;
            margin-top: .1rem;
            font-size: 1.1rem;
        }

        .status-info-box div h4 {
            font-size: .95rem;
            font-weight: 700;
            margin: 0 0 .3rem;
        }

        .status-info-box div p {
            font-size: .85rem;
            margin: 0;
            line-height: 1.5;
        }

        /* ---------- Responsive ---------- */
        @media (max-width: 900px) {
            .cr-layout {
                grid-template-columns: 1fr;
            }

            .cr-hero-title {
                font-size: 1.8rem;
            }

            .refund-step-label {
                font-size: .7rem;
                max-width: 70px;
            }

            .refund-step-circle {
                width: 44px;
                height: 44px;
                font-size: 1rem;
            }

            .refund-progress-line {
                top: 22px;
                left: 22px;
                right: 22px;
            }
        }

        @media (max-width: 480px) {
            .cr-hero-title {
                font-size: 1.5rem;
            }

            .order-detail-tour {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .order-detail-thumb {
                width: 100%;
                height: 140px;
            }

            .sim-btn-group {
                flex-direction: column;
            }

            .sim-btn,
            .sim-btn-auto {
                width: 100%;
                justify-content: center;
            }

            .refund-step-time {
                display: none;
            }

            .refund-step-label {
                font-size: .65rem;
                max-width: 60px;
            }

            .refund-step-circle {
                width: 38px;
                height: 38px;
                font-size: .85rem;
            }

            .refund-progress-line {
                top: 19px;
                left: 19px;
                right: 19px;
            }
        }
    </style>
</head>

<body>
    <?php
    $activePage = '';
    require __DIR__ . '/includes/header.php';
    ?>

    <!-- =============== HERO =============== -->
    <section class="cr-hero">
        <div class="container">
            <div class="cr-hero-inner">
                <nav class="cr-breadcrumb" aria-label="Breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <i class="fas fa-chevron-right"></i>
                    <a href="my_bookings.php">Lịch sử đặt tour</a>
                    <i class="fas fa-chevron-right"></i>
                    <span class="current">Hủy Tour & Hoàn Tiền</span>
                </nav>
                <h1 class="cr-hero-title">Hủy Tour & Hoàn Tiền</h1>
                <p class="cr-hero-sub">Gửi yêu cầu hủy tour và theo dõi tiến trình hoàn tiền của bạn</p>
            </div>
        </div>
    </section>

    <!-- =============== MAIN CONTENT =============== -->
    <div class="cr-page">
        <div class="container">
            <div class="cr-layout">

                <!-- ======= LEFT – ORDER DETAIL ======= -->
                <div class="cr-left-col">
                    <div class="cr-card">
                        <div class="cr-card-header cr-header-dark">
                            <i class="fas fa-file-invoice"></i>
                            <h2>Chi Tiết Đơn Hàng</h2>
                        </div>
                        <div class="cr-card-body">

                            <div class="order-detail-tour">
                                <img src="<?= $imageUrl ?>" alt="<?= $tourName ?>" class="order-detail-thumb" />
                                <div class="order-detail-info">
                                    <h3>
                                        <?= $tourName ?>
                                    </h3>
                                    <div class="order-detail-meta">
                                        <span><i class="fas fa-map-marker-alt"></i>
                                            <?= $destination ?>
                                        </span>
                                        <span><i class="fas fa-clock"></i>
                                            <?= $duration ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-users"></i>
                                            <?= (int) $booking['adults'] ?> người
                                            lớn
                                            <?= (int) $booking['children'] > 0 ? ', ' . (int) $booking['children'] . ' trẻ em' : '' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="order-row">
                                <span class="lbl"><i class="fas fa-hashtag"></i> Mã đơn</span>
                                <span class="val"
                                    style="font-family:monospace;color:var(--primary-color);font-weight:700;">#
                                    <?= $bookingId ?>
                                </span>
                            </div>
                            <div class="order-row">
                                <span class="lbl"><i class="fas fa-calendar-check"></i> Ngày đặt</span>
                                <span class="val">
                                    <?= $bookedDate ?>
                                </span>
                            </div>
                            <div class="order-row">
                                <span class="lbl"><i class="fas fa-info-circle"></i> Trạng thái</span>
                                <span class="val">
                                    <span class="order-status-badge <?= $badgeClass ?>" id="order-status-badge">
                                        <i class="fas <?= $badgeIcon ?>"></i>
                                        <?= htmlspecialchars($badgeLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </span>
                            </div>

                            <div class="order-divider"></div>

                            <div class="order-row">
                                <span class="lbl"><i class="fas fa-user"></i>
                                    <?= (int) $booking['adults'] ?> × Người
                                    lớn
                                </span>
                                <span class="val">
                                    <?= $adultTotalFmt ?>
                                </span>
                            </div>
                            <?php if ((int) $booking['children'] > 0): ?>
                                <div class="order-row">
                                    <span class="lbl"><i class="fas fa-child"></i>
                                        <?= (int) $booking['children'] ?> × Trẻ
                                        em
                                    </span>
                                    <span class="val">
                                        <?= $childTotalFmt ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <div class="order-total-box">
                                <div class="order-total-label"><i class="fas fa-wallet"></i> Tổng tiền</div>
                                <div class="order-total-amount">
                                    <?= number_format((float) $booking['total_amount'], 0, ',', '.') ?><span
                                        class="cur">đ</span>
                                </div>
                            </div>

                            <?php if ($cancelReason !== '' && !$canCancel): ?>
                                <div class="cancel-reason-note">
                                    <i class="fas fa-comment-slash"></i>
                                    Lý do hủy:
                                    <?= htmlspecialchars($cancelReason, ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>

                            <a href="my_bookings.php" class="btn-back-link">
                                <i class="fas fa-arrow-left"></i> Quay lại lịch sử đặt tour
                            </a>

                        </div>
                    </div>
                </div>

                <!-- ======= RIGHT – CANCEL FORM & REFUND TRACKER ======= -->
                <div class="cr-right-col">

                    <?php if (!empty($errors)): ?>
                        <div class="cr-alert error" role="alert">
                            <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:.1rem;"></i>
                            <div>
                                <strong>Có lỗi xảy ra:</strong>
                                <ul>
                                    <?php foreach ($errors as $e): ?>
                                        <li>
                                            <?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($canCancel): ?>
                        <!-- ── Cancellation Request Form (chỉ hiển thị khi còn có thể hủy) ── -->
                        <div class="cr-card" id="cancel-form-card">
                            <div class="cr-card-header cr-header-red">
                                <i class="fas fa-ban"></i>
                                <h2>Yêu Cầu Hủy Tour</h2>
                            </div>
                            <div class="cr-card-body">
                                <form method="POST" action="cancellation_refund.php?booking_id=<?= $bookingId ?>"
                                    id="cancel-form" novalidate>

                                    <div class="cr-form-group">
                                        <label for="cancel-reason-select">Lý do hủy tour <span
                                                class="required">*</span></label>
                                        <select class="cr-select" id="cancel-reason-select" name="cancel_reason_select"
                                            required>
                                            <option value="" disabled selected>-- Chọn lý do hủy --</option>
                                            <option value="Thay đổi kế hoạch">Thay đổi kế hoạch</option>
                                            <option value="Vấn đề sức khỏe">Vấn đề sức khỏe</option>
                                            <option value="Thời tiết xấu / Thiên tai">Thời tiết xấu / Thiên tai</option>
                                            <option value="Lý do tài chính">Lý do tài chính</option>
                                            <option value="Tìm được tour tốt hơn">Tìm được tour tốt hơn</option>
                                            <option value="Lý do cá nhân khác">Lý do cá nhân khác</option>
                                            <option value="Không hài lòng với dịch vụ">Không hài lòng với dịch vụ</option>
                                        </select>
                                    </div>

                                    <div class="cr-form-group">
                                        <label for="cancel-detail">Chi tiết lý do</label>
                                        <textarea class="cr-textarea" id="cancel-detail" name="cancel_reason_detail"
                                            placeholder="Vui lòng mô tả chi tiết lý do bạn muốn hủy tour... (tùy chọn)"
                                            maxlength="500"></textarea>
                                        <div class="cr-char-count"><span id="char-count">0</span>/500</div>
                                    </div>

                                    <!-- Chính sách hủy tour -->
                                    <div class="cr-policy-box">
                                        <h4><i class="fas fa-exclamation-triangle"></i> Chính sách hủy tour</h4>
                                        <ul class="cr-policy-list">
                                            <li><i class="fas fa-circle"></i> Hủy trước 15 ngày khởi hành: hoàn 100% tiền
                                                tour</li>
                                            <li><i class="fas fa-circle"></i> Hủy trước 7–14 ngày: hoàn 70% tiền tour</li>
                                            <li><i class="fas fa-circle"></i> Hủy trước 3–6 ngày: hoàn 50% tiền tour</li>
                                            <li><i class="fas fa-circle"></i> Hủy dưới 3 ngày: không hoàn tiền</li>
                                            <li><i class="fas fa-circle"></i> Thời gian hoàn tiền: 5–7 ngày làm việc</li>
                                        </ul>
                                    </div>

                                    <label class="cr-confirm-check">
                                        <input type="checkbox" id="confirm-cancel" name="confirm_cancel" value="1" />
                                        <span>Tôi đã đọc và đồng ý với <strong>chính sách hủy tour</strong> của Du Lịch
                                            Việt.
                                            Tôi hiểu rằng yêu cầu hủy này không thể hoàn tác sau khi được xử lý.</span>
                                    </label>

                                    <button type="submit" class="btn-cancel-submit" id="btn-submit-cancel" disabled>
                                        <i class="fas fa-paper-plane"></i> Gửi Yêu Cầu Hủy
                                    </button>
                                </form>
                            </div>
                        </div>

                    <?php elseif (in_array($status, ['yêu cầu hủy', 'đã hủy'], true)): ?>
                        <!-- ── Refund Progress Tracker ── -->
                        <div class="cr-card" id="refund-tracker-card">
                            <div class="cr-card-header cr-header-blue">
                                <i class="fas fa-sync-alt"></i>
                                <h2>Tiến Trình Hoàn Tiền</h2>
                            </div>
                            <div class="refund-tracker">
                                <div class="refund-steps">
                                    <div class="refund-progress-line">
                                        <div class="refund-progress-fill" id="progress-fill"></div>
                                    </div>

                                    <div class="refund-step pending" id="step-1">
                                        <div class="refund-step-circle"><i class="fas fa-paper-plane"></i></div>
                                        <span class="refund-step-label">Yêu cầu đã gửi</span>
                                        <span class="refund-step-time" id="step-1-time">—</span>
                                    </div>
                                    <div class="refund-step pending" id="step-2">
                                        <div class="refund-step-circle"><i class="fas fa-cog"></i></div>
                                        <span class="refund-step-label">Đang xử lý</span>
                                        <span class="refund-step-time" id="step-2-time">—</span>
                                    </div>
                                    <div class="refund-step pending" id="step-3">
                                        <div class="refund-step-circle"><i class="fas fa-check-double"></i></div>
                                        <span class="refund-step-label">Đã duyệt hoàn tiền</span>
                                        <span class="refund-step-time" id="step-3-time">—</span>
                                    </div>
                                    <div class="refund-step pending" id="step-4">
                                        <div class="refund-step-circle"><i class="fas fa-flag-checkered"></i></div>
                                        <span class="refund-step-label">Hoàn tất</span>
                                        <span class="refund-step-time" id="step-4-time">—</span>
                                    </div>
                                </div>

                                <div class="refund-summary status-submitted" id="refund-summary">
                                    <div class="refund-summary-icon submitted" id="refund-summary-icon">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <div class="refund-summary-text">
                                        <h4 id="refund-summary-title">Đang chờ xử lý</h4>
                                        <p id="refund-summary-desc">Yêu cầu hủy tour của bạn đã được gửi. Chúng tôi sẽ xử lý
                                            trong vòng 1–2 ngày làm việc.</p>
                                    </div>
                                </div>

                                <!-- Giả lập tiến trình -->
                                <div class="sim-controls">
                                    <div class="sim-controls-title">
                                        <i class="fas fa-flask"></i> Giả lập tiến trình hoàn tiền
                                    </div>
                                    <div class="sim-btn-group">
                                        <button class="sim-btn active" onclick="setRefundStep(1)">
                                            <i class="fas fa-paper-plane"></i> Đã gửi
                                        </button>
                                        <button class="sim-btn" onclick="setRefundStep(2)">
                                            <i class="fas fa-cog"></i> Đang xử lý
                                        </button>
                                        <button class="sim-btn" onclick="setRefundStep(3)">
                                            <i class="fas fa-check-double"></i> Đã duyệt
                                        </button>
                                        <button class="sim-btn" onclick="setRefundStep(4)">
                                            <i class="fas fa-flag-checkered"></i> Hoàn tất
                                        </button>
                                        <button class="sim-btn-auto" id="btn-auto-sim" onclick="runAutoSimulation()">
                                            <i class="fas fa-play"></i> Tự động chạy
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- ── Trạng thái không cho phép hủy ── -->
                        <div class="cr-card">
                            <div class="cr-card-header cr-header-orange">
                                <i class="fas fa-info-circle"></i>
                                <h2>Không Thể Hủy Tour</h2>
                            </div>
                            <div class="cr-card-body">
                                <div class="status-info-box info-orange">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>
                                        <h4>Đơn hàng không thể hủy</h4>
                                        <p>Đơn đặt tour này đang ở trạng thái
                                            <strong>
                                                <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                                            </strong> nên không
                                            thể gửi yêu cầu hủy. Vui lòng liên hệ bộ phận chăm sóc khách hàng để được hỗ
                                            trợ.
                                        </p>
                                    </div>
                                </div>
                                <a href="my_bookings.php" class="btn-back-link">
                                    <i class="fas fa-arrow-left"></i> Quay lại lịch sử đặt tour
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                </div><!-- end cr-right-col -->

            </div><!-- end cr-layout -->
        </div><!-- end container -->
    </div><!-- end cr-page -->

    <?php if ($justCancelled): ?>
        <!-- =============== SUCCESS MODAL (auto-show sau khi hủy thành công) =============== -->
        <div class="cr-success-overlay show" id="success-overlay">
            <div class="cr-success-content">
                <div class="cr-success-icon">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <h3>Yêu Cầu Hủy Đã Được Gửi!</h3>
                <p>Chúng tôi đã nhận được yêu cầu hủy tour của bạn. Vui lòng theo dõi tiến trình hoàn tiền bên dưới.</p>
                <button class="btn-back-orders" id="btn-close-modal" onclick="closeSuccessModal()">
                    <i class="fas fa-check"></i> Đã hiểu, theo dõi hoàn tiền
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script>
        // ============================================================
        //   CANCELLATION & REFUND – JAVASCRIPT
        // ============================================================

        // ---------- Form controls ----------
        const cancelDetailEl = document.getElementById('cancel-detail');
        const charCountEl = document.getElementById('char-count');
        const confirmCheck = document.getElementById('confirm-cancel');
        const btnSubmit = document.getElementById('btn-submit-cancel');

        if (cancelDetailEl) {
            cancelDetailEl.addEventListener('input', () => {
                charCountEl.textContent = cancelDetailEl.value.length;
            });
        }

        if (confirmCheck && btnSubmit) {
            confirmCheck.addEventListener('change', () => {
                btnSubmit.disabled = !confirmCheck.checked;
            });
        }

        // ---------- Success modal close ----------
        function closeSuccessModal() {
            const overlay = document.getElementById('success-overlay');
            if (overlay) overlay.classList.remove('show');

            const tracker = document.getElementById('refund-tracker-card');
            if (tracker) {
                tracker.scrollIntoView({ behavior: 'smooth', block: 'start' });
                setRefundStep(1);
            }
        }

        if (document.getElementById('success-overlay')) {
            document.getElementById('success-overlay').addEventListener('click', (e) => {
                if (e.target === e.currentTarget) closeSuccessModal();
            });
        }

        // ---------- Refund tracker (simulation) ----------
        const REFUND_AMOUNT = '<?= number_format((float) $booking['total_amount'], 0, ',', '.') ?> đ';

        const stepConfig = [
            {},
            {
                progressWidth: '0%', summaryClass: 'status-submitted',
                iconClass: 'submitted', icon: 'fa-hourglass-half',
                title: 'Yêu cầu đã gửi',
                desc: 'Yêu cầu hủy tour của bạn đã được gửi thành công. Chúng tôi sẽ bắt đầu xử lý trong vòng 1–2 ngày làm việc.',
                badgeClass: 'badge-refunding', badgeText: '<i class="fas fa-sync-alt"></i> Yêu cầu hủy'
            },
            {
                progressWidth: '33%', summaryClass: 'status-processing',
                iconClass: 'processing', icon: 'fa-cog',
                title: 'Đang xử lý yêu cầu',
                desc: 'Bộ phận chăm sóc khách hàng đang xem xét và tính toán số tiền hoàn lại theo chính sách.',
                badgeClass: 'badge-refunding', badgeText: '<i class="fas fa-sync-alt"></i> Đang xử lý'
            },
            {
                progressWidth: '66%', summaryClass: 'status-approved',
                iconClass: 'approved', icon: 'fa-check-double',
                title: 'Đã duyệt hoàn tiền',
                desc: 'Yêu cầu hoàn tiền đã được duyệt. ' + REFUND_AMOUNT + ' sẽ được hoàn vào tài khoản trong 3–5 ngày.',
                badgeClass: 'badge-refunding', badgeText: '<i class="fas fa-check-double"></i> Đã duyệt hoàn tiền'
            },
            {
                progressWidth: '100%', summaryClass: 'status-completed',
                iconClass: 'completed', icon: 'fa-flag-checkered',
                title: 'Hoàn tiền thành công!',
                desc: REFUND_AMOUNT + ' đã được hoàn vào tài khoản của bạn. Cảm ơn bạn đã sử dụng dịch vụ Du Lịch Việt!',
                badgeClass: 'badge-refunded', badgeText: '<i class="fas fa-check-circle"></i> Đã hoàn tiền'
            }
        ];

        let currentRefundStep = 0;
        let autoSimRunning = false;
        let autoSimTimer = null;

        function setRefundStep(step) {
            if (step < 1 || step > 4) return;
            currentRefundStep = step;

            const config = stepConfig[step];
            const now = new Date();

            for (let i = 1; i <= 4; i++) {
                const stepEl = document.getElementById(`step-${i}`);
                const timeEl = document.getElementById(`step-${i}-time`);
                if (!stepEl) continue;

                stepEl.classList.remove('completed', 'active', 'pending');

                if (i < step) {
                    stepEl.classList.add('completed');
                    const mins = (step - i) * 15 + Math.floor(Math.random() * 10);
                    const past = new Date(now.getTime() - mins * 60000);
                    timeEl.textContent = past.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                } else if (i === step) {
                    stepEl.classList.add('active');
                    timeEl.textContent = now.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
                } else {
                    stepEl.classList.add('pending');
                    timeEl.textContent = '—';
                }
            }

            const fill = document.getElementById('progress-fill');
            if (fill) {
                fill.classList.add('moving');
                fill.style.width = config.progressWidth;
                if (step === 4 || step === 1) setTimeout(() => fill.classList.remove('moving'), 1000);
            }

            const summary = document.getElementById('refund-summary');
            const summaryIcon = document.getElementById('refund-summary-icon');
            const sumTitle = document.getElementById('refund-summary-title');
            const sumDesc = document.getElementById('refund-summary-desc');
            const badge = document.getElementById('order-status-badge');

            if (summary) summary.className = `refund-summary ${config.summaryClass}`;
            if (summaryIcon) { summaryIcon.className = `refund-summary-icon ${config.iconClass}`; summaryIcon.innerHTML = `<i class="fas ${config.icon}"></i>`; }
            if (sumTitle) sumTitle.textContent = config.title;
            if (sumDesc) sumDesc.textContent = config.desc;
            if (badge) { badge.className = `order-status-badge ${config.badgeClass}`; badge.innerHTML = config.badgeText; }

            document.querySelectorAll('.sim-btn').forEach((btn, idx) => {
                btn.classList.toggle('active', idx === step - 1);
            });
        }

        function runAutoSimulation() {
            const btn = document.getElementById('btn-auto-sim');
            if (autoSimRunning) {
                autoSimRunning = false;
                clearInterval(autoSimTimer);
                autoSimTimer = null;
                if (btn) btn.innerHTML = '<i class="fas fa-play"></i> Tự động chạy';
                return;
            }

            autoSimRunning = true;
            if (btn) btn.innerHTML = '<i class="fas fa-stop"></i> Dừng lại';

            let simStep = 1;
            setRefundStep(simStep);

            autoSimTimer = setInterval(() => {
                simStep++;
                if (simStep > 4) {
                    autoSimRunning = false;
                    clearInterval(autoSimTimer);
                    autoSimTimer = null;
                    if (btn) btn.innerHTML = '<i class="fas fa-play"></i> Tự động chạy';
                    return;
                }
                setRefundStep(simStep);
            }, 2500);
        }

        // Init tracker nếu đang ở trang hoàn tiền
        if (document.getElementById('step-1')) {
            setRefundStep(1);
        }
    </script>
</body>

</html>