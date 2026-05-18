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
