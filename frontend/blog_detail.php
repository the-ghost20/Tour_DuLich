<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/blog_helpers.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';

if ($slug === '' || !blog_slug_is_safe($slug)) {
    http_response_code(404);
    ?>
    <!doctype html>
    <html lang="vi">
      <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Không tìm thấy bài viết - Du Lịch Việt</title>
        <link rel="stylesheet" href="../assets/css/style.css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
      </head>
      <body>
        <?php $activePage = 'blog'; require __DIR__ . '/../includes/header.php'; ?>
        <section class="blog-post-missing">
          <div class="container">
            <h1>Bài viết không tồn tại</h1>
            <p>Đường dẫn không hợp lệ hoặc bài đã gỡ.</p>
            <a href="blog.php" class="profile-btn">← Quay lại blog</a>
          </div>
        </section>
        <?php require __DIR__ . '/../includes/footer.php'; ?>
      </body>
    </html>
    <?php
    exit;
}

$post = null;
try {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.full_name AS author_name
         FROM blog_posts p
         LEFT JOIN users u ON u.id = p.author_id
         WHERE p.slug = :slug AND p.status = :st
         LIMIT 1'
    );
    $stmt->execute(['slug' => $slug, 'st' => 'published']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pub = $row['published_at'] ?? $row['created_at'] ?? null;
        $ts = $pub ? strtotime((string) $pub) : false;
        $post = [
            'title'  => (string) $row['title'],
            'tag'    => trim((string) ($row['tag_label'] ?? '')) !== '' ? (string) $row['tag_label'] : 'Blog',
            'date'   => $ts ? date('d/m/Y', $ts) : '',
            'author' => trim((string) ($row['author_name'] ?? '')) !== '' ? (string) $row['author_name'] : 'Ban biên tập',
            'image'  => trim((string) ($row['featured_image'] ?? '')) !== ''
                ? (string) $row['featured_image']
                : 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=1400&q=80',
            'body'   => (string) ($row['body'] ?? ''),
        ];
    }
} catch (Throwable) {
    $post = null;
}

if ($post === null) {
    $articles = require __DIR__ . '/../includes/blog_articles.php';
    if (!isset($articles[$slug])) {
        http_response_code(404);
        ?>
        <!doctype html>
        <html lang="vi">
          <head>
            <meta charset="UTF-8" />
            <meta name="viewport" content="width=device-width, initial-scale=1.0" />
            <title>Không tìm thấy bài viết - Du Lịch Việt</title>
            <link rel="stylesheet" href="../assets/css/style.css" />
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
          </head>
          <body>
            <?php $activePage = 'blog'; require __DIR__ . '/../includes/header.php'; ?>
            <section class="blog-post-missing">
              <div class="container">
                <h1>Bài viết chưa có bản đầy đủ</h1>
                <p>Slug không khớp hoặc nội dung đang được biên tập.</p>
                <a href="blog.php" class="profile-btn">← Quay lại blog</a>
              </div>
            </section>
            <?php require __DIR__ . '/../includes/footer.php'; ?>
          </body>
        </html>
        <?php
        exit;
    }
    $post = $articles[$slug];
}

$title = htmlspecialchars((string) $post['title'], ENT_QUOTES, 'UTF-8');
$tag = htmlspecialchars((string) $post['tag'], ENT_QUOTES, 'UTF-8');
$date = htmlspecialchars((string) $post['date'], ENT_QUOTES, 'UTF-8');
$author = htmlspecialchars((string) $post['author'], ENT_QUOTES, 'UTF-8');
$image = htmlspecialchars((string) $post['image'], ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?> - Blog Du Lịch Việt</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  </head>
  <body class="blog-page">
    <?php $activePage = 'blog'; require __DIR__ . '/../includes/header.php'; ?>

    <article class="blog-post-read">
      <header class="blog-post-read__hero" style="background-image: url('<?= $image ?>');">
        <div class="blog-post-read__overlay"></div>
        <div class="container blog-post-read__hero-inner">
          <span class="blog-tag"><?= $tag ?></span>
          <h1><?= $title ?></h1>
          <div class="blog-post-read__meta">
            <span><i class="far fa-calendar"></i> <?= $date ?></span>
            <span><i class="far fa-user"></i> <?= $author ?></span>
          </div>
        </div>
      </header>
      <div class="container blog-post-read__body">
        <div class="blog-post-read__content">
          <?= $post['body'] ?>
        </div>
        <p class="blog-post-read__back">
          <a href="blog.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách blog</a>
        </p>
      </div>
    </article>

    <?php require __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/main.js"></script>
  </body>
</html>
