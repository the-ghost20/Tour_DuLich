<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ' . app_url('auth/login.php'));
    exit;
}

$rows = [];
try {
    $rows = $pdo->query(
        "SELECT b.id, b.total_amount, b.cancel_reason, b.created_at,
                u.full_name, u.email, t.tour_name
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN tours t ON t.id = b.tour_id
         WHERE b.status = 'yêu cầu hủy'
         ORDER BY b.updated_at DESC, b.id DESC"
    )->fetchAll();
} catch (Throwable) {
    $rows = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function money_fmt(float $n): string
{
    return number_format($n, 0, ',', '.') . ' đ';
}

$pageTitle    = 'Yêu cầu hủy tour';
$pageSubtitle = 'Duyệt hủy hoặc giữ đơn (từ chối yêu cầu)';
$activePage   = 'cancel_requests';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card">
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Khách</th>
          <th>Tour</th>
          <th class="cell-right">Tổng</th>
          <th>Lý do</th>
          <th>Ngày đặt</th>
          <th class="cell-right">Xử lý</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-check-circle"></i><p>Không có yêu cầu hủy chờ xử lý.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($rows as $b): ?>
            <tr>
              <td>#<?= (int) $b['id'] ?></td>
              <td>
                <div class="cell-bold"><?= h($b['full_name']) ?></div>
                <div class="cell-muted" style="font-size:0.8rem"><?= h($b['email']) ?></div>
              </td>
              <td><?= h($b['tour_name']) ?></td>
              <td class="cell-right"><?= money_fmt((float) $b['total_amount']) ?></td>
              <td style="max-width:220px;font-size:0.88rem"><?= nl2br(h((string) $b['cancel_reason'])) ?></td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime((string) $b['created_at'])) ?></td>
              <td class="cell-right">
                <form method="post" action="process.php" style="display:flex;flex-direction:column;gap:6px;align-items:flex-end">
                  <input type="hidden" name="booking_id" value="<?= (int) $b['id'] ?>" />
                  <button type="submit" name="action" value="approve" class="btn btn-danger-ghost btn-sm">Đồng ý hủy</button>
                  <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap">
                    <select name="revert_status" class="form-control" style="width:auto;padding:4px 8px;font-size:0.75rem">
                      <option value="đã xác nhận">Giữ: đã xác nhận</option>
                      <option value="đã thanh toán">Giữ: đã thanh toán</option>
                    </select>
                    <button type="submit" name="action" value="reject" class="btn btn-primary btn-sm">Từ chối</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
