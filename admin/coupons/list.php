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

$tableOk = false;
$rows    = [];
try {
    $rows    = $pdo->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll();
    $tableOk = true;
} catch (Throwable) {
    $tableOk = false;
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Mã giảm giá';
$pageSubtitle = 'Quản lý coupon (cần import migration nếu chưa có bảng)';
$activePage   = 'coupons';

$topbarActions = $tableOk
    ? '<a href="add.php" class="topbar-btn topbar-btn-primary"><i class="fas fa-plus"></i> Thêm mã</a>'
    : '';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<?php if (!$tableOk): ?>
  <div class="alert alert-warning">
    <i class="fas fa-database"></i> Chưa có bảng <code>coupons</code>. Chạy file:
    <code>database/migrations/001_admin_coupons_blog.sql</code> trong MySQL (database <code>tour_dulich</code>).
  </div>
<?php else: ?>
  <div class="data-card">
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Mã</th>
            <th>Loại</th>
            <th class="cell-right">Giá trị</th>
            <th class="cell-right">Đơn tối thiểu</th>
            <th class="cell-right">Dùng</th>
            <th>Hạn</th>
            <th>Trạng thái</th>
            <th class="cell-right"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8"><div class="empty-state"><i class="fas fa-ticket-alt"></i><p>Chưa có mã.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($rows as $c): ?>
              <tr>
                <td class="cell-bold"><?= h($c['code']) ?></td>
                <td><?= h($c['discount_type']) === 'percent' ? '%' : 'Cố định' ?></td>
                <td class="cell-right"><?= h($c['discount_type']) === 'percent' ? (float) $c['discount_value'] . '%' : number_format((float) $c['discount_value'], 0, ',', '.') . ' đ' ?></td>
                <td class="cell-right"><?= number_format((float) $c['min_order_amount'], 0, ',', '.') ?> đ</td>
                <td class="cell-right"><?= (int) $c['used_count'] ?><?= $c['max_uses'] !== null ? ' / ' . (int) $c['max_uses'] : '' ?></td>
                <td class="cell-muted"><?= $c['expires_at'] ? h($c['expires_at']) : '—' ?></td>
                <td><?= (int) $c['is_active'] === 1 ? '<span class="badge badge-success">Bật</span>' : '<span class="badge badge-neutral">Tắt</span>' ?></td>
                <td class="cell-right">
                  <a href="edit.php?id=<?= (int) $c['id'] ?>" class="btn btn-ghost btn-sm btn-icon"><i class="fas fa-pen"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
