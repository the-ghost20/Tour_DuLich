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

$postsOk = false;
$posts   = [];
try {
    $posts = $pdo->query(
        "SELECT p.*, u.full_name AS author_name
         FROM blog_posts p
         LEFT JOIN users u ON u.id = p.author_id
         ORDER BY p.created_at DESC"
    )->fetchAll();
    $postsOk = true;
} catch (Throwable) {
    $postsOk = false;
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Blog';
$pageSubtitle = 'Danh sách bài viết — thêm / sửa nội dung cho khách';
$activePage   = 'blog';

$topbarActions = $postsOk
    ? '<a href="add.php" class="topbar-btn topbar-btn-primary"><i class="fas fa-plus"></i> Viết bài</a>'
    : '';

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<?php if (!$postsOk): ?>
  <div class="alert alert-warning">
    Chưa có bảng <code>blog_posts</code>. Hãy import <code>database/migrations/001_admin_coupons_blog.sql</code> trong phpMyAdmin.
  </div>
<?php else: ?>
  <div class="data-card">
    <div class="data-card-header">
      <div>
        <div class="data-card-title">Bài viết</div>
        <div class="data-card-sub"><?= count($posts) ?> bài</div>
      </div>
    </div>
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Tiêu đề</th>
            <th>Slug</th>
            <th>Trạng thái</th>
            <th>Xuất bản</th>
            <th>Tác giả</th>
            <th class="cell-right"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($posts)): ?>
            <tr><td colspan="6"><div class="empty-state"><i class="fas fa-newspaper"></i><p>Chưa có bài.</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($posts as $p): ?>
              <tr>
                <td class="cell-bold"><?= h($p['title']) ?></td>
                <td class="cell-muted"><?= h($p['slug']) ?></td>
                <td><?= h($p['status']) === 'published' ? '<span class="badge badge-success">Đã đăng</span>' : '<span class="badge badge-neutral">Nháp</span>' ?></td>
                <td class="cell-muted"><?= $p['published_at'] ? h($p['published_at']) : '—' ?></td>
                <td><?= h($p['author_name'] ?? '—') ?></td>
                <td class="cell-right">
                  <a href="edit.php?id=<?= (int) $p['id'] ?>" class="btn btn-ghost btn-sm btn-icon"><i class="fas fa-pen"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <p class="cell-muted" style="font-size:0.88rem;margin-top:12px">
    Phản hồi form trên trang blog khách xem tại mục <strong>Liên hệ</strong> trong menu nhân viên.
  </p>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
