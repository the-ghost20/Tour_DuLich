<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId  = (int) $_SESSION['user_id'];
$flash   = null;
$flashType = 'success';

// ── Handle POST actions ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = (string) ($_POST['action']    ?? '');
    $bookingId = (int)    ($_POST['booking_id'] ?? 0);

    // Verify this booking belongs to the current user
    $stmtCheck = $pdo->prepare(
        "SELECT id, status FROM bookings WHERE id = :id AND user_id = :uid LIMIT 1"
    );
    $stmtCheck->execute(['id' => $bookingId, 'uid' => $userId]);
    $row = $stmtCheck->fetch();

    if (!$row) {
        $flash     = 'Không tìm thấy đơn đặt tour.';
        $flashType = 'error';
    } elseif ((string) $row['status'] !== 'chờ duyệt') {
        $flash     = 'Hành động không hợp lệ với trạng thái hiện tại.';
        $flashType = 'error';
    } elseif ($action === 'pay') {
        $stmtPay = $pdo->prepare(
            "UPDATE bookings SET status = 'đã thanh toán' WHERE id = :id AND user_id = :uid"
        );
        $stmtPay->execute(['id' => $bookingId, 'uid' => $userId]);
        $flash = 'Thanh toán thành công! Đơn đặt tour của bạn đã được cập nhật.';
    } elseif ($action === 'cancel') {
        $reason = trim((string) ($_POST['cancel_reason'] ?? ''));
        if ($reason === '') {
            $flash     = 'Vui lòng nhập lý do hủy tour.';
            $flashType = 'error';
        } else {
            $stmtCancel = $pdo->prepare(
                "UPDATE bookings SET status = 'yêu cầu hủy', cancel_reason = :reason
                 WHERE id = :id AND user_id = :uid"
            );
            $stmtCancel->execute([
                'reason' => $reason,
                'id'     => $bookingId,
                'uid'    => $userId,
            ]);
            $flash = 'Yêu cầu hủy tour đã được ghi nhận. Chúng tôi sẽ liên hệ bạn sớm.';
        }
    }

    // PRG pattern – redirect to avoid re-submit on refresh
    if ($flashType === 'success') {
        header('Location: my_bookings.php?msg=' . urlencode($flash));
        exit;
    }
}

// Show success message from redirect
if (isset($_GET['msg'])) {
    $flash     = htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8');
    $flashType = 'success';
}

// ── Fetch bookings ────────────────────────────────────────────────────────────
$bookings = [];
try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.adults, b.children, b.total_amount, b.status,
                b.cancel_reason, b.created_at,
                t.tour_name, t.destination, t.duration
         FROM bookings b
         INNER JOIN tours t ON t.id = b.tour_id
         WHERE b.user_id = :user_id
         ORDER BY b.created_at DESC"
    );
    $stmt->execute(['user_id' => $userId]);
    $bookings = $stmt->fetchAll();
} catch (Throwable $e) {
    $bookings = [];
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusMeta(string $status): array {
    return match ($status) {
        'chờ duyệt'    => ['class' => 'status-pending',   'label' => 'Chờ duyệt',    'icon' => 'fa-clock'],
        'đã xác nhận'  => ['class' => 'status-confirmed', 'label' => 'Đã xác nhận',  'icon' => 'fa-check-circle'],
        'đã thanh toán'=> ['class' => 'status-paid',      'label' => 'Đã thanh toán','icon' => 'fa-money-bill-wave'],
        'yêu cầu hủy'  => ['class' => 'status-cancel-req','label' => 'Yêu cầu hủy', 'icon' => 'fa-hourglass-half'],
        'đã hủy'       => ['class' => 'status-cancelled', 'label' => 'Đã hủy',       'icon' => 'fa-times-circle'],
        default        => ['class' => 'status-pending',   'label' => $status,         'icon' => 'fa-question-circle'],
    };
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quản lý đặt tour - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
      /* ── Page layout ── */
      .mb-page { padding: 40px 0 70px; }

      .mb-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 28px;
      }
      .mb-header-left h1 {
        font-size: 1.9rem;
        font-weight: 800;
        color: #10213f;
        margin: 0 0 4px;
      }
      .mb-header-left p { color: #6b7fa0; margin: 0; font-size: 0.95rem; }

      .mb-back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 999px;
        background: #fff;
        border: 1.5px solid #c8d8f0;
        color: #1a3a70;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        text-decoration: none;
      }
      .mb-back-btn:hover { background: #eef5ff; border-color: #2196f3; color: #2196f3; }

      /* ── Flash messages ── */
      .mb-flash {
        padding: 14px 18px;
        border-radius: 14px;
        margin-bottom: 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
      }
      .mb-flash.flash-success { background: #ecfdf5; color: #0d6e3a; border: 1px solid #b2f0d3; }
      .mb-flash.flash-error   { background: #fff0f0; color: #991b1b; border: 1px solid #fcd5d5; }

      /* ── Cards grid ── */
      .bookings-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 22px;
      }

      .booking-card {
        background: #fff;
        border-radius: 20px;
        border: 1px solid #e4ecf9;
        box-shadow: 0 4px 20px rgba(15, 38, 90, 0.07);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
      }
      .booking-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 32px rgba(15, 38, 90, 0.13);
      }

      .booking-card-top {
        padding: 18px 20px 14px;
        background: linear-gradient(135deg, #eef5ff 0%, #f4f8ff 100%);
        border-bottom: 1px solid #dde8f8;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 10px;
      }
      .booking-card-top .tour-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f2552;
        margin: 0 0 4px;
        line-height: 1.35;
      }
      .booking-card-top .tour-meta {
        font-size: 0.82rem;
        color: #7b92bc;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }
      .booking-card-top .tour-meta span { display: flex; align-items: center; gap: 4px; }

      /* Status pill */
      .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 11px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.78rem;
        white-space: nowrap;
        flex-shrink: 0;
      }
      .status-pending    { background: #fff8e1; color: #856700; }
      .status-confirmed  { background: #e8f8ee; color: #1a7a36; }
      .status-paid       { background: #e3f0ff; color: #1255b5; }
      .status-cancel-req { background: #fff3e0; color: #b25e00; }
      .status-cancelled  { background: #ffebee; color: #ae1a1a; }

      /* Card body */
      .booking-card-body {
        padding: 16px 20px;
        flex: 1;
      }
      .booking-info-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 0.9rem;
      }
      .booking-info-row .label { color: #7b92bc; font-weight: 500; }
      .booking-info-row .value { color: #12264f; font-weight: 600; }
      .booking-total {
        margin-top: 12px;
        padding-top: 12px;
        border-top: 1px dashed #d8e4f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      .booking-total .label { color: #7b92bc; font-size: 0.88rem; }
      .booking-total .amount {
        font-size: 1.2rem;
        font-weight: 800;
        color: #2196f3;
      }

      /* Cancel reason note */
      .cancel-reason-note {
        background: #fff5f5;
        border-left: 3px solid #f87171;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 0.83rem;
        color: #7a1a1a;
        margin-top: 10px;
        font-style: italic;
      }

      /* Card footer / actions */
      .booking-card-footer {
        padding: 14px 20px;
        background: #f8faff;
        border-top: 1px solid #e4ecf9;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
      }

      .btn-pay {
        flex: 1;
        padding: 10px 16px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #1a73e8, #00bcd4);
        color: #fff;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
        box-shadow: 0 4px 12px rgba(33, 150, 243, 0.25);
      }
      .btn-pay:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(33,150,243,0.35); }

      .btn-cancel-open {
        flex: 1;
        padding: 10px 16px;
        border: 1.5px solid #ef4444;
        border-radius: 12px;
        background: #fff;
        color: #cd1515;
        font-weight: 700;
        font-size: 0.88rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: all 0.2s;
      }
      .btn-cancel-open:hover { background: #fff0f0; }

      .action-badge {
        font-size: 0.82rem;
        color: #7b92bc;
        font-style: italic;
        display: flex;
        align-items: center;
        gap: 6px;
      }

      /* ── Empty state ── */
      .mb-empty {
        text-align: center;
        padding: 60px 20px;
        color: #8a9fcb;
      }
      .mb-empty i { font-size: 3.5rem; margin-bottom: 16px; opacity: 0.4; }
      .mb-empty p { font-size: 1.05rem; margin: 0 0 20px; }
      .mb-empty a {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        background: linear-gradient(135deg, #1a73e8, #00bcd4);
        color: #fff;
        border-radius: 12px;
        font-weight: 700;
        text-decoration: none;
        transition: transform 0.2s;
      }
      .mb-empty a:hover { transform: translateY(-2px); }

      /* ── Cancel Modal ── */
      .cancel-modal-backdrop {
        position: fixed; inset: 0;
        background: rgba(10, 25, 60, 0.55);
        backdrop-filter: blur(6px);
        display: flex; align-items: center; justify-content: center;
        z-index: 2000;
        opacity: 0; pointer-events: none;
        transition: opacity 0.2s ease;
      }
      .cancel-modal-backdrop.open { opacity: 1; pointer-events: auto; }

      .cancel-modal {
        background: #fff;
        border-radius: 22px;
        padding: 32px 28px 24px;
        width: 100%; max-width: 460px;
        box-shadow: 0 30px 70px rgba(10, 25, 70, 0.25);
        position: relative;
        transform: scale(0.95);
        transition: transform 0.2s ease;
      }
      .cancel-modal-backdrop.open .cancel-modal { transform: scale(1); }

      .cancel-modal-close {
        position: absolute; top: 14px; right: 16px;
        background: #f0f4fa; border: none; border-radius: 50%;
        width: 32px; height: 32px;
        font-size: 0.9rem; color: #5a6e90;
        cursor: pointer; display: flex; align-items: center; justify-content: center;
        transition: background 0.2s;
      }
      .cancel-modal-close:hover { background: #dce6f8; }

      .cancel-modal h2 {
        font-size: 1.4rem; font-weight: 800;
        color: #0f2552; margin: 0 0 6px;
      }
      .cancel-modal .modal-sub {
        color: #7088b5; font-size: 0.9rem; margin: 0 0 20px;
      }
      .cancel-modal .modal-label {
        font-weight: 600; color: #2a3d60;
        font-size: 0.9rem; margin-bottom: 8px; display: block;
      }
      .cancel-modal textarea {
        width: 100%; border-radius: 12px;
        border: 2px solid #d6e4f8;
        padding: 12px 14px;
        font-size: 0.93rem;
        color: #1a2942;
        background: #f7faff;
        resize: vertical; min-height: 110px;
        outline: none;
        transition: border-color 0.2s;
      }
      .cancel-modal textarea:focus { border-color: #ef4444; background: #fff; }

      .cancel-modal-btns {
        display: flex; gap: 10px; margin-top: 18px;
      }
      .cancel-modal-btns .btn-confirm-cancel {
        flex: 1; padding: 12px;
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none; border-radius: 12px;
        color: #fff; font-weight: 700; font-size: 0.93rem;
        cursor: pointer;
        box-shadow: 0 6px 16px rgba(220,38,38,0.3);
        transition: transform 0.2s;
      }
      .cancel-modal-btns .btn-confirm-cancel:hover { transform: translateY(-1px); }
      .cancel-modal-btns .btn-dismiss {
        flex: 1; padding: 12px;
        background: #f0f4fa; border: none;
        border-radius: 12px; color: #5a6e8e;
        font-weight: 600; font-size: 0.93rem;
        cursor: pointer; transition: background 0.2s;
      }
      .cancel-modal-btns .btn-dismiss:hover { background: #dce6f8; }

      @media (max-width: 600px) {
        .bookings-grid { grid-template-columns: 1fr; }
        .mb-header { flex-direction: column; align-items: flex-start; }
      }
    </style>
  </head>
  <body>
    <?php
      $activePage = '';
      require __DIR__ . '/../includes/header.php';
    ?>

    <div class="container mb-page">

      <!-- Page header -->
      <div class="mb-header">
        <div class="mb-header-left">
          <h1><i class="fas fa-receipt" style="color:#2196f3;margin-right:10px;"></i>Quản lý đặt tour</h1>
          <p>Xem và quản lý tất cả đơn đặt tour của bạn.</p>
        </div>
        <a href="tours.php" class="mb-back-btn">
          <i class="fas fa-compass"></i> Khám phá thêm tour
        </a>
      </div>

      <!-- Flash message -->
      <?php if ($flash): ?>
        <div class="mb-flash flash-<?= $flashType ?>">
          <i class="fas <?= $flashType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
          <?= $flash ?>
        </div>
      <?php endif; ?>

      <!-- Booking cards -->
      <?php if (empty($bookings)): ?>
        <div class="mb-empty">
          <i class="fas fa-suitcase-rolling"></i>
          <p>Bạn chưa có đơn đặt tour nào.</p>
          <a href="tours.php"><i class="fas fa-search"></i> Tìm tour ngay</a>
        </div>
      <?php else: ?>
        <div class="bookings-grid">
          <?php foreach ($bookings as $booking):
            $bId      = (int) $booking['id'];
            $status   = (string) $booking['status'];
            $meta     = statusMeta($status);
            $total    = number_format((float) $booking['total_amount'], 0, ',', '.');
            $dateRaw  = (string) $booking['created_at'];
            $dateStr  = date('d/m/Y', strtotime($dateRaw));
            $guests   = (int)$booking['adults'] + (int)$booking['children'];
            $cancelReason = (string) ($booking['cancel_reason'] ?? '');
          ?>
          <div class="booking-card">

            <!-- Top: tour info + status -->
            <div class="booking-card-top">
              <div>
                <p class="tour-name"><?= htmlspecialchars((string) $booking['tour_name'], ENT_QUOTES, 'UTF-8') ?></p>
                <div class="tour-meta">
                  <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars((string) $booking['destination'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span><i class="fas fa-calendar-day"></i> <?= htmlspecialchars((string) $booking['duration'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </div>
              <span class="status-pill <?= $meta['class'] ?>">
                <i class="fas <?= $meta['icon'] ?>"></i>
                <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>

            <!-- Body: booking details -->
            <div class="booking-card-body">
              <div class="booking-info-row">
                <span class="label"><i class="fas fa-hashtag"></i> Mã đơn</span>
                <span class="value">#<?= $bId ?></span>
              </div>
              <div class="booking-info-row">
                <span class="label"><i class="fas fa-calendar-check"></i> Ngày đặt</span>
                <span class="value"><?= $dateStr ?></span>
              </div>
              <div class="booking-info-row">
                <span class="label"><i class="fas fa-users"></i> Số lượng</span>
                <span class="value">
                  <?= (int)$booking['adults'] ?> người lớn
                  <?php if ((int)$booking['children'] > 0): ?>
                    + <?= (int)$booking['children'] ?> trẻ em
                  <?php endif; ?>
                </span>
              </div>
              <div class="booking-total">
                <span class="label">Tổng tiền</span>
                <span class="amount"><?= $total ?> đ</span>
              </div>

              <!-- Cancel reason note -->
              <?php if ($cancelReason !== '' && in_array($status, ['yêu cầu hủy', 'đã hủy'], true)): ?>
                <div class="cancel-reason-note">
                  <i class="fas fa-comment-slash"></i>
                  Lý do hủy: <?= htmlspecialchars($cancelReason, ENT_QUOTES, 'UTF-8') ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Footer: action buttons -->
            <div class="booking-card-footer">
              <?php if ($status === 'chờ duyệt'): ?>

                <!-- Pay button -->
                <form method="post" action="" style="flex:1;">
                  <input type="hidden" name="action"     value="pay" />
                  <input type="hidden" name="booking_id" value="<?= $bId ?>" />
                  <button type="submit" class="btn-pay"
                    onclick="return confirm('Xác nhận thanh toán đơn #<?= $bId ?>?')">
                    <i class="fas fa-credit-card"></i> Thanh toán
                  </button>
                </form>

                <!-- Cancel button (opens modal) -->
                <button class="btn-cancel-open"
                  data-booking-id="<?= $bId ?>"
                  onclick="openCancelModal(<?= $bId ?>)">
                  <i class="fas fa-ban"></i> Hủy tour
                </button>

              <?php elseif ($status === 'đã thanh toán'): ?>
                <div class="action-badge"><i class="fas fa-check-circle" style="color:#2196f3;"></i> Đã thanh toán</div>

              <?php elseif ($status === 'đã xác nhận'): ?>
                <div class="action-badge"><i class="fas fa-thumbs-up" style="color:#22c55e;"></i> Đã xác nhận bởi hệ thống</div>

              <?php elseif ($status === 'yêu cầu hủy'): ?>
                <div class="action-badge"><i class="fas fa-hourglass-half" style="color:#f59e0b;"></i> Đang xử lý yêu cầu hủy</div>

              <?php elseif ($status === 'đã hủy'): ?>
                <div class="action-badge"><i class="fas fa-times-circle" style="color:#ef4444;"></i> Chuyến đi đã bị hủy</div>

              <?php else: ?>
                <div class="action-badge"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
            </div>

          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- ── Cancel Modal ── -->
    <div class="cancel-modal-backdrop" id="cancelModalBackdrop" onclick="closeCancelModalOnBackdrop(event)">
      <div class="cancel-modal" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
        <button class="cancel-modal-close" onclick="closeCancelModal()" aria-label="Đóng">
          <i class="fas fa-times"></i>
        </button>
        <h2 id="cancelModalTitle"><i class="fas fa-ban" style="color:#ef4444;margin-right:8px;"></i>Hủy tour</h2>
        <p class="modal-sub">Vui lòng cho chúng tôi biết lý do bạn muốn hủy chuyến đi này.</p>

        <form method="post" action="" id="cancelForm">
          <input type="hidden" name="action"     value="cancel" />
          <input type="hidden" name="booking_id" id="cancelBookingId" value="" />

          <label for="cancelReason" class="modal-label">Lý do hủy <span style="color:#ef4444;">*</span></label>
          <textarea id="cancelReason" name="cancel_reason"
            placeholder="Nhập lý do hủy tour của bạn..." required></textarea>

          <div class="cancel-modal-btns">
            <button type="button" class="btn-dismiss" onclick="closeCancelModal()">
              Quay lại
            </button>
            <button type="submit" class="btn-confirm-cancel">
              <i class="fas fa-check"></i> Xác nhận hủy
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>

    <script src="../assets/js/main.js"></script>
    <script>
      function openCancelModal(bookingId) {
        document.getElementById('cancelBookingId').value = bookingId;
        document.getElementById('cancelReason').value    = '';
        document.getElementById('cancelModalBackdrop').classList.add('open');
        document.body.style.overflow = 'hidden';
        setTimeout(() => document.getElementById('cancelReason').focus(), 100);
      }

      function closeCancelModal() {
        document.getElementById('cancelModalBackdrop').classList.remove('open');
        document.body.style.overflow = '';
      }

      function closeCancelModalOnBackdrop(e) {
        if (e.target === document.getElementById('cancelModalBackdrop')) {
          closeCancelModal();
        }
      }

      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeCancelModal();
      });
    </script>
  </body>
</html>
