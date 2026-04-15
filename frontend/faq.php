<?php
declare(strict_types=1);

$activePage = '';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FAQ - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  </head>

  <body class="faq-page">
    <?php require_once __DIR__ . '/../includes/header.php'; ?>

    <!-- HERO SECTION -->
    <section class="hero-section faq-hero">
      <div class="hero-overlay"></div>
      <div class="hero-content">
        <h1 class="hero-title">Câu hỏi thường gặp</h1>
        <p class="hero-subtitle">
          Giải đáp nhanh các thắc mắc phổ biến khi đặt tour
        </p>
      </div>
    </section>

    <!-- CONTENT -->
    <section class="faq-v2-section">
      <div class="container">
        <div class="faq-v2-wrap">
          <div class="faq-v2-header">
            <h2>Giải đáp nhanh</h2>
            <p>
              Những câu hỏi quan trọng nhất khi đặt tour tại Du Lịch Việt.
            </p>
          </div>

          <div class="faq-v2-list">
            <details class="faq-v2-item">
              <summary>
                <span>Làm sao để đặt tour?</span>
                <i class="fas fa-plus"></i>
              </summary>
              <p>
                Chọn tour, nhập thông tin khách và hoàn tất thanh toán. Xác nhận
                đặt chỗ sẽ được gửi qua email hoặc hotline.
              </p>
            </details>

            <details class="faq-v2-item">
              <summary>
                <span>Giá tour đã gồm các khoản nào?</span>
                <i class="fas fa-plus"></i>
              </summary>
              <p>
                Mục “Bao gồm/Không bao gồm” trong chi tiết tour thể hiện rõ toàn bộ
                chi phí trước khi bạn xác nhận.
              </p>
            </details>

            <details class="faq-v2-item">
              <summary>
                <span>Có thể đổi hoặc hủy tour không?</span>
                <i class="fas fa-plus"></i>
              </summary>
              <p>
                Có. Chính sách đổi/hủy phụ thuộc từng tour và thời điểm yêu cầu;
                càng sát ngày đi, phí xử lý có thể tăng.
              </p>
            </details>

            <details class="faq-v2-item">
              <summary>
                <span>Hỗ trợ thanh toán bằng gì?</span>
                <i class="fas fa-plus"></i>
              </summary>
              <p>
                Hệ thống hỗ trợ chuyển khoản, thẻ và ví điện tử (tùy thời điểm).
                Các lựa chọn hiện hành sẽ hiển thị khi tạo đơn.
              </p>
            </details>

            <details class="faq-v2-item">
              <summary>
                <span>Cần chuẩn bị gì trước khi khởi hành?</span>
                <i class="fas fa-plus"></i>
              </summary>
              <p>
                Chuẩn bị giấy tờ tùy thân hợp lệ, hành lý phù hợp thời tiết và theo
                dõi thông báo lịch trình từ Du Lịch Việt.
              </p>
            </details>
          </div>

          <div class="faq-v2-contact">
            <h3><i class="fas fa-headset"></i>Chưa thấy câu trả lời?</h3>
            <p>
              <strong>Email:</strong> support@dulichviet.com |
              <strong>Hotline:</strong> (028) 1234 5678
            </p>
          </div>
        </div>
      </div>
    </section>

    <?php require_once __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>

