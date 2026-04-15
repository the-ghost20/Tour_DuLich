<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$activePage = 'about';

$statCustomers = 0;
$statBookings = 0;
$statToursOnSale = 0;

try {
    $statCustomers = (int) $pdo->query(
        "SELECT COUNT(*) FROM users WHERE role = 'user'"
    )->fetchColumn();
    $statBookings = (int) $pdo->query(
        'SELECT COUNT(*) FROM bookings'
    )->fetchColumn();
    $row = $pdo->query(
        "SELECT SUM(status = 'hiện') AS visible FROM tours"
    )->fetch();
    $statToursOnSale = (int) ($row['visible'] ?? 0);
} catch (Throwable) {
    // Giữ 0 nếu truy vấn lỗi
}

$fmtStatPlus = static function (int $n): string {
    return htmlspecialchars(number_format($n, 0, ',', '.') . '+', ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Giới Thiệu - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>

  <body>
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero-section about-hero">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Giới Thiệu</h1>
        <p class="hero-subtitle">
          Câu chuyện và giá trị cốt lõi của Du lịch Việt
        </p>
      </div>
    </section>

    <!-- ABOUT CONTENT -->
    <section class="about-main">
      <div class="container">
        <!-- STORY SECTION -->
        <div class="story-section">
          <div class="story-text">
            <h2>Câu chuyện thương hiệu</h2>
            <h3>Về chúng tôi</h3>
            <p>
              Khởi nguồn từ tình yêu xê dịch, Du lịch Việt ra đời với sứ mệnh
              mang đến những trải nghiệm du lịch trọn vẹn, an toàn và đáng nhớ
              cho mọi hành trình.
            </p>
            <p>
              Chúng tôi tin rằng mỗi chuyến đi không chỉ là khám phá một vùng
              đất mới, mà còn là cơ hội để kết nối, tận hưởng và lưu giữ những
              kỷ niệm đẹp cùng gia đình, bạn bè.
            </p>
            <p>
              Với đội ngũ tận tâm, lịch trình chỉnh chu và dịch vụ minh bạch, Du
              lịch Việt luôn nỗ lực để mỗi khách hàng đều cảm nhận được sự khác
              biệt ngay từ lần đầu trải nghiệm.
            </p>
          </div>
          <div class="story-img">
            <img
              src="https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=900&q=80"
              alt="Vịnh Hạ Long — cảnh đẹp Việt Nam"
            />
          </div>
        </div>

        <!-- VALUES SECTION -->
        <div class="values-section">
          <h2>Tại sao chọn chúng tôi?</h2>
          <div class="values-container">
            <div class="value-card">
              <i class="fas fa-shield-alt"></i>
              <h3>An Toàn</h3>
              <p>Bảo hiểm toàn bộ chuyến đi, hỗ trợ 24/7 cho mọi khách hàng</p>
            </div>
            <div class="value-card">
              <i class="fas fa-hand-holding-heart"></i>
              <h3>Minh Bạch</h3>
              <p>Giá rõ ràng, không phí ẩn, dịch vụ trung thực</p>
            </div>
            <div class="value-card">
              <i class="fas fa-star"></i>
              <h3>Chất Lượng</h3>
              <p>Lịch trình chỉn chu, dịch vụ được chọn lọc kỹ lưỡng</p>
            </div>
            <div class="value-card">
              <i class="fas fa-headset"></i>
              <h3>Hỗ Trợ 24/7</h3>
              <p>Luôn đồng hành và hỗ trợ bạn trong suốt hành trình</p>
            </div>
          </div>
        </div>

        <!-- STATS SECTION -->
        <div class="stats-section">
          <div class="stat-item">
            <i class="fas fa-users" aria-hidden="true"></i>
            <h3><?= $fmtStatPlus($statCustomers) ?></h3>
            <p>Khách hàng hài lòng</p>
          </div>
          <div class="stat-item">
            <i class="fas fa-suitcase-rolling" aria-hidden="true"></i>
            <h3><?= $fmtStatPlus($statBookings) ?></h3>
            <p>Tour được đặt</p>
          </div>
          <div class="stat-item">
            <i class="fas fa-location-dot" aria-hidden="true"></i>
            <h3><?= $fmtStatPlus($statToursOnSale) ?></h3>
            <p>Tour đang mở bán</p>
          </div>
          <div class="stat-item">
            <i class="fas fa-headset" aria-hidden="true"></i>
            <h3>24/7</h3>
            <p>Hỗ trợ khách hàng</p>
          </div>
        </div>
      </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>

