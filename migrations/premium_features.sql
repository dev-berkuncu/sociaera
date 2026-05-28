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

-- Profile theme column
ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_theme VARCHAR(20) DEFAULT 'default' AFTER badge;

-- Ads feed position
ALTER TABLE ads MODIFY COLUMN position ENUM('carousel','sidebar_left','sidebar_right','footer_banner','feed') NOT NULL DEFAULT 'sidebar_right';
