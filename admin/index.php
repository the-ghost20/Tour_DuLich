<?php
declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ../frontend/login.php');
    exit;
}

// ── Statistics ──────────────────────────────────────────────
$stats = [
    'total_revenue'    => 0,
    'confirmed_revenue'=> 0,
    'total_bookings'   => 0,
    'pending_bookings' => 0,
    'confirmed'        => 0,
    'cancelled'        => 0,
    'total_tours'      => 0,
    'active_tours'     => 0,
    'total_users'      => 0,
];

try {
    $r = $pdo->query("SELECT
        SUM(total_amount) AS total_revenue,
        SUM(CASE WHEN status='đã xác nhận' THEN total_amount ELSE 0 END) AS confirmed_revenue,
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN status='chờ duyệt'   THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='đã xác nhận' THEN 1 ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status='đã hủy'      THEN 1 ELSE 0 END) AS cancelled
        FROM bookings");
    $row = $r->fetch();
    $stats['total_revenue']     = (float) ($row['total_revenue'] ?? 0);
    $stats['confirmed_revenue'] = (float) ($row['confirmed_revenue'] ?? 0);
    $stats['total_bookings']    = (int)   ($row['total_bookings'] ?? 0);
    $stats['pending_bookings']  = (int)   ($row['pending'] ?? 0);
    $stats['confirmed']         = (int)   ($row['confirmed'] ?? 0);
    $stats['cancelled']         = (int)   ($row['cancelled'] ?? 0);

    $r2 = $pdo->query("SELECT COUNT(*) as t, SUM(status='hiện') as a FROM tours");
    $row2 = $r2->fetch();
    $stats['total_tours']  = (int) ($row2['t'] ?? 0);
    $stats['active_tours'] = (int) ($row2['a'] ?? 0);

    $r3 = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'");
    $stats['total_users'] = (int) $r3->fetchColumn();
} catch (Throwable) {}

// ── Monthly revenue (last 6 months) ─────────────────────────
$monthlyRevenue = [];
$monthlyLabels  = [];
try {
    $r = $pdo->query(
        "SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
                SUM(total_amount) AS revenue,
                COUNT(*) AS bookings
         FROM bookings
         WHERE status != 'đã hủy'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
         GROUP BY ym
         ORDER BY ym ASC"
    );
    foreach ($r->fetchAll() as $row) {
        [$y, $m] = explode('-', $row['ym']);
        $monthlyLabels[]  = $m . '/' . $y;
        $monthlyRevenue[] = (float) $row['revenue'];
    }
} catch (Throwable) {}

// ── Revenue by destination (top 5) ──────────────────────────
$destRevenue = [];
try {
    $r = $pdo->query(
        "SELECT t.destination,
                SUM(b.total_amount) AS revenue
         FROM bookings b
         JOIN tours t ON t.id = b.tour_id
         WHERE b.status != 'đã hủy'
         GROUP BY t.destination
         ORDER BY revenue DESC
         LIMIT 5"
    );
    $destRevenue = $r->fetchAll();
} catch (Throwable) {}

// ── Recent bookings ──────────────────────────────────────────
$recentBookings = [];
try {
    $r = $pdo->query(
        "SELECT b.id, b.total_amount, b.status, b.created_at,
                u.full_name, t.tour_name, t.destination
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN tours t ON t.id = b.tour_id
         ORDER BY b.created_at DESC
         LIMIT 8"
    );
    $recentBookings = $r->fetchAll();
} catch (Throwable) {}

// ── Top tours ────────────────────────────────────────────────
$topTours = [];
try {
    $r = $pdo->query(
        "SELECT t.tour_name, t.destination, t.price,
                COUNT(b.id) AS total_bookings,
                SUM(b.total_amount) AS revenue
         FROM tours t
         LEFT JOIN bookings b ON b.tour_id = t.id AND b.status != 'đã hủy'
         GROUP BY t.id
         ORDER BY revenue DESC
         LIMIT 5"
    );
    $topTours = $r->fetchAll();
} catch (Throwable) {}

// ── Booking status pie data ──────────────────────────────────
$pieData = [$stats['pending_bookings'], $stats['confirmed'], $stats['cancelled']];

// ── Format helpers ───────────────────────────────────────────
function fmtMoney(float $v): string {
    return number_format($v, 0, ',', '.') . ' đ';
}
function adminH2(mixed $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Tổng quan Dashboard';
$pageSubtitle = 'Xin chào, ' . $_SESSION['full_name'] . ' 👋';
$activePage   = 'dashboard';
$cssDepth      = '../';

$topbarActions = <<<HTML
  <a href="tours.php" class="topbar-btn topbar-btn-primary">
    <i class="fas fa-plus"></i> Thêm Tour Mới
  </a>
  <a href="../frontend/index.php" class="topbar-btn topbar-btn-ghost" target="_blank">
    <i class="fas fa-external-link-alt"></i> Xem trang web
  </a>
HTML;

$extraHead = <<<HTML
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
HTML;

require __DIR__ . '/includes/header.php';
?>

<!-- ═══════════════════════════════════════════════
     STATS CARDS
════════════════════════════════════════════════ -->
<div class="stats-grid">

  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
    <div class="stat-info">
      <div class="stat-label">Doanh thu đã xác nhận</div>
      <div class="stat-value" style="font-size:1.3rem"><?= fmtMoney($stats['confirmed_revenue']) ?></div>
      <div class="stat-change neutral"><i class="fas fa-info-circle"></i> Tổng: <?= fmtMoney($stats['total_revenue']) ?></div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
    <div class="stat-info">
      <div class="stat-label">Tổng đơn đặt tour</div>
      <div class="stat-value"><?= number_format($stats['total_bookings']) ?></div>
      <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?= $stats['confirmed'] ?> đã xác nhận</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
    <div class="stat-info">
      <div class="stat-label">Đơn chờ duyệt</div>
      <div class="stat-value"><?= $stats['pending_bookings'] ?></div>
      <?php if ($stats['pending_bookings'] > 0): ?>
        <div class="stat-change down"><i class="fas fa-exclamation-circle"></i> Cần xử lý</div>
      <?php else: ?>
        <div class="stat-change up"><i class="fas fa-check-circle"></i> Không có đơn</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div class="stat-info">
      <div class="stat-label">Khách hàng</div>
      <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
      <div class="stat-change neutral"><i class="fas fa-route"></i> <?= $stats['active_tours'] ?>/<?= $stats['total_tours'] ?> tour đang hoạt động</div>
    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════
     CHARTS
════════════════════════════════════════════════ -->
<div class="charts-grid">

  <!-- BAR CHART: Monthly Revenue -->
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <div class="chart-title"><i class="fas fa-chart-bar" style="color:#6366f1;margin-right:8px"></i>Doanh thu theo tháng</div>
        <div class="chart-subtitle">6 tháng gần nhất (triệu đồng, không tính đơn hủy)</div>
      </div>
      <span class="chart-badge green"><i class="fas fa-arrow-trend-up"></i> Doanh thu</span>
    </div>
    <div class="chart-canvas-wrap">
      <canvas id="revenueBarChart"></canvas>
    </div>
  </div>

  <!-- PIE CHART: Booking status -->
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <div class="chart-title"><i class="fas fa-chart-pie" style="color:#8b5cf6;margin-right:8px"></i>Trạng thái đơn</div>
        <div class="chart-subtitle">Phân bổ đơn đặt tour</div>
      </div>
      <span class="chart-badge purple"><i class="fas fa-circle-dot"></i> Phân tích</span>
    </div>
    <div class="chart-canvas-pie">
      <canvas id="statusPieChart"></canvas>
    </div>
    <div class="pie-legend">
      <div class="pie-legend-item">
        <div class="pie-dot" style="background:#f59e0b"></div>
        Chờ duyệt
        <span class="pie-legend-val"><?= $stats['pending_bookings'] ?></span>
      </div>
      <div class="pie-legend-item">
        <div class="pie-dot" style="background:#10b981"></div>
        Đã xác nhận
        <span class="pie-legend-val"><?= $stats['confirmed'] ?></span>
      </div>
      <div class="pie-legend-item">
        <div class="pie-dot" style="background:#ef4444"></div>
        Đã hủy
        <span class="pie-legend-val"><?= $stats['cancelled'] ?></span>
      </div>
    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════
     SECOND ROW: Dest Revenue Bar + Top Tours
════════════════════════════════════════════════ -->
<div class="charts-grid" style="grid-template-columns:1fr 1fr; margin-bottom:28px">

  <!-- Revenue by Destination (Horizontal Bar) -->
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <div class="chart-title"><i class="fas fa-map-location-dot" style="color:#3b82f6;margin-right:8px"></i>Doanh thu theo điểm đến</div>
        <div class="chart-subtitle">Top 5 điểm đến có doanh thu cao nhất</div>
      </div>
    </div>
    <div class="chart-canvas-wrap" style="height:220px">
      <canvas id="destBarChart"></canvas>
    </div>
  </div>

  <!-- Donut: Active vs Hidden tours -->
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <div class="chart-title"><i class="fas fa-route" style="color:#10b981;margin-right:8px"></i>Tình trạng Tour</div>
        <div class="chart-subtitle">Tour đang hiển thị vs ẩn</div>
      </div>
    </div>
    <div class="chart-canvas-pie" style="height:200px">
      <canvas id="tourDonutChart"></canvas>
    </div>
    <div class="pie-legend" style="flex-direction:row;gap:20px;justify-content:center;margin-top:12px">
      <div class="pie-legend-item">
        <div class="pie-dot" style="background:#10b981"></div>
        Đang hiện <span class="pie-legend-val" style="margin-left:6px"><?= $stats['active_tours'] ?></span>
      </div>
      <div class="pie-legend-item">
        <div class="pie-dot" style="background:#6b7280"></div>
        Đang ẩn <span class="pie-legend-val" style="margin-left:6px"><?= $stats['total_tours'] - $stats['active_tours'] ?></span>
      </div>
    </div>
  </div>

</div>

<!-- ═══════════════════════════════════════════════
     RECENT BOOKINGS TABLE
════════════════════════════════════════════════ -->
<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Đơn đặt tour gần đây</div>
      <div class="data-card-sub">8 đơn mới nhất trong hệ thống</div>
    </div>
    <a href="bookings.php" class="btn btn-ghost btn-sm">
      <i class="fas fa-arrow-right"></i> Xem tất cả
    </a>
  </div>
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Khách hàng</th>
          <th>Tour</th>
          <th>Điểm đến</th>
          <th class="cell-right">Tổng tiền</th>
          <th>Trạng thái</th>
          <th>Ngày đặt</th>
          <th class="cell-right">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentBookings)): ?>
          <tr><td colspan="8"><div class="empty-state"><i class="fas fa-inbox"></i><p>Chưa có đơn nào.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($recentBookings as $b): ?>
            <?php
              $st = (string) $b['status'];
              [$cls,$dot] = match($st) {
                'đã xác nhận' => ['badge-success',''],
                'đã hủy'      => ['badge-danger',''],
                default       => ['badge-warning',''],
              };
            ?>
            <tr>
              <td class="cell-bold">#<?= (int) $b['id'] ?></td>
              <td>
                <div class="cell-bold"><?= adminH2($b['full_name']) ?></div>
              </td>
              <td><?= adminH2($b['tour_name']) ?></td>
              <td class="cell-muted"><?= adminH2($b['destination']) ?></td>
              <td class="cell-right cell-bold"><?= fmtMoney((float)$b['total_amount']) ?></td>
              <td><span class="badge <?= $cls ?>"><span class="badge-dot"></span><?= adminH2($st) ?></span></td>
              <td class="cell-muted"><?= date('d/m/Y', strtotime($b['created_at'])) ?></td>
              <td class="cell-right">
                <a href="bookings.php" class="btn btn-ghost btn-sm btn-icon" title="Xem chi tiết">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ═══════════════════════════════════════════════
     TOP TOURS TABLE
════════════════════════════════════════════════ -->
<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Top tour theo doanh thu</div>
      <div class="data-card-sub">5 tour doanh thu cao nhất (không tính đơn hủy)</div>
    </div>
    <a href="tours.php" class="btn btn-ghost btn-sm">
      <i class="fas fa-arrow-right"></i> Quản lý tour
    </a>
  </div>
  <?php
    $maxRev = array_reduce($topTours, fn($carry, $t) => max($carry, (float)$t['revenue']), 1);
  ?>
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Tour</th>
          <th>Điểm đến</th>
          <th class="cell-right">Đơn giá</th>
          <th class="cell-right">Số đơn</th>
          <th class="cell-right">Doanh thu</th>
          <th>Tỉ trọng</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topTours as $tour): ?>
          <?php $pct = $maxRev > 0 ? round((float)$tour['revenue'] / $maxRev * 100) : 0; ?>
          <tr>
            <td class="cell-bold"><?= adminH2($tour['tour_name']) ?></td>
            <td class="cell-muted"><?= adminH2($tour['destination']) ?></td>
            <td class="cell-right"><?= fmtMoney((float)$tour['price']) ?></td>
            <td class="cell-right cell-bold"><?= (int)$tour['total_bookings'] ?></td>
            <td class="cell-right cell-bold" style="color:#6366f1"><?= fmtMoney((float)$tour['revenue']) ?></td>
            <td>
              <div class="progress-bar-wrap">
                <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
// Prepare JS data
$jsMonthLabels  = json_encode($monthlyLabels);
$jsMonthRevenue = json_encode(array_map(fn($v) => round($v/1_000_000, 2), $monthlyRevenue));
$jsPieData      = json_encode($pieData);
$jsDestLabels   = json_encode(array_column($destRevenue, 'destination'));
$jsDestRevenue  = json_encode(array_map(fn($r) => round((float)$r['revenue']/1_000_000,2), $destRevenue));
$jsTourActive   = $stats['active_tours'];
$jsTourHidden   = $stats['total_tours'] - $stats['active_tours'];

$extraScripts = <<<JS
<script>
// ── Global chart defaults ──────────────────────────────
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6b7280';

// ── 1. Monthly Revenue Bar Chart ──────────────────────
(function() {
  const ctx = document.getElementById('revenueBarChart');
  if (!ctx) return;
  const labels = {$jsMonthLabels};
  const revenue = {$jsMonthRevenue};
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.length ? labels : ['Tháng 1','Tháng 2','Tháng 3','Tháng 4','Tháng 5','Tháng 6'],
      datasets: [{
        label: 'Doanh thu (triệu đ)',
        data: revenue.length ? revenue : [0,0,0,0,0,0],
        backgroundColor: function(ctx) {
          const chart = ctx.chart;
          const {ctx: c, chartArea} = chart;
          if (!chartArea) return '#6366f1';
          const grad = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
          grad.addColorStop(0, 'rgba(99,102,241,0.92)');
          grad.addColorStop(1, 'rgba(139,92,246,0.5)');
          return grad;
        },
        borderRadius: 8,
        borderSkipped: false,
        hoverBackgroundColor: '#4f46e5',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.parsed.y.toFixed(2) + ' triệu đ'
          }
        }
      },
      scales: {
        x: { grid: { display: false }, border: { display: false } },
        y: {
          grid: { color: '#f3f4f6' },
          border: { display: false },
          ticks: { callback: v => v + 'M' }
        }
      }
    }
  });
})();

// ── 2. Status Pie Chart ──────────────────────────────
(function() {
  const ctx = document.getElementById('statusPieChart');
  if (!ctx) return;
  const data = {$jsPieData};
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Chờ duyệt', 'Đã xác nhận', 'Đã hủy'],
      datasets: [{
        data: data,
        backgroundColor: ['#f59e0b','#10b981','#ef4444'],
        borderWidth: 3,
        borderColor: '#ffffff',
        hoverBorderColor: '#fff',
        hoverOffset: 8,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' đơn'
          }
        }
      }
    }
  });
})();

// ── 3. Destination Horizontal Bar ────────────────────
(function() {
  const ctx = document.getElementById('destBarChart');
  if (!ctx) return;
  const labels = {$jsDestLabels};
  const revenue = {$jsDestRevenue};
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels.length ? labels : ['Chưa có dữ liệu'],
      datasets: [{
        label: 'Doanh thu (triệu đ)',
        data: revenue.length ? revenue : [0],
        backgroundColor: ['#6366f1','#8b5cf6','#3b82f6','#10b981','#f59e0b'],
        borderRadius: 6,
        borderSkipped: false,
      }]
    },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + ctx.parsed.x.toFixed(2) + ' triệu đ' } }
      },
      scales: {
        x: { grid: { color: '#f3f4f6' }, border: { display: false }, ticks: { callback: v => v + 'M' } },
        y: { grid: { display: false }, border: { display: false } }
      }
    }
  });
})();

// ── 4. Tour Donut (active vs hidden) ─────────────────
(function() {
  const ctx = document.getElementById('tourDonutChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Đang hiện', 'Đang ẩn'],
      datasets: [{
        data: [{$jsTourActive}, {$jsTourHidden}],
        backgroundColor: ['#10b981','#d1d5db'],
        borderWidth: 3,
        borderColor: '#fff',
        hoverOffset: 6,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '65%',
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.parsed } }
      }
    }
  });
})();
</script>
JS;

require __DIR__ . '/includes/footer.php';
?>
