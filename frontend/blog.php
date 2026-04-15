<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

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

$activePage = 'blog';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Blog Du Lịch - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body class="blog-page">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

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
            <button class="blog-filter-btn is-active" data-filter="all">Tất cả</button>
            <button class="blog-filter-btn" data-filter="cam-nang">Cẩm nang</button>
            <button class="blog-filter-btn" data-filter="review">Review</button>
            <button class="blog-filter-btn" data-filter="am-thuc">Văn hóa & Ẩm thực</button>
            <button class="blog-filter-btn" data-filter="tin-tuc">Tin tức & Khuyến mãi</button>
            <button class="blog-filter-btn" data-filter="testimonials">Câu chuyện khách hàng</button>
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

        <div class="blog-featured">
          <article class="blog-featured-main">
            <img
              src="https://images.unsplash.com/photo-1527631746610-bca00a040d60?auto=format&fit=crop&w=1400&q=80"
              alt="Kinh nghiệm du lịch Đà Lạt"
            />
            <div class="blog-featured-main__content">
              <span class="blog-tag">Cẩm nang/Kinh nghiệm du lịch</span>
              <h2>Kinh nghiệm du lịch Đà Lạt 3 ngày 2 đêm chi tiết từ A-Z</h2>
              <p>
                Lịch trình thực tế, chi phí dự kiến, địa điểm check-in đẹp và gợi ý
                ăn uống phù hợp cho nhóm bạn hoặc gia đình.
              </p>
              <p class="blog-read-row">
                <a href="blog_post.php?slug=dalat-kinh-nghiem" class="blog-read-full">Đọc bài đầy đủ</a>
              </p>
              <a href="tours.php" class="blog-cta-link"
                >Xem ngay các tour Đà Lạt hấp dẫn <i class="fas fa-arrow-right"></i
              ></a>
            </div>
          </article>

          <div class="blog-featured-side">
            <article class="blog-featured-mini">
              <span class="blog-tag">Tin tức & Khuyến mãi</span>
              <h3>Flash Sale tháng 4: Giảm đến 30% tour biển đảo</h3>
              <a href="tours.php">Xem tour khuyến mãi</a>
            </article>
            <article class="blog-featured-mini">
              <span class="blog-tag">Review & Đánh giá</span>
              <h3>Review homestay view đồi cực chill tại Mộc Châu</h3>
              <a href="tours.php">Xem tour Mộc Châu</a>
            </article>
          </div>
        </div>

        <div class="blog-grid" id="blog-grid">
          <article
            class="blog-card"
            data-category="cam-nang"
            data-region="mien-bac"
            data-keywords="sapa mùa đẹp kinh nghiệm trekking"
          >
            <img
              src="https://images.unsplash.com/photo-1549880338-65ddcdfd017b?auto=format&fit=crop&w=900&q=80"
              alt="Đi Sapa mùa nào đẹp nhất"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Cẩm nang/Kinh nghiệm du lịch</span>
              <h3>Đi Sapa mùa nào đẹp nhất? Gợi ý theo từng tháng</h3>
              <p>
                So sánh thời tiết, cảnh sắc và chi phí theo mùa để chọn thời điểm
                phù hợp nhất cho chuyến đi Sapa.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 05/04/2026</span>
                <span><i class="far fa-user"></i> Admin Du Lịch Việt</span>
              </div>
              <a class="blog-cta-btn" href="blog_post.php?slug=sapa-mua-nao-dep">Đọc bài đầy đủ</a>
              <a class="blog-cta-btn blog-cta-btn--secondary" href="tours.php">Xem tour Sapa</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="cam-nang"
            data-region="mien-trung"
            data-keywords="đà nẵng 4 ngày 3 đêm lịch trình tự túc"
          >
            <img
              src="https://images.unsplash.com/photo-1555899434-94d1368aa7af?auto=format&fit=crop&w=900&q=80"
              alt="Lịch trình Đà Nẵng"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Cẩm nang/Kinh nghiệm du lịch</span>
              <h3>Lịch trình Đà Nẵng - Hội An 4 ngày 3 đêm tối ưu chi phí</h3>
              <p>
                Chi tiết giờ đi, điểm tham quan, gợi ý phương tiện và ngân sách
                theo từng ngày cho người đi lần đầu.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 04/04/2026</span>
                <span><i class="far fa-user"></i> Phương Linh</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Đà Nẵng - Hội An</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="review"
            data-region="bien-dao"
            data-keywords="phú quốc review resort bãi kem"
          >
            <img
              src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=900&q=80"
              alt="Review resort Phú Quốc"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Review & Đánh giá</span>
              <h3>Review resort ven biển Phú Quốc: Có đáng tiền?</h3>
              <p>
                Trải nghiệm thực tế phòng ở, bữa sáng, bãi biển riêng và những điểm
                cần lưu ý trước khi đặt phòng.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 03/04/2026</span>
                <span><i class="far fa-user"></i> Thảo Nhi</span>
              </div>
              <a class="blog-cta-btn" href="blog_post.php?slug=review-phu-quoc-resort">Đọc bài đầy đủ</a>
              <a class="blog-cta-btn blog-cta-btn--secondary" href="tours.php">Xem tour Phú Quốc</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="review"
            data-region="mien-trung"
            data-keywords="review nhà hàng hải sản nha trang giá"
          >
            <img
              src="https://images.unsplash.com/photo-1559847844-d721426d6edc?auto=format&fit=crop&w=900&q=80"
              alt="Review hải sản Nha Trang"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Review & Đánh giá</span>
              <h3>Review 5 quán hải sản Nha Trang ngon, giá hợp lý</h3>
              <p>
                Đánh giá thực đơn, mức giá và trải nghiệm phục vụ thực tế để bạn
                tránh chỗ đông nhưng chất lượng không như kỳ vọng.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 31/03/2026</span>
                <span><i class="far fa-user"></i> Mỹ Duyên</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Nha Trang</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="review"
            data-region="mien-bac"
            data-keywords="hà giang review homestay đèo mã pí lèng"
          >
            <img
              src="https://images.unsplash.com/photo-1472396961693-142e6e269027?auto=format&fit=crop&w=900&q=80"
              alt="Review homestay Hà Giang"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Review & Đánh giá</span>
              <h3>Review homestay Hà Giang gần Mã Pí Lèng cho nhóm 4 người</h3>
              <p>
                Đánh giá chi tiết phòng, view, chất lượng dịch vụ và cung đường
                thuận tiện để săn mây sáng sớm.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 02/04/2026</span>
                <span><i class="far fa-user"></i> Trọng Tín</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem ngay tour Hà Giang</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="am-thuc"
            data-region="mien-trung"
            data-keywords="ẩm thực huế bún bò bánh bèo"
          >
            <img
              src="https://images.unsplash.com/photo-1512058564366-18510be2db19?auto=format&fit=crop&w=900&q=80"
              alt="Ẩm thực Huế"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Văn hóa & Ẩm thực</span>
              <h3>Top món đặc sản Huế nhất định phải thử trong 2 ngày</h3>
              <p>
                Danh sách quán ngon, mức giá tham khảo và cách sắp xếp lịch ăn uống
                hợp lý khi đi Huế.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 01/04/2026</span>
                <span><i class="far fa-user"></i> Hải An</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Khám phá tour Huế ngay</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="am-thuc"
            data-region="mien-bac"
            data-keywords="hà nội món ngon phố cổ bún chả phở"
          >
            <img
              src="https://images.unsplash.com/photo-1529692236671-f1dc2a44bf24?auto=format&fit=crop&w=900&q=80"
              alt="Ẩm thực Hà Nội"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Văn hóa & Ẩm thực</span>
              <h3>Food tour phố cổ Hà Nội: ăn gì trong một buổi tối?</h3>
              <p>
                Lộ trình ăn tối từ bún chả, phở cuốn đến chè truyền thống, kèm mức
                giá tham khảo và khung giờ nên đi.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 27/03/2026</span>
                <span><i class="far fa-user"></i> Hoàng Yến</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Hà Nội</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="tin-tuc"
            data-region="mien-nam"
            data-keywords="thời tiết biển đảo khuyến mãi tour hè"
          >
            <img
              src="https://images.unsplash.com/photo-1473116763249-2faaef81ccda?auto=format&fit=crop&w=900&q=80"
              alt="Tin khuyến mãi tour hè"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Tin tức & Khuyến mãi</span>
              <h3>Cập nhật thời tiết du lịch hè và ưu đãi tour tháng này</h3>
              <p>
                Tổng hợp thông tin thời tiết theo vùng và các mã giảm giá nổi bật để
                bạn đặt tour tiết kiệm hơn.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 30/03/2026</span>
                <span><i class="far fa-user"></i> Ban biên tập</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour đang ưu đãi</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="tin-tuc"
            data-region="mien-bac"
            data-keywords="khuyến mãi tour hà giang tháng 5"
          >
            <img
              src="https://images.unsplash.com/photo-1464822759844-d150ad6d1d1b?auto=format&fit=crop&w=900&q=80"
              alt="Khuyến mãi tour Hà Giang"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Tin tức & Khuyến mãi</span>
              <h3>Ưu đãi tour Hà Giang tháng 5: giảm nhóm và quà tặng kèm</h3>
              <p>
                Cập nhật các khung ngày có ưu đãi mạnh, chính sách giảm giá nhóm
                và combo lưu trú áp dụng trong tháng.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 26/03/2026</span>
                <span><i class="far fa-user"></i> CSKH Du Lịch Việt</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Săn tour Hà Giang giá tốt</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="tin-tuc"
            data-region="mien-trung"
            data-keywords="quy định du lịch mới lịch bay đà nẵng"
          >
            <img
              src="https://images.unsplash.com/photo-1493558103817-58b2924bce98?auto=format&fit=crop&w=900&q=80"
              alt="Tin tức du lịch Đà Nẵng"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Tin tức & Khuyến mãi</span>
              <h3>Cập nhật quy định du lịch nội địa và lịch bay hè 2026</h3>
              <p>
                Những thay đổi quan trọng về giấy tờ, hành lý và lịch bay cao điểm
                giúp bạn chủ động đặt tour đúng thời gian.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 29/03/2026</span>
                <span><i class="far fa-user"></i> Ban biên tập</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Đà Nẵng ưu đãi</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="testimonials"
            data-region="mien-bac"
            data-keywords="khách hàng ninh bình trải nghiệm tour"
          >
            <img
              src="https://images.unsplash.com/photo-1527004013197-933c4bb611b3?auto=format&fit=crop&w=900&q=80"
              alt="Câu chuyện khách hàng"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Câu chuyện khách hàng</span>
              <h3>Gia đình chị Hạnh chia sẻ hành trình Ninh Bình 2N1Đ</h3>
              <p>
                Cảm nhận thực tế về lịch trình, chất lượng xe, bữa ăn và sự hỗ trợ
                của hướng dẫn viên trong suốt chuyến đi.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 28/03/2026</span>
                <span><i class="far fa-user"></i> Khách hàng thực tế</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Ninh Bình</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="testimonials"
            data-region="mien-trung"
            data-keywords="feedback khách hàng đà nẵng hội an"
          >
            <img
              src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=900&q=80"
              alt="Feedback tour Đà Nẵng"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Câu chuyện khách hàng</span>
              <h3>Feedback tour Đà Nẵng - Hội An: lịch trình nhẹ nhàng cho gia đình</h3>
              <p>
                Chia sẻ thực tế từ nhóm 3 thế hệ về chất lượng khách sạn, điểm tham
                quan phù hợp người lớn tuổi và trẻ nhỏ.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 23/03/2026</span>
                <span><i class="far fa-user"></i> Khách hàng Trần Gia</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Đà Nẵng - Hội An</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="cam-nang"
            data-region="bien-dao"
            data-keywords="leo núi chuẩn bị gì trekking an toàn"
          >
            <img
              src="https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=900&q=80"
              alt="Cần chuẩn bị gì khi leo núi"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Cẩm nang/Kinh nghiệm du lịch</span>
              <h3>Cần chuẩn bị gì khi leo núi? Checklist không thể bỏ qua</h3>
              <p>
                Hướng dẫn chuẩn bị trang phục, vật dụng, đồ ăn và kỹ năng an toàn
                cho người mới bắt đầu trekking.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 25/03/2026</span>
                <span><i class="far fa-user"></i> Minh Khang</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour trekking</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="am-thuc"
            data-region="mien-nam"
            data-keywords="ẩm thực miền tây chợ nổi bún cá"
          >
            <img
              src="https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=900&q=80"
              alt="Ẩm thực miền Tây"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Văn hóa & Ẩm thực</span>
              <h3>Khám phá ẩm thực miền Tây qua hành trình chợ nổi Cần Thơ</h3>
              <p>
                Gợi ý món đặc sản nên thử, khung giờ tham quan đẹp và mẹo ăn uống
                tiết kiệm khi đi tour sông nước.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 24/03/2026</span>
                <span><i class="far fa-user"></i> Thu Phương</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour miền Tây</a>
            </div>
          </article>

          <article
            class="blog-card"
            data-category="testimonials"
            data-region="bien-dao"
            data-keywords="khách hàng nha trang gia đình trải nghiệm"
          >
            <img
              src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=900&q=80"
              alt="Khách hàng chia sẻ tour Nha Trang"
            />
            <div class="blog-card__content">
              <span class="blog-tag">Câu chuyện khách hàng</span>
              <h3>Video cảm nhận tour Nha Trang 4N3Đ của gia đình anh Dũng</h3>
              <p>
                Hành trình thực tế với trẻ nhỏ, đánh giá lịch trình, bữa ăn và các
                điểm vui chơi phù hợp gia đình.
              </p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> 22/03/2026</span>
                <span><i class="far fa-user"></i> Video khách hàng</span>
              </div>
              <a class="blog-cta-btn" href="tours.php">Xem tour Nha Trang</a>
            </div>
          </article>
        </div>

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

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
    <script>
      (function () {
        var root = document.querySelector("[data-blog-root]");
        if (!root) return;

        var searchInput = root.querySelector("#blog-search");
        var filterBtns = root.querySelectorAll(".blog-filter-btn");
        var cards = root.querySelectorAll(".blog-card");
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

        function normalize(text) {
          return text.toLowerCase().trim();
        }

        function applyFilter() {
          var keyword = normalize(searchInput.value || "");
          var visible = 0;

          cards.forEach(function (card) {
            var category = card.getAttribute("data-category") || "";
            var fullText = normalize(card.textContent + " " + (card.getAttribute("data-keywords") || ""));

            var categoryMatch = currentFilter === "all" || category === currentFilter;
            var keywordMatch = keyword === "" || fullText.includes(keyword);
            var shouldShow = categoryMatch && keywordMatch;

            card.style.display = shouldShow ? "flex" : "none";
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
            currentFilter = btn.getAttribute("data-filter");
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

