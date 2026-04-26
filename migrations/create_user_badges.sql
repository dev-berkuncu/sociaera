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
