<?php
declare(strict_types=1);

$activePage = 'wishlist';
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tour yêu thích - Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  </head>
  <body>
    <?php require __DIR__ . '/../includes/header.php'; ?>

    <section class="wishlist-page-hero">
      <div class="container">
        <h1><i class="fas fa-heart"></i> Tour yêu thích</h1>
        <p>Danh sách tour bạn đã lưu trên trình duyệt này. Đăng nhập và đặt tour khi bạn đã sẵn sàng.</p>
      </div>
    </section>

    <div class="container wishlist-page-wrap" id="wishlist-page-root">
      <div id="wishlist-page-list" class="wishlist-page-list"></div>
      <div id="wishlist-page-actions" class="wishlist-page-actions">
        <button type="button" id="clear-all-wishlist" class="btn btn-secondary" disabled>Xóa tất cả</button>
      </div>
      <p class="wishlist-page-hint">
        Gợi ý: mở <a href="tours.php">danh sách tour</a> và nhấn biểu tượng <i class="fas fa-heart"></i> để thêm vào đây.
      </p>
    </div>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
  </body>
</html>
