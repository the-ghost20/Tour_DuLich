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
    header('Location: login.php?redirect=' . urlencode('order-history.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ============================================
//   XỬ LÝ HÀNH ĐỘNG (POST)
// ============================================
$action_msg = '';
$action_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string) ($_POST['action'] ?? ''));
    $bookingId = (int) ($_POST['booking_id'] ?? 0);

    if ($action === 'cancel' && $bookingId > 0) {
        // Kiểm tra đơn thuộc về user hiện tại và trạng thái hợp lệ
        $stmtChk = $pdo->prepare(
            "SELECT id, status FROM bookings WHERE id = :id AND user_id = :uid LIMIT 1"
        );
        $stmtChk->execute(['id' => $bookingId, 'uid' => $userId]);
        $row = $stmtChk->fetch();

        if (!$row) {
            $action_msg = 'Không tìm thấy đơn đặt tour.';
            $action_type = 'error';
        } elseif (in_array((string) $row['status'], ['đã hủy', 'yêu cầu hủy'], true)) {
            $action_msg = 'Đơn này đã được hủy hoặc đang chờ xử lý hủy.';
            $action_type = 'error';
        } else {
            try {
                $stmtCancel = $pdo->prepare(
                    "UPDATE bookings SET status = 'yêu cầu hủy' WHERE id = :id AND user_id = :uid"
                );
                $stmtCancel->execute(['id' => $bookingId, 'uid' => $userId]);
                // PRG – redirect to avoid re-submit on refresh
                header('Location: order-history.php?msg=' . urlencode('Yêu cầu hủy đơn #' . $bookingId . ' đã được ghi nhận. Chúng tôi sẽ liên hệ bạn sớm.') . '&type=success');
                exit;
            } catch (\Throwable $e) {
                error_log('Order cancel error: ' . $e->getMessage());
                $action_msg = 'Có lỗi xảy ra. Vui lòng thử lại.';
                $action_type = 'error';
            }
        }
    } else {
        header('Location: order-history.php');
        exit;
    }
}

// Flash message từ redirect
if ($action_msg === '' && isset($_GET['msg'])) {
    $action_msg = htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8');
    $action_type = (string) ($_GET['type'] ?? 'info');
}

// Flash thanh toán thành công từ payment.php
$paid_order = '';
if (isset($_GET['payment']) && $_GET['payment'] === 'success' && isset($_GET['order'])) {
    $paid_order = htmlspecialchars((string) $_GET['order'], ENT_QUOTES, 'UTF-8');
}

// ============================================
//   LẤY DỮ LIỆU ĐẶT TOUR TỪ DATABASE
// ============================================
$filter_status = trim((string) ($_GET['status'] ?? 'all'));
$search_query = trim((string) ($_GET['q'] ?? ''));
$allowed_statuses = ['all', 'chờ duyệt', 'đã xác nhận', 'đã thanh toán', 'yêu cầu hủy', 'đã hủy'];
if (!in_array($filter_status, $allowed_statuses, true))
    $filter_status = 'all';

// Xây dựng câu query có điều kiện lọc
$whereStatus = $filter_status !== 'all' ? "AND b.status = :status" : '';
$whereSearch = $search_query !== '' ? "AND (t.tour_name LIKE :q OR CAST(b.id AS CHAR) LIKE :q)" : '';

$sql = "SELECT b.id, b.adults, b.children, b.total_amount, b.status,
               b.cancel_reason, b.created_at,
               t.tour_name, t.destination, t.duration, t.image_url
        FROM bookings b
        INNER JOIN tours t ON t.id = b.tour_id
        WHERE b.user_id = :user_id
        {$whereStatus}
        {$whereSearch}
        ORDER BY b.created_at DESC";

$params = ['user_id' => $userId];
if ($filter_status !== 'all')
    $params['status'] = $filter_status;
if ($search_query !== '')
    $params['q'] = '%' . $search_query . '%';

$all_bookings = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $all_bookings = $stmt->fetchAll();
} catch (\Throwable $e) {
    error_log('Order history query error: ' . $e->getMessage());
}

// ============================================
//   THỐNG KÊ (lấy toàn bộ, không lọc)
// ============================================
$stat_total = 0;
$stat_confirmed = 0;
$stat_cancelled = 0;
try {
    $stmtS = $pdo->prepare(
        "SELECT status, COUNT(*) as cnt FROM bookings WHERE user_id = :uid GROUP BY status"
    );
    $stmtS->execute(['uid' => $userId]);
    foreach ($stmtS->fetchAll() as $row) {
        $stat_total += (int) $row['cnt'];
        if (in_array($row['status'], ['đã xác nhận', 'đã thanh toán'], true)) {
            $stat_confirmed += (int) $row['cnt'];
        }
        if (in_array($row['status'], ['đã hủy', 'yêu cầu hủy'], true)) {
            $stat_cancelled += (int) $row['cnt'];
        }
    }
} catch (\Throwable $e) {
    // bỏ qua
}

// ============================================
//   STATUS MAP
// ============================================
$status_map = [
    'chờ duyệt' => ['label' => 'Chờ duyệt', 'cls' => 'badge-pending', 'icon' => 'fa-clock'],
    'đã xác nhận' => ['label' => 'Đã xác nhận', 'cls' => 'badge-confirmed', 'icon' => 'fa-check-circle'],
    'đã thanh toán' => ['label' => 'Đã thanh toán', 'cls' => 'badge-paid', 'icon' => 'fa-money-bill-wave'],
    'yêu cầu hủy' => ['label' => 'Yêu cầu hủy', 'cls' => 'badge-pending', 'icon' => 'fa-hourglass-half'],
    'đã hủy' => ['label' => 'Đã hủy', 'cls' => 'badge-cancelled', 'icon' => 'fa-times-circle'],
];

// ============================================
//   HELPER FUNCTIONS
// ============================================
function fmtVND(float $n): string
{
    return number_format($n, 0, ',', '.') . ' đ';
}
function fmtDate(string $iso): string
{
    if (!$iso)
        return '—';
    return date('d/m/Y', strtotime($iso));
}
function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function buildUrl(array $params = []): string
{
    $base = $_GET;
    foreach ($params as $k => $v) {
        if ($v === null)
            unset($base[$k]);
        else
            $base[$k] = $v;
    }
    // Loại bỏ các key flash ở URL
    unset($base['msg'], $base['type'], $base['payment'], $base['order']);
    $qs = http_build_query($base);
    return 'order-history.php' . ($qs ? '?' . $qs : '');
}
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lịch Sử Đặt Tour - Du Lịch Việt</title>
    <meta name="description" content="Xem lại lịch sử các đơn hàng tour du lịch bạn đã đặt tại Du Lịch Việt." />
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />
    <style>
        /* =============================================
     ORDER HISTORY PAGE – STYLES
     ============================================= */

        /* ---------- Hero ---------- */
        .oh-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 3rem 0 2rem;
            position: relative;
            overflow: hidden;
        }

        .oh-hero::before {
            content: '';
            position: absolute;
            inset: -50%;
            width: 200%;
            height: 200%;
            background:
                radial-gradient(circle at 30% 40%, rgba(0, 188, 212, .07) 0%, transparent 55%),
                radial-gradient(circle at 75% 70%, rgba(33, 150, 243, .06) 0%, transparent 50%);
            animation: heroPulse 10s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes heroPulse {

            0%,
            100% {
                transform: scale(1) rotate(0deg);
            }

            50% {
                transform: scale(1.04) rotate(.5deg);
            }
        }

        .oh-hero-inner {
            position: relative;
            z-index: 2;
            text-align: center;
            color: #fff;
        }

        .oh-breadcrumb {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            font-size: .9rem;
            color: rgba(255, 255, 255, .55);
            margin-bottom: 1.5rem;
        }

        .oh-breadcrumb a {
            color: rgba(255, 255, 255, .55);
            transition: color .3s;
            text-decoration: none;
        }

        .oh-breadcrumb a:hover {
            color: var(--accent-color);
        }

        .oh-breadcrumb i {
            font-size: .7rem;
        }

        .oh-breadcrumb .current {
            color: var(--accent-color);
            font-weight: 600;
        }

        .oh-hero-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: .5rem;
            background: linear-gradient(135deg, #fff 0%, #b2ebf2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .oh-hero-sub {
            color: rgba(255, 255, 255, .65);
            font-size: 1rem;
        }

        /* Stats bar */
        .oh-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.8rem;
            flex-wrap: wrap;
        }

        .oh-stat {
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            border-radius: 12px;
            padding: .8rem 1.6rem;
            display: flex;
            align-items: center;
            gap: .7rem;
            backdrop-filter: blur(6px);
            transition: transform .3s, background .3s;
        }

        .oh-stat:hover {
            transform: translateY(-3px);
            background: rgba(255, 255, 255, .12);
        }

        .oh-stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }

        .oh-stat-icon.total {
            background: linear-gradient(135deg, #2196f3, #00bcd4);
            color: #fff;
        }

        .oh-stat-icon.ok {
            background: linear-gradient(135deg, #4caf50, #81c784);
            color: #fff;
        }

        .oh-stat-icon.cancel {
            background: linear-gradient(135deg, #f44336, #ef5350);
            color: #fff;
        }

        .oh-stat-info strong {
            display: block;
            font-size: 1.2rem;
            color: #fff;
            line-height: 1;
        }

        .oh-stat-info span {
            font-size: .75rem;
            color: rgba(255, 255, 255, .5);
        }

        /* Page body */
        .oh-page {
            background: #f0f4f8;
            min-height: 100vh;
            padding: 2.5rem 0 4rem;
        }

        /* Toolbar */
        .oh-toolbar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .oh-search {
            flex: 1;
            min-width: 200px;
            position: relative;
        }

        .oh-search input {
            width: 100%;
            padding: .75rem 1rem .75rem 2.6rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: .92rem;
            font-family: inherit;
            outline: none;
            transition: border-color .3s;
            background: #fff;
        }

        .oh-search input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(33, 150, 243, .1);
        }

        .oh-search i {
            position: absolute;
            left: .9rem;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: .9rem;
        }

        .oh-filter-btn {
            padding: .7rem 1.2rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: #fff;
            font-family: inherit;
            font-size: .88rem;
            font-weight: 600;
            color: #555;
            cursor: pointer;
            transition: all .3s;
            display: flex;
            align-items: center;
            gap: .4rem;
            white-space: nowrap;
            text-decoration: none;
        }

        .oh-filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .oh-filter-btn.active {
            border-color: var(--primary-color);
            background: #e3f2fd;
            color: var(--primary-color);
        }

        .oh-filter-btn i {
            font-size: .8rem;
        }

        /* View toggles */
        .oh-view-toggle {
            display: flex;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .oh-view-btn {
            padding: .65rem .9rem;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #aaa;
            font-size: .95rem;
            transition: all .25s;
        }

        .oh-view-btn.active {
            background: var(--primary-color);
            color: #fff;
        }

        .oh-view-btn:hover:not(.active) {
            color: var(--primary-color);
        }

        /* TABLE VIEW */
        .oh-table-wrap {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
        }

        .oh-table {
            width: 100%;
            border-collapse: collapse;
        }

        .oh-table thead {
            background: linear-gradient(135deg, #0f3460 0%, #16213e 100%);
        }

        .oh-table thead th {
            padding: 1rem 1.2rem;
            text-align: left;
            font-size: .82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: rgba(255, 255, 255, .85);
            white-space: nowrap;
        }

        .oh-table thead th i {
            margin-right: .35rem;
            color: var(--accent-color);
            font-size: .75rem;
        }

        .oh-table tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background .25s;
        }

        .oh-table tbody tr:last-child {
            border-bottom: none;
        }

        .oh-table tbody tr:hover {
            background: #f8fbff;
        }

        .oh-table tbody td {
            padding: 1rem 1.2rem;
            font-size: .9rem;
            color: #444;
            vertical-align: middle;
        }

        .order-code {
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Courier New', monospace;
            font-size: .88rem;
            letter-spacing: .3px;
        }

        .tour-name-cell {
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .tour-thumb {
            width: 56px;
            height: 42px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .12);
        }

        .tour-name-text {
            font-weight: 600;
            color: #1a1a2e;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price-cell {
            font-weight: 700;
            color: var(--secondary-color);
            white-space: nowrap;
        }

        .date-cell {
            white-space: nowrap;
            color: #555;
        }

        .date-cell i {
            color: var(--primary-color);
            margin-right: .3rem;
            font-size: .8rem;
        }

        /* STATUS BADGES */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .35rem .85rem;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .3px;
            white-space: nowrap;
        }

        .status-badge i {
            font-size: .7rem;
        }

        .badge-pending {
            background: #fff8e1;
            color: #f57f17;
            border: 1px solid #ffe082;
        }

        .badge-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .badge-paid {
            background: #e3f2fd;
            color: #1255b5;
            border: 1px solid #90caf9;
        }

        .badge-cancelled {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        /* Action buttons */
        .action-cell {
            display: flex;
            gap: .4rem;
        }

        .btn-action {
            width: 34px;
            height: 34px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .82rem;
            transition: all .25s;
        }

        .btn-view {
            background: #e3f2fd;
            color: var(--primary-color);
        }

        .btn-view:hover {
            background: var(--primary-color);
            color: #fff;
            transform: translateY(-2px);
        }

        .btn-cancel-order {
            background: #ffebee;
            color: #e53935;
        }

        .btn-cancel-order:hover {
            background: #e53935;
            color: #fff;
            transform: translateY(-2px);
        }

        .btn-repay {
            background: #fff8e1;
            color: #f57f17;
        }

        .btn-repay:hover {
            background: #f57f17;
            color: #fff;
            transform: translateY(-2px);
        }

        /* CARDS VIEW */
        .oh-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 1.5rem;
        }

        .oh-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
            transition: all .3s ease;
            border: 2px solid transparent;
            display: flex;
            flex-direction: column;
        }

        .oh-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 36px rgba(0, 0, 0, .12);
            border-color: #e3f2fd;
        }

        .oh-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.3rem;
            background: #f8f9fa;
            border-bottom: 1px solid #f0f0f0;
        }

        .oh-card-code {
            font-weight: 700;
            color: var(--primary-color);
            font-family: 'Courier New', monospace;
            font-size: .88rem;
        }

        .oh-card-body {
            padding: 1.3rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .oh-card-tour {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .oh-card-thumb {
            width: 80px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .15);
        }

        .oh-card-tour-info h4 {
            font-size: .95rem;
            font-weight: 700;
            color: #1a1a2e;
            line-height: 1.35;
            margin: 0 0 .3rem;
        }

        .oh-card-tour-info .card-meta {
            font-size: .82rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: .3rem;
            margin-top: .25rem;
        }

        .oh-card-tour-info .card-meta i {
            color: var(--primary-color);
            font-size: .75rem;
        }

        .oh-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px dashed #e8e8e8;
        }

        .oh-card-price {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--secondary-color);
        }

        .oh-card-actions {
            display: flex;
            gap: .4rem;
        }

        /* Empty state */
        .oh-empty {
            text-align: center;
            padding: 4rem 2rem;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .06);
        }

        .oh-empty i {
            font-size: 3.5rem;
            color: #ccc;
            margin-bottom: 1rem;
            display: block;
        }

        .oh-empty h3 {
            font-size: 1.2rem;
            color: #888;
            margin-bottom: .5rem;
        }

        .oh-empty p {
            color: #aaa;
            font-size: .92rem;
            margin-bottom: 1.5rem;
        }

        .oh-empty .btn-browse {
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

        .oh-empty .btn-browse:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, .4);
        }

        /* Alert bar */
        .oh-alert {
            padding: .9rem 1.4rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: .92rem;
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .oh-alert.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .oh-alert.info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .oh-alert.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        /* Pagination */
        .oh-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: .5rem;
            margin-top: 2rem;
        }

        .oh-page-btn {
            min-width: 38px;
            height: 38px;
            border: 2px solid #e0e0e0;
            background: #fff;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: .88rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .25s;
            color: #555;
            font-family: inherit;
            text-decoration: none;
        }

        .oh-page-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .oh-page-btn.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .oh-page-btn.disabled {
            opacity: .4;
            pointer-events: none;
        }

        @keyframes fadeRow {
            from {
                opacity: 0;
                transform: translateY(12px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .oh-hero-title {
                font-size: 1.8rem;
            }

            .oh-stats {
                gap: 1rem;
            }

            .oh-table-wrap {
                overflow-x: auto;
            }

            .oh-table {
                min-width: 700px;
            }
        }

        @media (max-width: 600px) {
            .oh-toolbar {
                flex-direction: column;
            }

            .oh-search {
                min-width: 0;
                width: 100%;
            }

            .oh-cards-grid {
                grid-template-columns: 1fr;
            }

            .oh-stat {
                padding: .6rem 1rem;
            }

            .oh-stat-info strong {
                font-size: 1rem;
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
    <section class="oh-hero">
        <div class="container">
            <div class="oh-hero-inner">
                <nav class="oh-breadcrumb" aria-label="Breadcrumb">
                    <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <i class="fas fa-chevron-right"></i>
                    <span class="current">Lịch sử đặt tour</span>
                </nav>
                <h1 class="oh-hero-title">Lịch Sử Đặt Tour</h1>
                <p class="oh-hero-sub">Theo dõi và quản lý tất cả đơn đặt tour của bạn</p>

                <!-- Stats (server-rendered from DB) -->
                <div class="oh-stats">
                    <div class="oh-stat">
                        <div class="oh-stat-icon total"><i class="fas fa-clipboard-list"></i></div>
                        <div class="oh-stat-info">
                            <strong>
                                <?= $stat_total ?>
                            </strong>
                            <span>Tổng đơn hàng</span>
                        </div>
                    </div>
                    <div class="oh-stat">
                        <div class="oh-stat-icon ok"><i class="fas fa-check-circle"></i></div>
                        <div class="oh-stat-info">
                            <strong>
                                <?= $stat_confirmed ?>
                            </strong>
                            <span>Đã xác nhận</span>
                        </div>
                    </div>
                    <div class="oh-stat">
                        <div class="oh-stat-icon cancel"><i class="fas fa-times-circle"></i></div>
                        <div class="oh-stat-info">
                            <strong>
                                <?= $stat_cancelled ?>
                            </strong>
                            <span>Đã hủy / Yêu cầu hủy</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- =============== MAIN CONTENT =============== -->
    <div class="oh-page">
        <div class="container">

            <!-- Payment success notification -->
            <?php if ($paid_order): ?>
                <div class="oh-alert success">
                    <i class="fas fa-check-circle"></i>
                    Đơn hàng <strong>
                        <?= $paid_order ?>
                    </strong> đã được thanh toán thành công! Cảm ơn bạn đã đặt tour.
                </div>
            <?php endif; ?>

            <!-- Action notification -->
            <?php if ($action_msg): ?>
                <div class="oh-alert <?= esc($action_type) ?>">
                    <i
                        class="fas <?= $action_type === 'success' ? 'fa-check-circle' : ($action_type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') ?>"></i>
                    <?= $action_msg ?>
                </div>
            <?php endif; ?>

            <!-- Toolbar (search + filters as GET links) -->
            <form method="GET" action="order-history.php" class="oh-toolbar">
                <div class="oh-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="oh-search-input" placeholder="Tìm theo mã đơn hoặc tên tour..."
                        value="<?= esc($search_query) ?>" />
                    <?php if ($filter_status !== 'all'): ?>
                        <input type="hidden" name="status" value="<?= esc($filter_status) ?>" />
                    <?php endif; ?>
                </div>
                <button type="submit" class="oh-filter-btn">
                    <i class="fas fa-search"></i> Tìm
                </button>
            </form>

            <div class="oh-toolbar" style="margin-top:-.5rem;">
                <!-- Status filters as links -->
                <a href="<?= buildUrl(['status' => null, 'q' => $search_query ?: null]) ?>"
                    class="oh-filter-btn <?= $filter_status === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-list"></i> Tất cả
                </a>
                <a href="<?= buildUrl(['status' => 'chờ duyệt', 'q' => $search_query ?: null]) ?>"
                    class="oh-filter-btn <?= $filter_status === 'chờ duyệt' ? 'active' : '' ?>">
                    <i class="fas fa-clock"></i> Chờ duyệt
                </a>
                <a href="<?= buildUrl(['status' => 'đã xác nhận', 'q' => $search_query ?: null]) ?>"
                    class="oh-filter-btn <?= $filter_status === 'đã xác nhận' ? 'active' : '' ?>">
                    <i class="fas fa-check"></i> Đã xác nhận
                </a>
                <a href="<?= buildUrl(['status' => 'đã thanh toán', 'q' => $search_query ?: null]) ?>"
                    class="oh-filter-btn <?= $filter_status === 'đã thanh toán' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave"></i> Đã thanh toán
                </a>
                <a href="<?= buildUrl(['status' => 'đã hủy', 'q' => $search_query ?: null]) ?>"
                    class="oh-filter-btn <?= $filter_status === 'đã hủy' ? 'active' : '' ?>">
                    <i class="fas fa-ban"></i> Đã hủy
                </a>

                <!-- View toggle (JS-driven) -->
                <div class="oh-view-toggle" style="margin-left:auto;">
                    <button class="oh-view-btn active" id="view-table-btn" onclick="setView('table')"
                        title="Xem dạng bảng">
                        <i class="fas fa-table-list"></i>
                    </button>
                    <button class="oh-view-btn" id="view-card-btn" onclick="setView('cards')" title="Xem dạng thẻ">
                        <i class="fas fa-grip"></i>
                    </button>
                </div>
            </div>

            <?php if (empty($all_bookings)): ?>
                <!-- Empty state -->
                <div class="oh-empty">
                    <i class="fas fa-inbox"></i>
                    <h3>Không tìm thấy đơn hàng nào</h3>
                    <p>
                        <?= $search_query || $filter_status !== 'all'
                            ? 'Hãy thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.'
                            : 'Bạn chưa có đơn đặt tour nào. Hãy khám phá và đặt tour ngay!' ?>
                    </p>
                    <a href="tours.php" class="btn-browse"><i class="fas fa-compass"></i> Khám phá tour</a>
                </div>

            <?php else: ?>

                <!-- TABLE VIEW (server-rendered HTML, shown by default) -->
                <div id="table-view">
                    <div class="oh-table-wrap">
                        <table class="oh-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag"></i> Mã đơn</th>
                                    <th><i class="fas fa-map-marked-alt"></i> Tên Tour</th>
                                    <th><i class="fas fa-calendar-alt"></i> Ngày đặt</th>
                                    <th><i class="fas fa-users"></i> Hành khách</th>
                                    <th><i class="fas fa-coins"></i> Tổng tiền</th>
                                    <th><i class="fas fa-info-circle"></i> Trạng thái</th>
                                    <th><i class="fas fa-cog"></i> Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_bookings as $i => $o):
                                    $bId = (int) $o['id'];
                                    $status = (string) $o['status'];
                                    $s = $status_map[$status] ?? $status_map['chờ duyệt'];
                                    $imgSrc = !empty($o['image_url'])
                                        ? esc($o['image_url'])
                                        : 'https://images.unsplash.com/photo-1537225228614-b4fad34a0b60?w=200&h=150&fit=crop';
                                    $pax = (int) $o['adults'] . ' NL' . ((int) $o['children'] > 0 ? ', ' . (int) $o['children'] . ' TE' : '');
                                    ?>
                                    <tr style="animation: fadeRow .4s ease <?= $i * 0.06 ?>s both;">
                                        <td><span class="order-code">#
                                                <?= $bId ?>
                                            </span></td>
                                        <td>
                                            <div class="tour-name-cell">
                                                <img src="<?= $imgSrc ?>" alt="<?= esc($o['tour_name']) ?>"
                                                    class="tour-thumb" />
                                                <span class="tour-name-text">
                                                    <?= esc($o['tour_name']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="date-cell">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= fmtDate($o['created_at']) ?>
                                        </td>
                                        <td>
                                            <?= esc($pax) ?>
                                        </td>
                                        <td class="price-cell">
                                            <?= fmtVND((float) $o['total_amount']) ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= esc($s['cls']) ?>">
                                                <i class="fas <?= esc($s['icon']) ?>"></i>
                                                <?= esc($s['label']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-cell">
                                                <button class="btn-action btn-view" title="Xem chi tiết" onclick="viewOrder(<?= json_encode([
                                                    'id' => $bId,
                                                    'tourName' => $o['tour_name'],
                                                    'destination' => $o['destination'],
                                                    'duration' => $o['duration'],
                                                    'createdAt' => $o['created_at'],
                                                    'adults' => (int) $o['adults'],
                                                    'children' => (int) $o['children'],
                                                    'totalAmount' => (float) $o['total_amount'],
                                                    'status' => $status,
                                                    'cancelReason' => $o['cancel_reason'] ?? '',
                                                ]) ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if (in_array($status, ['chờ duyệt'], true)): ?>
                                                    <a href="payment.php" class="btn-action btn-repay" title="Thanh toán"
                                                        style="display:flex;">
                                                        <i class="fas fa-credit-card"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (in_array($status, ['chờ duyệt', 'đã xác nhận'], true)): ?>
                                                    <form method="POST" action="order-history.php" style="display:inline;"
                                                        onsubmit="return confirm('Bạn có chắc muốn gửi yêu cầu hủy đơn #<?= $bId ?>?')">
                                                        <input type="hidden" name="action" value="cancel" />
                                                        <input type="hidden" name="booking_id" value="<?= $bId ?>" />
                                                        <button type="submit" class="btn-action btn-cancel-order" title="Hủy đơn">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CARDS VIEW (same data, hidden by default) -->
                <div id="cards-view" style="display:none;">
                    <div class="oh-cards-grid">
                        <?php foreach ($all_bookings as $i => $o):
                            $bId = (int) $o['id'];
                            $status = (string) $o['status'];
                            $s = $status_map[$status] ?? $status_map['chờ duyệt'];
                            $imgSrc = !empty($o['image_url'])
                                ? esc($o['image_url'])
                                : 'https://images.unsplash.com/photo-1537225228614-b4fad34a0b60?w=200&h=150&fit=crop';
                            ?>
                            <div class="oh-card" style="animation: fadeRow .4s ease <?= $i * 0.08 ?>s both;">
                                <div class="oh-card-top">
                                    <span class="oh-card-code">#
                                        <?= $bId ?>
                                    </span>
                                    <span class="status-badge <?= esc($s['cls']) ?>">
                                        <i class="fas <?= esc($s['icon']) ?>"></i>
                                        <?= esc($s['label']) ?>
                                    </span>
                                </div>
                                <div class="oh-card-body">
                                    <div class="oh-card-tour">
                                        <img src="<?= $imgSrc ?>" alt="<?= esc($o['tour_name']) ?>" class="oh-card-thumb" />
                                        <div class="oh-card-tour-info">
                                            <h4>
                                                <?= esc($o['tour_name']) ?>
                                            </h4>
                                            <div class="card-meta">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?= esc($o['destination']) ?>
                                            </div>
                                            <div class="card-meta">
                                                <i class="fas fa-clock"></i>
                                                <?= esc($o['duration']) ?>
                                            </div>
                                            <div class="card-meta">
                                                <i class="fas fa-calendar-day"></i>
                                                <?= fmtDate($o['created_at']) ?>
                                            </div>
                                            <div class="card-meta">
                                                <i class="fas fa-users"></i>
                                                <?= (int) $o['adults'] ?> người
                                                lớn
                                                <?= (int) $o['children'] > 0 ? ', ' . (int) $o['children'] . ' trẻ em' : '' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($o['cancel_reason']) && in_array($status, ['yêu cầu hủy', 'đã hủy'], true)): ?>
                                        <div
                                            style="background:#fff5f5;border-left:3px solid #f87171;border-radius:8px;padding:8px 12px;font-size:.83rem;color:#7a1a1a;margin-top:6px;font-style:italic;">
                                            <i class="fas fa-comment-slash"></i> Lý do hủy:
                                            <?= esc($o['cancel_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="oh-card-footer">
                                        <span class="oh-card-price">
                                            <?= fmtVND((float) $o['total_amount']) ?>
                                        </span>
                                        <div class="oh-card-actions">
                                            <button class="btn-action btn-view" title="Xem chi tiết" onclick="viewOrder(<?= json_encode([
                                                'id' => $bId,
                                                'tourName' => $o['tour_name'],
                                                'destination' => $o['destination'],
                                                'duration' => $o['duration'],
                                                'createdAt' => $o['created_at'],
                                                'adults' => (int) $o['adults'],
                                                'children' => (int) $o['children'],
                                                'totalAmount' => (float) $o['total_amount'],
                                                'status' => $status,
                                                'cancelReason' => $o['cancel_reason'] ?? '',
                                            ]) ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if (in_array($status, ['chờ duyệt'], true)): ?>
                                                <a href="payment.php" class="btn-action btn-repay" title="Thanh toán"
                                                    style="display:flex;">
                                                    <i class="fas fa-credit-card"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (in_array($status, ['chờ duyệt', 'đã xác nhận'], true)): ?>
                                                <form method="POST" action="order-history.php" style="display:inline;"
                                                    onsubmit="return confirm('Bạn có chắc muốn gửi yêu cầu hủy đơn #<?= $bId ?>?')">
                                                    <input type="hidden" name="action" value="cancel" />
                                                    <input type="hidden" name="booking_id" value="<?= $bId ?>" />
                                                    <button type="submit" class="btn-action btn-cancel-order" title="Hủy đơn">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Result count -->
                <p style="text-align:center;color:#aaa;font-size:.85rem;margin-top:1.2rem;">
                    Hiển thị
                    <?= count($all_bookings) ?> /
                    <?= $stat_total ?> đơn hàng
                </p>
            <?php endif; ?>

        </div>
    </div>

    <!-- =============== ORDER DETAIL MODAL =============== -->
    <div id="detail-overlay"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
        <div
            style="background:#fff;border-radius:20px;max-width:480px;width:92%;padding:2rem;box-shadow:0 20px 60px rgba(0,0,0,.25);animation:fadeRow .35s ease;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.2rem;">
                <h2 style="font-size:1.1rem;color:#1a1a2e;margin:0;"><i class="fas fa-receipt"
                        style="color:var(--primary-color);margin-right:.5rem;"></i> Chi tiết đơn hàng</h2>
                <button onclick="closeDetail()"
                    style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:#aaa;"><i
                        class="fas fa-times"></i></button>
            </div>
            <div id="detail-content"></div>
            <div style="margin-top:1.2rem;text-align:center;">
                <a href="tours.php"
                    style="display:inline-flex;align-items:center;gap:.5rem;padding:.7rem 1.6rem;background:linear-gradient(135deg,#2196f3,#00bcd4);color:#fff;border-radius:25px;font-weight:700;text-decoration:none;font-size:.9rem;box-shadow:0 4px 12px rgba(33,150,243,.3);transition:all .3s;"
                    onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                    <i class="fas fa-compass"></i> Đặt thêm tour
                </a>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>

    <script>
        // =============================================
        //  VIEW TOGGLE (Table ↔ Cards)
        // =============================================
        function setView(view) {
            const isTable = view === 'table';
            const tv = document.getElementById('table-view');
            const cv = document.getElementById('cards-view');
            if (tv) tv.style.display = isTable ? 'block' : 'none';
            if (cv) cv.style.display = isTable ? 'none' : 'block';
            document.getElementById('view-table-btn').classList.toggle('active', isTable);
            document.getElementById('view-card-btn').classList.toggle('active', !isTable);
            localStorage.setItem('oh-view', view);
        }

        // Restore preferred view
        document.addEventListener('DOMContentLoaded', () => {
            const saved = localStorage.getItem('oh-view');
            if (saved === 'cards') setView('cards');
        });

        // =============================================
        //  ORDER DETAIL MODAL
        // =============================================
        const STATUS_LABEL = {
            'chờ duyệt': { label: 'Chờ duyệt', color: '#f57f17' },
            'đã xác nhận': { label: 'Đã xác nhận', color: '#2e7d32' },
            'đã thanh toán': { label: 'Đã thanh toán', color: '#1255b5' },
            'yêu cầu hủy': { label: 'Yêu cầu hủy', color: '#e65100' },
            'đã hủy': { label: 'Đã hủy', color: '#c62828' },
        };

        function fmtC(n) {
            return n.toLocaleString('vi-VN') + ' đ';
        }

        function viewOrder(o) {
            const s = STATUS_LABEL[o.status] || { label: o.status, color: '#555' };
            const rows = [
                ['Mã đơn hàng', `<span style="font-family:monospace;font-weight:700;color:var(--primary-color)">#${o.id}</span>`],
                ['Tên tour', o.tourName],
                ['Điểm đến', o.destination || '—'],
                ['Thời gian', o.duration || '—'],
                ['Ngày đặt', o.createdAt ? new Date(o.createdAt).toLocaleDateString('vi-VN') : '—'],
                ['Hành khách', o.adults + ' người lớn' + (o.children > 0 ? ', ' + o.children + ' trẻ em' : '')],
                ['Tổng tiền', `<strong style="color:var(--secondary-color)">${fmtC(o.totalAmount)}</strong>`],
                ['Trạng thái', `<span style="font-weight:700;color:${s.color}">${s.label}</span>`],
            ];
            if (o.cancelReason) {
                rows.push(['Lý do hủy', `<em style="color:#c62828">${o.cancelReason}</em>`]);
            }

            const html = rows.map(([label, val]) => `
        <div style="display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f0f0f0;font-size:.9rem;">
          <span style="color:#888;">${label}</span>
          <span style="text-align:right;max-width:60%;">${val}</span>
        </div>`).join('');

            document.getElementById('detail-content').innerHTML = html;
            const ov = document.getElementById('detail-overlay');
            ov.style.display = 'flex';
        }

        function closeDetail() {
            document.getElementById('detail-overlay').style.display = 'none';
        }

        // Close on outside click
        document.getElementById('detail-overlay').addEventListener('click', function (e) {
            if (e.target === this) closeDetail();
        });

        // Close on Escape
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDetail();
        });
    </script>
</body>

</html>