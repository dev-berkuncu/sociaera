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
