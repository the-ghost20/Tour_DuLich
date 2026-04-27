<?php
declare(strict_types=1);

/** @var array<string,mixed>|null $blogFeatured */
/** @var list<array<string,mixed>> $blogGridPosts */
/** @var string $blogDefaultImg */

?>
        <div class="blog-featured">
          <?php if ($blogFeatured): ?>
          <?php
            $featCat = blog_normalize_category(isset($blogFeatured['category']) ? (string) $blogFeatured['category'] : null);
            $featKw = trim((string) ($blogFeatured['keywords'] ?? ''));
            ?>
          <article
            class="blog-featured-main"
            data-category="<?= htmlspecialchars($featCat, ENT_QUOTES, 'UTF-8') ?>"
            data-keywords="<?= htmlspecialchars($featKw, ENT_QUOTES, 'UTF-8') ?>"
          >
            <img
              src="<?= htmlspecialchars((string) (($blogFeatured['featured_image'] ?? '') !== '' ? $blogFeatured['featured_image'] : $blogDefaultImg), ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars((string) ($blogFeatured['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            />
            <div class="blog-featured-main__content">
              <span class="blog-tag"><?= htmlspecialchars((string) (($blogFeatured['tag_label'] ?? '') !== '' ? $blogFeatured['tag_label'] : 'Blog'), ENT_QUOTES, 'UTF-8') ?></span>
              <h2><?= htmlspecialchars((string) ($blogFeatured['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
              <p><?= htmlspecialchars((string) ($blogFeatured['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
              <p class="blog-read-row">
                <a href="blog_detail.php?slug=<?= rawurlencode((string) ($blogFeatured['slug'] ?? '')) ?>" class="blog-read-full">Đọc bài đầy đủ</a>
              </p>
              <a href="tours.php" class="blog-cta-link"
                >Xem thêm tour <i class="fas fa-arrow-right"></i
              ></a>
            </div>
          </article>
          <?php else: ?>
          <p class="cell-muted" style="grid-column:1/-1;padding:16px;">
            Chưa có bài viết xuất bản. Đăng nhập quản trị → mục Blog để thêm bài.
          </p>
          <?php endif; ?>

          <div class="blog-featured-side">
            <article class="blog-featured-mini">
              <span class="blog-tag">Tin tức & Khuyến mãi</span>
              <h3>Flash Sale: tour biển đảo &amp; nghỉ dưỡng</h3>
              <a href="tours.php">Xem tour đang ưu đãi</a>
            </article>
            <article class="blog-featured-mini">
              <span class="blog-tag">Gợi ý</span>
              <h3>Tìm tour theo điểm đến yêu thích</h3>
              <a href="tours.php">Khám phá danh sách tour</a>
            </article>
          </div>
        </div>

        <div class="blog-grid" id="blog-grid">
          <?php foreach ($blogGridPosts as $tPost): ?>
          <?php
            $cat = blog_normalize_category(isset($tPost['category']) ? (string) $tPost['category'] : null);
            $kw = trim((string) ($tPost['keywords'] ?? ''));
            $img = trim((string) ($tPost['featured_image'] ?? '')) !== '' ? (string) $tPost['featured_image'] : $blogDefaultImg;
            $dstr = blog_format_post_date(
                isset($tPost['published_at']) ? (string) $tPost['published_at'] : null,
                isset($tPost['created_at']) ? (string) $tPost['created_at'] : null
            );
            $auth = trim((string) ($tPost['author_name'] ?? '')) !== '' ? (string) $tPost['author_name'] : 'Ban biên tập';
            ?>
          <article
            class="blog-card"
            data-category="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"
            data-region=""
            data-keywords="<?= htmlspecialchars($kw, ENT_QUOTES, 'UTF-8') ?>"
          >
            <img
              src="<?= htmlspecialchars($img, ENT_QUOTES, 'UTF-8') ?>"
              alt="<?= htmlspecialchars((string) ($tPost['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            />
            <div class="blog-card__content">
              <span class="blog-tag"><?= htmlspecialchars((string) (($tPost['tag_label'] ?? '') !== '' ? $tPost['tag_label'] : 'Blog'), ENT_QUOTES, 'UTF-8') ?></span>
              <h3><?= htmlspecialchars((string) ($tPost['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
              <p><?= htmlspecialchars((string) ($tPost['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
              <div class="blog-meta">
                <span><i class="far fa-calendar"></i> <?= htmlspecialchars($dstr, ENT_QUOTES, 'UTF-8') ?></span>
                <span><i class="far fa-user"></i> <?= htmlspecialchars($auth, ENT_QUOTES, 'UTF-8') ?></span>
              </div>
              <a class="blog-cta-btn" href="blog_detail.php?slug=<?= rawurlencode((string) ($tPost['slug'] ?? '')) ?>">Đọc bài đầy đủ</a>
              <a class="blog-cta-btn blog-cta-btn--secondary" href="tours.php">Xem tour</a>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
