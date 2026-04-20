-- ========================================================
-- Sociaera — Veritabanı Şeması
-- GTA World TR Sosyal Keşif & Check-in Platformu
-- ========================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";
SET NAMES utf8mb4;

-- ── Kullanıcılar ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `tag` VARCHAR(30) DEFAULT NULL,
    `email` VARCHAR(100) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `avatar` VARCHAR(255) DEFAULT NULL,
    `banner` VARCHAR(255) DEFAULT NULL,
    `bio` VARCHAR(280) DEFAULT NULL,
    `gta_user_id` INT DEFAULT NULL,
    `gta_username` VARCHAR(100) DEFAULT NULL,
    `gta_character_id` INT DEFAULT NULL,
    `gta_character_name` VARCHAR(100) DEFAULT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `is_premium` TINYINT(1) NOT NULL DEFAULT 0,
    `banned_until` DATETIME DEFAULT NULL,
    `last_login_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_username` (`username`),
    UNIQUE KEY `uk_email` (`email`),
    UNIQUE KEY `uk_tag` (`tag`),
    KEY `idx_gta_user` (`gta_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Mekanlar ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `venues` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `facebrowser_url` VARCHAR(255) DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_status_created` (`status`, `created_at`),
    KEY `idx_category` (`category`),
    KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Check-in'ler (= Gönderiler) ──────────────────────────
CREATE TABLE IF NOT EXISTS `checkins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `venue_id` INT UNSIGNED NOT NULL,
    `note` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `is_flagged` TINYINT(1) NOT NULL DEFAULT 0,
    `is_excluded_from_leaderboard` TINYINT(1) NOT NULL DEFAULT 0,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_created` (`user_id`, `created_at`),
    KEY `idx_venue_created` (`venue_id`, `created_at`),
    KEY `idx_created` (`created_at`),
    KEY `idx_deleted` (`is_deleted`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Beğeniler ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `post_likes` (
    `user_id` INT UNSIGNED NOT NULL,
    `checkin_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `checkin_id`),
    KEY `idx_checkin` (`checkin_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`checkin_id`) REFERENCES `checkins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Repostlar ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `post_reposts` (
    `user_id` INT UNSIGNED NOT NULL,
    `checkin_id` INT UNSIGNED NOT NULL,
    `quote` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `checkin_id`),
    KEY `idx_checkin` (`checkin_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`checkin_id`) REFERENCES `checkins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Yorumlar ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `post_comments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `checkin_id` INT UNSIGNED NOT NULL,
    `comment` TEXT NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_checkin_created` (`checkin_id`, `created_at`),
    KEY `idx_user` (`user_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`checkin_id`) REFERENCES `checkins`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Takip İlişkileri ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_follows` (
    `follower_id` INT UNSIGNED NOT NULL,
    `following_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`follower_id`, `following_id`),
    KEY `idx_following` (`following_id`),
    FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Bildirimler ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `from_user_id` INT UNSIGNED DEFAULT NULL,
    `type` VARCHAR(30) NOT NULL COMMENT 'mention, like, comment, follow, repost',
    `content` TEXT DEFAULT NULL,
    `checkin_id` INT UNSIGNED DEFAULT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_read_created` (`user_id`, `is_read`, `created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`checkin_id`) REFERENCES `checkins`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Cüzdan ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `wallets` (
    `user_id` INT UNSIGNED PRIMARY KEY,
    `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── İşlem Geçmişi ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `type` ENUM('deposit','withdraw','transfer_in','transfer_out') NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `reference_id` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_created` (`user_id`, `created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Reklamlar ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `ads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `image_url` VARCHAR(500) NOT NULL,
    `link_url` VARCHAR(500) DEFAULT NULL,
    `position` ENUM('carousel','sidebar_left','sidebar_right','footer_banner') NOT NULL DEFAULT 'carousel',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Sistem Ayarları ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Admin İşlem Logları ───────────────────────────────────
CREATE TABLE IF NOT EXISTS `admin_logs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `action_type` VARCHAR(50) NOT NULL,
    `target_type` VARCHAR(50) NOT NULL,
    `target_id` INT UNSIGNED DEFAULT NULL,
    `details` TEXT DEFAULT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_admin_created` (`admin_id`, `created_at`),
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Varsayılan Ayarlar ────────────────────────────────────
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
    ('site_name', 'Sociaera'),
    ('site_description', 'GTA World TR Sosyal Keşif & Check-in Platformu'),
    ('site_email', 'info@sociaera.online'),
    ('checkin_cooldown', '300'),
    ('checkin_rate_limit', '10'),
    ('checkin_rate_window', '3600'),
    ('leaderboard_top_users', '10'),
    ('leaderboard_top_venues', '10'),
    ('login_max_attempts', '8'),
    ('login_window_seconds', '600'),
    ('timezone', 'Europe/Istanbul'),
    ('week_start', 'monday'),
    ('maintenance_mode', '0')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
