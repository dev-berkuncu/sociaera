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
