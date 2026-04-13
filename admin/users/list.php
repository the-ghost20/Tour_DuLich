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

$q = trim((string) ($_GET['q'] ?? ''));
$users = [];

try {
    $sql = "SELECT id, full_name, email, phone, is_active, created_at
            FROM users
            WHERE role = 'user'";
    $params = [];
    if ($q !== '') {
        $sql .= " AND (full_name LIKE :q OR email LIKE :q OR phone LIKE :q)";
        $params['q'] = '%' . $q . '%';
    }
    $sql .= " ORDER BY id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (Throwable) {
    $users = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Khách hàng';
$pageSubtitle = 'Tài khoản vai trò khách (user)';
$activePage   = 'users';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Danh sách khách</div>
      <div class="data-card-sub"><?= count($users) ?> tài khoản</div>
    </div>
    <form method="get" style="display:flex;gap:8px;align-items:center">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Tên, email, SĐT…" />
      </div>
      <button type="submit" class="btn btn-primary btn-sm">Tìm</button>
      <?php if ($q !== ''): ?>
        <a href="list.php" class="btn btn-ghost btn-sm">Xóa</a>
      <?php endif; ?>
    </form>
  </div>

  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Họ tên</th>
          <th>Email</th>
          <th>SĐT</th>
          <th>Trạng thái</th>
          <th>Ngày tạo</th>
          <th class="cell-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7"><div class="empty-state"><i class="fas fa-users"></i><p>Chưa có khách.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td>#<?= (int) $u['id'] ?></td>
              <td class="cell-bold"><?= h($u['full_name']) ?></td>
              <td><?= h($u['email']) ?></td>
              <td class="cell-muted"><?= h($u['phone']) ?></td>
              <td>
                <?php if ((int) $u['is_active'] === 1): ?>
                  <span class="badge badge-success"><span class="badge-dot"></span>Hoạt động</span>
                <?php else: ?>
                  <span class="badge badge-danger"><span class="badge-dot"></span>Đã khóa</span>
                <?php endif; ?>
              </td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime((string) $u['created_at'])) ?></td>
              <td class="cell-right" style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
                <a href="detail.php?id=<?= (int) $u['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Chi tiết"><i class="fas fa-eye"></i></a>
                <form method="post" action="toggle_status.php" style="margin:0">
                  <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>" />
                  <input type="hidden" name="redirect" value="<?= h(app_admin_url('users/list.php')) ?>" />
                  <button type="submit" class="btn btn-warning-ghost btn-sm" title="Bật/tắt tài khoản">
                    <i class="fas fa-<?= (int) $u['is_active'] === 1 ? 'lock' : 'unlock' ?>"></i>
                  </button>
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
