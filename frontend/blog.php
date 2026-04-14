<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/blog_helpers.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$blogFeedbackFlash = null;
$blogFeedbackErr = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form'] ?? '') === 'blog_feedback') {
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim((string) ($_POST['comment'] ?? ''));
    $uid = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    if ($rating < 1 || $rating > 5) {
        $blogFeedbackErr = 'Vui lòng chọn số sao từ 1 đến 5.';
    } elseif ($comment === '') {
        $blogFeedbackErr = 'Vui lòng nhập bình luận.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO blog_feedback (user_id, rating, comment) VALUES (:uid, :r, :c)'
            );
            $stmt->execute([
                'uid' => $uid,
                'r' => $rating,
                'c' => $comment,
            ]);
            header('Location: blog.php?thanks=1');
            exit;
        } catch (Throwable $e) {
            $blogFeedbackErr = 'Chưa lưu được góp ý. Hãy cập nhật CSDL (bảng blog_feedback).';
        }
    }
}

if (isset($_GET['thanks'])) {
    $blogFeedbackFlash = 'Cảm ơn bạn đã đánh giá và góp ý nội dung blog!';
}

$blogPosts = [];
try {
    $blogPosts = $pdo->query(
        'SELECT p.*, u.full_name AS author_name
         FROM blog_posts p
         LEFT JOIN users u ON u.id = p.author_id
         WHERE p.status = \'published\'
         ORDER BY IFNULL(p.published_at, p.created_at) DESC, p.id DESC'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable) {
    $blogPosts = [];
}

$blogDefaultImg = 'https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=900&q=80';
$blogFeatured = $blogPosts[0] ?? null;
$blogGridPosts = count($blogPosts) > 1 ? array_slice($blogPosts, 1) : [];

$activePage = 'blog';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blog Du Lịch - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body class="blog-page">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <section class="hero-section blog-hero">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Blog du lịch chi tiết</h1>
        <p class="hero-subtitle">
          Cẩm nang, review, ẩm thực, tin khuyến mãi và trải nghiệm thực tế từ khách
          hàng.
        </p>
      </div>
    </section>

    <section class="blog-main" data-blog-root>
      <div class="container">
        <div class="blog-search-filter">
          <div class="blog-search-box">
            <i class="fas fa-search"></i>
            <input
              id="blog-search"
              type="text"
              placeholder="Tìm bài viết theo từ khóa, điểm đến, món ăn..."
            />
          </div>
          <div class="blog-filter-group">
            <button type="button" class="blog-filter-btn is-active" data-filter="all">Tất cả</button>
            <button type="button" class="blog-filter-btn" data-filter="cam-nang">Cẩm nang</button>
            <button type="button" class="blog-filter-btn" data-filter="review">Review</button>
            <button type="button" class="blog-filter-btn" data-filter="am-thuc">Văn hóa & Ẩm thực</button>
            <button type="button" class="blog-filter-btn" data-filter="tin-tuc">Tin tức & Khuyến mãi</button>
            <button type="button" class="blog-filter-btn" data-filter="testimonials">Câu chuyện khách hàng</button>
          </div>
        </div>
        <div class="blog-filter-context" id="blog-filter-context">
          <h3 id="blog-context-title">Tất cả bài viết</h3>
          <p id="blog-context-desc">
            Tổng hợp cẩm nang, review, ẩm thực, tin ưu đãi và chia sẻ thực tế để bạn
            lên kế hoạch chuyến đi nhanh hơn.
          </p>
          <span id="blog-result-count">Đang hiển thị 10 bài viết</span>
        </div>
        <div class="blog-category-detail" id="blog-category-detail">
          <h4>Nội dung nổi bật trong chuyên mục</h4>
          <ul id="blog-category-points">
            <li>Gợi ý lịch trình thực tế theo ngân sách và thời gian.</li>
            <li>Thông tin cập nhật mới giúp bạn đặt tour tự tin hơn.</li>
            <li>Liên kết nhanh sang tour liên quan để đặt ngay.</li>
          </ul>
        </div>

        <?php if (!empty($blogFeedbackFlash)): ?>
          <div class="blog-flash blog-flash--ok"><?= htmlspecialchars($blogFeedbackFlash, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if (!empty($blogFeedbackErr)): ?>
          <div class="blog-flash blog-flash--err"><?= htmlspecialchars($blogFeedbackErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php require __DIR__ . '/../includes/blog_listing_fragment.php'; ?>


        <section class="blog-interaction" aria-labelledby="blog-interaction-title">
          <h2 id="blog-interaction-title"><i class="fas fa-star"></i> Đánh giá &amp; góp ý blog</h2>
          <p>Chấm điểm 5 sao và để lại bình luận để chúng tôi cải thiện nội dung.</p>
          <form method="post" class="blog-feedback-form">
            <input type="hidden" name="form" value="blog_feedback" />
            <fieldset class="star-rating-input">
              <legend class="visually-hidden">Chọn từ 1 đến 5 sao</legend>
              <?php for ($s = 5; $s >= 1; $s--): ?>
                <input type="radio" name="rating" id="blog-fs-<?= $s ?>" value="<?= $s ?>" <?= $s === 5 ? 'checked' : '' ?> required />
                <label for="blog-fs-<?= $s ?>" title="<?= $s ?> sao"><i class="fas fa-star"></i></label>
              <?php endfor; ?>
            </fieldset>
            <label for="blog-feedback-comment">Bình luận của bạn</label>
            <textarea id="blog-feedback-comment" name="comment" rows="4" required placeholder="Chia sẻ trải nghiệm đọc blog..."></textarea>
            <button type="submit" class="profile-btn">Gửi đánh giá</button>
          </form>
        </section>

        <div id="blog-empty" class="blog-empty" hidden>
          <i class="fas fa-inbox"></i>
          <h3>Không tìm thấy bài viết phù hợp</h3>
          <p>Thử đổi từ khóa hoặc chọn lại danh mục khác.</p>
        </div>
      </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
    <script>
      (function () {
        var root = document.querySelector("[data-blog-root]");
        if (!root) return;

        var searchInput = root.querySelector("#blog-search");
        if (!searchInput) return;
        var filterBtns = root.querySelectorAll(".blog-filter-btn");
        var cards = root.querySelectorAll(".blog-featured-main, .blog-card");
        var emptyState = root.querySelector("#blog-empty");
        var contextTitle = root.querySelector("#blog-context-title");
        var contextDesc = root.querySelector("#blog-context-desc");
        var resultCount = root.querySelector("#blog-result-count");
        var categoryPoints = root.querySelector("#blog-category-points");
        var currentFilter = "all";
        var contextMap = {
          all: {
            title: "Tất cả bài viết",
            desc: "Tổng hợp cẩm nang, review, ẩm thực, tin ưu đãi và chia sẻ thực tế để bạn lên kế hoạch chuyến đi nhanh hơn.",
            points: [
              "Mỗi bài đều có thông tin áp dụng thực tế cho chuyến đi.",
              "Nội dung đa dạng từ kinh nghiệm, đánh giá đến tin ưu đãi mới.",
              "Có CTA liên kết trực tiếp sang tour liên quan để đặt nhanh.",
            ],
          },
          "cam-nang": {
            title: "Cẩm nang/Kinh nghiệm du lịch",
            desc: "Hướng dẫn chi tiết về lịch trình, thời điểm đẹp và chuẩn bị hành lý cho từng điểm đến.",
            points: [
              "Checklist chuẩn bị trước chuyến đi theo từng loại hình tour.",
              "Gợi ý lịch trình theo ngày, ngân sách và mức độ di chuyển.",
              "Mẹo tránh phát sinh chi phí khi đi mùa cao điểm.",
            ],
          },
          review: {
            title: "Review & Đánh giá",
            desc: "Trải nghiệm thực tế về khách sạn, homestay, nhà hàng và địa điểm check-in nổi bật.",
            points: [
              "Đánh giá thực tế ưu/nhược điểm của từng dịch vụ.",
              "Thông tin mức giá, chất lượng và độ phù hợp từng nhóm khách.",
              "Gợi ý tour tương ứng để bạn chốt lịch trình nhanh hơn.",
            ],
          },
          "am-thuc": {
            title: "Văn hóa & Ẩm thực",
            desc: "Khám phá đặc sản vùng miền, văn hóa bản địa và các trải nghiệm bản sắc địa phương.",
            points: [
              "Danh sách món ngon nên thử theo từng điểm đến.",
              "Gợi ý khung giờ, khu vực ăn uống và chi phí tham khảo.",
              "Kết hợp tham quan văn hóa địa phương trong cùng lịch trình.",
            ],
          },
          "tin-tuc": {
            title: "Tin tức & Khuyến mãi",
            desc: "Cập nhật thời tiết, chính sách du lịch mới, ưu đãi và mã giảm giá tour mới nhất.",
            points: [
              "Thông báo các thay đổi quan trọng ảnh hưởng đến kế hoạch đi tour.",
              "Tổng hợp ưu đãi đang chạy và điều kiện áp dụng cụ thể.",
              "Mẹo săn giá tốt theo thời điểm mở bán tour.",
            ],
          },
          testimonials: {
            title: "Câu chuyện khách hàng",
            desc: "Hình ảnh, video và cảm nhận chân thực từ những khách hàng đã trực tiếp trải nghiệm tour.",
            points: [
              "Cảm nhận thực tế về lịch trình, phương tiện và dịch vụ lưu trú.",
              "Bài học kinh nghiệm khi đi theo nhóm, gia đình hoặc cặp đôi.",
              "Minh chứng uy tín để bạn tham khảo trước khi đặt tour.",
            ],
          },
        };

        function foldVi(str) {
          var s = String(str || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
          s = s.replace(/đ/g, "d").replace(/Đ/g, "d");
          return s.toLowerCase().trim();
        }

        function applyFilter() {
          var keyword = foldVi(searchInput.value || "");
          var visible = 0;

          cards.forEach(function (card) {
            var category = card.getAttribute("data-category") || "";
            var fullText = foldVi(
              card.textContent + " " + (card.getAttribute("data-keywords") || "")
            );

            var categoryMatch = currentFilter === "all" || category === currentFilter;
            var keywordMatch = keyword === "" || fullText.indexOf(keyword) !== -1;
            var shouldShow = categoryMatch && keywordMatch;

            if (card.classList.contains("blog-card")) {
              card.style.display = shouldShow ? "flex" : "none";
            } else {
              card.style.display = shouldShow ? "" : "none";
            }
            if (shouldShow) visible += 1;
          });

          emptyState.hidden = visible !== 0;
          if (contextMap[currentFilter]) {
            contextTitle.textContent = contextMap[currentFilter].title;
            contextDesc.textContent = contextMap[currentFilter].desc;
            categoryPoints.innerHTML = contextMap[currentFilter].points
              .map(function (point) {
                return "<li>" + point + "</li>";
              })
              .join("");
          }
          resultCount.textContent = "Đang hiển thị " + visible + " bài viết";
        }

        filterBtns.forEach(function (btn) {
          btn.addEventListener("click", function () {
            currentFilter = btn.getAttribute("data-filter") || "all";
            filterBtns.forEach(function (b) {
              b.classList.toggle("is-active", b === btn);
            });
            applyFilter();
          });
        });

        searchInput.addEventListener("input", applyFilter);
        applyFilter();
      })();
    </script>
  </body>
</html>

