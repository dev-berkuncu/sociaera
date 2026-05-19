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
