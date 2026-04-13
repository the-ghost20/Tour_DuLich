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

$statusFilter = (string) ($_GET['status'] ?? '');
$q = trim((string) ($_GET['q'] ?? ''));
$allowedStatus = ['chờ duyệt', 'đã xác nhận', 'đã thanh toán', 'yêu cầu hủy', 'đã hủy'];
if ($statusFilter !== '' && !in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = '';
}

$bookings = [];
try {
    $sql = "SELECT b.id, b.total_amount, b.status, b.adults, b.children, b.created_at,
                   u.full_name, u.email, u.phone,
                   t.tour_name, t.destination
            FROM bookings b
            JOIN users u ON u.id = b.user_id
            JOIN tours t ON t.id = b.tour_id
            WHERE 1=1";
    $params = [];
    if ($statusFilter !== '') {
        $sql .= ' AND b.status = :st';
        $params['st'] = $statusFilter;
    }
    if ($q !== '') {
        $sql .= ' AND (t.tour_name LIKE :q OR u.full_name LIKE :q OR u.email LIKE :q)';
        $params['q'] = '%' . $q . '%';
    }
    $sql .= ' ORDER BY b.created_at DESC, b.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (Throwable) {
    $bookings = [];
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function money_fmt(float $n): string
{
    return number_format($n, 0, ',', '.') . ' đ';
}

function status_badge_class(string $st): string
{
    return match ($st) {
        'đã xác nhận', 'đã thanh toán' => 'badge-success',
        'đã hủy' => 'badge-danger',
        'yêu cầu hủy' => 'badge-warning',
        default => 'badge-info',
    };
}

$pageTitle    = 'Đơn đặt tour';
$pageSubtitle = 'Xem và cập nhật trạng thái đơn của khách';
$activePage   = 'bookings';

require dirname(__DIR__, 2) . '/includes/staff_header.php';
?>

<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Bộ lọc</div>
      <div class="data-card-sub">Tìm theo khách, tour, email — lọc theo trạng thái</div>
    </div>
    <form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Khách, tour, email…" />
      </div>
      <select name="status" class="form-control" style="width:auto;padding:8px 12px;font-size:0.82rem" onchange="this.form.submit()">
        <option value="">Mọi trạng thái</option>
        <?php foreach ($allowedStatus as $st): ?>
          <option value="<?= h($st) ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= h($st) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Lọc</button>
      <?php if ($q !== '' || $statusFilter !== ''): ?>
        <a href="list.php" class="btn btn-ghost btn-sm">Xóa lọc</a>
      <?php endif; ?>
    </form>
  </div>

  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Khách</th>
          <th>Tour</th>
          <th class="cell-right">Người</th>
          <th class="cell-right">Tổng tiền</th>
          <th>Trạng thái</th>
          <th>Ngày đặt</th>
          <th class="cell-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($bookings)): ?>
          <tr>
            <td colspan="8">
              <div class="empty-state"><i class="fas fa-inbox"></i><p>Chưa có đơn phù hợp.</p></div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($bookings as $b): ?>
            <?php $st = (string) $b['status']; ?>
            <tr>
              <td class="cell-bold">#<?= (int) $b['id'] ?></td>
              <td>
                <div class="cell-bold"><?= h($b['full_name']) ?></div>
                <div class="cell-muted" style="font-size:0.8rem"><?= h($b['email']) ?></div>
              </td>
              <td>
                <div><?= h($b['tour_name']) ?></div>
                <div class="cell-muted" style="font-size:0.8rem"><?= h($b['destination']) ?></div>
              </td>
              <td class="cell-right"><?= (int) $b['adults'] ?> + <?= (int) $b['children'] ?> trẻ</td>
              <td class="cell-right cell-bold"><?= money_fmt((float) $b['total_amount']) ?></td>
              <td>
                <span class="badge <?= h(status_badge_class($st)) ?>">
                  <span class="badge-dot"></span><?= h($st) ?>
                </span>
              </td>
              <td class="cell-muted"><?= date('d/m/Y H:i', strtotime((string) $b['created_at'])) ?></td>
              <td class="cell-right">
                <a href="detail.php?id=<?= (int) $b['id'] ?>" class="btn btn-ghost btn-sm btn-icon" title="Chi tiết">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="data-card-footer">
    <span>Hiển thị <?= count($bookings) ?> đơn</span>
  </div>
</div>

<?php require dirname(__DIR__, 2) . '/includes/staff_footer.php';
