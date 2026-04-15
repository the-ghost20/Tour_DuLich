<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

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
    "SELECT id, tour_name, description, destination, duration, price, image_url, available_slots, status
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
$priceNum = (float) $tour['price'];
$priceText = number_format($priceNum, 0, ',', '.') . ' đ';
$imageUrl = !empty($tour['image_url'])
    ? htmlspecialchars((string) $tour['image_url'], ENT_QUOTES, 'UTF-8')
    : 'https://images.unsplash.com/photo-1528127269322-539801943592?w=1200&h=700&fit=crop';
$slots = (int) $tour['available_slots'];

$displayAvg = $avgRating !== null && $reviewCount > 0 ? number_format($avgRating, 1, ',', '.') : '—';

?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $tourName ?> - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  </head>
  <body>
    <?php
      $activePage = 'tours';
      require __DIR__ . '/includes/header.php';
    ?>

    <article class="tour-detail-page">
      <div class="tour-detail-hero">
        <img src="<?= $imageUrl ?>" alt="<?= $tourName ?>" />
        <div class="tour-detail-hero-overlay"></div>
        <div class="container tour-detail-hero-inner">
          <nav class="tour-detail-breadcrumb">
            <a href="index.php">Trang chủ</a>
            <span>/</span>
            <a href="tours.php">Tour</a>
            <span>/</span>
            <span><?= $tourName ?></span>
          </nav>
          <h1><?= $tourName ?></h1>
          <p class="tour-detail-meta-line">
            <span><i class="fas fa-map-marker-alt"></i> <?= $destination ?></span>
            <span><i class="fas fa-calendar-alt"></i> <?= $duration ?></span>
            <span><i class="fas fa-users"></i> Còn <?= $slots ?> chỗ</span>
          </p>
        </div>
      </div>

      <div class="container tour-detail-body">
        <div class="tour-detail-main">
          <section class="tour-detail-section">
            <h2>Giới thiệu hành trình</h2>
            <div class="tour-detail-desc"><?= $description ?></div>
          </section>

          <section class="tour-detail-section tour-reviews-block">
            <h2>Đánh giá từ khách (<?= (int) $reviewCount ?>)</h2>
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
              <p class="tour-detail-muted"><a href="login.php">Đăng nhập</a> để gửi đánh giá và bình luận.</p>
            <?php endif; ?>
          </section>
        </div>

        <aside class="tour-detail-aside">
          <div class="tour-detail-book-card tour-card"
            data-tour-id="<?= (int) $tour['id'] ?>"
            data-destination="<?= htmlspecialchars(mb_strtolower((string) $tour['destination'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
            <div class="tour-detail-price"><?= $priceText ?> <small>/ người</small></div>
            <button type="button" class="btn-wishlist" title="Yêu thích" data-tour-id="<?= (int) $tour['id'] ?>" data-tour-name="<?= $tourName ?>"><i class="fas fa-heart"></i></button>
            <button type="button" class="btn-book tour-detail-book-btn"
              data-tour-id="<?= (int) $tour['id'] ?>"
              data-tour-name="<?= $tourName ?>"
              data-tour-price="<?= $priceNum ?>">Đặt tour</button>
            <a href="tours.php" class="btn-tour-detail btn-tour-detail--block">← Quay lại danh sách</a>
          </div>
        </aside>
      </div>
    </article>

    <?php require __DIR__ . '/includes/booking_modal.php'; ?>

    <?php require __DIR__ . '/includes/footer.php'; ?>
    <script>
      window.__PHP_IS_LOGGED_IN__ = <?= $_jsIsLoggedIn ?>;
    </script>
    <script src="js/script.js"></script>
  </body>
</html>
