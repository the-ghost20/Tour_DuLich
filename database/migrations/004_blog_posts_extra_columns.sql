-- Ảnh đại diện, chuyên mục, nhãn, từ khóa — blog
-- mysql -u root -p tour_dulich < database/migrations/004_blog_posts_extra_columns.sql

ALTER TABLE `blog_posts`
  ADD COLUMN `featured_image` VARCHAR(500) NULL DEFAULT NULL AFTER `excerpt`,
  ADD COLUMN `category` VARCHAR(32) NULL DEFAULT NULL AFTER `featured_image`,
  ADD COLUMN `tag_label` VARCHAR(160) NULL DEFAULT NULL AFTER `category`,
  ADD COLUMN `keywords` VARCHAR(400) NULL DEFAULT NULL AFTER `tag_label`;
