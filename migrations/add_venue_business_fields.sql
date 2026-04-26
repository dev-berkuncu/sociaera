-- Venues tablosuna işletme paneli alanları ekleme
-- Bu SQL'i sunucuda çalıştırın

ALTER TABLE venues 
    ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER address,
    ADD COLUMN hours VARCHAR(255) DEFAULT NULL AFTER phone,
    ADD COLUMN is_open TINYINT(1) DEFAULT 1 AFTER hours,
    ADD COLUMN cover_image VARCHAR(255) DEFAULT NULL AFTER image;
