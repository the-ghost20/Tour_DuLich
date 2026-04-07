<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$bookings = [];

try {
    $stmt = $pdo->prepare(
        "SELECT b.id, b.adults, b.children, b.total_amount, b.status, b.created_at,
                t.tour_name, t.destination, t.duration
         FROM bookings b
         INNER JOIN tours t ON t.id = b.tour_id
         WHERE b.user_id = :user_id
         ORDER BY b.created_at DESC"
    );
    $stmt->execute(['user_id' => $userId]);
    $bookings = $stmt->fetchAll();
} catch (Throwable $exception) {
    $bookings = [];
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Lịch sử đặt tour - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <style>
      .bookings-page {
        padding: 40px 0 60px;
      }
      .bookings-card {
        background: linear-gradient(160deg, #ffffff 0%, #f7fbff 100%);
        border: 1px solid rgba(33, 150, 243, 0.14);
        border-radius: 22px;
        box-shadow: 0 20px 50px rgba(13, 36, 79, 0.14);
        padding: 24px;
      }
      .bookings-title {
        margin: 0;
        font-size: 2rem;
        color: #16233b;
      }
      .bookings-subtitle {
        margin: 8px 0 20px;
        color: #607089;
      }
      .bookings-table-wrap {
        overflow-x: auto;
        border-radius: 14px;
        border: 1px solid rgba(22, 101, 216, 0.15);
      }
      .bookings-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 860px;
        background: #fff;
      }
      .bookings-table th,
      .bookings-table td {
        padding: 12px 14px;
        text-align: left;
        border-bottom: 1px solid #e9eef6;
        font-size: 0.95rem;
      }
      .bookings-table th {
        background: #eef5ff;
        color: #1a3a70;
        font-weight: 700;
      }
      .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.82rem;
      }
      .status-pill.status-pending {
        background: #fff6df;
        color: #9b6b00;
      }
      .status-pill.status-confirmed {
        background: #e9f9ef;
        color: #1c7b34;
      }
      .status-pill.status-cancelled {
        background: #ffebee;
        color: #ad1f2d;
      }
      .bookings-empty {
        padding: 30px 20px;
        text-align: center;
        color: #607089;
      }
    </style>
  </head>
  <body>
    <?php
      $activePage = '';
      require __DIR__ . '/includes/header.php';
    ?>

    <div class="container bookings-page">
      <section class="bookings-card">
        <h1 class="bookings-title">Lịch sử đặt tour</h1>
        <p class="bookings-subtitle">Danh sách các đơn đặt tour của bạn.</p>

        <?php if (empty($bookings)): ?>
          <div class="bookings-empty">
            <i class="fas fa-suitcase-rolling"></i>
            <p>Bạn chưa có đơn đặt tour nào.</p>
          </div>
        <?php else: ?>
          <div class="bookings-table-wrap">
            <table class="bookings-table">
              <thead>
                <tr>
                  <th>Mã đơn</th>
                  <th>Tên tour</th>
                  <th>Điểm đến</th>
                  <th>Thời lượng</th>
                  <th>Người lớn</th>
                  <th>Trẻ em</th>
                  <th>Tổng tiền</th>
                  <th>Trạng thái</th>
                  <th>Ngày đặt</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($bookings as $booking): ?>
                  <?php
                    $statusRaw = (string) $booking['status'];
                    $statusClass = 'status-pending';
                    if ($statusRaw === 'đã xác nhận') {
                        $statusClass = 'status-confirmed';
                    } elseif ($statusRaw === 'đã hủy') {
                        $statusClass = 'status-cancelled';
                    }
                  ?>
                  <tr>
                    <td>#<?= (int) $booking['id'] ?></td>
                    <td><?= htmlspecialchars((string) $booking['tour_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $booking['destination'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) $booking['duration'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $booking['adults'] ?></td>
                    <td><?= (int) $booking['children'] ?></td>
                    <td><?= number_format((float) $booking['total_amount'], 0, ',', '.') ?> đ</td>
                    <td><span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($statusRaw, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string) $booking['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>
    </div>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
  </body>
</html>
