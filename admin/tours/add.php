<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/tour_itinerary.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin', 'staff'], true)) {
    header('Location: ../../auth/login.php');
    exit;
}

$cats = [];
try {
    $cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
} catch (Throwable) {
    $cats = [];
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string) ($_POST['tour_name'] ?? ''));
    $dest  = trim((string) ($_POST['destination'] ?? ''));
    $dur   = trim((string) ($_POST['duration'] ?? ''));
    $desc  = (string) ($_POST['description'] ?? '');
    $itPlain = (string) ($_POST['itinerary_plain'] ?? '');
    $itJson = tour_itinerary_from_plaintext($itPlain);
    $price = (float) ($_POST['price'] ?? 0);
    $slots = max(0, (int) ($_POST['available_slots'] ?? 0));
    $img   = trim((string) ($_POST['image_url'] ?? ''));
    $st    = (string) ($_POST['status'] ?? 'hiện');
    $cid   = (int) ($_POST['category_id'] ?? 0);
    if ($st !== 'ẩn') {
        $st = 'hiện';
    }
    $catId = $cid > 0 ? $cid : null;

    if ($name === '' || $dest === '') {
        $err = 'Nhập tên tour và điểm đến.';
    } elseif ($price < 0) {
        $err = 'Giá không hợp lệ.';
    } else {
        try {
            $pdo->prepare(
                "INSERT INTO tours (category_id, tour_name, description, itinerary, destination, duration, price, image_url, available_slots, status)
                 VALUES (:c,:n,:d,:it,:de,:du,:p,:i,:s,:st)"
            )->execute([
                'c'  => $catId,
                'n'  => $name,
                'd'  => $desc === '' ? null : $desc,
                'it' => $itJson,
                'de' => $dest,
                'du' => $dur,
                'p'  => $price,
                'i'  => $img === '' ? null : $img,
                's'  => $slots,
                'st' => $st,
            ]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không lưu được tour.';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Thêm tour';
$pageSubtitle = '';
$activePage   = 'tours';

$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách tour</a>';

require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="data-card" style="max-width:640px">
  <?php if ($err): ?>
    <div class="alert alert-danger"><?= h($err) ?></div>
  <?php endif; ?>
  <form method="post" style="display:grid;gap:12px;padding:8px 4px 20px">
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Danh mục</label>
      <select class="form-control" name="category_id">
        <option value="0">— Không chọn —</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) ($_POST['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Tên tour</label>
      <input class="form-control" name="tour_name" required value="<?= h((string) ($_POST['tour_name'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Điểm đến</label>
      <input class="form-control" name="destination" required value="<?= h((string) ($_POST['destination'] ?? '')) ?>" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Thời lượng</label>
      <input class="form-control" name="duration" value="<?= h((string) ($_POST['duration'] ?? '')) ?>" placeholder="3 ngày 2 đêm" />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Mô tả ngắn</label>
      <textarea class="form-control" name="description" rows="3"><?= h((string) ($_POST['description'] ?? '')) ?></textarea>
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Lịch trình chi tiết (theo ngày)</label>
      <p class="cell-muted" style="font-size:0.75rem;margin:0 0 6px;line-height:1.4">
        Mỗi ngày một khối: dòng <code>=== NGÀY 1 ===</code>, dòng tiếp là <strong>tiêu đề</strong>, các dòng sau là nội dung (đi đâu, ăn gì, hoạt động). Tiếp tục <code>=== NGÀY 2 ===</code> …
      </p>
      <textarea class="form-control" name="itinerary_plain" rows="12" placeholder="=== NGÀY 1 ===&#10;Tiêu đề ngày 1&#10;Nội dung chi tiết...&#10;&#10;=== NGÀY 2 ===&#10;..."><?= h((string) ($_POST['itinerary_plain'] ?? '')) ?></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Giá (đ)</label>
        <input class="form-control" type="number" step="1000" name="price" required value="<?= h((string) ($_POST['price'] ?? '')) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Chỗ trống</label>
        <input class="form-control" type="number" name="available_slots" value="<?= h((string) ($_POST['available_slots'] ?? '0')) ?>" />
      </div>
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">URL ảnh</label>
      <input class="form-control" name="image_url" value="<?= h((string) ($_POST['image_url'] ?? '')) ?>" placeholder="https://..." />
    </div>
    <div>
      <label class="cell-muted" style="font-size:0.8rem">Trạng thái</label>
      <select class="form-control" name="status">
        <option value="hiện" <?= (($_POST['status'] ?? '') === 'ẩn') ? '' : 'selected' ?>>Hiện</option>
        <option value="ẩn" <?= (($_POST['status'] ?? '') === 'ẩn') ? 'selected' : '' ?>>Ẩn</option>
      </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Lưu tour</button>
  </form>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
