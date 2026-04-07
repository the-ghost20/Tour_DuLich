<?php
declare(strict_types=1);

$activePage = 'pricing';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bảng Giá - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>
  <body class="pricing-page">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <section class="hero-section pricing-hero">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Bảng giá tour du lịch trọn gói</h1>
        <p class="hero-subtitle">
          Giá minh bạch theo từng hạng tour, phù hợp từ tiết kiệm đến cao cấp cho
          chuyến đi của bạn.
        </p>
      </div>
    </section>

    <section class="pricing-section-v2" data-pricing-root>
      <div class="container">
        <div class="pricing-intro">
          <h2>Chọn hạng tour phù hợp ngân sách và trải nghiệm</h2>
          <p>
            Mỗi hạng tour đã bao gồm các dịch vụ cốt lõi. Bạn có thể chọn giá ngày
            thường hoặc xem trước mức phụ thu mùa cao điểm.
          </p>
        </div>

        <div class="pricing-highlights">
          <div class="pricing-highlight-item">
            <i class="fas fa-rocket"></i>
            <div>
              <h3>Giá theo từng tuyến tour</h3>
              <p>Mỗi mức giá gắn với lịch trình cụ thể, không nhập nhằng hạng dịch vụ</p>
            </div>
          </div>
          <div class="pricing-highlight-item">
            <i class="fas fa-shield-alt"></i>
            <div>
              <h3>Bao gồm và phụ thu minh bạch</h3>
              <p>Hiển thị rõ phần đã gồm, chưa gồm và phụ thu cuối tuần/lễ Tết</p>
            </div>
          </div>
          <div class="pricing-highlight-item">
            <i class="fas fa-headset"></i>
            <div>
              <h3>Giữ chỗ nhanh, xác nhận rõ</h3>
              <p>Đặt cọc linh hoạt, xác nhận lịch khởi hành và danh sách dịch vụ trước chuyến đi</p>
            </div>
          </div>
        </div>

        <div class="pricing-billing">
          <div class="pricing-billing-inner" role="group" aria-label="Chu kỳ thanh toán">
            <button
              type="button"
              class="pricing-billing-btn is-active"
              data-cycle="monthly"
            >
              Giá ngày thường
            </button>
            <button type="button" class="pricing-billing-btn" data-cycle="yearly">
              Giá cao điểm
              <span class="pricing-billing-badge">Lễ/Tết</span>
            </button>
          </div>
        </div>

        <div class="pricing-grid-v2">
          <article
            class="pricing-card-v2"
            data-monthly="2490000"
            data-yearly="2890000"
          >
            <div class="pricing-card-v2__icon" aria-hidden="true">
              <i class="fas fa-user"></i>
            </div>
            <h3>Tour Tiết Kiệm</h3>
            <p class="pricing-card-v2__desc">Phù hợp nhóm bạn trẻ, gia đình nhỏ</p>
            <div class="pricing-card-v2__price">
              <span class="pricing-card-v2__amount">
                <span class="js-price-amount">2.490.000</span>đ
              </span>
              <span class="pricing-card-v2__period js-price-period">/khách</span>
              <span class="pricing-card-v2__saving">
                <i class="fas fa-leaf"></i> Phụ thu cao điểm từ 400.000đ/khách
              </span>
            </div>
            <ul class="pricing-card-v2__list">
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Tour ghép đoàn <strong>3N2Đ</strong> trong nước</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Khách sạn tiêu chuẩn 2-3 sao</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Xe đưa đón + 5 bữa ăn theo chương trình</span>
              </li>
            </ul>
            <button type="button" class="pricing-card-v2__cta pricing-card-v2__cta--outline">
              Xem lịch khởi hành
            </button>
          </article>

          <article
            class="pricing-card-v2 pricing-card-v2--featured"
            data-monthly="4290000"
            data-yearly="4990000"
          >
            <span class="pricing-card-v2__badge">
              <i class="fas fa-crown"></i>Bán chạy nhất
            </span>
            <div class="pricing-card-v2__icon" aria-hidden="true">
              <i class="fas fa-store"></i>
            </div>
            <h3>Tour Phổ Biến</h3>
            <p class="pricing-card-v2__desc">Lựa chọn cân bằng giữa chi phí và trải nghiệm</p>
            <div class="pricing-card-v2__price">
              <span class="pricing-card-v2__amount">
                <span class="js-price-amount">4.290.000</span>đ
              </span>
              <span class="pricing-card-v2__period js-price-period">/khách</span>
              <span class="pricing-card-v2__saving">
                <i class="fas fa-leaf"></i> Phụ thu cao điểm từ 700.000đ/khách
              </span>
            </div>
            <ul class="pricing-card-v2__list">
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Tour trọn gói <strong>4N3Đ</strong> điểm đến hot</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Khách sạn 3-4 sao, vé tham quan chính</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Hướng dẫn viên kinh nghiệm và bảo hiểm du lịch</span>
              </li>
            </ul>
            <button type="button" class="pricing-card-v2__cta pricing-card-v2__cta--solid">
              Đặt tour ngay
            </button>
          </article>

          <article
            class="pricing-card-v2"
            data-monthly="7990000"
            data-yearly="8990000"
          >
            <div class="pricing-card-v2__icon" aria-hidden="true">
              <i class="fas fa-building"></i>
            </div>
            <h3>Tour Cao Cấp</h3>
            <p class="pricing-card-v2__desc">Dành cho khách cần trải nghiệm nghỉ dưỡng chất lượng cao</p>
            <div class="pricing-card-v2__price">
              <span class="pricing-card-v2__amount">
                <span class="js-price-amount">7.990.000</span>đ
              </span>
              <span class="pricing-card-v2__period js-price-period">/khách</span>
              <span class="pricing-card-v2__saving">
                <i class="fas fa-leaf"></i> Phụ thu cao điểm từ 1.000.000đ/khách
              </span>
            </div>
            <ul class="pricing-card-v2__list">
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Tour nghỉ dưỡng <strong>5N4Đ</strong> khách sạn/resort 4-5 sao</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Lịch trình riêng linh hoạt, xe đời mới</span>
              </li>
              <li>
                <i class="fas fa-check-circle"></i>
                <span>Ưu tiên check-in, quà tặng và hỗ trợ 24/7</span>
              </li>
            </ul>
            <button type="button" class="pricing-card-v2__cta pricing-card-v2__cta--dark">
              Nhận tư vấn riêng
            </button>
          </article>
        </div>

        <div class="pricing-guarantee">
          <div class="pricing-guarantee__icon">
            <i class="fas fa-check-double"></i>
          </div>
          <div class="pricing-guarantee__content">
            <h3>Cam kết giá đúng như báo trước khi khởi hành</h3>
            <p>
              Bảng giá, lịch trình, chính sách cọc và hoàn/hủy được gửi đầy đủ trước
              khi thanh toán để bạn an tâm chốt tour.
            </p>
          </div>
          <button type="button" class="pricing-card-v2__cta pricing-card-v2__cta--solid">
            Nhận báo giá theo đoàn
          </button>
        </div>

        <div class="pricing-faq">
          <h3>Câu hỏi thường gặp</h3>
          <div class="pricing-faq-grid">
            <article class="pricing-faq-item">
              <h4>Giá hiển thị là giá trọn gói hay giá cơ bản?</h4>
              <p>
                Giá hiển thị là giá theo gói dịch vụ của từng hạng tour. Các khoản
                chưa bao gồm (nếu có) sẽ được liệt kê rõ ngay dưới lịch trình.
              </p>
            </article>
            <article class="pricing-faq-item">
              <h4>Khi nào áp dụng giá cao điểm?</h4>
              <p>
                Giá cao điểm áp dụng vào dịp lễ, Tết và giai đoạn cao điểm du lịch.
                Mức phụ thu được thông báo trước khi bạn đặt cọc.
              </p>
            </article>
            <article class="pricing-faq-item">
              <h4>Đặt cọc và hoàn/hủy tính như thế nào?</h4>
              <p>
                Bạn có thể giữ chỗ bằng tiền cọc. Chính sách hoàn/hủy được tính theo
                mốc thời gian trước ngày khởi hành và ghi rõ trong xác nhận đặt tour.
              </p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
    <script>
      (function () {
        var root = document.querySelector("[data-pricing-root]");
        if (!root) return;
        var buttons = root.querySelectorAll(".pricing-billing-btn");
        var cards = root.querySelectorAll("[data-monthly]");

        function formatVnd(n) {
          return new Intl.NumberFormat("vi-VN").format(n);
        }

        function setCycle(cycle) {
          buttons.forEach(function (b) {
            b.classList.toggle("is-active", b.getAttribute("data-cycle") === cycle);
          });
          cards.forEach(function (card) {
            var monthly = parseInt(card.getAttribute("data-monthly"), 10);
            var yearly = parseInt(card.getAttribute("data-yearly"), 10);
            var amountEl = card.querySelector(".js-price-amount");
            var periodEl = card.querySelector(".js-price-period");
            if (!amountEl || !periodEl) return;
            if (cycle === "monthly") {
              amountEl.textContent = formatVnd(monthly);
              periodEl.textContent = "/khách";
            } else {
              amountEl.textContent = formatVnd(yearly);
              periodEl.textContent = "/khách";
            }
            card.classList.toggle("is-yearly", cycle === "yearly");
          });
        }

        buttons.forEach(function (btn) {
          btn.addEventListener("click", function () {
            setCycle(btn.getAttribute("data-cycle"));
          });
        });
      })();
    </script>
  </body>
</html>

