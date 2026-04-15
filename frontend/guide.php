<?php
declare(strict_types=1);

$activePage = '';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Hướng dẫn đặt tour - Du Lịch Việt</title>
    <link rel="stylesheet" href="css/styles.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>

  <body class="terms-page">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero-section terms-hero">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Hướng dẫn đặt tour</h1>
        <p class="hero-subtitle">
          Quy trình đặt tour nhanh gọn, dễ theo dõi
        </p>
      </div>
    </section>

    <!-- CONTENT -->
    <section class="terms-section">
      <div class="container">
        <div class="terms-layout">
          <aside class="terms-sidebar">
            <nav class="terms-toc">
              <h3>Mục lục nhanh</h3>
              <a href="#step-1"><i class="fas fa-magnifying-glass"></i>Chọn tour</a>
              <a href="#step-2"><i class="fas fa-user-pen"></i>Nhập thông tin</a>
              <a href="#step-3"><i class="fas fa-credit-card"></i>Thanh toán</a>
              <a href="#step-4"><i class="fas fa-envelope-open-text"></i>Xác nhận</a>
              <a href="#note"><i class="fas fa-circle-info"></i>Lưu ý quan trọng</a>
              <a href="#contact"><i class="fas fa-headset"></i>Hỗ trợ</a>
            </nav>
          </aside>

          <div class="terms-content">
            <article class="terms-card" id="step-1">
              <h3><i class="fas fa-magnifying-glass"></i>Bước 1: Chọn tour phù hợp</h3>
              <p>
                Vào trang Tour du lịch, lọc theo điểm đến, ngân sách hoặc thời gian.
                Kiểm tra lịch trình, ngày khởi hành và điều kiện đổi/hủy trước khi
                đặt.
              </p>
            </article>

            <article class="terms-card" id="step-2">
              <h3><i class="fas fa-user-pen"></i>Bước 2: Điền thông tin đặt tour</h3>
              <p>
                Nhập thông tin liên hệ của người đại diện và số lượng khách. Vui lòng
                cung cấp chính xác để việc xác nhận và hỗ trợ diễn ra nhanh chóng.
              </p>
            </article>

            <article class="terms-card" id="step-3">
              <h3><i class="fas fa-credit-card"></i>Bước 3: Chọn phương thức thanh toán</h3>
              <p>
                Bạn có thể thanh toán qua chuyển khoản, thẻ hoặc ví điện tử (nếu hỗ
                trợ). Hệ thống sẽ hiển thị tổng chi phí trước khi bạn xác nhận.
              </p>
            </article>

            <article class="terms-card" id="step-4">
              <h3><i class="fas fa-envelope-open-text"></i>Bước 4: Nhận xác nhận đặt chỗ</h3>
              <p>
                Sau khi thanh toán thành công, bạn sẽ nhận email hoặc cuộc gọi xác
                nhận cùng thông tin đơn hàng và hướng dẫn trước ngày khởi hành.
              </p>
            </article>

            <article class="terms-card" id="note">
              <h3><i class="fas fa-circle-info"></i>Lưu ý cần thiết</h3>
              <p>
                Chuẩn bị giấy tờ tùy thân hợp lệ, đến đúng giờ tập trung và giữ liên
                lạc với bộ phận chăm sóc khách hàng khi cần đổi lịch hoặc hỗ trợ khẩn.
              </p>
            </article>

            <article class="terms-card terms-contact" id="contact">
              <h3><i class="fas fa-headset"></i>Cần hỗ trợ đặt tour?</h3>
              <p>Nếu gặp khó khăn trong quá trình đặt, vui lòng liên hệ:</p>
              <p>
                <strong>Email:</strong> support@dulichviet.com<br />
                <strong>Hotline:</strong> (028) 1234 5678<br />
                <strong>Chat:</strong> Có sẵn 24/7 trên website
              </p>
            </article>
          </div>
        </div>
      </div>
    </section>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
    <script src="js/script.js"></script>
  </body>
</html>

