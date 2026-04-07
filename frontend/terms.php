<?php
declare(strict_types=1);

$activePage = '';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Điều khoản sử dụng - Du Lịch Việt</title>
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
        <h1 class="hero-title">Điều khoản sử dụng</h1>
        <p class="hero-subtitle">
          Vui lòng đọc kỹ các quy định trước khi đặt tour và sử dụng dịch vụ
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
              <a href="#scope"><i class="fas fa-compass"></i>Phạm vi áp dụng</a>
              <a href="#booking"
                ><i class="fas fa-calendar-check"></i>Đặt tour và xác nhận</a
              >
              <a href="#payment"
                ><i class="fas fa-credit-card"></i>Giá và thanh toán</a
              >
              <a href="#change"><i class="fas fa-repeat"></i>Đổi/Hủy dịch vụ</a>
              <a href="#responsibility"
                ><i class="fas fa-handshake"></i>Trách nhiệm các bên</a
              >
              <a href="#limitation"
                ><i class="fas fa-shield-alt"></i>Giới hạn trách nhiệm</a
              >
              <a href="#contact"><i class="fas fa-headset"></i>Liên hệ</a>
            </nav>
          </aside>

          <div class="terms-content">
            <article class="terms-card" id="scope">
              <h3><i class="fas fa-compass"></i>Phạm vi áp dụng</h3>
              <p>
                Điều khoản này áp dụng cho hoạt động truy cập website, đặt tour
                và sử dụng dịch vụ của Du Lịch Việt. Việc tiếp tục sử dụng hệ
                thống đồng nghĩa với việc bạn đồng ý các điều khoản hiện hành.
              </p>
            </article>

            <article class="terms-card" id="booking">
              <h3>
                <i class="fas fa-calendar-check"></i>Đặt tour và xác nhận dịch vụ
              </h3>
              <p>
                Khách hàng cần cung cấp thông tin hành khách chính xác theo giấy
                tờ hợp lệ. Đơn đặt tour chỉ có hiệu lực sau khi được hệ thống hoặc
                bộ phận chăm sóc khách hàng xác nhận, và Du Lịch Việt có quyền từ
                chối các yêu cầu không đầy đủ hoặc không hợp lệ.
              </p>
            </article>

            <article class="terms-card" id="payment">
              <h3>
                <i class="fas fa-credit-card"></i>Giá tour, thanh toán và ưu đãi
              </h3>
              <p>
                Giá tour được áp dụng theo thời điểm đặt dịch vụ và theo nội dung
                đã công bố. Các chương trình ưu đãi có thời hạn riêng, không tự
                động cộng dồn nếu không có thông báo cụ thể, và mọi khoản phụ thu
                phát sinh (nếu có) sẽ được thông tin trước khi thanh toán.
              </p>
            </article>

            <article class="terms-card" id="change">
              <h3><i class="fas fa-repeat"></i>Chính sách đổi/hủy dịch vụ</h3>
              <p>
                Việc đổi hoặc hủy dịch vụ được xử lý theo thời điểm yêu cầu và
                điều kiện của tour đã xác nhận. Càng gần ngày khởi hành, mức phí
                hủy có thể càng cao do chi phí vận hành đã phát sinh từ phía nhà
                cung cấp dịch vụ.
              </p>
            </article>

            <article class="terms-card" id="responsibility">
              <h3>
                <i class="fas fa-handshake"></i>Quyền và trách nhiệm của các bên
              </h3>
              <p>
                Du Lịch Việt cam kết cung cấp thông tin minh bạch và hỗ trợ đúng
                quy trình trong suốt hành trình. Khách hàng có trách nhiệm tuân
                thủ lịch trình, chuẩn bị giấy tờ cần thiết và phối hợp xử lý các
                tình huống phát sinh trên tinh thần hợp tác.
              </p>
            </article>

            <article class="terms-card" id="limitation">
              <h3>
                <i class="fas fa-shield-alt"></i>Giới hạn trách nhiệm và bất khả kháng
              </h3>
              <p>
                Du Lịch Việt không chịu trách nhiệm với gián đoạn do thiên tai,
                dịch bệnh, thay đổi chính sách nhập cảnh hoặc các sự kiện ngoài
                tầm kiểm soát. Chúng tôi sẽ hỗ trợ phương án thay thế phù hợp.
              </p>
            </article>

            <article class="terms-card terms-contact" id="contact">
              <h3><i class="fas fa-headset"></i>Điều chỉnh điều khoản và liên hệ</h3>
              <p>
                Điều khoản có thể được cập nhật để phù hợp với quy định pháp luật
                và chính sách vận hành mới. Nếu cần hỗ trợ, vui lòng liên hệ:
              </p>
              <p>
                <strong>Email:</strong> support@dulichviet.com<br />
                <strong>Hotline:</strong> (028) 1234 5678
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

