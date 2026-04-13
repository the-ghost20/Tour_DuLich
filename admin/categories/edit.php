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
$row = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = null;
    }
}

$err = '';
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $sort = (int) ($_POST['sort_order'] ?? 0);
    if ($name === '') {
        $err = 'Nhập tên danh mục.';
    } else {
        if ($slug === '') {
            $slug = vn_slug($name);
        } else {
            $slug = vn_slug($slug);
        }
        try {
            $pdo->prepare(
                'UPDATE categories SET name=:n, slug=:s, sort_order=:o WHERE id=:id'
            )->execute(['n' => $name, 's' => $slug, 'o' => $sort, 'id' => $id]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không lưu được (slug trùng?).';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Sửa danh mục';
$pageSubtitle = '';
$activePage   = 'categories';

$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách</a>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';
?>

<?php if (!$row): ?>
  <div class="data-card"><p class="cell-muted">Không tìm thấy.</p><a href="list.php" class="btn btn-ghost btn-sm">← Quay lại</a></div>
<?php else: ?>
  <div class="data-card" style="max-width:560px">
    <?php if ($err): ?>
      <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= h($err) ?></div>
    <?php endif; ?>
    <form method="post" style="display:grid;gap:14px;padding:8px 4px 16px">
      <div>
        <label class="cell-muted" style="display:block;font-size:0.8rem;margin-bottom:4px">Tên</label>
        <input class="form-control" name="name" required value="<?= h((string) ($_POST['name'] ?? $row['name'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="display:block;font-size:0.8rem;margin-bottom:4px">Slug</label>
        <input class="form-control" name="slug" value="<?= h((string) ($_POST['slug'] ?? $row['slug'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="display:block;font-size:0.8rem;margin-bottom:4px">Thứ tự</label>
        <input class="form-control" type="number" name="sort_order" value="<?= h((string) ($_POST['sort_order'] ?? (string) $row['sort_order'])) ?>" />
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Cập nhật</button>
    </form>
  </div>
<?php endif; ?>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
