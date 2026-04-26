-- Kullanıcı rozetleri tablosu
CREATE TABLE IF NOT EXISTS user_badges (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    badge_key VARCHAR(50) NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_badge (user_id, badge_key),
    KEY idx_user_id (user_id),
    KEY idx_badge_key (badge_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
