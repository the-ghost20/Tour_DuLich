<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$tours = [];

try {
    $stmt = $pdo->prepare(
        "SELECT id, tour_name, description, destination, duration, price, image_url, available_slots
         FROM tours
         WHERE status = 'hiện'
         ORDER BY id DESC"
    );
    $stmt->execute();
    $tours = $stmt->fetchAll();
} catch (Throwable $exception) {
    $tours = [];
}

function slugify(string $value): string
{
    $value = mb_strtolower(trim($value), 'UTF-8');
    $map = [
        'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a',
        'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
        'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
        'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
        'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
        'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
        'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o',
        'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o',
        'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
        'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u',
        'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
        'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
        'đ' => 'd',
    ];
    $value = strtr($value, $map);
    $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';
    return trim($value, '-');
}

function durationFilterTag(string $duration): string
{
    if (preg_match('/(\d+)\s*n/iu', $duration, $matches)) {
        $days = (int) $matches[1];
        if ($days <= 1) {
            return '1-day';
        }
        if ($days === 2) {
            return '2-day';
        }
        if ($days === 3) {
            return '3-day';
        }
    }
    return '4-day';
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Danh Sách Tour - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body>
    <!-- NAVIGATION BAR -->
    <nav class="navbar">
      <div class="navbar-container">
        <a href="index.html" class="navbar-logo">
          <i class="fas fa-map-marker-alt"></i>
          <span>Du lịch Việt</span>
        </a>

        <ul class="navbar-menu">
          <li><a href="index.html" class="nav-link">TRANG CHỦ</a></li>
          <li><a href="about.html" class="nav-link">GIỚI THIỆU</a></li>
          <li><a href="tours.php" class="nav-link active">TOUR DU LỊCH</a></li>
          <li><a href="pricing.html" class="nav-link">BẢNG GIÁ</a></li>
          <li><a href="blog.html" class="nav-link">BLOG</a></li>
          <li><a href="#contact" class="nav-link">LIÊN HỆ</a></li>
        </ul>

        <div class="navbar-right">
          <div class="search-box">
            <input type="text" placeholder="Tra cứu..." class="search-input" />
            <i class="fas fa-search"></i>
          </div>
          <button class="btn-login">ĐĂNG NHẬP</button>
        </div>

        <div class="mobile-toggle">
          <i class="fas fa-bars"></i>
        </div>
      </div>
    </nav>

    <!-- HERO SECTION -->
    <section
      class="hero-section"
      style="
        background-image: url(&quot;https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1200&h=400&fit=crop&quot;);
      "
    >
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Danh Sách Tour</h1>
        <p class="hero-subtitle">
          Khám phá những điểm đến tuyệt vời trên khắp Việt Nam
        </p>
      </div>
    </section>

    <!-- TOURS LISTING WITH FILTERS -->
    <div class="container tours-container">
      <!-- SIDEBAR FILTERS -->
      <aside class="sidebar">
        <div class="filter-section">
          <h3 class="filter-title">
            <i class="fas fa-sliders-h"></i> Lọc Tour
          </h3>

          <!-- DESTINATION FILTER -->
          <div class="filter-group">
            <h4>Địa Điểm</h4>
            <div class="filter-options">
              <label class="filter-checkbox">
                <input type="checkbox" name="destination" value="mien-tay" />
                <span>Miền Tây</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="destination" value="ha-long" />
                <span>Hạ Long</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="destination" value="phu-quoc" />
                <span>Phú Quốc</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="destination" value="sai-gon" />
                <span>Sài Gòn</span>
              </label>
              <label class="filter-checkbox">
                <input
                  type="checkbox"
                  name="destination"
                  value="mu-cang-chai"
                />
                <span>Mù Cang Chải</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="destination" value="vung-tau" />
                <span>Vũng Tàu</span>
              </label>
            </div>
          </div>

          <!-- PRICE FILTER -->
          <div class="filter-group">
            <h4>Giá Tiền</h4>
            <div class="price-range">
              <input
                type="range"
                id="price-slider"
                name="price-range"
                min="0"
                max="5000000"
                value="5000000"
                class="slider"
              />
              <div class="price-display">
                <span
                  >Tối đa: <strong id="price-value">5.000.000 đ</strong></span
                >
              </div>
            </div>
          </div>

          <!-- DURATION FILTER -->
          <div class="filter-group">
            <h4>Thời Hạn</h4>
            <div class="filter-options">
              <label class="filter-checkbox">
                <input type="checkbox" name="duration" value="1-day" />
                <span>1 Ngày</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="duration" value="2-day" />
                <span>2 Ngày</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="duration" value="3-day" />
                <span>3 Ngày</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="duration" value="4-day" />
                <span>4 Ngày trở lên</span>
              </label>
            </div>
          </div>

          <!-- TOUR TYPE FILTER -->
          <div class="filter-group">
            <h4>Loại Tour</h4>
            <div class="filter-options">
              <label class="filter-checkbox">
                <input type="checkbox" name="tour-type" value="domestic" />
                <span>Trong Nước</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="tour-type" value="adventure" />
                <span>Mạo Hiểm</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="tour-type" value="beach" />
                <span>Biển Đảo</span>
              </label>
              <label class="filter-checkbox">
                <input type="checkbox" name="tour-type" value="cultural" />
                <span>Văn Hóa</span>
              </label>
            </div>
          </div>

          <!-- FILTER BUTTONS -->
          <div class="filter-buttons">
            <button id="apply-filter" class="btn btn-primary">
              Áp Dụng Lọc
            </button>
            <button id="clear-filter" class="btn btn-secondary">Xóa Lọc</button>
          </div>
        </div>

        <!-- WISHLIST SIDEBAR -->
        <div class="wishlist-sidebar">
          <h3><i class="fas fa-heart"></i> Yêu Thích</h3>
          <div id="wishlist-items">
            <p class="empty-wishlist">Chưa có tour yêu thích</p>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT -->
      <main class="main-content">
        <!-- SORT AND SEARCH -->
        <div class="tours-toolbar">
          <div class="search-section">
            <input
              type="text"
              id="search-input"
              placeholder="Tìm tour..."
              class="search-field"
            />
            <button class="btn-search-small">
              <i class="fas fa-search"></i>
            </button>
          </div>
          <div class="sort-section">
            <label for="sort-by">Sắp xếp:</label>
            <select id="sort-by" class="sort-dropdown">
              <option value="default">Mặc định</option>
              <option value="price-low">Giá: Thấp đến Cao</option>
              <option value="price-high">Giá: Cao đến Thấp</option>
              <option value="rating">Đánh Giá Cao Nhất</option>
              <option value="newest">Mới Nhất</option>
            </select>
          </div>
        </div>

        <!-- TOURS GRID -->
        <div class="tours-grid" id="tours-grid">
          <?php if (empty($tours)): ?>
          <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Hiện chưa có tour khả dụng</h3>
            <p>Vui lòng quay lại sau để xem thêm tour mới.</p>
          </div>
          <?php else: ?>
            <?php foreach ($tours as $tour): ?>
              <?php
                $tourName = htmlspecialchars((string) $tour['tour_name'], ENT_QUOTES, 'UTF-8');
                $destination = htmlspecialchars((string) $tour['destination'], ENT_QUOTES, 'UTF-8');
                $duration = htmlspecialchars((string) $tour['duration'], ENT_QUOTES, 'UTF-8');
                $priceText = number_format((float) $tour['price'], 0, ',', '.') . ' đ';
                $imageUrl = !empty($tour['image_url'])
                    ? htmlspecialchars((string) $tour['image_url'], ENT_QUOTES, 'UTF-8')
                    : 'https://images.unsplash.com/photo-1528127269322-539801943592?w=400&h=300&fit=crop';
                $tourId = (int) $tour['id'];
              ?>
              <div
                class="tour-card"
                data-tour-id="<?= $tourId ?>"
                data-destination="<?= slugify((string) $tour['destination']) ?>"
                data-duration="<?= durationFilterTag((string) $tour['duration']) ?>"
                data-type="domestic"
                data-rating="4.5"
              >
                <div class="tour-card-image">
                  <img src="<?= $imageUrl ?>" alt="<?= $tourName ?>" />
                  <div class="tour-card-overlay">
                    <button class="btn-wishlist" title="Thêm vào yêu thích">
                      <i class="fas fa-heart"></i>
                    </button>
                  </div>
                </div>
                <div class="tour-card-content">
                  <h3><?= $tourName ?></h3>
                  <div class="tour-meta">
                    <span class="tour-duration"><i class="fas fa-calendar"></i> <?= $duration ?></span>
                    <span class="tour-destination"><i class="fas fa-map-marker-alt"></i> <?= $destination ?></span>
                  </div>
                  <div class="tour-rating">
                    <div class="stars">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="rating-count">(120 đánh giá)</span>
                  </div>
                  <div class="tour-card-footer">
                    <span class="tour-price"><?= $priceText ?></span>
                    <button class="btn-book">Đặt Ngay</button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- PAGINATION -->
        <div class="pagination">
          <button class="pagination-btn" disabled>
            <i class="fas fa-chevron-left"></i> Trước
          </button>
          <button class="pagination-btn active">1</button>
          <button class="pagination-btn">2</button>
          <button class="pagination-btn">3</button>
          <span class="pagination-dots">...</span>
          <button class="pagination-btn">10</button>
          <button class="pagination-btn">
            Sau <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </main>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
      <div class="container">
        <div class="footer-grid">
          <div class="footer-col">
            <h4>Du Lịch Việt</h4>
            <p>
              Chúng tôi cam kết mang đến những chuyến đi ý nghĩa và kỳ niệm khó
              quên cho mọi gia đình Việt.
            </p>
            <div class="contact-info">
              <p>
                <i class="fas fa-map-marker-alt"></i> 12 Nguyễn Văn Bảo, Gò Vấp,
                TP HCM
              </p>
              <p><i class="fas fa-phone"></i> (+84) 778-118 008</p>
              <p><i class="fas fa-envelope"></i> thairan2706@gmail.com</p>
            </div>
          </div>
          <div class="footer-col">
            <h4>DANH MỤC</h4>
            <ul>
              <li><a href="index.html">Trang chủ</a></li>
              <li><a href="about.html">Giới thiệu</a></li>
              <li><a href="tours.php">Tour trong nước</a></li>
              <li><a href="tours.php">Tour quốc tế</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>HỖ TRỢ</h4>
            <ul>
              <li><a href="terms.html">Điều khoản sử dụng</a></li>
              <li><a href="privacy.html">Chính sách bảo mật</a></li>
              <li><a href="guide.html">Hướng dẫn đặt tour</a></li>
              <li><a href="faq.html">FAQ</a></li>
            </ul>
          </div>
          <div class="footer-col">
            <h4>KẾT NỐI VỚI CHÚNG TÔI</h4>
            <p>Đăng ký để nhận tin khuyến mãi mới nhất!</p>
            <div class="social-links">
              <a href="#" title="Facebook"><i class="fab fa-facebook"></i></a>
              <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
              <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
            </div>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; 2026 Du lịch Việt.</p>
        </div>
      </div>
    </footer>

    <script src="js/script.js"></script>
  </body>
</html>
