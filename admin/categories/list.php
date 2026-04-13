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
    $rows = $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll();
} catch (Throwable) {
    $rows = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Danh mục tour';
$pageSubtitle = 'Nhóm tour theo vùng / loại hình';
$activePage   = 'categories';

$topbarActions = '<a href="add.php" class="topbar-btn topbar-btn-primary"><i class="fas fa-plus"></i> Thêm danh mục</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<div class="data-card">
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Tên</th>
          <th>Slug</th>
          <th class="cell-right">Thứ tự</th>
          <th>Ngày tạo</th>
          <th class="cell-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6"><div class="empty-state"><i class="fas fa-tags"></i><p>Chưa có danh mục.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>#<?= (int) $r['id'] ?></td>
              <td class="cell-bold"><?= h($r['name']) ?></td>
              <td class="cell-muted"><?= h($r['slug']) ?></td>
              <td class="cell-right"><?= (int) $r['sort_order'] ?></td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime((string) $r['created_at'])) ?></td>
              <td class="cell-right">
                <a href="edit.php?id=<?= (int) $r['id'] ?>" class="btn btn-ghost btn-sm btn-icon"><i class="fas fa-pen"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
