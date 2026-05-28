-- ── Bakiye Çekim ve Banka Hesap Numarası Güncellemesi ──────────────────────

-- users tablosuna bank_account kolonu eklenmesi
ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_account VARCHAR(50) DEFAULT NULL AFTER bio;

-- transactions tablosuna status kolonu eklenmesi
ALTER TABLE transactions ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved' AFTER reference_id;
