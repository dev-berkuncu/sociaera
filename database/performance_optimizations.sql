-- Sociaera Performance Optimizations Migration
-- Execute this file in your MySQL/phpMyAdmin

-- 1. Add counter columns to checkins
ALTER TABLE `checkins` ADD COLUMN `like_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `image`;
ALTER TABLE `checkins` ADD COLUMN `comment_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `like_count`;
ALTER TABLE `checkins` ADD COLUMN `repost_count` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `comment_count`;

-- 2. Backfill existing counters
UPDATE `checkins` c SET `like_count` = (SELECT COUNT(*) FROM `post_likes` pl WHERE pl.checkin_id = c.id);
UPDATE `checkins` c SET `comment_count` = (SELECT COUNT(*) FROM `post_comments` pc WHERE pc.checkin_id = c.id AND pc.is_deleted = 0);
UPDATE `checkins` c SET `repost_count` = (SELECT COUNT(*) FROM `post_reposts` pr WHERE pr.checkin_id = c.id);

-- 3. Add necessary indexes
-- For global feed
CREATE INDEX `idx_is_deleted_created_at` ON `checkins` (`is_deleted`, `created_at` DESC);

-- For users search and mentions
CREATE INDEX `idx_username` ON `users` (`username`);

-- For venues search
CREATE INDEX `idx_name` ON `venues` (`name`);
