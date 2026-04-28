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
