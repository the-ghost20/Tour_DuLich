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

$id = (int) ($_GET['id'] ?? 0);
$flash = '';
$flashType = 'success';

$allowedStatus = ['chờ duyệt', 'đã xác nhận', 'đã thanh toán', 'yêu cầu hủy', 'đã hủy'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = (string) ($_POST['status'] ?? '');
    $bid = (int) ($_POST['booking_id'] ?? 0);
    if ($bid > 0 && in_array($newStatus, $allowedStatus, true)) {
        try {
            $pdo->beginTransaction();
            $sel = $pdo->prepare(
                'SELECT id, tour_id, adults, children, status FROM bookings WHERE id = :id LIMIT 1 FOR UPDATE'
            );
            $sel->execute(['id' => $bid]);
            $prev = $sel->fetch(PDO::FETCH_ASSOC);
            if (!$prev) {
                $pdo->rollBack();
                $flash     = 'Không tìm thấy đơn.';
                $flashType = 'danger';
            } else {
                $guests = booking_guest_total((int) $prev['adults'], (int) $prev['children']);
                booking_release_slots_if_cancelled(
                    $pdo,
                    (string) $prev['status'],
                    $newStatus,
                    (int) $prev['tour_id'],
                    $guests
                );
                $pdo->prepare('UPDATE bookings SET status = :s WHERE id = :id')
                    ->execute(['s' => $newStatus, 'id' => $bid]);
                $pdo->commit();
                $flash = 'Đã cập nhật trạng thái đơn.';
            }
        } catch (Throwable) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $flash     = 'Không cập nhật được.';
            $flashType = 'danger';
        }
    } else {
        $flash     = 'Dữ liệu không hợp lệ.';
        $flashType = 'danger';
    }
}

$row = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare(
            "SELECT b.*, u.full_name, u.email, u.phone, u.id AS user_id,
                    t.tour_name, t.destination, t.duration, t.price AS tour_price
             FROM bookings b
             JOIN users u ON u.id = b.user_id
             JOIN tours t ON t.id = b.tour_id
             WHERE b.id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = null;
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function money_fmt(float $n): string
{
    return number_format($n, 0, ',', '.') . ' đ';
}

$pageTitle    = 'Chi tiết đơn #' . ($row ? (string) $row['id'] : '?');
$pageSubtitle = 'Thông tin đặt tour và cập nhật trạng thái';
$activePage   = 'bookings';

$listUrl = htmlspecialchars(app_staff_url('bookings/list.php'), ENT_QUOTES, 'UTF-8');
$topbarActions = <<<HTML
  <a href="{$listUrl}" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách đơn</a>
HTML;

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<?php if ($flash): ?>
  <div class="alert alert-<?= h($flashType) ?>">
    <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i> <?= h($flash) ?>
  </div>
<?php endif; ?>

<?php if (!$row): ?>
  <div class="data-card">
    <p class="cell-muted">Không tìm thấy đơn đặt.</p>
    <a href="list.php" class="btn btn-ghost btn-sm">← Quay lại</a>
  </div>
<?php else: ?>
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:20px">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="fas fa-receipt"></i></div>
      <div class="stat-info">
        <div class="stat-label">Mã đơn</div>
        <div class="stat-value" style="font-size:1.2rem">#<?= (int) $row['id'] ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green"><i class="fas fa-coins"></i></div>
      <div class="stat-info">
        <div class="stat-label">Tổng thanh toán</div>
        <div class="stat-value" style="font-size:1.05rem"><?= money_fmt((float) $row['total_amount']) ?></div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon purple"><i class="fas fa-users"></i></div>
      <div class="stat-info">
        <div class="stat-label">Số khách</div>
        <div class="stat-value"><?= (int) $row['adults'] ?> NL + <?= (int) $row['children'] ?> trẻ</div>
      </div>
    </div>
  </div>

  <div class="data-card" style="margin-bottom:20px">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Khách hàng & tour</div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;padding:0 4px 16px">
      <div>
        <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">Khách</div>
        <div class="cell-bold"><?= h($row['full_name']) ?></div>
        <div><?= h($row['email']) ?></div>
        <div class="cell-muted"><?= h($row['phone']) ?></div>
        <a href="<?= h(app_admin_url('users/detail.php?id=' . (int) $row['user_id'])) ?>" class="btn btn-ghost btn-sm" style="margin-top:8px">Hồ sơ khách</a>
      </div>
      <div>
        <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">Tour</div>
        <div class="cell-bold"><?= h($row['tour_name']) ?></div>
        <div><?= h($row['destination']) ?> · <?= h($row['duration']) ?></div>
        <div class="cell-muted">Đơn giá: <?= money_fmt((float) $row['tour_price']) ?></div>
      </div>
      <div>
        <div class="cell-muted" style="font-size:0.75rem;text-transform:uppercase">Đặt lúc</div>
        <div><?= date('d/m/Y H:i', strtotime((string) $row['created_at'])) ?></div>
        <?php if (!empty($row['paid_at'])): ?>
          <div class="cell-muted" style="margin-top:10px;font-size:0.75rem;text-transform:uppercase">Khách xác nhận thanh toán</div>
          <div class="cell-bold"><?= h(date('d/m/Y H:i', strtotime((string) $row['paid_at']))) ?></div>
        <?php endif; ?>
        <?php
          $depD = $row['departure_date'] ?? null;
          if ($depD): ?>
          <div class="cell-muted" style="margin-top:10px;font-size:0.75rem;text-transform:uppercase">Ngày khởi hành</div>
          <div class="cell-bold"><?= h(date('d/m/Y', strtotime((string) $depD))) ?></div>
        <?php endif; ?>
        <?php if ((float) ($row['holiday_surcharge_amount'] ?? 0) > 0): ?>
          <div class="cell-muted" style="margin-top:8px">Phụ thu lễ/Tết:
            <strong>+<?= money_fmt((float) $row['holiday_surcharge_amount']) ?></strong>
            <?php if ((int) ($row['holiday_surcharge_percent'] ?? 0) > 0): ?>
              (<?= (int) $row['holiday_surcharge_percent'] ?>%)
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($row['coupon_code'])): ?>
          <div class="cell-muted" style="margin-top:8px">Mã KM: <strong><?= h((string) $row['coupon_code']) ?></strong>
            <?php if ((float) ($row['discount_amount'] ?? 0) > 0): ?>
              · Giảm <?= money_fmt((float) $row['discount_amount']) ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($row['cancel_reason'])): ?>
          <div class="cell-muted" style="margin-top:8px">Lý do hủy (khách):</div>
          <div><?= nl2br(h($row['cancel_reason'])) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="data-card">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Trạng thái đơn</div>
        <div class="data-card-sub">Hiện tại: <strong><?= h($row['status']) ?></strong></div>
      </div>
    </div>
    <form method="post" style="padding:0 4px 16px;display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end">
      <input type="hidden" name="booking_id" value="<?= (int) $row['id'] ?>" />
      <div>
        <label class="cell-muted" style="display:block;font-size:0.8rem;margin-bottom:4px">Trạng thái mới</label>
        <select name="status" class="form-control" style="min-width:220px">
          <?php foreach ($allowedStatus as $st): ?>
            <option value="<?= h($st) ?>" <?= (string) $row['status'] === $st ? 'selected' : '' ?>><?= h($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="update_status" value="1" class="btn btn-primary btn-sm">
        <i class="fas fa-save"></i> Lưu
      </button>
    </form>
  </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
