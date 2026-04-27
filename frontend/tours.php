<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$_isLoggedIn = !empty($_SESSION['user_id']);
$_jsIsLoggedIn = $_isLoggedIn ? 'true' : 'false';

$tours = [];

try {
    $stmt = $pdo->prepare(
        'SELECT id, tour_name, description, destination, duration, price, image_url, available_slots
         FROM tours
         WHERE ' . tour_sql_public_visible() . '
         ORDER BY id DESC'
    );
    $stmt->execute();
    $tours = $stmt->fetchAll();
} catch (Throwable $exception) {
    $tours = [];
}

$tourPriceSliderMax = 20000000;
if (!empty($tours)) {
    $maxP = max(array_map(static fn(array $t): float => (float) $t['price'], $tours));
    $tourPriceSliderMax = max(5000000, (int) (ceil($maxP / 250000) * 250000));
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

/** Các khóa khớp checkbox địa điểm trong sidebar (slug + từ khóa). */
function filterDestinationKeys(string $destination): string
{
    $d  = mb_strtolower(trim($destination), 'UTF-8');
    $keys = [];
    $slug = slugify($destination);
    if ($slug !== '') {
        $keys[] = $slug;
    }
    if (preg_match('/hạ\s*long|ha\s*long|vịnh\s*hạ\s*long/i', $d) || str_contains($slug, 'ha-long')) {
        $keys[] = 'ha-long';
    }
    if (preg_match('/phú\s*quốc|phu\s*quoc/i', $d) || str_contains($slug, 'phu-quoc')) {
        $keys[] = 'phu-quoc';
    }
    if (preg_match('/sài\s*gòn|sai\s*gon|hồ\s*chí\s*minh|tp\.?\s*hcm|hcm\b/i', $d)) {
        $keys[] = 'sai-gon';
    }
    if (preg_match('/mù\s*cang\s*chai|mu\s*cang\s*chai/i', $d)) {
        $keys[] = 'mu-cang-chai';
    }
    if (preg_match('/vũng\s*tàu|vung\s*tau/i', $d)) {
        $keys[] = 'vung-tau';
    }
    if (preg_match('/miền\s*tây|mien\s*tay|cần\s*thơ|bến\s*tre|an\s*giang|châu\s*đốc|cà\s*mau|sóc\s*trăng/i', $d)) {
        $keys[] = 'mien-tay';
    }
    return implode(' ', array_unique($keys));
}

/** Tag loại tour: domestic | international + beach | adventure | cultural */
function filterTourTypeTags(string $destination, string $tourName, string $description = ''): string
{
    $blob = mb_strtolower($destination . ' ' . $tourName . ' ' . $description, 'UTF-8');
    $tags = [];
    $intl = (bool) preg_match(
        '/singapore|trung quốc|thượng hải|hàng châu|thái lan|thailand|nhật bản|japan|malaysia|indonesia|pháp|paris|úc|australia|quốc tế|international|campuchia|lào|myanmar|đài loan|taiwan|hàn quốc|korea/i',
        $blob
    );
    $tags[] = $intl ? 'international' : 'domestic';

    if (preg_match('/biển|phú quốc|nha trang|đảo|vịnh|bãi|san hô|vinpearl|lặn|mỹ khê/i', $blob)) {
        $tags[] = 'beach';
    }
    if (preg_match('/núi|sapa|fansipan|mạo hiểm|trek|đèo|jeep|bản làng|cáp treo/i', $blob)) {
        $tags[] = 'adventure';
    }
    if (preg_match('/cố đô|huế|văn hóa|di sản|đại nội|chùa|đình|bảo tàng|phố cổ|hội an|động|hang/i', $blob)) {
        $tags[] = 'cultural';
    }

    return implode(' ', array_unique($tags));
}
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Danh Sách Tour - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body class="tours-page">
    <?php
      $activePage = 'tours';
      require __DIR__ . '/../includes/header.php';
    ?>

    <!-- HERO SECTION -->
    <section
      class="hero-section tours-hero"
      style="
        background-image: url(&quot;https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=1600&h=700&fit=crop&quot;);
      "
    >
      <div class="hero-overlay tours-hero-overlay"></div>
      <div class="hero-content tours-hero-content">
        <p class="tours-hero-eyebrow">
          <i class="fas fa-compass"></i> Khám phá Việt Nam
        </p>
        <h1 class="hero-title tours-hero-title">Tour du lịch</h1>
        <p class="hero-subtitle tours-hero-subtitle">
          Chọn hành trình phù hợp — giá minh bạch, đặt nhanh, đồng hành trọn vẹn
        </p>
        <div class="tours-hero-stats" aria-label="Thông tin nhanh">
          <div class="tours-hero-stat">
            <strong><?= count($tours) ?></strong>
            <span>Tour đang mở bán</span>
          </div>
          <div class="tours-hero-stat">
            <strong>24/7</strong>
            <span>Hỗ trợ đặt tour</span>
          </div>
        </div>
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
                max="<?= (int) $tourPriceSliderMax ?>"
                value="<?= (int) $tourPriceSliderMax ?>"
                class="slider"
                data-price-max="<?= (int) $tourPriceSliderMax ?>"
              />
              <div class="price-display">
                <span
                  >Tối đa: <strong id="price-value"><?= number_format((int) $tourPriceSliderMax, 0, ',', '.') ?> đ</strong></span
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
          <div class="search-section tours-search-section">
            <div class="tours-search-wrap">
              <i class="fas fa-search" aria-hidden="true"></i>
              <input
                type="text"
                id="search-input"
                placeholder="Tìm theo tên tour hoặc điểm đến..."
                class="search-field tours-search-field"
                autocomplete="off"
              />
            </div>
            <button type="button" class="btn-search-small tours-search-btn" aria-label="Tìm kiếm">
              <i class="fas fa-arrow-right"></i>
            </button>
          </div>
          <div class="sort-section tours-sort-section">
            <label for="sort-by">Sắp xếp</label>
            <select id="sort-by" class="sort-dropdown tours-sort-dropdown">
              <option value="default">Mặc định</option>
              <option value="price-low">Giá: Thấp đến Cao</option>
              <option value="price-high">Giá: Cao đến Thấp</option>
              <option value="rating">Đánh Giá Cao Nhất</option>
              <option value="newest">Mới Nhất</option>
            </select>
          </div>
        </div>

        <!-- TOURS GRID -->
        <div class="tours-grid" id="tours-grid" data-tours-static="1">
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
                $descRaw = isset($tour['description']) ? (string) $tour['description'] : '';
                $priceText = number_format((float) $tour['price'], 0, ',', '.') . ' đ';
                $imageUrl = !empty($tour['image_url'])
                    ? htmlspecialchars((string) $tour['image_url'], ENT_QUOTES, 'UTF-8')
                    : 'https://images.unsplash.com/photo-1528127269322-539801943592?w=400&h=300&fit=crop';
                $tourId = (int) $tour['id'];
                $slots = isset($tour['available_slots']) ? (int) $tour['available_slots'] : 0;
                $slotsLow = $slots > 0 && $slots <= 12;
                $destSlug = slugify((string) $tour['destination']);
                $regionKeys = htmlspecialchars(filterDestinationKeys((string) $tour['destination']), ENT_QUOTES, 'UTF-8');
                $typeTags = htmlspecialchars(filterTourTypeTags((string) $tour['destination'], (string) $tour['tour_name'], $descRaw), ENT_QUOTES, 'UTF-8');
                $dataSearch = htmlspecialchars(
                    mb_strtolower((string) $tour['tour_name'] . ' ' . (string) $tour['destination'], 'UTF-8'),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $priceNum = (int) round((float) $tour['price']);
              ?>
              <article
                class="tour-card"
                data-tour-id="<?= $tourId ?>"
                data-destination="<?= htmlspecialchars($destSlug, ENT_QUOTES, 'UTF-8') ?>"
                data-filter-regions="<?= $regionKeys ?>"
                data-search-text="<?= $dataSearch ?>"
                data-price="<?= $priceNum ?>"
                data-duration="<?= durationFilterTag((string) $tour['duration']) ?>"
                data-tour-tags="<?= $typeTags ?>"
                data-rating="4.5"
              >
                <div class="tour-card-image">
                  <img src="<?= $imageUrl ?>" alt="<?= $tourName ?>" loading="lazy" />
                  <div class="tour-card-image-shine" aria-hidden="true"></div>
                  <div class="tour-card-badges">
                    <span class="tour-chip tour-chip--duration"><i class="fas fa-clock" aria-hidden="true"></i> <?= $duration ?></span>
                    <?php if ($slotsLow): ?>
                    <span class="tour-chip tour-chip--slots">Còn <?= $slots ?> chỗ</span>
                    <?php endif; ?>
                  </div>
                  <div class="tour-card-overlay">
                    <a href="tour_detail.php?id=<?= $tourId ?>" class="tour-card-quick-view">Xem chi tiết</a>
                  </div>
                  <button type="button" class="btn-wishlist btn-wishlist--card" title="Thêm vào yêu thích" aria-label="Thêm vào yêu thích" aria-pressed="false">
                    <i class="far fa-heart" aria-hidden="true"></i>
                  </button>
                </div>
                <div class="tour-card-content">
                  <h3><a href="tour_detail.php?id=<?= $tourId ?>" class="tour-title-link"><?= $tourName ?></a></h3>
                  <div class="tour-meta tour-meta--inline">
                    <span class="tour-destination"><i class="fas fa-location-dot" aria-hidden="true"></i> <?= $destination ?></span>
                  </div>
                  <div class="tour-rating">
                    <div class="stars" aria-hidden="true">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star-half-alt"></i>
                    </div>
                    <span class="rating-count">4.5 · 120 đánh giá</span>
                  </div>
                  <div class="tour-card-footer">
                    <div class="tour-price-block">
                      <span class="tour-price-label">Giá từ</span>
                      <span class="tour-price"><?= $priceText ?></span>
                    </div>
                    <div class="tour-card-footer-btns">
                      <a href="tour_detail.php?id=<?= $tourId ?>" class="btn-tour-detail">Chi tiết</a>
                      <button class="btn-book" type="button"
                        data-tour-id="<?= $tourId ?>"
                        data-tour-name="<?= $tourName ?>"
                        data-tour-price="<?= (float) $tour['price'] ?>">Đặt tour</button>
                    </div>
                  </div>
                </div>
              </article>
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

    <?php require __DIR__ . '/../includes/booking_modal.php'; ?>

    <script>
      window.__PHP_IS_LOGGED_IN__ = <?= $_jsIsLoggedIn ?>;
    </script>
    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
