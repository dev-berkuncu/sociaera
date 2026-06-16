-- ── Ads user_id kolonu ve ilişkisi ────────────────────────────
ALTER TABLE ads ADD COLUMN IF NOT EXISTS user_id INT UNSIGNED DEFAULT NULL AFTER id;

-- Yabancı anahtar kısıtlaması ekleme
ALTER TABLE ads ADD CONSTRAINT fk_ads_user_id FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
