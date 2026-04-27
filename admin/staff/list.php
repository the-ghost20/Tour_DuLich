<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ' . app_admin_url('index.php'));
    exit;
}

$rows = [];
try {
    $rows = $pdo->query(
        "SELECT id, full_name, email, phone, is_active, created_at
         FROM users
         WHERE role = 'staff'
         ORDER BY id ASC"
    )->fetchAll();
} catch (Throwable) {
    $rows = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Nhân viên';
$pageSubtitle = 'Quản lý tài khoản nhân viên (đăng nhập khu vực nhân viên)';
$activePage   = 'staff';

$topbarActions = '<a href="add.php" class="topbar-btn topbar-btn-primary"><i class="fas fa-user-plus"></i> Thêm nhân viên</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card">
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>Số điện thoại</th>
          <th>Trạng thái</th>
          <th>Ngày tạo</th>
          <th class="cell-right"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-user-tie"></i><p>Chưa có nhân viên (chỉ admin).</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>#<?= (int) $r['id'] ?></td>
              <td class="cell-bold"><?= h($r['full_name']) ?></td>
              <td><?= h($r['email']) ?></td>
              <td class="cell-muted"><?= h($r['phone']) ?></td>
              <td><?= (int) $r['is_active'] === 1 ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-danger">Khóa</span>' ?></td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime((string) $r['created_at'])) ?></td>
              <td class="cell-right">
                <a href="edit.php?id=<?= (int) $r['id'] ?>" class="btn btn-ghost btn-sm btn-icon"><i class="fas fa-pen"></i></a>
                <form method="post" action="delete.php" style="display:inline;margin:0" onsubmit="return confirm('Khóa tài khoản nhân viên này?')">
                  <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>" />
                  <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon"><i class="fas fa-ban"></i></button>
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
