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

$byMonth = [];
$byDest  = [];
$summary = ['orders' => 0, 'revenue' => 0.0, 'avg' => 0.0];

try {
    $stmt = $pdo->query(
        "SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym,
                SUM(total_amount) AS revenue,
                COUNT(*) AS cnt
         FROM bookings
         WHERE status != 'đã hủy'
           AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY ym
         ORDER BY ym ASC"
    );
    $byMonth = $stmt->fetchAll();

    $stmt2 = $pdo->query(
        "SELECT t.destination, SUM(b.total_amount) AS revenue, COUNT(*) AS cnt
         FROM bookings b
         JOIN tours t ON t.id = b.tour_id
         WHERE b.status != 'đã hủy'
         GROUP BY t.destination
         ORDER BY revenue DESC
         LIMIT 15"
    );
    $byDest = $stmt2->fetchAll();

    $s = $pdo->query(
        "SELECT COUNT(*) AS c, COALESCE(SUM(total_amount),0) AS r
         FROM bookings WHERE status != 'đã hủy'"
    )->fetch();
    $summary['orders']  = (int) ($s['c'] ?? 0);
    $summary['revenue'] = (float) ($s['r'] ?? 0);
    $summary['avg']    = $summary['orders'] > 0 ? $summary['revenue'] / $summary['orders'] : 0;
} catch (Throwable) {
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function money_fmt(float $n): string
{
    return number_format($n, 0, ',', '.') . ' đ';
}

$pageTitle    = 'Báo cáo doanh thu';
$pageSubtitle = 'Theo tháng (12 tháng) và theo điểm đến';
$activePage   = 'reports';

$exportUrl = htmlspecialchars(app_admin_url('reports/export.php'), ENT_QUOTES, 'UTF-8');
$topbarActions = <<<HTML
  <a href="{$exportUrl}" class="topbar-btn topbar-btn-primary"><i class="fas fa-file-csv"></i> Xuất CSV theo tháng</a>
HTML;

$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>';

require dirname(__DIR__, 2) . '/includes/admin_header.php';

$labels = [];
$data   = [];
foreach ($byMonth as $row) {
    [$y, $m] = explode('-', (string) $row['ym']);
    $labels[] = $m . '/' . $y;
    $data[]   = (float) $row['revenue'];
}
$labelsJson = json_encode($labels, JSON_UNESCAPED_UNICODE);
$dataJson   = json_encode($data, JSON_UNESCAPED_UNICODE);
?>

<div class="stats-grid" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-coins"></i></div>
    <div class="stat-info">
      <div class="stat-label">Tổng doanh thu (trừ đơn hủy)</div>
      <div class="stat-value" style="font-size:1.2rem"><?= money_fmt($summary['revenue']) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-receipt"></i></div>
    <div class="stat-info">
      <div class="stat-label">Số đơn</div>
      <div class="stat-value"><?= number_format($summary['orders']) ?></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fas fa-balance-scale"></i></div>
    <div class="stat-info">
      <div class="stat-label">Giá trị đơn trung bình</div>
      <div class="stat-value" style="font-size:1.05rem"><?= money_fmt($summary['avg']) ?></div>
    </div>
  </div>
</div>

<div class="data-card" style="margin-bottom:24px">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Doanh thu theo tháng</div>
    </div>
  </div>
  <div style="padding:16px;max-width:900px">
    <canvas id="revChart" height="120"></canvas>
  </div>
</div>

<div class="data-card">
  <div class="data-card-header">
    <div>
      <div class="data-card-title">Theo điểm đến (top 15)</div>
    </div>
  </div>
  <div style="overflow-x:auto">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Điểm đến</th>
          <th class="cell-right">Số đơn</th>
          <th class="cell-right">Doanh thu</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($byDest)): ?>
          <tr><td colspan="3"><div class="empty-state"><i class="fas fa-chart-line"></i><p>Chưa có dữ liệu.</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($byDest as $d): ?>
            <tr>
              <td class="cell-bold"><?= h($d['destination']) ?></td>
              <td class="cell-right"><?= (int) $d['cnt'] ?></td>
              <td class="cell-right"><?= money_fmt((float) $d['revenue']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
(function(){
  const el = document.getElementById('revChart');
  if (!el || typeof Chart === 'undefined') return;
  const labels = <?= $labelsJson ?: '[]' ?>;
  const data = <?= $dataJson ?: '[]' ?>;
  new Chart(el, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Doanh thu (đ)',
        data,
        borderColor: '#6366f1',
        backgroundColor: 'rgba(99,102,241,0.15)',
        fill: true,
        tension: 0.25
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: { beginAtZero: true }
      }
    }
  });
})();
</script>

<?php require dirname(__DIR__, 2) . '/includes/admin_footer.php'; ?>
