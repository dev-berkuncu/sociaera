-- ========================================================
-- Sociaera — Test / Seed Verileri
-- phpMyAdmin'den schema.sql'den SONRA import edin.
-- ========================================================

SET NAMES utf8mb4;

-- ── Admin Kullanıcı ───────────────────────────────────────
-- Şifre: admin123 (bcrypt hash)
INSERT INTO `users` (`id`, `username`, `tag`, `email`, `password_hash`, `is_admin`, `is_active`, `created_at`) VALUES
(1, 'Admin', 'admin', 'admin@sociaera.online', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2026-01-01 00:00:00');

-- ── Test Kullanıcıları ────────────────────────────────────
-- Şifre: test123 (bcrypt hash)
INSERT INTO `users` (`id`, `username`, `tag`, `email`, `password_hash`, `is_admin`, `is_active`, `created_at`) VALUES
(2, 'Carlos_Mendoza', 'carlos', 'carlos@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, '2026-01-10 12:00:00'),
(3, 'Aylin_Demir', 'aylin', 'aylin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, '2026-01-15 14:30:00'),
(4, 'Jake_Morrison', 'jake', 'jake@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0, 1, '2026-02-01 09:00:00');

-- ── Mekanlar ──────────────────────────────────────────────
INSERT INTO `venues` (`id`, `name`, `description`, `address`, `category`, `facebrowser_url`, `status`, `is_active`, `created_by`, `created_at`) VALUES
(1, 'Pillbox Casino', 'Los Santos''un en büyük kumarhanesi. Blackjack, poker ve slot makineleri.', 'Vinewood Park Dr, Los Santos', 'eglence', 'https://face.gta.world/pages/pillbox-casino', 'approved', 1, 1, '2026-01-05 10:00:00'),
(2, 'Bean Machine Coffee', 'En kaliteli kahveyi sunan zincir kafe. Her köşede bir şubemiz var.', 'Mirror Park Blvd, Los Santos', 'kafe', 'https://face.gta.world/pages/bean-machine', 'approved', 1, 1, '2026-01-06 11:00:00'),
(3, 'Tequi-la-la', 'Canlı müzik ve dans pistine sahip gece kulübü.', 'West Hollywood Blvd, Los Santos', 'bar', 'https://face.gta.world/pages/tequilala', 'approved', 1, 1, '2026-01-08 15:00:00'),
(4, 'Bahama Mamas West', 'Tropikal temalı beach bar. Kokteyller ve DJ performansları.', 'Del Perro Beach, Los Santos', 'bar', 'https://face.gta.world/pages/bahama-mamas', 'approved', 1, 1, '2026-01-10 09:00:00'),
(5, 'Paradise Hotel & Spa', 'Lüks konaklama ve spa hizmetleri.', 'Rockford Hills, Los Santos', 'otel', 'https://face.gta.world/pages/paradise-hotel', 'approved', 1, 1, '2026-01-12 13:00:00');

-- ── Check-in'ler ──────────────────────────────────────────
INSERT INTO `checkins` (`id`, `user_id`, `venue_id`, `note`, `created_at`) VALUES
(1, 2, 1, 'Akşam poker masasındaydım 🎰 Harika bir gece!', '2026-04-14 20:30:00'),
(2, 3, 2, 'Sabah kahvesi burada içilir ☕', '2026-04-15 08:15:00'),
(3, 4, 3, 'Muhteşem canlı müzik performansı! 🎸', '2026-04-15 22:00:00'),
(4, 2, 4, 'Gün batımında kokteyl keyfi 🍹 @aylin sen de gel!', '2026-04-16 18:45:00'),
(5, 3, 1, 'Blackjack masasında şansım yaver gitti 💰', '2026-04-16 21:00:00'),
(6, 4, 2, 'Bean Machine her zamanki gibi harika ☕', '2026-04-17 09:30:00'),
(7, 2, 5, 'Spa günü yapalım dedik 🧖', '2026-04-17 14:00:00'),
(8, 3, 3, 'DJ seçimi muhteşemdi bu gece 🔥 @jake_morrison', '2026-04-18 23:15:00');

-- ── Beğeniler ─────────────────────────────────────────────
INSERT INTO `post_likes` (`user_id`, `checkin_id`) VALUES
(3, 1), (4, 1), (2, 2), (4, 2), (2, 3),
(3, 4), (4, 5), (2, 8), (4, 8);

-- ── Yorumlar ──────────────────────────────────────────────
INSERT INTO `post_comments` (`user_id`, `checkin_id`, `comment`, `created_at`) VALUES
(3, 1, 'Keşke ben de olsaydım! 😄', '2026-04-14 20:45:00'),
(4, 1, 'Bir sonrakine ben de geleyim!', '2026-04-14 21:00:00'),
(2, 2, 'Oranın latte''si efsane 👌', '2026-04-15 08:30:00'),
(3, 3, '🎵🎵🎵', '2026-04-15 22:15:00'),
(4, 4, 'Geliyorum hemen! 🏃', '2026-04-16 18:50:00');

-- ── Repostlar ─────────────────────────────────────────────
INSERT INTO `post_reposts` (`user_id`, `checkin_id`, `quote`) VALUES
(4, 1, 'Poker gecelerini seviyoruz! 🃏'),
(2, 3, 'Muhteşem müzik 🎶');

-- ── Takip İlişkileri ──────────────────────────────────────
INSERT INTO `user_follows` (`follower_id`, `following_id`) VALUES
(2, 3), (2, 4), (3, 2), (3, 4), (4, 2), (4, 3);

-- ── Bildirimler ───────────────────────────────────────────
INSERT INTO `notifications` (`user_id`, `from_user_id`, `type`, `content`, `checkin_id`) VALUES
(2, 3, 'like', 'Aylin_Demir gönderini beğendi.', 1),
(2, 4, 'comment', 'Jake_Morrison gönderine yorum yaptı.', 1),
(3, 2, 'like', 'Carlos_Mendoza gönderini beğendi.', 2),
(3, 2, 'follow', 'Carlos_Mendoza seni takip etmeye başladı.', NULL),
(4, 3, 'mention', 'Aylin_Demir senden bahsetti.', 8);

-- ── Cüzdanlar ─────────────────────────────────────────────
INSERT INTO `wallets` (`user_id`, `balance`) VALUES
(1, 100000.00), (2, 5000.00), (3, 7500.00), (4, 3200.00);

-- ── İşlemler ──────────────────────────────────────────────
INSERT INTO `transactions` (`user_id`, `type`, `amount`, `description`) VALUES
(2, 'deposit', 10000.00, 'Hoş geldin bonusu'),
(2, 'withdraw', 5000.00, 'Premium abonelik'),
(3, 'deposit', 7500.00, 'Hoş geldin bonusu'),
(4, 'deposit', 3200.00, 'Hoş geldin bonusu');
