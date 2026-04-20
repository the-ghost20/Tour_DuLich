<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$hotTours = [];

try {
    $stmt = $pdo->prepare(
        'SELECT id, tour_name, price, image_url
         FROM tours
         WHERE ' . tour_sql_public_visible() . '
         ORDER BY id DESC
         LIMIT 6'
    );
    $stmt->execute();
    $hotTours = $stmt->fetchAll();
} catch (Throwable $exception) {
    $hotTours = [];
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Du Lịch Việt - Đặt Tour Du Lịch Online</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>

  <body>
    <?php
      $activePage = 'home';
      require __DIR__ . '/../includes/header.php';
    ?>

    <!-- HERO SECTION WITH SEARCH -->
    <section
      class="hero-section"
      style="
        background-image: url(&quot;https://images.unsplash.com/photo-1528127269322-539801943592?auto=format&fit=crop&w=1920&q=80&quot;);
      "
    >
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Khám phá Việt Nam</h1>
        <p class="hero-subtitle">
          Trải nghiệm những hành trình độc đáo và chuyên nghiệp nhất
        </p>

        <div class="hero-search">
          <input
            type="text"
            placeholder="Bạn muốn đi đâu? (Ví dụ: Hà Nội, Phú Quốc...)"
            class="hero-search-input"
          />
          <button class="btn-search">
            <i class="fas fa-search"></i>
            TÌM KIẾM
          </button>
        </div>
      </div>
    </section>

    <!-- HOT TOURS SECTION -->
    <section class="hot-tours-section">
      <div class="container">
        <div class="section-header">
          <h2>TOUR BÁN CHẠY NHẤT</h2>
          <a href="tours.php" class="see-all-link"
            >Xem tất cả <i class="fas fa-arrow-right"></i
          ></a>
        </div>

        <div class="hot-tours-grid" id="hot-tours-grid" data-hot-tours-static="1">
          <?php if (empty($hotTours)): ?>
            <div class="hot-tour-card">
              <div class="tour-info">
                <h3>Hiện chưa có tour khả dụng</h3>
                <div class="tour-price">
                  <span class="price">Vui lòng quay lại sau</span>
                </div>
                <a href="tours.php" class="btn-detail">Xem tour</a>
              </div>
            </div>
          <?php else: ?>
            <?php foreach ($hotTours as $tour): ?>
              <?php
                $tourName = htmlspecialchars((string) $tour['tour_name'], ENT_QUOTES, 'UTF-8');
                $priceText = number_format((float) $tour['price'], 0, ',', '.') . ' đ';
                $imageUrl = !empty($tour['image_url'])
                  ? htmlspecialchars((string) $tour['image_url'], ENT_QUOTES, 'UTF-8')
                  : 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1200&h=800&fit=crop';
              ?>
              <div class="hot-tour-card">
                <div class="tour-image">
                  <img src="<?= $imageUrl ?>" alt="<?= $tourName ?>" />
                  <span class="hot-badge">Hot</span>
                </div>
                <div class="tour-info">
                  <h3><?= $tourName ?></h3>
                  <div class="tour-price">
                    <span class="price"><?= $priceText ?></span>
                  </div>
                  <a href="tour_detail.php?id=<?= (int) $tour['id'] ?>" class="btn-detail">Chi tiết tour</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- FEATURES SECTION -->
    <section class="features-section">
      <div class="container">
        <div class="features-grid">
          <div class="feature-card">
            <i class="fas fa-shield-alt"></i>
            <h3>Đảm Bảo An Toàn</h3>
            <p>Bảo hiểm toàn bộ chuyến đi, hỗ trợ 24/7</p>
          </div>
          <div class="feature-card">
            <i class="fas fa-dollar-sign"></i>
            <h3>Giá Cạnh Tranh</h3>
            <p>Giá tốt nhất trên thị trường, không phí ẩn</p>
          </div>
          <div class="feature-card">
            <i class="fas fa-users"></i>
            <h3>Hướng Dẫn Chuyên Nghiệp</h3>
            <p>Đội hướng dẫn viên giàu kinh nghiệm</p>
          </div>
          <div class="feature-card">
            <i class="fas fa-map"></i>
            <h3>Tour Đa Dạng</h3>
            <p>Từ trong nước đến quốc tế, đủ loại</p>
          </div>
        </div>
      </div>
    </section>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>

