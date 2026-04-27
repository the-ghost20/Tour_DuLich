<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/payment_bank.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$userId = (int) $_SESSION['user_id'];

/** @var string $paymentBankDisplayName */
/** @var string $paymentBankBin */
/** @var string $paymentAccountNumber */
/** @var string $paymentAccountHolder */

function pay_h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

// ── POST: xác nhận đã chuyển khoản ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm_payment'] ?? '') === '1') {
    $bookingId = (int) ($_POST['booking_id'] ?? 0);
    if ($bookingId <= 0) {
        header('Location: my_bookings.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare(
            "SELECT id, status FROM bookings WHERE id = :id AND user_id = :uid LIMIT 1"
        );
        $stmt->execute(['id' => $bookingId, 'uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $_SESSION['my_bookings_flash_error'] = 'Không tìm thấy đơn đặt tour.';
            header('Location: my_bookings.php');
            exit;
        }

        if ((string) $row['status'] !== 'chờ duyệt') {
            $_SESSION['my_bookings_flash_error'] = 'Đơn không còn ở trạng thái chờ thanh toán.';
            header('Location: my_bookings.php');
            exit;
        }

        $upd = $pdo->prepare(
            "UPDATE bookings
             SET status = 'đã thanh toán', paid_at = NOW()
             WHERE id = :id AND user_id = :uid AND status = 'chờ duyệt'"
        );
        $upd->execute(['id' => $bookingId, 'uid' => $userId]);

        if ($upd->rowCount() < 1) {
            $_SESSION['my_bookings_flash_error'] = 'Không cập nhật được thanh toán. Vui lòng tải lại trang.';
            header('Location: my_bookings.php');
            exit;
        }

        $msg = 'Thanh toán thành công! Đơn #' . $bookingId . ' đã được ghi nhận.';
        header('Location: my_bookings.php?msg=' . rawurlencode($msg));
        exit;
    } catch (Throwable $e) {
        error_log('payment.php confirm: ' . $e->getMessage());
        $_SESSION['my_bookings_flash_error'] = 'Có lỗi khi lưu thanh toán. Vui lòng thử lại.';
        header('Location: my_bookings.php');
        exit;
    }
}

// ── GET: hiển thị QR & thông tin ───────────────────────────────────────────
$bookingId = (int) ($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) {
    header('Location: my_bookings.php');
    exit;
}

$booking = null;
try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.total_amount, b.status, b.paid_at, t.tour_name
         FROM bookings b
         INNER JOIN tours t ON t.id = b.tour_id
         WHERE b.id = :id AND b.user_id = :uid
         LIMIT 1"
    );
    $stmt->execute(['id' => $bookingId, 'uid' => $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('payment.php load: ' . $e->getMessage());
    $booking = null;
}

if (!$booking) {
    header('Location: my_bookings.php');
    exit;
}

$status = (string) $booking['status'];
if ($status === 'đã thanh toán') {
    header('Location: my_bookings.php?msg=' . rawurlencode('Đơn #' . $bookingId . ' đã được thanh toán trước đó.'));
    exit;
}

if ($status !== 'chờ duyệt') {
    $_SESSION['my_bookings_flash_error'] = 'Chỉ thanh toán QR khi đơn đang chờ duyệt.';
    header('Location: my_bookings.php');
    exit;
}

$totalFloat = (float) $booking['total_amount'];
$amountVnd  = (int) max(0, round($totalFloat));
$memo       = payment_transfer_memo($bookingId);
$qrReady    = payment_bank_config_ready($paymentBankBin, $paymentAccountNumber);
$qrUrl      = '';
if ($qrReady && $amountVnd > 0) {
    $qrUrl = payment_vietqr_image_url(
        $paymentBankBin,
        $paymentAccountNumber,
        $amountVnd,
        $memo,
        $paymentAccountHolder
    );
}

$totalFmt = number_format($totalFloat, 0, ',', '.');
$activePage = '';
$pageTitle  = 'Thanh toán đơn #' . $bookingId;
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= pay_h($pageTitle) ?> - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
      .pay-page { padding: 40px 0 70px; max-width: 520px; margin: 0 auto; }
      .pay-card {
        background: #fff;
        border-radius: 22px;
        border: 1px solid #e4ecf9;
        box-shadow: 0 8px 32px rgba(15, 38, 90, 0.1);
        overflow: hidden;
      }
      .pay-card-head {
        background: linear-gradient(135deg, #1a73e8, #00bcd4);
        color: #fff;
        padding: 22px 24px;
      }
      .pay-card-head h1 {
        margin: 0 0 6px;
        font-size: 1.35rem;
        font-weight: 800;
      }
      .pay-card-head p { margin: 0; opacity: 0.95; font-size: 0.92rem; }
      .pay-card-body { padding: 24px; }
      .pay-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px dashed #e0e8f5;
        font-size: 0.95rem;
      }
      .pay-row:last-of-type { border-bottom: none; }
      .pay-row .k { color: #6b7fa0; font-weight: 600; }
      .pay-row .v { color: #10213f; font-weight: 700; text-align: right; }
      .pay-amount {
        font-size: 1.45rem !important;
        color: #2196f3 !important;
      }
      .pay-qr-wrap {
        margin: 20px 0;
        text-align: center;
        padding: 16px;
        background: #f8faff;
        border-radius: 16px;
        border: 1px solid #dde8f8;
      }
      .pay-qr-wrap img {
        max-width: 280px;
        width: 100%;
        height: auto;
        border-radius: 12px;
        background: #fff;
      }
      .pay-note {
        font-size: 0.88rem;
        color: #5a6e90;
        line-height: 1.5;
        margin: 16px 0 0;
      }
      .pay-actions {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 22px;
      }
      .btn-confirm-pay {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 14px 20px;
        border: none;
        border-radius: 14px;
        background: linear-gradient(135deg, #15803d, #22c55e);
        color: #fff;
        font-weight: 800;
        font-size: 0.95rem;
        cursor: pointer;
        box-shadow: 0 6px 18px rgba(21, 128, 61, 0.35);
      }
      .btn-confirm-pay:hover { filter: brightness(1.05); }
      .btn-back-pay {
        text-align: center;
        padding: 12px;
        color: #1a3a70;
        font-weight: 600;
        text-decoration: none;
        border-radius: 12px;
        border: 1.5px solid #c8d8f0;
        background: #fff;
      }
      .btn-back-pay:hover { background: #eef5ff; }
      .pay-warn {
        background: #fff8e1;
        border: 1px solid #ffe082;
        color: #856700;
        padding: 12px 14px;
        border-radius: 12px;
        font-size: 0.88rem;
        margin-top: 12px;
      }
    </style>
  </head>
  <body>
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <div class="container pay-page">
      <div class="pay-card">
        <div class="pay-card-head">
          <h1><i class="fas fa-qrcode" style="margin-right:10px;"></i>Thanh toán chuyển khoản</h1>
          <p><?= pay_h((string) $booking['tour_name']) ?></p>
        </div>
        <div class="pay-card-body">
          <div class="pay-row">
            <span class="k"><i class="fas fa-hashtag"></i> Mã đơn hàng</span>
            <span class="v">#<?= (int) $booking['id'] ?></span>
          </div>
          <div class="pay-row">
            <span class="k"><i class="fas fa-university"></i> Ngân hàng</span>
            <span class="v"><?= pay_h($paymentBankDisplayName) ?></span>
          </div>
          <div class="pay-row">
            <span class="k"><i class="fas fa-piggy-bank"></i> Số tài khoản</span>
            <span class="v"><?= pay_h(preg_replace('/\s+/', '', $paymentAccountNumber)) ?></span>
          </div>
          <div class="pay-row">
            <span class="k"><i class="fas fa-user"></i> Chủ tài khoản</span>
            <span class="v"><?= pay_h($paymentAccountHolder) ?></span>
          </div>
          <div class="pay-row">
            <span class="k"><i class="fas fa-comment-dots"></i> Nội dung CK</span>
            <span class="v" style="font-family:ui-monospace,monospace"><?= pay_h($memo) ?></span>
          </div>
          <div class="pay-row">
            <span class="k"><i class="fas fa-money-bill-wave"></i> Số tiền</span>
            <span class="v pay-amount"><?= pay_h($totalFmt) ?> đ</span>
          </div>

          <?php if ($amountVnd > 0 && $qrUrl !== ''): ?>
            <div class="pay-qr-wrap">
              <img src="<?= pay_h($qrUrl) ?>" alt="Mã QR thanh toán VietQR" width="280" height="280" loading="lazy" decoding="async" />
            </div>
            <p class="pay-note">
              Quét mã bằng ứng dụng ngân hàng. Kiểm tra đúng <strong>số tiền</strong> và <strong>nội dung <?= pay_h($memo) ?></strong>
              trùng mã đơn trước khi xác nhận.
            </p>
          <?php elseif ($amountVnd > 0 && !$qrReady): ?>
            <div class="pay-warn">
              <i class="fas fa-exclamation-triangle"></i>
              Chưa cấu hình đủ BIN/số tài khoản VietQR trong <code>includes/payment_bank.php</code>.
              Vui lòng chuyển khoản thủ công theo thông tin trên.
            </div>
          <?php else: ?>
            <p class="pay-note">Đơn có tổng tiền 0 đ — bạn có thể xác nhận hoàn tất ngay.</p>
          <?php endif; ?>

          <form method="post" action="" class="pay-actions">
            <input type="hidden" name="confirm_payment" value="1" />
            <input type="hidden" name="booking_id" value="<?= (int) $booking['id'] ?>" />
            <button type="submit" class="btn-confirm-pay"
              onclick="return confirm('Bạn đã chuyển khoản thành công và muốn xác nhận đơn #<?= (int) $booking['id'] ?>?');">
              <i class="fas fa-check-circle"></i> Tôi đã quét QR / chuyển khoản — xác nhận thanh toán
            </button>
            <a href="my_bookings.php" class="btn-back-pay"><i class="fas fa-arrow-left"></i> Quay lại đơn của tôi</a>
          </form>
        </div>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>
