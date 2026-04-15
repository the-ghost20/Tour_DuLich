<?php
declare(strict_types=1);

$activePage = '';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Chính sách bảo mật - Du Lịch Việt</title>
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
        <h1 class="hero-title">Chính sách bảo mật</h1>
        <p class="hero-subtitle">
          Cách Du Lịch Việt thu thập, sử dụng và bảo vệ dữ liệu của bạn
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
              <a href="#collect"><i class="fas fa-database"></i>Thông tin thu thập</a>
              <a href="#use"><i class="fas fa-gears"></i>Mục đích sử dụng</a>
              <a href="#share"><i class="fas fa-share-nodes"></i>Chia sẻ dữ liệu</a>
              <a href="#security"><i class="fas fa-shield-alt"></i>Bảo mật dữ liệu</a>
              <a href="#rights"><i class="fas fa-user-check"></i>Quyền của bạn</a>
              <a href="#contact"><i class="fas fa-headset"></i>Liên hệ</a>
            </nav>
          </aside>

          <div class="terms-content">
            <article class="terms-card" id="collect">
              <h3><i class="fas fa-database"></i>Thông tin chúng tôi thu thập</h3>
              <p>
                Chúng tôi chỉ thu thập thông tin cần cho việc đặt tour và hỗ trợ
                khách hàng: họ tên, số điện thoại, email, thông tin hành khách và
                dữ liệu thanh toán ở mức cần thiết.
              </p>
            </article>

            <article class="terms-card" id="use">
              <h3><i class="fas fa-gears"></i>Mục đích sử dụng dữ liệu</h3>
              <p>
                Dữ liệu được dùng để xác nhận đơn, liên hệ trước chuyến đi, xử lý
                thanh toán, chăm sóc sau bán và cải thiện trải nghiệm dịch vụ.
              </p>
            </article>

            <article class="terms-card" id="share">
              <h3><i class="fas fa-share-nodes"></i>Chia sẻ thông tin</h3>
              <p>
                Du Lịch Việt không bán dữ liệu cá nhân. Thông tin chỉ được chia sẻ
                cho đối tác liên quan đến việc thực hiện tour hoặc khi có yêu cầu
                hợp pháp từ cơ quan chức năng.
              </p>
            </article>

            <article class="terms-card" id="security">
              <h3><i class="fas fa-shield-alt"></i>Bảo mật và lưu trữ</h3>
              <p>
                Chúng tôi áp dụng biện pháp kỹ thuật và quy trình nội bộ để bảo vệ
                dữ liệu, giới hạn truy cập theo vai trò và lưu trữ trong thời gian
                cần thiết theo mục đích vận hành hoặc quy định pháp luật.
              </p>
            </article>

            <article class="terms-card" id="rights">
              <h3><i class="fas fa-user-check"></i>Quyền của khách hàng</h3>
              <p>
                Bạn có quyền yêu cầu xem, cập nhật hoặc xóa dữ liệu cá nhân (trong
                phạm vi pháp luật cho phép), và có thể từ chối nhận thông tin tiếp
                thị bất cứ lúc nào.
              </p>
            </article>

            <article class="terms-card terms-contact" id="contact">
              <h3><i class="fas fa-headset"></i>Liên hệ xử lý dữ liệu</h3>
              <p>Khi cần hỗ trợ về quyền riêng tư, vui lòng liên hệ:</p>
              <p>
                <strong>Email:</strong> privacy@dulichviet.com<br />
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

