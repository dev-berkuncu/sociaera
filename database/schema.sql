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
    KEY `idx_gta_user` (`gta_user_id`),
    KEY `idx_username` (`username`)
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
    KEY `idx_created_by` (`created_by`),
    KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Check-in'ler (= Gönderiler) ──────────────────────────
CREATE TABLE IF NOT EXISTS `checkins` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `venue_id` INT UNSIGNED NOT NULL,
    `note` TEXT DEFAULT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `like_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `comment_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `repost_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `is_flagged` TINYINT(1) NOT NULL DEFAULT 0,
    `is_excluded_from_leaderboard` TINYINT(1) NOT NULL DEFAULT 0,
    `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user_created` (`user_id`, `created_at`),
    KEY `idx_venue_created` (`venue_id`, `created_at`),
    KEY `idx_created` (`created_at`),
    KEY `idx_deleted` (`is_deleted`),
    KEY `idx_is_deleted_created_at` (`is_deleted`, `created_at`),
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
    UNIQUE KEY `uk_reference_id` (`reference_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Platform Giderleri ────────────────────────────────────
CREATE TABLE IF NOT EXISTS `platform_expenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
-- Migration bloğu iptal edildi (transaction uk_reference_id tablo içinde zaten var)
-- ── Ads user_id ve diğer eksik kolonlar ────────────────────────────
ALTER TABLE ads 
    ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED DEFAULT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER is_active,
    ADD COLUMN IF NOT EXISTS media_type VARCHAR(50) NOT NULL DEFAULT 'image' AFTER status,
    ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL AFTER media_type;

-- Yabancı anahtar kısıtlaması ekleme
ALTER TABLE ads ADD CONSTRAINT fk_ads_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
-- Venues tablosuna işletme paneli alanları ekleme
ALTER TABLE venues 
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER address,
    ADD COLUMN IF NOT EXISTS hours VARCHAR(255) DEFAULT NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS is_open TINYINT(1) DEFAULT 1 AFTER hours,
    ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) DEFAULT NULL AFTER image;

-- Users tablosuna premium süre ve rozet kolonları ekleme
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS premium_until DATETIME DEFAULT NULL AFTER is_premium,
    ADD COLUMN IF NOT EXISTS badge VARCHAR(30) DEFAULT NULL AFTER premium_until;
-- Kullanıcı rozetleri tablosu (haftalık sıfırlama destekli)
CREATE TABLE IF NOT EXISTS user_badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    badge_key VARCHAR(50) NOT NULL,
    week_start DATE NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_badge_week (user_id, badge_key, week_start),
    KEY idx_user_id (user_id),
    KEY idx_badge_key (badge_key),
    KEY idx_week_start (week_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Venue Ratings Table
-- Kullanıcıların mekanlara 1-5 arası puan vermesini sağlar
-- UNIQUE constraint ile aynı kullanıcının aynı mekana birden fazla rating vermesi engellenir

CREATE TABLE IF NOT EXISTS venue_ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    venue_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_venue_user (venue_id, user_id),
    KEY idx_venue_id (venue_id),
    KEY idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- Fleeca Banking V2 — Ödeme Takip Tablosu
CREATE TABLE IF NOT EXISTS `fleeca_payments` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `payment_id` VARCHAR(36) NOT NULL,           -- Fleeca UUID
    `user_id`    INT UNSIGNED NOT NULL,
    `amount`     DECIMAL(10,2) NOT NULL,
    `status`     ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
    `mode`       ENUM('sandbox','live') NOT NULL DEFAULT 'sandbox',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `paid_at`    DATETIME DEFAULT NULL,
    UNIQUE KEY `uk_payment_id` (`payment_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ============================================================
-- Sociaera — Gizli Müşteri Sistemi Migration
-- ============================================================

-- Başvuru tablosu
CREATE TABLE IF NOT EXISTS `mystery_shoppers` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id`         INT UNSIGNED NOT NULL,
    `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `motivation`      TEXT DEFAULT NULL,          -- Başvuru motivasyon metni
    `admin_note`      TEXT DEFAULT NULL,          -- Admin notu
    `reviewed_by`     INT UNSIGNED DEFAULT NULL,  -- Onaylayan admin
    `applied_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `reviewed_at`     DATETIME DEFAULT NULL,
    UNIQUE KEY `uk_user` (`user_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- checkins tablosuna gizli müşteri alanı ekle (yoksa)
ALTER TABLE `checkins`
    ADD COLUMN `is_mystery_shopper` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_deleted`;
-- Profile views table
CREATE TABLE IF NOT EXISTS profile_views (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_user_id INT UNSIGNED NOT NULL,
    viewer_user_id INT UNSIGNED NOT NULL,
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_profile_viewer (profile_user_id, viewer_user_id),
    KEY idx_viewed_at (viewed_at),
    FOREIGN KEY (profile_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (viewer_user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Venue favorites table
CREATE TABLE IF NOT EXISTS venue_favorites (
    user_id INT UNSIGNED NOT NULL,
    venue_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, venue_id),
    KEY idx_venue_fav (venue_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profile theme and last_seen_at columns
ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS theme VARCHAR(20) DEFAULT 'default' AFTER badge,
    ADD COLUMN IF NOT EXISTS last_seen_at DATETIME DEFAULT NULL AFTER theme;

-- Ads feed position
ALTER TABLE ads MODIFY COLUMN position ENUM('carousel','sidebar_left','sidebar_right','footer_banner','feed') NOT NULL DEFAULT 'sidebar_right';
-- ============================================================
-- Sociaera V1 Admin Panel Migration
-- Çalıştır: mysql -u root -p sociaera_db < migrations/v1_admin_panel.sql
-- ============================================================

-- 1. Rol sistemi: is_admin yanına admin_role ekle
ALTER TABLE `users`
    ADD COLUMN `admin_role` ENUM('super_admin','moderator','finance_admin','business_admin','readonly_admin')
    DEFAULT NULL AFTER `is_admin`;

-- Mevcut admin'leri super_admin yap
UPDATE `users` SET `admin_role` = 'super_admin' WHERE `is_admin` = 1;

-- 2. İçerik raporları tablosu
CREATE TABLE IF NOT EXISTS `content_reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT UNSIGNED NOT NULL,
    `entity_type` ENUM('checkin','comment','user','venue') NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `reason` ENUM('spam','harassment','inappropriate','wrong_venue','fake_checkin','fraud','privacy','copyright','other') NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` ENUM('pending','reviewed','resolved','dismissed') DEFAULT 'pending',
    `admin_id` INT UNSIGNED DEFAULT NULL,
    `admin_note` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    KEY `idx_status` (`status`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_reporter` (`reporter_id`),
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Admin notları tablosu
CREATE TABLE IF NOT EXISTS `admin_notes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `entity_type` ENUM('user','venue','checkin','comment','transaction') NOT NULL,
    `entity_id` INT UNSIGNED NOT NULL,
    `note` TEXT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_entity` (`entity_type`, `entity_id`),
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. admin_logs tablosunu genişlet
ALTER TABLE `admin_logs`
    ADD COLUMN `old_value` TEXT DEFAULT NULL,
    ADD COLUMN `new_value` TEXT DEFAULT NULL,
    ADD COLUMN `user_agent` VARCHAR(500) DEFAULT NULL;

-- 5. post_comments tablosuna is_hidden ekle (yoksa)
ALTER TABLE `post_comments`
    ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_deleted`;
-- ============================================================
-- Sociaera — Kampanya Sistemi Migration
-- ============================================================

CREATE TABLE IF NOT EXISTS `venue_campaigns` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `venue_id`        INT UNSIGNED NOT NULL,
    `title`           VARCHAR(100) NOT NULL,
    `description`     VARCHAR(280) DEFAULT NULL,
    -- Tetikleyici tip: nth_checkin = Ninci check-in ödülü
    `trigger_type`    ENUM('nth_checkin','total_checkins','first_checkin') NOT NULL DEFAULT 'nth_checkin',
    `trigger_value`   INT UNSIGNED NOT NULL DEFAULT 10,   -- Örn: 10. check-in
    -- Ödül tipi
    `reward_type`     ENUM('discount_percent','discount_fixed','free_item','custom') NOT NULL DEFAULT 'discount_percent',
    `reward_value`    DECIMAL(10,2) DEFAULT NULL,         -- Örn: 50 (%)
    `reward_text`     VARCHAR(200) DEFAULT NULL,          -- Örn: "%50 İndirim Kuponu"
    -- Durum
    `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
    `starts_at`       DATETIME DEFAULT NULL,
    `ends_at`         DATETIME DEFAULT NULL,
    `max_redemptions` INT UNSIGNED DEFAULT NULL,          -- NULL = sınırsız
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY `idx_venue` (`venue_id`),
    KEY `idx_active` (`is_active`, `ends_at`),
    FOREIGN KEY (`venue_id`) REFERENCES `venues`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kampanya kazanımları (kim, ne zaman kazandı)
CREATE TABLE IF NOT EXISTS `campaign_redemptions` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `campaign_id` INT UNSIGNED NOT NULL,
    `user_id`     INT UNSIGNED NOT NULL,
    `venue_id`    INT UNSIGNED NOT NULL,
    `code`        VARCHAR(32) NOT NULL,       -- Kullanıcıya gösterilen kod
    `status`      ENUM('earned','used','expired') NOT NULL DEFAULT 'earned',
    `earned_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `used_at`     DATETIME DEFAULT NULL,
    UNIQUE KEY `uk_campaign_user` (`campaign_id`, `user_id`),   -- Bir kampanyadan bir kez
    KEY `idx_user` (`user_id`),
    KEY `idx_code` (`code`),
    FOREIGN KEY (`campaign_id`) REFERENCES `venue_campaigns`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- ── Bakiye Çekim ve Banka Hesap Numarası Güncellemesi ──────────────────────

-- users tablosuna bank_account kolonu eklenmesi
ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_account VARCHAR(50) DEFAULT NULL AFTER bio;

-- transactions tablosuna status kolonu eklenmesi
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER reference_id;
-- ── Para Çekme Talepleri (withdrawal_requests) ──────────────────
CREATE TABLE IF NOT EXISTS `withdrawal_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `account_info` TEXT NOT NULL,
    `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_user` (`user_id`),
    KEY `idx_status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
