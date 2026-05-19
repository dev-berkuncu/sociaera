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
