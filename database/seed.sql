-- ========================================================
-- Sociaera — Test / Seed Verileri
-- phpMyAdmin'den schema.sql'den SONRA import edin.
-- ========================================================

SET NAMES utf8mb4;

-- ── Admin Kullanıcı ───────────────────────────────────────
-- NOT: Production ortamında default şifre ile kurulum YAPMAYIN!
-- Örnek kurulum için şifre sıfırlanmıştır, kendi güvenli şifrenizi belirleyin.
INSERT INTO `users` (`id`, `username`, `tag`, `email`, `password_hash`, `is_admin`, `admin_role`, `is_active`, `created_at`) VALUES
(1, 'Admin', 'admin', 'admin@sociaera.online', '$2y$10$invalid_hash_change_me', 1, 'super_admin', 1, '2026-01-01 00:00:00');

-- SADECE ADMIN KULLANICISI KALDI
-- Diğer tüm demo veriler (kullanıcılar, mekanlar, check-in'ler) temizlendi.
