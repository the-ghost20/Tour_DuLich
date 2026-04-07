<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || (string) ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../frontend/login.php');
    exit;
}

$tours = [];
$errorMessage = null;

try {
    $stmt = $pdo->query(
        "SELECT id, tour_name, destination, duration, price, available_slots, status, created_at
         FROM tours
         ORDER BY id DESC"
    );
    $tours = $stmt->fetchAll();
} catch (Throwable $exception) {
    $errorMessage = 'Không thể tải danh sách tour.';
    $tours = [];
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Quản lý Tour</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <style>
      body { background: #f6f8fb; }
      .page-header { display:flex; align-items:center; justify-content:space-between; gap:12px; }
      .table thead th { white-space: nowrap; }
      .badge-status { font-weight: 600; }
    </style>
  </head>
  <body>
    <div class="container py-4">
      <div class="page-header mb-3">
        <div>
          <h1 class="h3 mb-1">Quản lý Tour</h1>
          <div class="text-muted">Danh sách tất cả tour trong hệ thống</div>
        </div>
        <div class="d-flex gap-2">
          <a href="tour_create.php" class="btn btn-primary">Thêm Tour Mới</a>
          <a href="../frontend/index.php" class="btn btn-outline-secondary">Về trang người dùng</a>
        </div>
      </div>

      <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= h($errorMessage) ?></div>
      <?php endif; ?>

      <div class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>ID</th>
                <th>Tên tour</th>
                <th>Điểm đến</th>
                <th>Thời lượng</th>
                <th class="text-end">Giá</th>
                <th class="text-end">Chỗ trống</th>
                <th>Trạng thái</th>
                <th>Ngày tạo</th>
                <th class="text-end">Sửa</th>
                <th class="text-end">Xóa</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($tours)): ?>
                <tr>
                  <td colspan="10" class="text-center py-4 text-muted">
                    Chưa có tour nào.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($tours as $tour): ?>
                  <?php
                    $status = (string) $tour['status'];
                    $badgeClass = $status === 'hiện' ? 'bg-success' : 'bg-secondary';
                    $priceText = number_format((float) $tour['price'], 0, ',', '.') . ' đ';
                  ?>
                  <tr>
                    <td><?= (int) $tour['id'] ?></td>
                    <td class="fw-semibold"><?= h($tour['tour_name']) ?></td>
                    <td><?= h($tour['destination']) ?></td>
                    <td><?= h($tour['duration']) ?></td>
                    <td class="text-end"><?= h($priceText) ?></td>
                    <td class="text-end"><?= (int) $tour['available_slots'] ?></td>
                    <td>
                      <span class="badge <?= h($badgeClass) ?> badge-status">
                        <?= h($status) ?>
                      </span>
                    </td>
                    <td><?= h($tour['created_at']) ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary" href="tour_edit.php?id=<?= (int) $tour['id'] ?>">
                        Sửa
                      </a>
                    </td>
                    <td class="text-end">
                      <a
                        class="btn btn-sm btn-outline-danger"
                        href="tour_delete.php?id=<?= (int) $tour['id'] ?>"
                        onclick="return confirm('Bạn có chắc muốn xóa tour này?');"
                      >
                        Xóa
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </body>
</html>

