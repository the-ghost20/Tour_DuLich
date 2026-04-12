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
  header('Location: login.php?redirect=' . urlencode('payment.php'));
  exit;
}

$userId = (int) $_SESSION['user_id'];

// ============================================
//   LẤY DỮ LIỆU ĐẶT TOUR TỪ SESSION
// ============================================
// Nếu session không tồn tại → dùng dữ liệu mẫu (demo)
$booking = $_SESSION['booking'] ?? [
  'tour_name' => 'Phú Quốc Paradise – Đảo Ngọc 4 Ngày 3 Đêm',
  'adults' => 2,
  'children' => 1,
  'adult_price' => 3500000,
  'child_price' => 1750000,
  'adult_total' => 7000000,
  'child_total' => 1750000,
  'grand_total' => 8750000,
  'full_name' => '',
  'phone' => '',
  'email' => '',
  'departure_date' => '',
  'tour_slot' => '',
  'special_req' => '',
  'booked_at' => date('Y-m-d H:i:s'),
];

// Tạo mã đơn hàng duy nhất (dựa theo thời gian)
$order_id = $booking['order_id'] ?? ('DLV' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 5)));
$_SESSION['booking']['order_id'] = $order_id;

// ============================================
//   VOUCHER HỢP LỆ (server-side)
// ============================================
$valid_vouchers = [
  'GIAM10' => ['percent' => 10, 'flat' => 0, 'label' => 'Giảm 10% tổng đơn'],
  'GIAM20' => ['percent' => 20, 'flat' => 0, 'label' => 'Giảm 20% tổng đơn'],
  'GIAM50K' => ['percent' => 0, 'flat' => 50000, 'label' => 'Giảm 50.000đ'],
];

// ============================================
//   XỬ LÝ KHI SUBMIT THANH TOÁN (POST)
// ============================================
$pay_errors = [];
$pay_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $method = trim($_POST['payment_method'] ?? '');
  $voucher_code = strtoupper(trim($_POST['voucher_code'] ?? ''));
  $discount_pct = (int) ($_POST['discount_percent'] ?? 0);
  $discount_amt = (int) ($_POST['discount_amount'] ?? 0);
  $final_total = (int) ($_POST['final_total'] ?? $booking['grand_total']);

  // --- Validate ---
  $allowed_methods = ['credit', 'bank', 'momo', 'vnpay'];
  if (!in_array($method, $allowed_methods)) {
    $pay_errors[] = 'Vui lòng chọn phương thức thanh toán.';
  }

  // Validate thêm nếu là thẻ tín dụng
  if ($method === 'credit') {
    $cc_number = preg_replace('/\s+/', '', $_POST['cc_number'] ?? '');
    $cc_name = trim($_POST['cc_name'] ?? '');
    $cc_expiry = trim($_POST['cc_expiry'] ?? '');
    $cc_cvv = trim($_POST['cc_cvv'] ?? '');

    if (strlen($cc_number) < 16 || !ctype_digit($cc_number)) {
      $pay_errors[] = 'Số thẻ tín dụng không hợp lệ (cần 16 chữ số).';
    }
    if (empty($cc_name)) {
      $pay_errors[] = 'Vui lòng nhập tên chủ thẻ.';
    }
    if (!preg_match('/^\d{2}\/\d{2}$/', $cc_expiry)) {
      $pay_errors[] = 'Ngày hết hạn không hợp lệ (định dạng MM/YY).';
    }
    if (strlen($cc_cvv) < 3) {
      $pay_errors[] = 'Mã CVV không hợp lệ.';
    }
  }

  // Kiểm tra voucher nếu có
  if (!empty($voucher_code) && !isset($valid_vouchers[$voucher_code])) {
    $pay_errors[] = 'Mã giảm giá "' . htmlspecialchars($voucher_code, ENT_QUOTES, 'UTF-8') . '" không hợp lệ.';
  }

  if (empty($pay_errors)) {
    // Tính lại tổng tiền phía server (bảo mật)
    $subtotal_calc = (int) $booking['grand_total'];
    $server_discount = 0;
    if (!empty($voucher_code) && isset($valid_vouchers[$voucher_code])) {
      $v = $valid_vouchers[$voucher_code];
      if ($v['percent'] > 0) {
        $server_discount = (int) round($subtotal_calc * $v['percent'] / 100);
      } elseif ($v['flat'] > 0) {
        $server_discount = $v['flat'];
      }
    }
    $server_final = max(0, $subtotal_calc - $server_discount);

    // Lưu kết quả thanh toán vào session
    $_SESSION['payment'] = [
      'order_id' => $order_id,
      'method' => $method,
      'voucher_code' => $voucher_code,
      'discount' => $server_discount,
      'final_total' => $server_final,
      'paid_at' => date('Y-m-d H:i:s'),
      'booking' => $booking,
    ];

    // Lưu trạng thái thanh toán vào database (cập nhật booking nếu có booking_id)
    if (!empty($booking['booking_id'])) {
      try {
        $stmt = $pdo->prepare(
          "UPDATE bookings SET status = 'đã xác nhận' WHERE id = :id AND user_id = :uid"
        );
        $stmt->execute([
          'id' => (int) $booking['booking_id'],
          'uid' => $userId,
        ]);
      } catch (\Throwable $e) {
        error_log('Payment update booking error: ' . $e->getMessage());
      }
    }

    // Xoá booking session sau khi thanh toán xong
    unset($_SESSION['booking']);

    $pay_success = true;
    // Redirect sang trang lịch sử đặt tour
    header('Location: my_bookings.php?payment=success&order=' . urlencode($order_id));
    exit();
  }
}

// ============================================
//   HÀM HELPER
// ============================================
function formatVND(float $amount): string
{
  return number_format($amount, 0, ',', '.') . ' đ';
}

function esc(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$subtotal = (int) $booking['grand_total'];
$adult_total = (int) $booking['adult_total'];
$child_total = (int) $booking['child_total'];
?>
<!doctype html>
<html lang="vi">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Thanh Toán - Du Lịch Việt</title>
  <meta name="description"
    content="Hoàn tất thanh toán đặt tour du lịch trực tuyến tại Du Lịch Việt. Hỗ trợ thẻ tín dụng, chuyển khoản ngân hàng, ví MoMo và VNPay." />
  <link rel="stylesheet" href="css/styles.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <style>
    /* =============================================
         PAYMENT PAGE – SPECIFIC STYLES
         ============================================= */

    /* ---------- Hero ---------- */
    .payment-hero {
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
      padding: 3rem 0 2rem;
      position: relative;
      overflow: hidden;
    }

    .payment-hero::before {
      content: '';
      position: absolute;
      inset: -50%;
      width: 200%;
      height: 200%;
      background:
        radial-gradient(circle at 70% 30%, rgba(0, 188, 212, 0.08) 0%, transparent 60%),
        radial-gradient(circle at 25% 75%, rgba(33, 150, 243, 0.06) 0%, transparent 50%);
      animation: heroBreath 8s ease-in-out infinite;
      pointer-events: none;
    }

    @keyframes heroBreath {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.04);
      }
    }

    .payment-hero-content {
      position: relative;
      z-index: 2;
      text-align: center;
      color: #fff;
    }

    /* Breadcrumb */
    .payment-breadcrumb {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      font-size: .9rem;
      color: rgba(255, 255, 255, .6);
      margin-bottom: 1.5rem;
    }

    .payment-breadcrumb a {
      color: rgba(255, 255, 255, .6);
      transition: color .3s;
      text-decoration: none;
    }

    .payment-breadcrumb a:hover {
      color: var(--accent-color);
    }

    .payment-breadcrumb i {
      font-size: .7rem;
    }

    .payment-breadcrumb .current {
      color: var(--accent-color);
      font-weight: 600;
    }

    .payment-hero-title {
      font-size: 2.5rem;
      font-weight: 800;
      margin-bottom: .5rem;
      background: linear-gradient(135deg, #fff 0%, #b2ebf2 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .payment-hero-subtitle {
      color: rgba(255, 255, 255, .7);
      font-size: 1rem;
    }

    /* Progress Steps */
    .payment-steps {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-top: 1.5rem;
    }

    .pstep {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .35rem;
    }

    .pstep-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: .85rem;
      font-weight: 700;
      transition: all .3s;
    }

    .pstep.completed .pstep-circle {
      background: #4caf50;
      color: #fff;
    }

    .pstep.active .pstep-circle {
      background: var(--accent-color);
      color: #fff;
      box-shadow: 0 0 0 4px rgba(0, 188, 212, .2);
    }

    .pstep.pending .pstep-circle {
      background: rgba(255, 255, 255, .15);
      color: rgba(255, 255, 255, .5);
      border: 2px solid rgba(255, 255, 255, .2);
    }

    .pstep-label {
      font-size: .72rem;
      font-weight: 500;
      white-space: nowrap;
    }

    .pstep.completed .pstep-label {
      color: #4caf50;
    }

    .pstep.active .pstep-label {
      color: var(--accent-color);
    }

    .pstep.pending .pstep-label {
      color: rgba(255, 255, 255, .4);
    }

    .pstep-line {
      width: 60px;
      height: 2px;
      margin-bottom: 1.4rem;
    }

    .pstep-line.done {
      background: #4caf50;
    }

    .pstep-line.todo {
      background: rgba(255, 255, 255, .2);
    }

    /* ---------- Page body ---------- */
    .payment-page {
      background: #f0f4f8;
      min-height: 100vh;
      padding: 2.5rem 0 4rem;
    }

    .payment-layout {
      display: grid;
      grid-template-columns: 1fr 400px;
      gap: 2rem;
      align-items: start;
    }

    /* ---------- Generic card ---------- */
    .pay-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 24px rgba(0, 0, 0, .08);
      margin-bottom: 1.5rem;
    }

    .pay-card-header {
      padding: 1.3rem 1.8rem;
      display: flex;
      align-items: center;
      gap: .8rem;
    }

    .pay-card-header i {
      font-size: 1.15rem;
    }

    .pay-card-header h2 {
      font-size: 1.05rem;
      margin: 0;
      font-weight: 700;
    }

    .pay-card-body {
      padding: 1.8rem;
    }

    /* color variants */
    .header-dark {
      background: linear-gradient(135deg, #0f3460, #16213e);
    }

    .header-dark i,
    .header-dark h2 {
      color: #fff;
    }

    .header-dark i {
      color: var(--accent-color);
    }

    .header-blue {
      background: linear-gradient(135deg, #2196f3, #00bcd4);
    }

    .header-blue i,
    .header-blue h2 {
      color: #fff;
    }

    .header-orange {
      background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    }

    .header-orange i,
    .header-orange h2 {
      color: #fff;
    }

    .header-green {
      background: linear-gradient(135deg, #4caf50, #81c784);
    }

    .header-green i,
    .header-green h2 {
      color: #fff;
    }

    /* ---------- ORDER SUMMARY ---------- */
    .order-tour-name {
      font-size: 1rem;
      font-weight: 700;
      color: #1a1a2e;
      line-height: 1.4;
      margin-bottom: 1rem;
      padding-bottom: 1rem;
      border-bottom: 1px dashed #e0e0e0;
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

    .order-row .val.green {
      color: #4caf50;
    }

    .order-row .val.red {
      color: #f44336;
    }

    .order-row .val.zero {
      color: #ccc;
      font-weight: 400;
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

    .order-total-label i {
      font-size: .8rem;
    }

    .order-total-amount {
      font-size: 1.8rem;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.5px;
      line-height: 1;
    }

    .order-total-amount .cur {
      font-size: 1rem;
      font-weight: 600;
      color: rgba(255, 255, 255, .65);
      margin-left: .3rem;
      vertical-align: middle;
    }

    .total-note {
      font-size: .73rem;
      color: rgba(255, 255, 255, .38);
      margin-top: .5rem;
    }

    @keyframes priceFlash {
      0% {
        transform: scale(1);
      }

      40% {
        transform: scale(1.07);
        color: #00bcd4;
      }

      100% {
        transform: scale(1);
      }
    }

    .price-flash {
      animation: priceFlash .4s ease;
    }

    /* ---------- VOUCHER SECTION ---------- */
    .voucher-row {
      display: flex;
      gap: .6rem;
    }

    .voucher-input {
      flex: 1;
      padding: .75rem 1rem;
      border: 2px solid #e8e8e8;
      border-radius: 8px;
      font-size: .95rem;
      font-family: inherit;
      outline: none;
      text-transform: uppercase;
      letter-spacing: 1px;
      transition: border-color .3s;
    }

    .voucher-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(33, 150, 243, .1);
    }

    .voucher-input.error {
      border-color: #f44336;
    }

    .voucher-input.success {
      border-color: #4caf50;
    }

    .btn-voucher {
      padding: .75rem 1.6rem;
      background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
      color: #fff;
      font-weight: 700;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all .3s;
      white-space: nowrap;
      font-family: inherit;
      font-size: .9rem;
      box-shadow: 0 4px 12px rgba(33, 150, 243, .3);
    }

    .btn-voucher:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(33, 150, 243, .4);
    }

    .voucher-msg {
      margin-top: .6rem;
      font-size: .84rem;
      display: flex;
      align-items: center;
      gap: .4rem;
      min-height: 1.4em;
    }

    .voucher-msg.ok {
      color: #2e7d32;
    }

    .voucher-msg.err {
      color: #c62828;
    }

    .voucher-applied-tag {
      display: none;
      margin-top: .8rem;
      padding: .6rem 1rem;
      background: #e8f5e9;
      border: 1px solid #a5d6a7;
      border-radius: 8px;
      font-size: .85rem;
      font-weight: 600;
      color: #2e7d32;
      justify-content: space-between;
      align-items: center;
    }

    .voucher-applied-tag .remove-voucher {
      background: none;
      border: none;
      color: #c62828;
      cursor: pointer;
      font-size: .9rem;
      padding: 2px 6px;
      border-radius: 4px;
      transition: background .2s;
    }

    .voucher-applied-tag .remove-voucher:hover {
      background: #ffcdd2;
    }

    /* ---------- PAYMENT METHODS ---------- */
    .methods-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
      gap: 1rem;
    }

    .method-card {
      position: relative;
      border: 2px solid #e8e8e8;
      border-radius: 12px;
      padding: 1.2rem 1rem;
      text-align: center;
      cursor: pointer;
      transition: all .3s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: .5rem;
      background: #fff;
    }

    .method-card:hover {
      border-color: #90caf9;
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(33, 150, 243, .12);
    }

    .method-card.selected {
      border-color: var(--primary-color);
      background: linear-gradient(135deg, rgba(33, 150, 243, .04), rgba(0, 188, 212, .04));
      box-shadow: 0 4px 16px rgba(33, 150, 243, .18);
    }

    .method-card input[type="radio"] {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .method-icon {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      margin-bottom: .2rem;
      transition: transform .3s;
    }

    .method-card:hover .method-icon {
      transform: scale(1.1);
    }

    .method-icon.credit {
      background: linear-gradient(135deg, #1565c0, #42a5f5);
      color: #fff;
    }

    .method-icon.bank {
      background: linear-gradient(135deg, #2e7d32, #66bb6a);
      color: #fff;
    }

    .method-icon.momo {
      background: linear-gradient(135deg, #ad1457, #e91e63);
      color: #fff;
    }

    .method-icon.vnpay {
      background: linear-gradient(135deg, #e65100, #ff9800);
      color: #fff;
    }

    .method-label {
      font-size: .82rem;
      font-weight: 600;
      color: #555;
    }

    .method-card.selected .method-label {
      color: var(--primary-color);
    }

    .check-dot {
      position: absolute;
      top: .6rem;
      right: .6rem;
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid #ccc;
      transition: all .3s;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .method-card.selected .check-dot {
      border-color: var(--primary-color);
      background: var(--primary-color);
    }

    .method-card.selected .check-dot::after {
      content: '\f00c';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      color: #fff;
      font-size: .6rem;
    }

    /* ---------- Payment Detail Panels ---------- */
    .payment-panel {
      display: none;
      margin-top: 1.5rem;
      padding: 1.5rem;
      background: #f8f9fa;
      border-radius: 12px;
      border: 1px solid #e8e8e8;
      animation: panelSlideIn .35s ease;
    }

    .payment-panel.show {
      display: block;
    }

    @keyframes panelSlideIn {
      from {
        opacity: 0;
        transform: translateY(10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .panel-title {
      font-size: .95rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 1.2rem;
      display: flex;
      align-items: center;
      gap: .5rem;
    }

    .panel-title i {
      color: var(--primary-color);
      font-size: .9rem;
    }

    /* Credit-card form */
    .cc-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
    }

    .cc-grid .full {
      grid-column: 1 / -1;
    }

    .cc-label {
      display: block;
      font-size: .82rem;
      font-weight: 600;
      color: #555;
      margin-bottom: .35rem;
    }

    .cc-input {
      width: 100%;
      padding: .7rem 1rem;
      border: 2px solid #e8e8e8;
      border-radius: 8px;
      font-size: .92rem;
      font-family: inherit;
      outline: none;
      transition: border-color .3s;
    }

    .cc-input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(33, 150, 243, .1);
    }

    .cc-icons {
      display: flex;
      gap: .6rem;
      margin-top: .8rem;
      opacity: .5;
      font-size: 1.8rem;
    }

    /* QR panel */
    .qr-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    .qr-box {
      width: 200px;
      height: 200px;
      background: #fff;
      border: 2px solid #e0e0e0;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }

    .qr-box::before {
      content: '';
      position: absolute;
      inset: 15px;
      background:
        repeating-conic-gradient(#1a1a2e 0% 25%, #fff 0% 50%) 0 0 / 14px 14px,
        repeating-conic-gradient(#1a1a2e 0% 25%, #fff 0% 50%) 7px 7px / 14px 14px;
      opacity: .85;
      border-radius: 4px;
    }

    .qr-box .qr-logo {
      position: relative;
      z-index: 2;
      width: 44px;
      height: 44px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      color: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .2);
    }

    .qr-logo.momo-bg {
      background: linear-gradient(135deg, #ad1457, #e91e63);
    }

    .qr-logo.vnpay-bg {
      background: linear-gradient(135deg, #e65100, #ff9800);
    }

    .qr-logo.bank-bg {
      background: linear-gradient(135deg, #2e7d32, #66bb6a);
    }

    .qr-instruction {
      font-size: .85rem;
      color: #666;
      text-align: center;
      line-height: 1.5;
    }

    .qr-instruction strong {
      color: #1a1a2e;
    }

    .bank-info {
      width: 100%;
      background: #fff;
      border-radius: 10px;
      padding: 1rem 1.2rem;
      margin-top: .5rem;
    }

    .bank-info-row {
      display: flex;
      justify-content: space-between;
      padding: .45rem 0;
      font-size: .85rem;
      border-bottom: 1px solid #f0f0f0;
    }

    .bank-info-row:last-child {
      border-bottom: none;
    }

    .bank-info-row .bi-label {
      color: #888;
    }

    .bank-info-row .bi-value {
      font-weight: 600;
      color: #1a1a2e;
    }

    /* ---------- CONFIRM BUTTON ---------- */
    .btn-confirm {
      width: 100%;
      padding: 1rem 1.5rem;
      background: linear-gradient(135deg, #2196f3, #00bcd4);
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
      box-shadow: 0 4px 15px rgba(33, 150, 243, .4);
      letter-spacing: .3px;
      font-family: inherit;
    }

    .btn-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(33, 150, 243, .5);
      background: linear-gradient(135deg, #1976d2, #0097a7);
    }

    .btn-confirm:active {
      transform: translateY(0);
    }

    .btn-confirm:disabled {
      opacity: .55;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: none !important;
    }

    .btn-back-link {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .5rem;
      margin-top: .8rem;
      padding: .8rem;
      background: transparent;
      border: 2px solid #e0e0e0;
      border-radius: 10px;
      color: #666;
      font-weight: 600;
      font-size: .9rem;
      cursor: pointer;
      transition: all .3s;
      font-family: inherit;
      width: 100%;
    }

    .btn-back-link:hover {
      border-color: #bbb;
      color: #333;
      background: #f8f9fa;
    }

    .security-note {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: .4rem;
      font-size: .78rem;
      color: #aaa;
      margin-top: 1rem;
    }

    .security-note i {
      color: #4caf50;
      font-size: .85rem;
    }

    /* ---------- Server error alert ---------- */
    .pay-alert {
      padding: .9rem 1.2rem;
      border-radius: 8px;
      margin-bottom: 1.2rem;
      font-size: .9rem;
      display: flex;
      align-items: flex-start;
      gap: .6rem;
    }

    .pay-alert.error {
      background: #ffebee;
      color: #c62828;
      border: 1px solid #ef9a9a;
    }

    .pay-alert ul {
      margin: .4rem 0 0 1rem;
      padding: 0;
    }

    .pay-alert ul li {
      margin-bottom: .2rem;
    }

    /* ---------- Success overlay ---------- */
    .success-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .55);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }

    .success-overlay.show {
      display: flex;
    }

    .success-modal {
      background: #fff;
      border-radius: 20px;
      max-width: 420px;
      width: 90%;
      padding: 2.5rem 2rem;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
      animation: modalPop .4s ease;
    }

    @keyframes modalPop {
      from {
        opacity: 0;
        transform: scale(.85);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    .success-icon {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      background: linear-gradient(135deg, #4caf50, #81c784);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.5rem;
      font-size: 2.2rem;
      color: #fff;
      box-shadow: 0 8px 24px rgba(76, 175, 80, .35);
    }

    .success-modal h2 {
      color: #1a1a2e;
      font-size: 1.4rem;
      margin-bottom: .6rem;
    }

    .success-modal p {
      color: #666;
      font-size: .95rem;
      line-height: 1.6;
      margin-bottom: 1.5rem;
    }

    .success-modal .btn-home {
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      padding: .8rem 2rem;
      background: linear-gradient(135deg, #2196f3, #00bcd4);
      color: #fff;
      border: none;
      border-radius: 25px;
      font-weight: 700;
      cursor: pointer;
      transition: all .3s;
      font-family: inherit;
      font-size: .95rem;
      box-shadow: 0 4px 15px rgba(33, 150, 243, .3);
      text-decoration: none;
    }

    .success-modal .btn-home:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(33, 150, 243, .4);
    }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 900px) {
      .payment-layout {
        grid-template-columns: 1fr;
      }

      .payment-right-col {
        order: -1;
      }

      .payment-hero-title {
        font-size: 1.8rem;
      }
    }

    @media (max-width: 600px) {
      .methods-grid {
        grid-template-columns: 1fr 1fr;
      }

      .cc-grid {
        grid-template-columns: 1fr;
      }

      .cc-grid .full {
        grid-column: 1;
      }

      .payment-steps {
        gap: 0;
      }

      .pstep-line {
        width: 40px;
      }

      .voucher-row {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <?php
  $activePage = 'tours';
  require __DIR__ . '/includes/header.php';
  ?>

  <!-- =============== HERO =============== -->
  <section class="payment-hero">
    <div class="container">
      <div class="payment-hero-content">
        <nav class="payment-breadcrumb" aria-label="Breadcrumb">
          <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
          <i class="fas fa-chevron-right"></i>
          <a href="tours.php">Tour du lịch</a>
          <i class="fas fa-chevron-right"></i>
          <a href="my_bookings.php">Đặt tour</a>
          <i class="fas fa-chevron-right"></i>
          <span class="current">Thanh toán</span>
        </nav>
        <h1 class="payment-hero-title">Thanh Toán Đơn Hàng</h1>
        <p class="payment-hero-subtitle">Kiểm tra đơn hàng và hoàn tất thanh toán</p>

        <!-- Steps -->
        <div class="payment-steps">
          <div class="pstep completed">
            <div class="pstep-circle"><i class="fas fa-check"></i></div>
            <span class="pstep-label">Chọn hành khách</span>
          </div>
          <div class="pstep-line done"></div>
          <div class="pstep completed">
            <div class="pstep-circle"><i class="fas fa-check"></i></div>
            <span class="pstep-label">Xác nhận</span>
          </div>
          <div class="pstep-line done"></div>
          <div class="pstep active">
            <div class="pstep-circle"><i class="fas fa-credit-card"></i></div>
            <span class="pstep-label">Thanh toán</span>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- =============== PAYMENT CONTENT =============== -->
  <div class="payment-page">
    <div class="container">

      <!-- Server-side error alert -->
      <?php if (!empty($pay_errors)): ?>
        <div class="pay-alert error" role="alert" style="margin-bottom:1.5rem;">
          <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:.1rem;"></i>
          <div>
            <strong>Vui lòng kiểm tra lại:</strong>
            <ul>
              <?php foreach ($pay_errors as $err): ?>
                <li><?= esc($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      <?php endif; ?>

      <form id="payment-form" action="payment.php" method="POST">

        <!-- Hidden fields synced by JS before submit -->
        <input type="hidden" name="payment_method" id="input-method" value="" />
        <input type="hidden" name="voucher_code" id="input-voucher-code" value="" />
        <input type="hidden" name="discount_percent" id="input-discount-percent" value="0" />
        <input type="hidden" name="discount_amount" id="input-discount-amount" value="0" />
        <input type="hidden" name="final_total" id="input-final-total" value="<?= $subtotal ?>" />

        <!-- Credit card hidden fields -->
        <input type="hidden" name="cc_number" id="h-cc-number" value="" />
        <input type="hidden" name="cc_name" id="h-cc-name" value="" />
        <input type="hidden" name="cc_expiry" id="h-cc-expiry" value="" />
        <input type="hidden" name="cc_cvv" id="h-cc-cvv" value="" />

        <div class="payment-layout">

          <!-- ===== LEFT COLUMN ===== -->
          <div class="payment-left-col">

            <!-- 1 ─ VOUCHER / DISCOUNT -->
            <div class="pay-card">
              <div class="pay-card-header header-green">
                <i class="fas fa-tags"></i>
                <h2>Mã giảm giá / Voucher</h2>
              </div>
              <div class="pay-card-body">
                <div class="voucher-row">
                  <input type="text" id="voucher-input" class="voucher-input" placeholder="Nhập mã giảm giá"
                    maxlength="20" autocomplete="off" />
                  <button type="button" class="btn-voucher" id="btn-apply-voucher" onclick="applyVoucher()">
                    <i class="fas fa-check-circle"></i> Áp dụng
                  </button>
                </div>
                <div class="voucher-msg" id="voucher-msg"></div>
                <div class="voucher-applied-tag" id="voucher-tag">
                  <span id="voucher-tag-text"></span>
                  <button type="button" class="remove-voucher" onclick="removeVoucher()" title="Xóa voucher">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <p style="font-size:.8rem;color:#aaa;margin-top:.6rem;">
                  <i class="fas fa-info-circle"></i> Thử mã <strong style="color:#4caf50;">GIAM10</strong> để được giảm
                  10%.
                </p>
              </div>
            </div>

            <!-- 2 ─ PAYMENT METHODS -->
            <div class="pay-card">
              <div class="pay-card-header header-blue">
                <i class="fas fa-wallet"></i>
                <h2>Phương thức thanh toán</h2>
              </div>
              <div class="pay-card-body">

                <div class="methods-grid" id="methods-grid">
                  <!-- Credit Card -->
                  <label class="method-card" id="method-credit" onclick="selectMethod('credit')">
                    <input type="radio" name="payment-method-ui" value="credit" />
                    <div class="check-dot"></div>
                    <div class="method-icon credit"><i class="fas fa-credit-card"></i></div>
                    <span class="method-label">Thẻ tín dụng</span>
                  </label>

                  <!-- Bank Transfer -->
                  <label class="method-card" id="method-bank" onclick="selectMethod('bank')">
                    <input type="radio" name="payment-method-ui" value="bank" />
                    <div class="check-dot"></div>
                    <div class="method-icon bank"><i class="fas fa-building-columns"></i></div>
                    <span class="method-label">Chuyển khoản</span>
                  </label>

                  <!-- MoMo -->
                  <label class="method-card" id="method-momo" onclick="selectMethod('momo')">
                    <input type="radio" name="payment-method-ui" value="momo" />
                    <div class="check-dot"></div>
                    <div class="method-icon momo"><i class="fas fa-mobile-screen"></i></div>
                    <span class="method-label">Ví MoMo</span>
                  </label>

                  <!-- VNPay -->
                  <label class="method-card" id="method-vnpay" onclick="selectMethod('vnpay')">
                    <input type="radio" name="payment-method-ui" value="vnpay" />
                    <div class="check-dot"></div>
                    <div class="method-icon vnpay"><i class="fas fa-qrcode"></i></div>
                    <span class="method-label">VNPay</span>
                  </label>
                </div>

                <!-- ── Credit Card Panel ── -->
                <div class="payment-panel" id="panel-credit">
                  <div class="panel-title"><i class="fas fa-lock"></i> Thông tin thẻ tín dụng</div>
                  <div class="cc-grid">
                    <div class="full">
                      <label class="cc-label" for="cc-number">Số thẻ</label>
                      <input class="cc-input" id="cc-number" type="text" placeholder="0000 0000 0000 0000"
                        maxlength="19" oninput="formatCardNumber(this)" />
                    </div>
                    <div class="full">
                      <label class="cc-label" for="cc-name">Tên chủ thẻ</label>
                      <input class="cc-input" id="cc-name" type="text" placeholder="NGUYEN VAN A"
                        style="text-transform:uppercase;" />
                    </div>
                    <div>
                      <label class="cc-label" for="cc-expiry">Ngày hết hạn</label>
                      <input class="cc-input" id="cc-expiry" type="text" placeholder="MM/YY" maxlength="5"
                        oninput="formatExpiry(this)" />
                    </div>
                    <div>
                      <label class="cc-label" for="cc-cvv">CVV</label>
                      <input class="cc-input" id="cc-cvv" type="password" placeholder="•••" maxlength="4" />
                    </div>
                  </div>
                  <div class="cc-icons">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-jcb"></i>
                  </div>
                </div>

                <!-- ── Bank Transfer Panel ── -->
                <div class="payment-panel" id="panel-bank">
                  <div class="panel-title"><i class="fas fa-building-columns"></i> Chuyển khoản ngân hàng</div>
                  <div class="qr-wrapper">
                    <div class="qr-box">
                      <div class="qr-logo bank-bg"><i class="fas fa-building-columns"></i></div>
                    </div>
                    <p class="qr-instruction">
                      Quét mã QR hoặc chuyển khoản theo thông tin bên dưới.<br>
                      <strong>Nội dung CK:</strong> DULICHVIET_<span class="order-id-ref"><?= esc($order_id) ?></span>
                    </p>
                    <div class="bank-info">
                      <div class="bank-info-row">
                        <span class="bi-label">Ngân hàng</span>
                        <span class="bi-value">Vietcombank</span>
                      </div>
                      <div class="bank-info-row">
                        <span class="bi-label">Số tài khoản</span>
                        <span class="bi-value">1234 5678 9012</span>
                      </div>
                      <div class="bank-info-row">
                        <span class="bi-label">Chủ tài khoản</span>
                        <span class="bi-value">CONG TY DU LICH VIET</span>
                      </div>
                      <div class="bank-info-row">
                        <span class="bi-label">Số tiền</span>
                        <span class="bi-value" id="bank-amount"><?= formatVND((float) $subtotal) ?></span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- ── MoMo Panel ── -->
                <div class="payment-panel" id="panel-momo">
                  <div class="panel-title"><i class="fas fa-mobile-screen"></i> Thanh toán qua MoMo</div>
                  <div class="qr-wrapper">
                    <div class="qr-box">
                      <div class="qr-logo momo-bg"><i class="fas fa-mobile-screen"></i></div>
                    </div>
                    <p class="qr-instruction">
                      Mở ứng dụng <strong>MoMo</strong> → Quét mã QR để thanh toán.<br>
                      Số tiền: <strong id="momo-amount"><?= formatVND((float) $subtotal) ?></strong>
                    </p>
                  </div>
                </div>

                <!-- ── VNPay Panel ── -->
                <div class="payment-panel" id="panel-vnpay">
                  <div class="panel-title"><i class="fas fa-qrcode"></i> Thanh toán qua VNPay</div>
                  <div class="qr-wrapper">
                    <div class="qr-box">
                      <div class="qr-logo vnpay-bg"><i class="fas fa-qrcode"></i></div>
                    </div>
                    <p class="qr-instruction">
                      Mở app ngân hàng hoặc <strong>VNPay</strong> → Quét mã QR để thanh toán.<br>
                      Số tiền: <strong id="vnpay-amount"><?= formatVND((float) $subtotal) ?></strong>
                    </p>
                  </div>
                </div>

              </div>
            </div>
          </div><!-- end left col -->

          <!-- ===== RIGHT COLUMN – ORDER SUMMARY ===== -->
          <div class="payment-right-col">
            <div class="pay-card" style="position:sticky;top:90px;">
              <div class="pay-card-header header-orange">
                <i class="fas fa-receipt"></i>
                <h2>Tóm tắt đơn hàng</h2>
              </div>
              <div class="pay-card-body">
                <div class="order-tour-name" id="order-tour-name">
                  <?= esc($booking['tour_name']) ?>
                </div>

                <!-- Thông tin người đặt (từ session) -->
                <?php if (!empty($booking['full_name'])): ?>
                  <div class="order-row">
                    <span class="lbl"><i class="fas fa-user"></i> Khách đặt</span>
                    <span class="val"><?= esc($booking['full_name']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($booking['departure_date'])): ?>
                  <div class="order-row">
                    <span class="lbl"><i class="fas fa-calendar"></i> Ngày khởi hành</span>
                    <span class="val"><?= esc(date('d/m/Y', strtotime($booking['departure_date']))) ?></span>
                  </div>
                <?php endif; ?>

                <div class="order-divider"></div>

                <div class="order-row">
                  <span class="lbl"><i class="fas fa-user"></i> Người lớn (<?= (int) $booking['adults'] ?> người)</span>
                  <span class="val blue" id="order-adult-total"><?= formatVND((float) $adult_total) ?></span>
                </div>

                <?php if ((int) $booking['children'] > 0): ?>
                  <div class="order-row">
                    <span class="lbl"><i class="fas fa-child"></i> Trẻ em (<?= (int) $booking['children'] ?> trẻ)</span>
                    <span class="val green" id="order-child-total"><?= formatVND((float) $child_total) ?></span>
                  </div>
                <?php endif; ?>

                <div class="order-divider"></div>

                <div class="order-row">
                  <span class="lbl"><i class="fas fa-calculator"></i> Tạm tính</span>
                  <span class="val" id="order-subtotal"><?= formatVND((float) $subtotal) ?></span>
                </div>

                <div class="order-row" id="discount-row" style="display:none;">
                  <span class="lbl"><i class="fas fa-percent"></i> Giảm giá (<span
                      id="discount-percent">0</span>%)</span>
                  <span class="val red" id="order-discount">-0 đ</span>
                </div>

                <!-- Grand total -->
                <div class="order-total-box">
                  <div class="order-total-label"><i class="fas fa-money-bill-wave"></i> Tổng thanh toán</div>
                  <div class="order-total-amount" id="order-grand-total">
                    <?= number_format($subtotal, 0, ',', '.') ?><span class="cur">đ</span>
                  </div>
                  <div class="total-note">* Đã bao gồm thuế và phí dịch vụ</div>
                </div>
              </div>

              <!-- Actions -->
              <div style="padding: 0 1.8rem 1.5rem;">
                <button type="button" class="btn-confirm" id="btn-confirm" onclick="confirmPayment()" disabled>
                  <i class="fas fa-lock"></i>
                  XÁC NHẬN THANH TOÁN
                </button>
                <button type="button" class="btn-back-link" onclick="history.back()">
                  <i class="fas fa-arrow-left"></i> Quay lại
                </button>
                <div class="security-note">
                  <i class="fas fa-shield-alt"></i> Giao dịch được mã hoá SSL 256‑bit
                </div>
              </div>
            </div>
          </div><!-- end right col -->

        </div><!-- end layout -->
      </form><!-- end #payment-form -->

    </div>
  </div>

  <!-- =============== SUCCESS OVERLAY =============== -->
  <div class="success-overlay" id="success-overlay">
    <div class="success-modal">
      <div class="success-icon"><i class="fas fa-check"></i></div>
      <h2>Thanh toán thành công!</h2>
      <p>
        Cảm ơn bạn đã đặt tour tại <strong>Du Lịch Việt</strong>.<br>
        Chúng tôi sẽ gửi email xác nhận trong vài phút tới.
      </p>
      <a href="my_bookings.php" class="btn-home"><i class="fas fa-receipt"></i> Xem lịch sử đặt tour</a>
    </div>
  </div>

  <?php require __DIR__ . '/includes/footer.php'; ?>

  <script>
    // =============================================
    //  ORDER DATA – injected from PHP session
    // =============================================
    const ORDER = {
      tourName: <?= json_encode($booking['tour_name']) ?>,
      adults: <?= (int) $booking['adults'] ?>,
      children: <?= (int) $booking['children'] ?>,
      adultPrice: <?= (int) $booking['adult_price'] ?>,
      childPrice: <?= (int) $booking['child_price'] ?>,
    };

    ORDER.adultTotal = <?= $adult_total ?>;
    ORDER.childTotal = <?= $child_total ?>;
    ORDER.subtotal = <?= $subtotal ?>;

    // Voucher database (mirrored from PHP for client-side preview)
    const VOUCHERS = {
      "GIAM10": { percent: 10, flat: 0, label: "Giảm 10% tổng đơn" },
      "GIAM20": { percent: 20, flat: 0, label: "Giảm 20% tổng đơn" },
      "GIAM50K": { percent: 0, flat: 50000, label: "Giảm 50.000đ" },
    };

    // ─── State ───
    let discountPercent = 0;
    let discountAmount = 0;
    let grandTotal = ORDER.subtotal;
    let selectedMethod = null;
    let voucherApplied = false;
    let appliedCode = "";

    // =============================================
    //  HELPERS
    // =============================================
    function fmt(n) {
      if (n === 0) return "0";
      return n.toLocaleString("vi-VN");
    }
    function fmtC(n) { return fmt(n) + " đ"; }
    function flash(el) {
      el.classList.remove("price-flash");
      void el.offsetWidth;
      el.classList.add("price-flash");
    }

    // Sync hidden form fields before submit
    function syncHiddenFields() {
      document.getElementById("input-method").value = selectedMethod || "";
      document.getElementById("input-voucher-code").value = appliedCode;
      document.getElementById("input-discount-percent").value = discountPercent;
      document.getElementById("input-discount-amount").value = discountAmount;
      document.getElementById("input-final-total").value = grandTotal;

      if (selectedMethod === "credit") {
        document.getElementById("h-cc-number").value = document.getElementById("cc-number").value.replace(/\s/g, "");
        document.getElementById("h-cc-name").value = document.getElementById("cc-name").value;
        document.getElementById("h-cc-expiry").value = document.getElementById("cc-expiry").value;
        document.getElementById("h-cc-cvv").value = document.getElementById("cc-cvv").value;
      }
    }

    // =============================================
    //  RENDER ORDER SUMMARY
    // =============================================
    function renderSummary() {
      const discountRow = document.getElementById("discount-row");
      if (discountPercent > 0 || discountAmount > 0) {
        discountRow.style.display = "flex";
        if (discountPercent > 0) {
          discountAmount = Math.round(ORDER.subtotal * discountPercent / 100);
          document.getElementById("discount-percent").textContent = discountPercent;
        }
        document.getElementById("order-discount").textContent = "-" + fmtC(discountAmount);
      } else {
        discountRow.style.display = "none";
      }

      grandTotal = ORDER.subtotal - discountAmount;
      if (grandTotal < 0) grandTotal = 0;

      const gtEl = document.getElementById("order-grand-total");
      gtEl.innerHTML = fmt(grandTotal) + '<span class="cur">đ</span>';
      flash(gtEl);

      // Update payment panel amounts
      document.getElementById("bank-amount").textContent = fmtC(grandTotal);
      document.getElementById("momo-amount").textContent = fmtC(grandTotal);
      document.getElementById("vnpay-amount").textContent = fmtC(grandTotal);

      // Sync hidden input
      document.getElementById("input-final-total").value = grandTotal;
    }

    // =============================================
    //  VOUCHER LOGIC
    // =============================================
    function applyVoucher() {
      const input = document.getElementById("voucher-input");
      const msgEl = document.getElementById("voucher-msg");
      const tag = document.getElementById("voucher-tag");
      const tagText = document.getElementById("voucher-tag-text");
      const code = input.value.trim().toUpperCase();

      if (!code) {
        msgEl.className = "voucher-msg err";
        msgEl.innerHTML = '<i class="fas fa-exclamation-circle"></i> Vui lòng nhập mã giảm giá.';
        input.classList.add("error");
        input.classList.remove("success");
        return;
      }

      const v = VOUCHERS[code];
      if (!v) {
        msgEl.className = "voucher-msg err";
        msgEl.innerHTML = '<i class="fas fa-times-circle"></i> Mã "' + code + '" không hợp lệ hoặc đã hết hạn.';
        input.classList.add("error");
        input.classList.remove("success");
        return;
      }

      // Apply
      voucherApplied = true;
      appliedCode = code;
      if (v.percent) {
        discountPercent = v.percent;
        discountAmount = Math.round(ORDER.subtotal * v.percent / 100);
      } else if (v.flat) {
        discountPercent = 0;
        discountAmount = v.flat;
      }

      input.classList.remove("error");
      input.classList.add("success");
      input.disabled = true;
      document.getElementById("btn-apply-voucher").disabled = true;

      msgEl.className = "voucher-msg ok";
      msgEl.innerHTML = '<i class="fas fa-check-circle"></i> Áp dụng thành công! ' + v.label;

      tag.style.display = "flex";
      tagText.innerHTML = '<i class="fas fa-tag"></i> ' + code + " – " + v.label;

      document.getElementById("input-voucher-code").value = code;
      document.getElementById("input-discount-percent").value = discountPercent;
      document.getElementById("input-discount-amount").value = discountAmount;

      renderSummary();
    }

    function removeVoucher() {
      voucherApplied = false;
      appliedCode = "";
      discountPercent = 0;
      discountAmount = 0;

      const input = document.getElementById("voucher-input");
      input.value = "";
      input.disabled = false;
      input.classList.remove("success", "error");
      document.getElementById("btn-apply-voucher").disabled = false;
      document.getElementById("voucher-msg").innerHTML = "";
      document.getElementById("voucher-tag").style.display = "none";

      document.getElementById("input-voucher-code").value = "";
      document.getElementById("input-discount-percent").value = 0;
      document.getElementById("input-discount-amount").value = 0;

      renderSummary();
    }

    // =============================================
    //  PAYMENT METHOD SELECTION
    // =============================================
    function selectMethod(method) {
      selectedMethod = method;
      document.getElementById("input-method").value = method;

      document.querySelectorAll(".method-card").forEach(c => c.classList.remove("selected"));
      document.getElementById("method-" + method).classList.add("selected");
      document.querySelector('#method-' + method + ' input[type="radio"]').checked = true;

      document.querySelectorAll(".payment-panel").forEach(p => p.classList.remove("show"));
      const panel = document.getElementById("panel-" + method);
      if (panel) panel.classList.add("show");

      document.getElementById("btn-confirm").disabled = false;
    }

    // =============================================
    //  CREDIT CARD FORMATTERS
    // =============================================
    function formatCardNumber(el) {
      let v = el.value.replace(/\D/g, "").substring(0, 16);
      el.value = v.replace(/(.{4})/g, "$1 ").trim();
    }

    function formatExpiry(el) {
      let v = el.value.replace(/\D/g, "").substring(0, 4);
      if (v.length >= 3) {
        v = v.substring(0, 2) + "/" + v.substring(2);
      }
      el.value = v;
    }

    // =============================================
    //  CONFIRM PAYMENT → submit to PHP
    // =============================================
    function confirmPayment() {
      if (!selectedMethod) return;

      // Client-side validate credit card
      if (selectedMethod === "credit") {
        const num = document.getElementById("cc-number").value.replace(/\s/g, "");
        const name = document.getElementById("cc-name").value.trim();
        const expiry = document.getElementById("cc-expiry").value.trim();
        const cvv = document.getElementById("cc-cvv").value.trim();

        if (num.length < 16) {
          alert("Vui lòng nhập đầy đủ số thẻ (16 chữ số).");
          document.getElementById("cc-number").focus();
          return;
        }
        if (!name) {
          alert("Vui lòng nhập tên chủ thẻ.");
          document.getElementById("cc-name").focus();
          return;
        }
        if (expiry.length < 5) {
          alert("Vui lòng nhập ngày hết hạn (MM/YY).");
          document.getElementById("cc-expiry").focus();
          return;
        }
        if (cvv.length < 3) {
          alert("Vui lòng nhập mã CVV.");
          document.getElementById("cc-cvv").focus();
          return;
        }
      }

      // Simulate processing spinner, then submit to PHP
      const btn = document.getElementById("btn-confirm");
      btn.disabled = true;
      btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

      setTimeout(() => {
        syncHiddenFields();
        document.getElementById("payment-form").submit();
      }, 1500);
    }

    // Close overlay on outside click
    document.addEventListener("DOMContentLoaded", () => {
      document.getElementById("voucher-input").addEventListener("keydown", (e) => {
        if (e.key === "Enter") { e.preventDefault(); applyVoucher(); }
      });

      document.getElementById("success-overlay").addEventListener("click", (e) => {
        if (e.target === e.currentTarget) {
          e.currentTarget.classList.remove("show");
        }
      });

      renderSummary();
    });
  </script>
</body>

</html>