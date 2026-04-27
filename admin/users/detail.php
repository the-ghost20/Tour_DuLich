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

$id = (int) ($_GET['id'] ?? 0);
$user = null;
$bookings = [];

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id AND role = 'user' LIMIT 1");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        if ($user) {
            $b = $pdo->prepare(
                "SELECT b.id, b.status, b.total_amount, b.created_at, t.tour_name
                 FROM bookings b
                 JOIN tours t ON t.id = b.tour_id
                 WHERE b.user_id = :uid
                 ORDER BY b.created_at DESC"
            );
            $b->execute(['uid' => $id]);
            $bookings = $b->fetchAll();
        }
    } catch (Throwable) {
        $user = null;
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

$pageTitle    = $user ? h($user['full_name']) : 'Không tìm thấy';
$pageSubtitle = 'Chi tiết khách & lịch sử đặt tour';
$activePage   = 'users';

$listUrl = htmlspecialchars(app_admin_url('users/list.php'), ENT_QUOTES, 'UTF-8');
$topbarActions = <<<HTML
  <a href="{$listUrl}" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>
HTML;

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<?php if (!$user): ?>
  <div class="data-card"><p class="cell-muted">Không có khách này.</p><a href="list.php" class="btn btn-ghost btn-sm">← Quay lại</a></div>
<?php else: ?>
  <div class="data-card" style="margin-bottom:20px">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Thông tin</div>
      </div>
      <form method="post" action="toggle_status.php" style="margin:0">
        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>" />
        <input type="hidden" name="redirect" value="<?= h(app_admin_url('users/detail.php?id=' . (int) $user['id'])) ?>" />
        <button type="submit" class="btn btn-warning-ghost btn-sm">
          <i class="fas fa-<?= (int) $user['is_active'] === 1 ? 'lock' : 'unlock' ?>"></i>
          <?= (int) $user['is_active'] === 1 ? 'Khóa tài khoản' : 'Mở khóa' ?>
        </button>
      </form>
    </div>
    <div style="padding:0 8px 16px;display:grid;gap:10px">
      <div><span class="cell-muted">Email:</span> <?= h($user['email']) ?></div>
      <div><span class="cell-muted">SĐT:</span> <?= h($user['phone']) ?></div>
      <div>
        <span class="cell-muted">Trạng thái:</span>
        <?php if ((int) $user['is_active'] === 1): ?>
          <span class="badge badge-success">Hoạt động</span>
        <?php else: ?>
          <span class="badge badge-danger">Đã khóa</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="data-card">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Đơn đặt tour</div>
        <div class="data-card-sub"><?= count($bookings) ?> đơn</div>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Tour</th>
            <th class="cell-right">Tổng</th>
            <th>Trạng thái</th>
            <th>Ngày</th>
            <th class="cell-right"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($bookings)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-receipt"></i><p>Chưa có đơn.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($bookings as $bk): ?>
              <tr>
                <td>#<?= (int) $bk['id'] ?></td>
                <td><?= h($bk['tour_name']) ?></td>
                <td class="cell-right"><?= money_fmt((float) $bk['total_amount']) ?></td>
                <td><?= h($bk['status']) ?></td>
                <td class="cell-muted"><?= date('d/m/Y', strtotime((string) $bk['created_at'])) ?></td>
                <td class="cell-right">
                  <a href="<?= h(app_admin_url('bookings/detail.php?id=' . (int) $bk['id'])) ?>" class="btn btn-ghost btn-sm btn-icon"><i class="fas fa-eye"></i></a>
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
