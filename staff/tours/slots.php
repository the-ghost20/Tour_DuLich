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

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_slot'])) {
    $tid = (int) ($_POST['tour_id'] ?? 0);
    $slots = max(0, (int) ($_POST['available_slots'] ?? 0));
    if ($tid > 0) {
        try {
            $pdo->prepare('UPDATE tours SET available_slots = :s WHERE id = :id')->execute(['s' => $slots, 'id' => $tid]);
            $flash = 'Đã cập nhật số chỗ trống cho tour #' . $tid . '.';
        } catch (Throwable) {
            $flash     = 'Không cập nhật được.';
            $flashType = 'danger';
        }
    }
}

$tours = [];
try {
    $tours = $pdo->query(
        'SELECT id, tour_name, destination, available_slots, status
         FROM tours
         ORDER BY tour_name ASC'
    )->fetchAll();
} catch (Throwable) {
    $tours = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Cập nhật chỗ trống';
$pageSubtitle = 'Số chỗ còn nhận khách cho từng tour (hiển thị trên trang đặt tour)';
$activePage   = 'tour_slots';

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<?php if ($flash): ?>
  <div class="alert alert-<?= h($flashType) ?>"><?= h($flash) ?></div>
<?php endif; ?>

<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Danh sách tour</div>
      <div class="data-card-sub">Nhập số chỗ trống mới rồi bấm Lưu từng dòng</div>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Tour</th>
          <th>Điểm đến</th>
          <th>Trạng thái</th>
          <th class="cell-right">Chỗ trống & lưu</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tours)): ?>
          <tr><td colspan="5"><div class="empty-state"><i class="fas fa-route"></i><p>Chưa có tour.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($tours as $t): ?>
            <tr>
              <td class="cell-bold">#<?= (int) $t['id'] ?></td>
              <td class="cell-bold"><?= h($t['tour_name']) ?></td>
              <td class="cell-muted"><?= h($t['destination']) ?></td>
              <td>
                <?php $st = (string) $t['status']; ?>
                <?= $st === 'hiện'
                    ? '<span class="badge badge-success">Hiển thị</span>'
                    : '<span class="badge badge-neutral">Ẩn</span>' ?>
              </td>
              <td class="cell-right" style="min-width:200px">
                <form method="post" style="display:flex;gap:8px;justify-content:flex-end;align-items:center;margin:0;flex-wrap:wrap">
                  <input type="hidden" name="tour_id" value="<?= (int) $t['id'] ?>" />
                  <input type="number" name="available_slots" class="form-control" style="width:100px;min-width:80px" min="0" value="<?= (int) $t['available_slots'] ?>" />
                  <button type="submit" name="save_slot" value="1" class="btn btn-primary btn-sm"><i class="fas fa-floppy-disk"></i> Lưu</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
