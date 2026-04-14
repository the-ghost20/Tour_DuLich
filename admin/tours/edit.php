<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/tour_itinerary.php';
require_once __DIR__ . '/../../includes/tour_content_helpers.php';

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
    $journeyIntro = (string) ($_POST['journey_intro'] ?? '');
    $highlightsPlain = (string) ($_POST['highlights_plain'] ?? '');
    $departurePlain = (string) ($_POST['departure_schedule_plain'] ?? '');
    $galleryUrlsPlain = (string) ($_POST['gallery_urls_plain'] ?? '');
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
        $hiJson = tour_highlights_from_textarea($highlightsPlain);
        $depJson = tour_departures_to_storage($departurePlain, $price);
        $galJson = tour_gallery_urls_from_textarea($galleryUrlsPlain);
        if (trim($departurePlain) !== '' && $depJson === null) {
            $err = 'Lịch khởi hành không hợp lệ. Mỗi dòng: YYYY-MM-DD hoặc YYYY-MM-DD giá promo';
        } else {
        try {
            $pdo->prepare(
                "UPDATE tours SET category_id=:c, tour_name=:n, description=:d, journey_intro=:ji, highlights=:hi,
                 departure_schedule=:dep, itinerary=:it, destination=:de, duration=:du,
                 price=:p, image_url=:i, gallery_urls=:gal, available_slots=:s, status=:st WHERE id=:id"
            )->execute([
                'c'   => $catId,
                'n'   => $name,
                'd'   => $desc === '' ? null : $desc,
                'ji'  => trim($journeyIntro) === '' ? null : $journeyIntro,
                'hi'  => $hiJson,
                'dep' => $depJson,
                'it'  => $itJson,
                'de'  => $dest,
                'du'  => $dur,
                'p'   => $price,
                'i'   => $img === '' ? null : $img,
                'gal' => $galJson,
                's'   => $slots,
                'st'  => $st,
                'id'  => $id,
            ]);
            header('Location: list.php', true, 302);
            exit;
        } catch (Throwable) {
            $err = 'Không cập nhật được.';
        }
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
        <label class="cell-muted" style="font-size:0.8rem">Giới thiệu hành trình (chi tiết)</label>
        <p class="cell-muted" style="font-size:0.75rem;margin:0 0 6px;line-height:1.4">
          Đoạn dài hiển thị trên trang chi tiết tour (dưới mô tả ngắn). Có thể nhiều đoạn.
        </p>
        <textarea class="form-control" name="journey_intro" rows="10"><?= h((string) ($_POST['journey_intro'] ?? (string) ($row['journey_intro'] ?? ''))) ?></textarea>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Điểm nhấn chương trình</label>
        <p class="cell-muted" style="font-size:0.75rem;margin:0 0 6px;line-height:1.4">
          Mỗi dòng một ý. Dùng <code>**từ khóa**</code> để in đậm (ví dụ <code>**Vịnh Hạ Long**</code>: di sản…).
        </p>
        <?php
          $hiDefault = '';
        if ($row && isset($row['highlights'])) {
            foreach (tour_highlights_decode($row['highlights'] ?? null) as $ln) {
                $hiDefault .= $ln . "\n";
            }
            $hiDefault = rtrim($hiDefault);
        }
        ?>
        <textarea class="form-control" name="highlights_plain" rows="8" placeholder="**Điểm A:** Mô tả...&#10;**Điểm B:** ..."><?= h((string) ($_POST['highlights_plain'] ?? $hiDefault)) ?></textarea>
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Lịch khởi hành (lịch trên web)</label>
        <p class="cell-muted" style="font-size:0.75rem;margin:0 0 6px;line-height:1.4">
          Mỗi dòng: <code>YYYY-MM-DD</code> hoặc <code>YYYY-MM-DD giá_đồng [promo]</code> (giá để hiển thị; đặt tour vẫn dùng giá tour nếu chưa tích hợp giá theo ngày). Ví dụ: <code>2026-04-22 19190000 promo</code>
        </p>
        <?php
          $depDefault = '';
        if ($row && !empty($row['departure_schedule'])) {
            $rawDep = (string) $row['departure_schedule'];
            if ($rawDep !== '' && $rawDep[0] === '[') {
                $dec = tour_departures_decode($rawDep, (float) $row['price']);
                foreach ($dec as $x) {
                    $line = $x['date'] . ' ' . (string) (int) $x['price'];
                    if ($x['promo']) {
                        $line .= ' promo';
                    }
                    $depDefault .= $line . "\n";
                }
                $depDefault = rtrim($depDefault);
            } else {
                $depDefault = $rawDep;
            }
        }
        ?>
        <textarea class="form-control" name="departure_schedule_plain" rows="6" placeholder="2026-04-22&#10;2026-04-29 23990000 promo"><?= h((string) ($_POST['departure_schedule_plain'] ?? $depDefault)) ?></textarea>
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
        <label class="cell-muted" style="font-size:0.8rem">URL ảnh đại diện</label>
        <input class="form-control" name="image_url" value="<?= h((string) ($_POST['image_url'] ?? (string) ($row['image_url'] ?? ''))) ?>" placeholder="https://..." />
      </div>
      <div>
        <label class="cell-muted" style="font-size:0.8rem">Ảnh gallery (thêm nhiều ảnh)</label>
        <p class="cell-muted" style="font-size:0.75rem;margin:0 0 6px;line-height:1.4">
          Mỗi dòng một URL đầy đủ (<code>https://...</code>). Hiển thị trên trang chi tiết tour và lightbox. Ảnh đại diện ở trên luôn là ảnh đầu tiên nếu có.
        </p>
        <?php
          $galDefault = '';
        if ($row && !empty($row['gallery_urls'])) {
            $galDefault = implode("\n", tour_gallery_urls_decode((string) $row['gallery_urls']));
        }
        ?>
        <textarea class="form-control" name="gallery_urls_plain" rows="5" placeholder="https://...&#10;https://..."><?= h((string) ($_POST['gallery_urls_plain'] ?? $galDefault)) ?></textarea>
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
