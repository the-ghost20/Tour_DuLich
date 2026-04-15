<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/tour_itinerary.php';
require_once __DIR__ . '/../includes/tour_content_helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$tourId = (int) ($_GET['id'] ?? 0);
$errors = [];
$reviewSuccess = null;

if ($tourId <= 0) {
    http_response_code(404);
    exit('Không tìm thấy tour.');
}

$stmt = $pdo->prepare(
    "SELECT id, tour_name, description, journey_intro, highlights, departure_schedule, itinerary, destination, duration, price, image_url, gallery_urls, available_slots, status
     FROM tours WHERE id = :id LIMIT 1"
);
$stmt->execute(['id' => $tourId]);
$tour = $stmt->fetch();

if (!$tour || (string) $tour['status'] !== 'hiện') {
    http_response_code(404);
    exit('Tour không tồn tại hoặc đã ngừng bán.');
}

$userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$_jsIsLoggedIn = $userId > 0 ? 'true' : 'false';

$avgRating = null;
$reviewCount = 0;
$reviews = [];
$userReview = null;

try {
    $r = $pdo->prepare(
        'SELECT ROUND(AVG(rating), 1) AS avg_r, COUNT(*) AS c FROM tour_reviews WHERE tour_id = :tid'
    );
    $r->execute(['tid' => $tourId]);
    $row = $r->fetch();
    if ($row) {
        $avgRating = $row['avg_r'] !== null ? (float) $row['avg_r'] : null;
        $reviewCount = (int) $row['c'];
    }

    $list = $pdo->prepare(
        "SELECT tr.rating, tr.comment, tr.created_at, u.full_name
         FROM tour_reviews tr
         INNER JOIN users u ON u.id = tr.user_id
         WHERE tr.tour_id = :tid
         ORDER BY tr.created_at DESC
         LIMIT 50"
    );
    $list->execute(['tid' => $tourId]);
    $reviews = $list->fetchAll();

    if ($userId > 0) {
        $mine = $pdo->prepare(
            'SELECT rating, comment FROM tour_reviews WHERE tour_id = :tid AND user_id = :uid LIMIT 1'
        );
        $mine->execute(['tid' => $tourId, 'uid' => $userId]);
        $userReview = $mine->fetch() ?: null;
    }
} catch (Throwable $e) {
    // Bảng tour_reviews chưa có khi chưa cập nhật CSDL
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'tour_review') {
    if ($userId <= 0) {
        $errors[] = 'Vui lòng đăng nhập để gửi đánh giá.';
    } else {
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'Vui lòng chọn số sao từ 1 đến 5.';
        }
        if ($comment === '') {
            $errors[] = 'Vui lòng nhập nội dung đánh giá.';
        }
        if (empty($errors)) {
            try {
                $ins = $pdo->prepare(
                    "INSERT INTO tour_reviews (tour_id, user_id, rating, comment)
                     VALUES (:tid, :uid, :r, :c)
                     ON DUPLICATE KEY UPDATE
                       rating = VALUES(rating),
                       comment = VALUES(comment),
                       updated_at = CURRENT_TIMESTAMP"
                );
                $ins->execute([
                    'tid' => $tourId,
                    'uid' => $userId,
                    'r' => $rating,
                    'c' => $comment,
                ]);
                header('Location: tour_detail.php?id=' . $tourId . '&reviewed=1');
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Không thể lưu đánh giá. Hãy kiểm tra đã import bảng tour_reviews trong CSDL.';
            }
        }
    }
}

if (isset($_GET['reviewed'])) {
    $reviewSuccess = 'Cảm ơn bạn đã đánh giá tour!';
}

$tourName = htmlspecialchars((string) $tour['tour_name'], ENT_QUOTES, 'UTF-8');
$destination = htmlspecialchars((string) $tour['destination'], ENT_QUOTES, 'UTF-8');
$duration = htmlspecialchars((string) $tour['duration'], ENT_QUOTES, 'UTF-8');
$description = nl2br(htmlspecialchars((string) $tour['description'], ENT_QUOTES, 'UTF-8'));
$itineraryDays = tour_itinerary_decode($tour['itinerary'] ?? null);
$descPlain = trim((string) ($tour['description'] ?? ''));
$journeyIntroPlain = trim((string) ($tour['journey_intro'] ?? ''));
$journeyIntroHtml = $journeyIntroPlain !== ''
    ? nl2br(htmlspecialchars($journeyIntroPlain, ENT_QUOTES, 'UTF-8'))
    : '';
$highlightsList = tour_highlights_decode($tour['highlights'] ?? null);
$priceNum = (float) $tour['price'];
$departuresList = tour_departures_decode($tour['departure_schedule'] ?? null, $priceNum);
$departuresJson = htmlspecialchars(
    json_encode($departuresList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);
$priceText = number_format($priceNum, 0, ',', '.') . ' đ';
$slots = (int) $tour['available_slots'];

$displayAvg = $avgRating !== null && $reviewCount > 0 ? number_format($avgRating, 1, ',', '.') : '—';

$tourRefCode = 'DLV-' . str_pad((string) $tourId, 5, '0', STR_PAD_LEFT);
$slotsUrgent = $slots > 0 && $slots <= 8;

$galleryMainRaw = !empty($tour['image_url'])
    ? (string) $tour['image_url']
    : '';
$galleryAdmin = tour_gallery_urls_decode($tour['gallery_urls'] ?? null);
$galleryFallback = [
    'https://images.unsplash.com/photo-1528127269322-539801943592?w=1200&h=800&fit=crop',
    'https://images.unsplash.com/photo-1559827260-dc66d52bef19?w=1200&h=800&fit=crop',
    'https://images.unsplash.com/photo-1537225228614-b4fad34a0b60?w=1200&h=800&fit=crop',
    'https://images.unsplash.com/photo-1584422604131-a971d26d8f44?w=1200&h=800&fit=crop',
];
$galleryPool = [];
if ($galleryMainRaw !== '') {
    $galleryPool[] = $galleryMainRaw;
}
foreach ($galleryAdmin as $u) {
    $galleryPool[] = $u;
}
$galleryImages = [];
$gallerySeen = [];
foreach ($galleryPool as $gUrl) {
    if (isset($gallerySeen[$gUrl])) {
        continue;
    }
    $gallerySeen[$gUrl] = true;
    $galleryImages[] = $gUrl;
}
if ($galleryImages === []) {
    foreach ($galleryFallback as $gUrl) {
        if (isset($gallerySeen[$gUrl])) {
            continue;
        }
        $gallerySeen[$gUrl] = true;
        $galleryImages[] = $gUrl;
        if (count($galleryImages) >= 4) {
            break;
        }
    }
}

$galleryLightboxItems = [];
foreach ($galleryImages as $gFull) {
    $thumbLb = str_contains($gFull, 'unsplash.com')
        ? (preg_replace('/\?.*$/', '', $gFull) ?: $gFull) . '?auto=format&fit=crop&w=160&h=112&q=75'
        : $gFull;
    $galleryLightboxItems[] = ['full' => $gFull, 'thumb' => $thumbLb];
}
$galleryItemsJson = htmlspecialchars(
    json_encode($galleryLightboxItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);

?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $tourName ?> - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script>
      window.__PHP_IS_LOGGED_IN__ = <?= $_jsIsLoggedIn ?>;
    </script>
  </head>
  <body>
    <?php
      $activePage = 'tours';
      require __DIR__ . '/../includes/header.php';
    ?>

    <article class="tour-detail-page td-pro">
      <div class="container td-pro-head">
        <nav class="td-breadcrumb" aria-label="Breadcrumb">
          <a href="index.php">Trang chủ</a>
          <span class="td-bc-sep">/</span>
          <a href="tours.php">Tour</a>
          <span class="td-bc-sep">/</span>
          <span class="td-bc-current"><?= $tourName ?></span>
        </nav>
        <h1 class="td-pro-title"><?= $tourName ?></h1>
      </div>

      <div class="container td-pro-booking-row">
        <div
          class="td-pro-gallery"
          data-td-gallery
          data-gallery-items="<?= $galleryItemsJson ?>"
        >
          <div class="td-pro-gallery-thumbs" role="tablist" aria-label="Ảnh tour">
            <?php foreach ($galleryImages as $gi => $gFull): ?>
              <?php
                $gFullEsc = htmlspecialchars($gFull, ENT_QUOTES, 'UTF-8');
                $gThumbRaw = str_contains($gFull, 'unsplash.com')
                    ? (preg_replace('/\?.*$/', '', $gFull) ?: $gFull) . '?auto=format&fit=crop&w=200&h=140&q=70'
                    : $gFull;
                $gThumbEsc = htmlspecialchars($gThumbRaw, ENT_QUOTES, 'UTF-8');
              ?>
              <button
                type="button"
                class="td-gallery-thumb<?= $gi === 0 ? ' is-active' : '' ?>"
                data-full="<?= $gFullEsc ?>"
                aria-label="Xem ảnh <?= $gi + 1 ?>"
                <?= $gi === 0 ? ' aria-current="true"' : '' ?>
              >
                <img src="<?= $gThumbEsc ?>" alt="" loading="<?= $gi === 0 ? 'eager' : 'lazy' ?>" width="200" height="140" />
              </button>
            <?php endforeach; ?>
          </div>
          <div
            class="td-pro-gallery-main td-gallery-main td-gallery-main-trigger"
            tabindex="0"
            role="button"
            aria-label="Mở xem ảnh lớn"
          >
            <img
              src="<?= htmlspecialchars($galleryImages[0] ?? $galleryMainRaw, ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= $tourName ?>"
              width="1200"
              height="800"
            />
          </div>
        </div>

        <aside class="td-book-card tour-detail-book-card tour-card"
          data-tour-id="<?= (int) $tour['id'] ?>"
          data-destination="<?= htmlspecialchars(mb_strtolower((string) $tour['destination'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
          <button type="button" class="btn-wishlist td-book-wishlist" title="Yêu thích" aria-label="Yêu thích" aria-pressed="false" data-tour-id="<?= (int) $tour['id'] ?>" data-tour-name="<?= $tourName ?>">
            <i class="far fa-heart" aria-hidden="true"></i>
          </button>
          <div class="td-book-price-block">
            <span class="td-book-price-label">Giá từ</span>
            <div class="td-book-price-row">
              <span class="td-price-current"><?= $priceText ?></span>
              <span class="td-price-unit">/ khách</span>
            </div>
          </div>
          <div class="td-book-promo" role="note">
            <i class="fas fa-gift" aria-hidden="true"></i>
            <p>Đặt online — nhập mã khuyến mãi trong bước xác nhận để được ưu đãi (nếu có).</p>
          </div>
          <ul class="td-book-meta">
            <li>
              <i class="fas fa-ticket-alt" aria-hidden="true"></i>
              <div><span class="td-meta-k">Mã tour</span> <strong class="td-meta-v"><?= htmlspecialchars($tourRefCode, ENT_QUOTES, 'UTF-8') ?></strong></div>
            </li>
            <li>
              <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
              <div><span class="td-meta-k">Điểm đến</span> <strong class="td-meta-v"><?= $destination ?></strong></div>
            </li>
            <li>
              <i class="fas fa-clock" aria-hidden="true"></i>
              <div><span class="td-meta-k">Thời lượng</span> <strong class="td-meta-v"><?= $duration ?></strong></div>
            </li>
            <li>
              <i class="fas fa-clipboard-list" aria-hidden="true"></i>
              <div>
                <span class="td-meta-k">Số chỗ còn</span>
                <strong class="td-meta-v td-meta-slots<?= $slotsUrgent ? ' td-meta-slots--urgent' : '' ?>"><?= $slots ?></strong>
              </div>
            </li>
          </ul>
          <div class="td-book-actions">
            <button type="button" class="btn-book tour-detail-book-btn td-btn-book-primary"
              data-tour-id="<?= (int) $tour['id'] ?>"
              data-tour-name="<?= $tourName ?>"
              data-tour-price="<?= $priceNum ?>">
              <i class="fas fa-calendar-check" aria-hidden="true"></i> Đặt ngay
            </button>
            <a href="tours.php" class="td-btn-book-outline">← Chọn tour khác</a>
          </div>
        </aside>
      </div>

      <div class="container td-pro-content">
        <input type="hidden" id="td-picked-departure" value="" autocomplete="off" />
        <div class="tour-detail-main td-pro-main">
          <section class="tour-detail-section td-pro-section">
            <h2 class="td-section-title">Giới thiệu hành trình</h2>
            <div class="td-journey-stack">
              <?php if ($descPlain !== ''): ?>
                <div class="tour-detail-desc td-pro-lead td-journey-short"><?= $description ?></div>
              <?php endif; ?>
              <?php if ($journeyIntroPlain !== ''): ?>
                <div class="tour-detail-desc td-journey-long"><?= $journeyIntroHtml ?></div>
              <?php elseif ($descPlain === ''): ?>
                <p class="tour-detail-muted">Nội dung đang được cập nhật.</p>
              <?php endif; ?>
            </div>
          </section>

          <?php if ($highlightsList !== []): ?>
            <section class="td-pro-section" aria-labelledby="td-highlights-heading">
              <h2 id="td-highlights-heading" class="visually-hidden">Điểm nhấn chương trình</h2>
              <details class="td-highlights-card" open>
                <summary class="td-highlights-summary">
                  <span class="td-highlights-title">Điểm nhấn của chương trình</span>
                  <span class="td-highlights-toggle">
                    <span class="td-hl-when-open">Thu gọn</span>
                    <span class="td-hl-when-closed">Mở rộng</span>
                  </span>
                </summary>
                <ul class="td-highlights-list">
                  <?php foreach ($highlightsList as $hl): ?>
                    <li><?= tour_format_highlight_line($hl) ?></li>
                  <?php endforeach; ?>
                </ul>
              </details>
            </section>
          <?php endif; ?>

          <?php if ($departuresList !== []): ?>
            <section
              class="td-pro-section td-cal-section"
              id="td-departure-root"
              data-td-departures="<?= $departuresJson ?>"
              data-td-base-price="<?= htmlspecialchars((string) $priceNum, ENT_QUOTES, 'UTF-8') ?>"
            >
              <h2 class="td-section-title td-section-title--center">Lịch khởi hành</h2>
              <div class="td-cal-card">
                <div class="td-cal-inner">
                  <aside class="td-cal-sidebar" aria-label="Chọn tháng">
                    <div class="td-cal-sidebar-title">Chọn tháng</div>
                    <div class="td-cal-month-list" data-td-cal-months></div>
                  </aside>
                  <div class="td-cal-main">
                    <div class="td-cal-nav">
                      <button type="button" class="td-cal-nav-btn" data-td-cal-prev aria-label="Tháng trước">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                      </button>
                      <div class="td-cal-month-title" data-td-cal-title>—</div>
                      <button type="button" class="td-cal-nav-btn td-cal-nav-btn--next" data-td-cal-next aria-label="Tháng sau">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                      </button>
                    </div>
                    <div class="td-cal-weekdays" aria-hidden="true">
                      <span>T2</span><span>T3</span><span>T4</span><span>T5</span><span>T6</span><span class="td-cal-wd-h">T7</span><span class="td-cal-wd-h">CN</span>
                    </div>
                    <div class="td-cal-grid" data-td-cal-grid></div>
                    <p class="td-cal-hint">
                      <em>Quý khách vui lòng chọn ngày phù hợp — ngày đã chọn sẽ gợi ý trong form đặt tour.</em>
                    </p>
                    <p class="td-cal-disclaimer">Giá hiển thị trên lịch mang tính tham khảo; tổng tiền đặt tour vẫn theo bảng giá và mã khuyến mãi khi xác nhận đơn.</p>
                  </div>
                </div>
              </div>
            </section>
          <?php endif; ?>

          <section class="td-pro-section td-quick-section" aria-labelledby="td-quick-heading">
            <h2 id="td-quick-heading" class="td-section-title td-section-title--center">Thông tin thêm về chuyến đi</h2>
            <div class="td-quick-grid">
              <div class="td-quick-card">
                <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
                <h3>Điểm tham quan</h3>
                <p><?= $destination ?></p>
              </div>
              <div class="td-quick-card">
                <i class="fas fa-utensils" aria-hidden="true"></i>
                <h3>Ẩm thực</h3>
                <p>Bữa ăn theo chương trình từng ngày (chi tiết trong lịch trình).</p>
              </div>
              <div class="td-quick-card">
                <i class="fas fa-users" aria-hidden="true"></i>
                <h3>Đối tượng phù hợp</h3>
                <p>Gia đình, cặp đôi, nhóm bạn và khách lẻ yêu thích khám phá.</p>
              </div>
              <div class="td-quick-card">
                <i class="fas fa-sun" aria-hidden="true"></i>
                <h3>Thời gian lý tưởng</h3>
                <p>Quanh năm — tham khảo thêm ngày khởi hành khi đặt tour.</p>
              </div>
              <div class="td-quick-card">
                <i class="fas fa-bus" aria-hidden="true"></i>
                <h3>Phương tiện</h3>
                <p>Theo lịch trình (xe du lịch, phương tiện công cộng hoặc máy bay nếu có ghi rõ).</p>
              </div>
              <div class="td-quick-card">
                <i class="fas fa-tags" aria-hidden="true"></i>
                <h3>Khuyến mãi</h3>
                <p>Áp dụng mã giảm giá tại bước đặt tour (nếu còn hiệu lực).</p>
              </div>
            </div>
          </section>

          <?php if ($itineraryDays !== []): ?>
            <section class="tour-detail-section td-pro-section" aria-labelledby="td-itin-heading">
              <h2 id="td-itin-heading" class="td-section-title td-section-title--center">Lịch trình</h2>
              <div class="td-itinerary-acc-list">
                <?php foreach ($itineraryDays as $idx => $d): ?>
                  <details class="td-itinerary-acc"<?= $idx === 0 ? ' open' : '' ?>>
                    <summary>
                      <span class="td-itin-badge">Ngày <?= $idx + 1 ?></span>
                      <span class="td-itin-sum-title"><?= htmlspecialchars($d['title'], ENT_QUOTES, 'UTF-8') ?></span>
                      <i class="fas fa-chevron-down td-itin-chevron" aria-hidden="true"></i>
                    </summary>
                    <div class="td-itin-body tour-itinerary-day-body"><?= nl2br(htmlspecialchars($d['body'], ENT_QUOTES, 'UTF-8')) ?></div>
                  </details>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <section class="tour-detail-section tour-reviews-block td-pro-section">
            <h2 class="td-section-title">Đánh giá từ khách (<?= (int) $reviewCount ?>)</h2>
            <div class="tour-detail-rating-summary">
              <strong><?= htmlspecialchars($displayAvg, ENT_QUOTES, 'UTF-8') ?></strong>
              <span>/ 5 sao trung bình</span>
            </div>
            <?php if (empty($reviews)): ?>
              <p class="tour-detail-muted">Chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ trải nghiệm.</p>
            <?php else: ?>
              <ul class="tour-review-list">
                <?php foreach ($reviews as $rv): ?>
                  <li class="tour-review-item">
                    <div class="tour-review-stars" aria-label="<?= (int) $rv['rating'] ?> sao">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star<?= $i <= (int) $rv['rating'] ? '' : ' tour-star--dim' ?>"></i>
                      <?php endfor; ?>
                    </div>
                    <p class="tour-review-text"><?= nl2br(htmlspecialchars((string) $rv['comment'], ENT_QUOTES, 'UTF-8')) ?></p>
                    <div class="tour-review-by">
                      <?= htmlspecialchars((string) $rv['full_name'], ENT_QUOTES, 'UTF-8') ?>
                      · <?= htmlspecialchars(date('d/m/Y', strtotime((string) $rv['created_at'])), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if ($reviewSuccess): ?>
              <div class="profile-flash profile-flash--ok"><?= htmlspecialchars($reviewSuccess, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
              <div class="profile-flash profile-flash--err"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <?php if ($userId > 0): ?>
              <form method="post" class="tour-review-form">
                <input type="hidden" name="form" value="tour_review" />
                <h3><?= $userReview ? 'Cập nhật đánh giá của bạn' : 'Viết đánh giá 5 sao' ?></h3>
                <fieldset class="star-rating-input">
                  <legend class="visually-hidden">Chọn số sao</legend>
                  <?php for ($s = 5; $s >= 1; $s--): ?>
                    <input type="radio" name="rating" id="td-fs-<?= $s ?>" value="<?= $s ?>"
                      <?= ($userReview && (int) $userReview['rating'] === $s) || (!$userReview && $s === 5) ? 'checked' : '' ?> required />
                    <label for="td-fs-<?= $s ?>" title="<?= $s ?> sao"><i class="fas fa-star"></i></label>
                  <?php endfor; ?>
                </fieldset>
                <label for="review-comment">Bình luận</label>
                <textarea id="review-comment" name="comment" rows="4" required placeholder="Chia sẻ cảm nhận về tour..."><?= $userReview ? htmlspecialchars((string) $userReview['comment'], ENT_QUOTES, 'UTF-8') : '' ?></textarea>
                <button type="submit" class="profile-btn">Gửi đánh giá</button>
              </form>
            <?php else: ?>
              <p class="tour-detail-muted"><a href="../auth/login.php">Đăng nhập</a> để gửi đánh giá và bình luận.</p>
            <?php endif; ?>
          </section>

          <section class="td-pro-section td-policy-section" aria-labelledby="td-policy-heading">
            <h2 id="td-policy-heading" class="td-section-title td-section-title--center">Những thông tin cần lưu ý</h2>
            <div class="td-policy-grid">
              <div class="td-policy-col">
                <details class="td-policy-item">
                  <summary>Giá tour bao gồm</summary>
                  <div class="td-policy-body">
                    <p>Vé tham quan theo chương trình, các bữa ăn ghi trong lịch trình, xe đưa đón theo hành trình, hướng dẫn viên (nếu có), bảo hiểm du lịch khi được nêu trong xác nhận đơn.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Giá tour không bao gồm</summary>
                  <div class="td-policy-body">
                    <p>Chi phí cá nhân, đồ uống ngoài chương trình, hành lý quá cước, tip (tuỳ ý), vé máy bay / visa nếu không ghi rõ trong chương trình.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Lưu ý giá trẻ em</summary>
                  <div class="td-policy-body">
                    <p>Trên hệ thống, trẻ em thường được tính 50% giá người lớn khi đặt; chi tiết cụ thể sẽ được xác nhận khi duyệt đơn.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Điều kiện thanh toán</summary>
                  <div class="td-policy-body">
                    <p>Thanh toán theo hướng dẫn sau khi đơn được duyệt; có thể qua chuyển khoản hoặc hình thức công ty thông báo.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Điều kiện đăng ký</summary>
                  <div class="td-policy-body">
                    <p>Cung cấp thông tin chính xác; đặt cọc / thanh toán đúng hạn theo xác nhận từ bộ phận tư vấn.</p>
                  </div>
                </details>
              </div>
              <div class="td-policy-col">
                <details class="td-policy-item">
                  <summary>Lưu ý chuyển hoặc hủy tour</summary>
                  <div class="td-policy-body">
                    <p>Liên hệ hotline sớm nhất khi cần đổi lịch hoặc hủy; mức phí phụ thuộc thời điểm và chính sách từng tour.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Điều kiện hủy tour (ngày thường)</summary>
                  <div class="td-policy-body">
                    <p>Áp dụng theo từng mốc thời gian (ví dụ trước 30 ngày, 15 ngày…) — chi tiết gửi kèm khi xác nhận đơn.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Điều kiện hủy tour (lễ, Tết)</summary>
                  <div class="td-policy-body">
                    <p>Dịp cao điểm có thể áp dụng mức phí hoặc điều kiện chặt hơn; vui lòng đọc kỹ khi đặt khởi hành dịp lễ.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Trường hợp bất khả kháng</summary>
                  <div class="td-policy-body">
                    <p>Thời tiết, sự cố hàng không hoặc sự kiện ngoài kiểm soát — công ty hỗ trợ phương án thay thế hoặc hoàn tiền theo quy định hiện hành.</p>
                  </div>
                </details>
                <details class="td-policy-item">
                  <summary>Liên hệ</summary>
                  <div class="td-policy-body">
                    <p><strong>Hotline:</strong> (+84) 909 090 909 (08:00–22:00)<br /><strong>Email:</strong> dulichviet@gmail.com<br /><strong>Địa chỉ:</strong> Số 1 Đường Bạch Đằng, Quận 1, TP. HCM</p>
                  </div>
                </details>
              </div>
            </div>
          </section>
        </div>
      </div>
    </article>

    <div
      id="td-lightbox"
      class="td-lightbox"
      hidden
      aria-hidden="true"
      role="dialog"
      aria-modal="true"
      aria-labelledby="td-lightbox-title"
    >
      <div class="td-lightbox-backdrop" data-td-lb-close tabindex="-1"></div>
      <div class="td-lightbox-panel">
        <h2 id="td-lightbox-title" class="visually-hidden">Xem ảnh tour</h2>
        <button type="button" class="td-lightbox-close" data-td-lb-close aria-label="Đóng">
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div class="td-lightbox-main-wrap">
          <button
            type="button"
            class="td-lightbox-nav td-lightbox-prev"
            aria-label="Ảnh trước"
          >
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
          </button>
          <div class="td-lightbox-stage">
            <img class="td-lightbox-img" src="" alt="" />
            <a
              class="td-lightbox-fs"
              href="#"
              target="_blank"
              rel="noopener noreferrer"
              aria-label="Mở ảnh full size trong tab mới"
              title="Full size"
            >
              <i class="fas fa-expand" aria-hidden="true"></i>
            </a>
          </div>
          <button
            type="button"
            class="td-lightbox-nav td-lightbox-next"
            aria-label="Ảnh sau"
          >
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
          </button>
        </div>
        <div class="td-lightbox-thumbs-head">
          <span class="td-lightbox-count"
            >Tất cả ảnh (<span data-td-lb-count>0</span>)</span>
        </div>
        <div class="td-lightbox-thumbs-scroll">
          <div class="td-lightbox-thumbs" data-td-lb-thumbs></div>
        </div>
      </div>
    </div>

    <?php require __DIR__ . '/../includes/booking_modal.php'; ?>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
