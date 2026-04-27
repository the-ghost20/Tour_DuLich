<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ../../auth/login.php');
    exit;
}

// ── Handle Delete ─────────────────────────────────────
$flashMsg  = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int) $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tours WHERE id = :id");
        $stmt->execute(['id' => $deleteId]);
        $flashMsg = 'Đã xóa tour thành công.';
    } catch (Throwable) {
        $flashMsg  = 'Không thể xóa tour này (có thể đang có đơn đặt liên quan).';
        $flashType = 'danger';
    }
}

// ── Handle Toggle Status ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_id'])) {
    $tid = (int) $_POST['toggle_id'];
    try {
        $curr = $pdo->prepare("SELECT status FROM tours WHERE id=:id");
        $curr->execute(['id' => $tid]);
        $curStatus = $curr->fetchColumn();
        $newStatus = $curStatus === 'hiện' ? 'ẩn' : 'hiện';
        $upd = $pdo->prepare("UPDATE tours SET status=:s WHERE id=:id");
        $upd->execute(['s' => $newStatus, 'id' => $tid]);
        $flashMsg = "Đã chuyển tour sang trạng thái '{$newStatus}'.";
    } catch (Throwable) {
        $flashMsg  = 'Không thể cập nhật trạng thái.';
        $flashType = 'danger';
    }
}

// ── Fetch Tours ───────────────────────────────────────
$search    = trim((string) ($_GET['q'] ?? ''));
$filterStatus = (string) ($_GET['status'] ?? '');
$tours = [];

try {
    $sql = "SELECT t.id, t.tour_name, t.destination, t.duration, t.price,
                   t.available_slots, t.status, t.created_at,
                   COUNT(b.id) AS booking_count
            FROM tours t
            LEFT JOIN bookings b ON b.tour_id = t.id
            WHERE 1=1 ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (t.tour_name LIKE :q OR t.destination LIKE :q)";
        $params['q'] = '%' . $search . '%';
    }
    if ($filterStatus !== '') {
        $sql .= " AND t.status = :st";
        $params['st'] = $filterStatus;
    }
    $sql .= " GROUP BY t.id ORDER BY t.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tours = $stmt->fetchAll();
} catch (Throwable) {
    $tours = [];
}

$toursSoldOutPublic = [];
try {
    $toursSoldOutPublic = $pdo->query(
        "SELECT id, tour_name, destination FROM tours WHERE status = 'hiện' AND available_slots = 0 ORDER BY id DESC"
    )->fetchAll();
} catch (Throwable) {
    $toursSoldOutPublic = [];
}

function h3(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$totalActive = count(array_filter($tours, fn($t) => $t['status'] === 'hiện'));
$totalHidden = count($tours) - $totalActive;

$pageTitle    = 'Quản lý Tour Du lịch';
$pageSubtitle = 'Danh sách tất cả tour trong hệ thống';
$activePage   = 'tours';

$topbarActions = <<<HTML
  <a href="add.php" class="topbar-btn topbar-btn-primary">
    <i class="fas fa-plus"></i> Thêm Tour Mới
  </a>
HTML;

require __DIR__ . '/../../includes/admin_header.php';
?>

<?php if ($flashMsg): ?>
  <div class="alert alert-<?= $flashType ?>">
    <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= h3($flashMsg) ?>
  </div>
<?php endif; ?>

<?php if (!empty($toursSoldOutPublic)): ?>
  <div class="alert alert-warning" style="margin-bottom:20px;border-left:4px solid #f59e0b">
    <div style="font-weight:700;margin-bottom:8px">
      <i class="fas fa-ticket-alt"></i>
      <?= count($toursSoldOutPublic) ?> tour đang <strong>hiển thị</strong> nhưng <strong>đã hết chỗ</strong>
      — khách <strong>không</strong> thấy trên website cho đến khi có chỗ hoặc bạn ẩn tour.
    </div>
    <p class="cell-muted" style="margin:0 0 12px;font-size:0.9rem">
      Thêm chỗ trong Sửa tour / trang cập nhật chỗ (staff), hoặc ẩn tour nếu không mở bán thêm.
    </p>
    <ul style="margin:0;padding-left:1.2rem;line-height:1.65">
      <?php foreach ($toursSoldOutPublic as $so): ?>
        <li>
          <strong><?= h3($so['tour_name']) ?></strong>
          <span class="cell-muted">(#<?= (int) $so['id'] ?> · <?= h3((string) $so['destination']) ?>)</span>
          <a href="edit.php?id=<?= (int) $so['id'] ?>" class="btn btn-ghost btn-sm" style="margin-left:6px">Sửa tour</a>
          <form method="post" style="display:inline;margin:0" onsubmit="return confirm('Ẩn tour khỏi website?');">
            <input type="hidden" name="toggle_id" value="<?= (int) $so['id'] ?>" />
            <button type="submit" class="btn btn-warning-ghost btn-sm" style="margin-left:4px">Ẩn tour</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <p style="margin:14px 0 0">
      <a href="<?= h3(app_staff_url('tours/update_slots.php')) ?>" class="btn btn-primary btn-sm">
        <i class="fas fa-chair"></i> Cập nhật số chỗ (Staff)
      </a>
    </p>
  </div>
<?php endif; ?>

<!-- Mini Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-route"></i></div>
    <div class="stat-info">
      <div class="stat-label">Tổng số tour</div>
      <div class="stat-value"><?= count($tours) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-eye"></i></div>
    <div class="stat-info">
      <div class="stat-label">Đang hiển thị</div>
      <div class="stat-value"><?= $totalActive ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon amber"><i class="fas fa-eye-slash"></i></div>
    <div class="stat-info">
      <div class="stat-label">Đang ẩn</div>
      <div class="stat-value"><?= $totalHidden ?></div>
    </div>
  </div>
</div>

<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Danh sách Tour</div>
      <div class="data-card-sub">Tìm kiếm và quản lý các tour du lịch</div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
      <!-- Search -->
      <form method="get" style="display:flex;gap:8px;align-items:center">
        <div class="search-bar">
          <i class="fas fa-search"></i>
          <input type="text" name="q" value="<?= h3($search) ?>" placeholder="Tìm tên, điểm đến…" />
        </div>
        <select name="status" class="form-control" style="width:auto;padding:8px 12px;font-size:0.82rem" onchange="this.form.submit()">
          <option value="">Tất cả trạng thái</option>
          <option value="hiện" <?= $filterStatus === 'hiện' ? 'selected' : '' ?>>Đang hiện</option>
          <option value="ẩn"   <?= $filterStatus === 'ẩn'   ? 'selected' : '' ?>>Đang ẩn</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Tìm</button>
        <?php if ($search || $filterStatus): ?>
          <a href="list.php" class="btn btn-ghost btn-sm"><i class="fas fa-times"></i></a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Tên Tour</th>
          <th>Điểm đến</th>
          <th>Thời lượng</th>
          <th class="cell-right">Đơn giá</th>
          <th class="cell-right">Chỗ trống</th>
          <th class="cell-right">Đơn đặt</th>
          <th>Trạng thái</th>
          <th>Ngày tạo</th>
          <th class="cell-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tours)): ?>
          <tr>
            <td colspan="10">
              <div class="empty-state">
                <i class="fas fa-route"></i>
                <p>Chưa có tour nào<?= $search ? ' khớp với "' . h3($search) . '"' : '' ?>.</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($tours as $tour): ?>
            <?php
              $isActive = $tour['status'] === 'hiện';
              $isSoldOutPublic = $isActive && (int) $tour['available_slots'] === 0;
              $statusBadge = $isActive ? 'badge-success' : 'badge-neutral';
              $toggleLabel = $isActive ? 'Ẩn tour' : 'Hiện tour';
              $toggleIcon  = $isActive ? 'eye-slash' : 'eye';
            ?>
            <tr<?= $isSoldOutPublic ? ' style="background:#fffbeb"' : '' ?>>
              <td><span class="cell-muted">#<?= (int)$tour['id'] ?></span></td>
              <td>
                <div class="cell-bold" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?= h3($tour['tour_name']) ?>">
                  <?= h3($tour['tour_name']) ?>
                </div>
              </td>
              <td>
                <span style="display:inline-flex;align-items:center;gap:6px">
                  <i class="fas fa-map-marker-alt" style="color:#6366f1;font-size:0.75rem"></i>
                  <?= h3($tour['destination']) ?>
                </span>
              </td>
              <td><span class="badge badge-info"><?= h3($tour['duration']) ?></span></td>
              <td class="cell-right cell-bold"><?= number_format((float)$tour['price'],0,',','.') ?> đ</td>
              <td class="cell-right">
                <span class="<?= (int)$tour['available_slots'] === 0 ? 'cell-muted' : 'cell-bold' ?>">
                  <?= (int)$tour['available_slots'] ?>
                </span>
              </td>
              <td class="cell-right">
                <span class="badge badge-info"><?= (int)$tour['booking_count'] ?></span>
              </td>
              <td>
                <span class="badge <?= $statusBadge ?>">
                  <span class="badge-dot"></span>
                  <?= h3($tour['status']) ?>
                </span>
                <?php if ($isSoldOutPublic): ?>
                  <div style="margin-top:6px"><span class="badge badge-danger">Hết chỗ</span></div>
                <?php endif; ?>
              </td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime($tour['created_at'])) ?></td>
              <td class="cell-right">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                  <a href="edit.php?id=<?= (int)$tour['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Chỉnh sửa">
                    <i class="fas fa-pen"></i>
                  </a>
                  <!-- Toggle Status -->
                  <form method="post" style="margin:0">
                    <input type="hidden" name="toggle_id" value="<?= (int)$tour['id'] ?>" />
                    <button type="submit" class="btn btn-warning-ghost btn-sm btn-icon" title="<?= h3($toggleLabel) ?>">
                      <i class="fas fa-<?= $toggleIcon ?>"></i>
                    </button>
                  </form>
                  <!-- Delete -->
                  <form method="post" style="margin:0" onsubmit="return confirm('Bạn có chắc muốn xóa tour này?')">
                    <input type="hidden" name="delete_id" value="<?= (int)$tour['id'] ?>" />
                    <button type="submit" class="btn btn-danger-ghost btn-sm btn-icon" title="Xóa">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="data-card-footer">
    <span>Hiển thị <?= count($tours) ?> tour</span>
    <a href="add.php" class="btn btn-primary btn-sm">
      <i class="fas fa-plus"></i> Thêm Tour Mới
    </a>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
