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

$id = (int) ($_GET['id'] ?? 0);
$cats = [];
try {
    $cats = $pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
} catch (Throwable) {
    $cats = [];
}

$row = null;
if ($id > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM tours WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
    } catch (Throwable) {
        $row = null;
    }
}

$err = '';
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
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
                "UPDATE tours SET category_id=:c, tour_name=:n, description=:d, itinerary=:it, destination=:de, duration=:du,
                 price=:p, image_url=:i, available_slots=:s, status=:st WHERE id=:id"
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
                'id' => $id,
            ]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không cập nhật được.';
        }
    }
}

function h(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

$pageTitle    = 'Sửa tour';
$pageSubtitle = '';
$activePage   = 'tours';

$topbarActions = '<a href="list.php" class="topbar-btn topbar-btn-ghost"><i class="fas fa-arrow-left"></i> Danh sách tour</a>';

$itineraryPlainDefault = '';
if ($row) {
    $itineraryPlainDefault = tour_itinerary_to_plaintext(tour_itinerary_decode($row['itinerary'] ?? null));
}

require __DIR__ . '/../../includes/admin_header.php';
?>

<?php if (!$row): ?>
  <div class="data-card"><p class="cell-muted">Không tìm thấy tour.</p><a href="list.php" class="btn btn-ghost btn-sm">← Quay lại</a></div>
<?php else: ?>
  <div class="data-card" style="max-width:640px">
    <?php if ($err): ?>
      <div class="alert alert-danger"><?= h($err) ?></div>
    <?php endif; ?>
    <?php
      $cidSel = (int) ($_POST['category_id'] ?? ($row['category_id'] ?? 0));
    ?>
    <form method="post" style="display:grid;gap:12px;padding:8px 4px 20px">
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Danh mục</label>
        <select class="form-control" name="category_id">
          <option value="0">— Không chọn —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $cidSel === (int) $c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Tên tour</label>
        <input class="form-control" name="tour_name" required value="<?= h((string) ($_POST['tour_name'] ?? $row['tour_name'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Điểm đến</label>
        <input class="form-control" name="destination" required value="<?= h((string) ($_POST['destination'] ?? $row['destination'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Thời lượng</label>
        <input class="form-control" name="duration" value="<?= h((string) ($_POST['duration'] ?? $row['duration'])) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Mô tả ngắn</label>
        <textarea class="form-control" name="description" rows="3"><?= h((string) ($_POST['description'] ?? (string) ($row['description'] ?? ''))) ?></textarea>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Lịch trình chi tiết (theo ngày)</label>
        <p class="cell-muted" style="font-size:0.75rem;margin:0 0 6px;line-height:1.4">
          Mỗi ngày: <code>=== NGÀY 1 ===</code>, dòng tiếp là tiêu đề, các dòng sau là nội dung. Để trống toàn bộ nếu không dùng lịch trình theo ngày.
        </p>
        <textarea class="form-control" name="itinerary_plain" rows="12"><?= h((string) ($_POST['itinerary_plain'] ?? $itineraryPlainDefault)) ?></textarea>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <label class="cell-muted" style="font-size:0.8rem">Giá (đ)</label>
          <input class="form-control" type="number" step="1000" name="price" required value="<?= h((string) ($_POST['price'] ?? $row['price'])) ?>" />
        </div>
        <div>
          <label class="cell-muted" style="font-size:0.8rem">Chỗ trống</label>
          <input class="form-control" type="number" name="available_slots" value="<?= h((string) ($_POST['available_slots'] ?? $row['available_slots'])) ?>" />
        </div>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">URL ảnh</label>
        <input class="form-control" name="image_url" value="<?= h((string) ($_POST['image_url'] ?? (string) ($row['image_url'] ?? ''))) ?>" />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Trạng thái</label>
        <?php $st = (string) ($_POST['status'] ?? $row['status']); ?>
        <select class="form-control" name="status">
          <option value="hiện" <?= $st !== 'ẩn' ? 'selected' : '' ?>>Hiện</option>
          <option value="ẩn" <?= $st === 'ẩn' ? 'selected' : '' ?>>Ẩn</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Cập nhật</button>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
