-- ============================================================
-- Sociaera — DEMO / Müşteri Tanıtım Verileri
-- ============================================================
-- Bu dosyayı schema.sql + tüm migration'lardan SONRA çalıştırın.
-- Mevcut veri varsa önce temizler, sonra demo veri yükler.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Mevcut verileri temizle ────────────────────────────────
TRUNCATE TABLE `admin_logs`;
TRUNCATE TABLE `admin_notes`;
TRUNCATE TABLE `content_reports`;
TRUNCATE TABLE `campaign_redemptions`;
TRUNCATE TABLE `venue_campaigns`;
TRUNCATE TABLE `venue_ratings`;
TRUNCATE TABLE `venue_favorites`;
TRUNCATE TABLE `mystery_shoppers`;
TRUNCATE TABLE `user_badges`;
TRUNCATE TABLE `profile_views`;
TRUNCATE TABLE `notifications`;
TRUNCATE TABLE `post_comments`;
TRUNCATE TABLE `post_reposts`;
TRUNCATE TABLE `post_likes`;
TRUNCATE TABLE `checkins`;
TRUNCATE TABLE `wallets`;
TRUNCATE TABLE `transactions`;
TRUNCATE TABLE `fleeca_payments`;
TRUNCATE TABLE `ads`;
TRUNCATE TABLE `venues`;
TRUNCATE TABLE `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- KULLANICILAR
-- Şifre: demo1234 → bcrypt hash (cost 10)
-- ============================================================
INSERT INTO `users`
  (`id`, `username`, `tag`, `email`, `password_hash`,
   `bio`, `is_admin`, `admin_role`, `is_active`, `is_premium`,
   `premium_until`, `badge`,
   `gta_character_name`, `last_login_at`, `created_at`)
VALUES
-- Admin
(1,  'SociaAdmin',    'sociaadmin',  'admin@sociaera.online',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Platform yöneticisi. Her şeyi görür, her şeyi bilir. 👁',
     1, 'super_admin', 1, 1, '2027-12-31 23:59:59', 'verified',
     NULL, NOW(), '2026-01-01 00:00:00'),

-- Moderatör
(2,  'ModeMaxwell',   'modermax',    'mod@sociaera.online',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Içerik moderatörü. Kaliteyi koruyoruz.',
     1, 'moderator', 1, 1, '2027-06-30 23:59:59', 'moderator',
     NULL, NOW(), '2026-01-05 09:00:00'),

-- Premium Kullanıcılar
(3,  'Carlos_Mendoza',  'carlos',    'carlos@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Los Santos''un en aktif kaşifi 🗺️ | 150+ mekan | Premium üye',
     0, NULL, 1, 1, '2027-03-01 00:00:00', 'gold',
     'Carlos Mendoza', NOW(), '2026-01-10 12:00:00'),

(4,  'Aylin_Demir',    'aylin',      'aylin@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Kafe bağımlısı ☕ | Fotoğrafçı 📸 | Her köşeyi keşfediyorum',
     0, NULL, 1, 1, '2027-01-15 00:00:00', 'explorer',
     'Aylin Demir', NOW(), '2026-01-15 14:30:00'),

(5,  'Jake_Morrison',  'jake',       'jake@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Gece kulübü hayranı 🕺 | DJ severler burada | Nightlife ambassador',
     0, NULL, 1, 0, NULL, NULL,
     'Jake Morrison', NOW(), '2026-02-01 09:00:00'),

(6,  'Sofia_Rossi',    'sofia',      'sofia@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'İtalyan gurme 🍕 | Restoran kritigi | Premium yemek rehberi',
     0, NULL, 1, 1, '2026-12-31 00:00:00', 'foodie',
     'Sofia Rossi', NOW(), '2026-02-10 16:00:00'),

(7,  'Marcus_Webb',    'marcus',     'marcus@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Spor kulüpleri & fitness 💪 | Sağlıklı yaşam bloggeri',
     0, NULL, 1, 0, NULL, NULL,
     'Marcus Webb', NOW(), '2026-02-20 08:00:00'),

(8,  'Luna_Vega',      'luna',       'luna@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Sanat galerisi & kültür 🎨 | Müze gezgini | Şehrin nabzını tutuyorum',
     0, NULL, 1, 1, '2026-11-30 00:00:00', 'silver',
     'Luna Vega', NOW(), '2026-03-01 11:00:00'),

(9,  'Ryan_Chase',     'ryan',       'ryan@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Casino & poker pro 🃏 | Yüksek riskli, yüksek kazanç',
     0, NULL, 1, 0, NULL, NULL,
     'Ryan Chase', NOW(), '2026-03-10 20:00:00'),

(10, 'Emma_Stone',     'emma',       'emma@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Beach lover 🏖️ | Kokteyller & gün batımları | Hayat kısa, keyif uzun',
     0, NULL, 1, 1, '2027-02-28 00:00:00', 'explorer',
     'Emma Stone', NOW(), '2026-03-15 15:00:00'),

(11, 'Diego_Reyes',    'diego',      'diego@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Lüks otel & spa 🧖‍♂️ | VIP deneyimler | İşletme sahibi',
     0, NULL, 1, 0, NULL, NULL,
     'Diego Reyes', NOW(), '2026-04-01 10:00:00'),

(12, 'Zara_Khan',      'zara',       'zara@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Yeni keşifler peşinde 🔍 | İlk hafta Premium oldum, pişman değilim!',
     0, NULL, 1, 1, '2026-08-01 00:00:00', NULL,
     'Zara Khan', NOW(), '2026-04-10 13:00:00'),

(13, 'Tom_Fletcher',   'tom',        'tom@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Bira & pub kültürü 🍺 | 80+ bar ziyareti | Sociaera''nin en sadık barfly',
     0, NULL, 1, 0, NULL, NULL,
     'Tom Fletcher', NOW(), '2026-04-20 19:00:00'),

(14, 'Nina_Park',      'nina',       'nina@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Canlı müzik takipçisi 🎵 | Konser fotoğrafçısı | Sociaera bağımlısı',
     0, NULL, 1, 1, '2026-09-30 00:00:00', NULL,
     'Nina Park', NOW(), '2026-05-01 17:00:00'),

(15, 'Alex_Torres',    'alex',       'alex@demo.com',
     '$2y$10$T0gqR5P1H1.gPsq0VHhbbeXA5gH.5hQi8Bfm7PmLdAlyVlMGVVlgS',
     'Yeni kullanıcı 🆕 | Şehri tanımaya başlıyorum',
     0, NULL, 1, 0, NULL, NULL,
     'Alex Torres', NOW(), '2026-06-01 10:00:00');

-- ============================================================
-- MEKANLAR — 20 adet, çeşitli kategoriler
-- ============================================================
INSERT INTO `venues`
  (`id`, `name`, `description`, `address`, `phone`, `hours`,
   `is_open`, `category`, `facebrowser_url`, `status`, `is_active`,
   `created_by`, `created_at`)
VALUES
-- Eğlence
(1,  'Pillbox Grand Casino',
     'Los Santos''un en prestijli kumarhanesi. Blackjack, Teksas Hold''em, Rulet ve 200+ slot makinesi. VIP özel salonlar mevcut.',
     'Pillbox Hill, Los Santos', '(555) 800-2500', 'Pzt-Paz 00:00-24:00',
     1, 'eglence', 'https://face.gta.world/pages/pillbox-casino',
     'approved', 1, 1, '2026-01-05 10:00:00'),

(2,  'Vinewood Bowl Amphitheater',
     'Açık hava amfitiyatro. Canlı konserler, stand-up gösterileri ve sinema geceleri. Kapasitesi 5000 kişi.',
     'Vinewood Hills, Los Santos', '(555) 900-1111', 'Etkinlik günleri',
     1, 'eglence', 'https://face.gta.world/pages/vinewood-bowl',
     'approved', 1, 1, '2026-01-20 12:00:00'),

(3,  'Maze Bank Arena',
     'Los Santos''un dev spor & etkinlik arenası. Basketbol, boks maçları, dev konserler burada.',
     'LSIA District, Los Santos', '(555) 700-ARENA', 'Etkinliğe göre',
     0, 'eglence', 'https://face.gta.world/pages/maze-bank-arena',
     'approved', 1, 1, '2026-02-01 09:00:00'),

-- Barlar & Kulüpler
(4,  'Tequi-la-la',
     'Vinewood''un ikonik rock bar''ı. Canlı müzik, dans pisti ve efsanevi kokteyller. Perşembe ''Ladies Night'' gecesi.',
     'West Vinewood, Los Santos', '(555) 433-2522', 'Sal-Paz 20:00-04:00',
     1, 'bar', 'https://face.gta.world/pages/tequilala',
     'approved', 1, 1, '2026-01-08 15:00:00'),

(5,  'Bahama Mamas West',
     'Del Perro sahilinde tropikal temalı beach bar. Sunset kokteyller, DJ performansları ve deniz manzarası.',
     'Del Perro Beach, Los Santos', '(555) 226-2626', 'Pzt-Paz 16:00-02:00',
     1, 'bar', 'https://face.gta.world/pages/bahama-mamas',
     'approved', 1, 1, '2026-01-10 09:00:00'),

(6,  'Vanilla Unicorn Lounge',
     'Strawberry''nin en popüler karanlık bar''ı. Cuma ve Cumartesi geceleri özel DJ setleri.',
     'Strawberry Ave, Los Santos', '(555) 100-VUNL', 'Çar-Paz 21:00-06:00',
     1, 'bar', 'https://face.gta.world/pages/vanilla-unicorn',
     'approved', 1, 2, '2026-02-05 20:00:00'),

-- Kafeler & Restoranlar
(7,  'Bean Machine - Mirror Park',
     'Şehrin en sevilen specialty coffee zinciri. Single origin espresso, taze pastane ürünleri ve çalışma alanları.',
     'Mirror Park Blvd, Los Santos', '(555) 232-2327', 'Pzt-Paz 07:00-22:00',
     1, 'kafe', 'https://face.gta.world/pages/bean-machine',
     'approved', 1, 1, '2026-01-06 11:00:00'),

(8,  'Burger Shot - Rockford',
     'GTA''nın en iyi fast food zinciri. Dev burgerleri, soggy fries''ı ve gece yarısı açık olmasıyla efsane.',
     'Rockford Hills, Los Santos', '(555) 267-8437', 'Pzt-Paz 00:00-24:00',
     1, 'restoran', 'https://face.gta.world/pages/burger-shot',
     'approved', 1, 1, '2026-01-12 08:00:00'),

(9,  'Pizza This - Downtown',
     'İtalyan usulü el yapımı pizza. Odun fırını, taze malzemeler. Cumartesi öğlen rezervasyonsuz girilmiyor.',
     'Downtown, Los Santos', '(555) 749-8324', 'Pzt-Cmt 11:00-23:00',
     1, 'restoran', 'https://face.gta.world/pages/pizza-this',
     'approved', 1, 3, '2026-02-01 14:00:00'),

(10, 'Hookies - Sandy Shores',
     'Blaine County''nin balık-cips spesyalisti. Sandy Shores''un sakin atmosferinde deniz mahsulleri.',
     'Sandy Shores, Blaine County', '(555) 465-3726', 'Sal-Paz 12:00-21:00',
     1, 'restoran', 'https://face.gta.world/pages/hookies',
     'approved', 1, 2, '2026-02-15 10:00:00'),

-- Oteller & SPA
(11, 'Paradise Hotel & Spa',
     'Rockford Hills''in 5 yıldızlı lüks oteli. Infinity havuz, tam donanımlı spa ve Michelin yıldızlı restoran.',
     'Rockford Hills, Los Santos', '(555) 747-3337', 'Pzt-Paz 00:00-24:00',
     1, 'otel', 'https://face.gta.world/pages/paradise-hotel',
     'approved', 1, 1, '2026-01-12 13:00:00'),

(12, 'Del Perro Beach Hotel',
     'Sahil kenarında butik otel. Sonsuzluk havuzu, beach bar ve günbatımı manzarası.',
     'Del Perro, Los Santos', '(555) 375-7243', 'Pzt-Paz 00:00-24:00',
     1, 'otel', 'https://face.gta.world/pages/del-perro-hotel',
     'approved', 1, 4, '2026-03-01 11:00:00'),

-- Spor & Fitness
(13, 'Muscle Sands Gym',
     'Vespucci sahilinde açık hava spor alanı. Ücretsiz dumbbell''lar, paralel barlar ve Pacificko manzarası.',
     'Vespucci Beach, Los Santos', NULL, 'Pzt-Paz 06:00-22:00',
     1, 'spor', 'https://face.gta.world/pages/muscle-sands',
     'approved', 1, 1, '2026-01-25 07:00:00'),

(14, 'LS Fitness Center',
     'Premium spor merkezi. 300+ ekipman, grup dersleri, kişisel antrenörler ve sauna.',
     'Downtown, Los Santos', '(555) 547-8348', 'Pzt-Cmt 05:00-23:00',
     1, 'spor', 'https://face.gta.world/pages/ls-fitness',
     'approved', 1, 1, '2026-02-10 06:00:00'),

-- Sanat & Kültür
(15, 'Galileo Observatory',
     'Vinewood Hills''in ikonik gözlemevi. Şehrin en iyi manzarası, gece yıldız izleme etkinlikleri.',
     'Vinewood Hills, Los Santos', '(555) 924-9244', 'Pzt-Paz 10:00-22:00',
     1, 'kultur', 'https://face.gta.world/pages/galileo-obs',
     'approved', 1, 2, '2026-02-20 10:00:00'),

(16, 'LS Art Gallery',
     'Çağdaş sanat galerisi. Yerel ve uluslararası sanatçıların eserleri. Her ay yeni sergi.',
     'Little Seoul, Los Santos', '(555) 534-5343', 'Sal-Paz 10:00-19:00',
     1, 'kultur', 'https://face.gta.world/pages/ls-art-gallery',
     'approved', 1, 2, '2026-03-05 09:00:00'),

-- Alışveriş
(17, 'Rockford Hills Mall',
     'Lüks alışveriş merkezi. Gucci, Prada, Hermes ve daha fazlası. Şehrin en prestijli adresi.',
     'Rockford Hills, Los Santos', '(555) 764-7368', 'Pzt-Paz 09:00-22:00',
     1, 'alisveris', 'https://face.gta.world/pages/rh-mall',
     'approved', 1, 1, '2026-03-15 10:00:00'),

-- Onay Bekleyen
(18, 'Sandy Shores Motel',
     'Sade ama temiz konaklama. Blaine County''ye yakın, makul fiyat.',
     'Sandy Shores, Blaine County', '(555) 726-3726', 'Pzt-Paz 00:00-24:00',
     1, 'otel', NULL,
     'pending', 1, 15, '2026-06-01 12:00:00'),

(19, 'Downtown Diner',
     'Ev yemekleri ve kahvaltı spesiyalisti. 1960''lar retro atmosferi.',
     'Downtown, Los Santos', '(555) 346-3463', 'Pzt-Paz 07:00-20:00',
     1, 'restoran', NULL,
     'pending', 1, 12, '2026-06-05 09:00:00'),

(20, 'La Mesa Warehouse Club',
     'La Mesa''nın yeraltı elektronik müzik kulübü. Underground sahnesi.',
     'La Mesa, Los Santos', NULL, 'Cum-Paz 23:00-08:00',
     0, 'bar', NULL,
     'rejected', 0, 13, '2026-05-20 22:00:00');

-- ============================================================
-- CHECK-IN'LER — 60 adet, son 2 ayda gerçekçi dağılım
-- ============================================================
INSERT INTO `checkins`
  (`id`, `user_id`, `venue_id`, `note`, `created_at`)
VALUES
-- Carlos (aktif kullanıcı, 12 check-in)
(1,  3, 1,  'Akşam poker masasındaydım 🎰 Tam 3 saat oturdum. Harika bir gece!', '2026-04-14 20:30:00'),
(2,  3, 4,  'Tequi-la-la hiç değişmemiş 🎸 Canlı müzik efsaneydi bu gece!', '2026-04-18 22:00:00'),
(3,  3, 7,  'Sabah kahvem Bean Machine''de ☕ Latte art''ı görülmeye değer', '2026-04-22 09:15:00'),
(4,  3, 11, 'Paradise Spa''da bir hafta sonu 🧖‍♂️ İş stresi tamamen gitti', '2026-04-28 14:00:00'),
(5,  3, 5,  'Gün batımında Bahama Mamas 🍹 Manzara inanılmaz @aylin gel bir ara!', '2026-05-03 18:45:00'),
(6,  3, 2,  'Vinewood Bowl''da konser gecesi 🎤 Sahne kurulumu muhteşem', '2026-05-10 21:00:00'),
(7,  3, 8,  'Gece yarısı Burger Shot 🍔 Suçluluk duyuyorum ama pişman değilim 😂', '2026-05-15 01:30:00'),
(8,  3, 17, 'Rockford Hills Mall — alışveriş seansı 🛍️ Cüzdanım ağladı', '2026-05-20 14:00:00'),
(9,  3, 13, 'Muscle Sands sabah antrenmanı 💪 Deniz havası ayrı güzel', '2026-05-25 07:30:00'),
(10, 3, 1,  'Blackjack masasında şansım yaver gitti 💰 +5000 🤑', '2026-05-30 21:00:00'),
(11, 3, 9,  'Pizza This''in odun fırını pizzası... Tanrı''m 🍕 Sözcük bulamıyorum', '2026-06-03 19:30:00'),
(12, 3, 4,  'Perşembe geceleri Tequi-la-la''da olmak şart 🎶 @jake @nina gelin', '2026-06-08 22:30:00'),

-- Aylin (kafe uzmanı, 11 check-in)
(13, 4, 7,  'Sabah kahvesi burada içilir ☕ Oat milk latte = hayat', '2026-04-15 08:15:00'),
(14, 4, 16, 'LS Art Gallery''deki yeni sergiye gittim 🎨 Türk sanatçının eserleri vardı!', '2026-04-20 11:00:00'),
(15, 4, 11, 'Paradise Spa — doğum günü hediyem 🎁 Harika bir deneyim', '2026-04-25 13:00:00'),
(16, 4, 1,  'Blackjack masasında şansım yaver gitti 💰 @carlos öğretti', '2026-04-30 21:00:00'),
(17, 4, 5,  'Bahama Mamas''da sunset 🌅 Bu manzarayı görmeden yaşama', '2026-05-05 18:00:00'),
(18, 4, 7,  'Bean Machine yeni menüsü gelmiş ☕ Pistachio latte 10/10', '2026-05-12 10:00:00'),
(19, 4, 15, 'Galileo Observatory''den şehir manzarası 😍 Gece ayrı güzel', '2026-05-18 20:00:00'),
(20, 4, 9,  'Pizza This — hafta sonu öğle yemeği 🍕 40 dk kuyruk beklettim', '2026-05-24 13:30:00'),
(21, 4, 16, 'Art Gallery tekrar geldim 🖼️ Sergi değişmiş, yeni eserler var', '2026-05-29 15:00:00'),
(22, 4, 4,  'Tequi-la-la''da DJ gecesi 🎧 @jake dans pisti yaktı', '2026-06-04 23:00:00'),
(23, 4, 7,  'Üçüncü Bean Machine bu hafta 😅 Bağımlıyım buna', '2026-06-09 09:00:00'),

-- Jake (gece kulübü uzmanı, 10 check-in)
(24, 5, 4,  'Muhteşem canlı müzik performansı! 🎸 Bu band gelecek vaat ediyor', '2026-04-15 22:00:00'),
(25, 5, 6,  'Vanilla Unicorn''un friday night seti... 🔥 Saat 04''e kadar dans ettim', '2026-04-19 23:30:00'),
(26, 5, 5,  'Bahama Mamas beach session 🏖️ Summer vibes başladı', '2026-04-23 17:00:00'),
(27, 5, 7,  'Bean Machine her zamanki gibi harika ☕ @aylin haklıymış', '2026-04-27 10:30:00'),
(28, 5, 2,  'Vinewood Bowl open air concert 🎵 5000 kişilik kalabalık inanılmaz', '2026-05-02 20:00:00'),
(29, 5, 4,  'Perşembe geceleri Tequi-la-la''da olmak şart! 🎶', '2026-05-08 22:00:00'),
(30, 5, 6,  'Vanilla Unicorn underground set 🎧 En iyi DJ Los Santos''ta burada', '2026-05-14 00:00:00'),
(31, 5, 3,  'Maze Bank Arena''da basketbol maçı 🏀 Ev sahibi takım kazandı!', '2026-05-22 19:00:00'),
(32, 5, 4,  'Tequi-la-la''dan tekrar selamlar 🎸 Bu hafta 2. gelişim 😄', '2026-05-28 21:30:00'),
(33, 5, 14, 'LS Fitness sabah seansı 💪 Jake Morrison kas yapmayı öğreniyor', '2026-06-02 07:00:00'),

-- Sofia (yemek uzmanı, 9 check-in)
(34, 6, 9,  'Pizza This odun fırını pizzası 🍕 İtalya''dan daha iyi, yemin ederim', '2026-04-16 19:00:00'),
(35, 6, 8,  'Burger Shot — kirli guilty pleasure 🍔 Bazen insan kendini ödüllendirmeli', '2026-04-21 13:00:00'),
(36, 6, 11, 'Paradise Hotel restoranı 🍽️ Michelin yıldızlı şef, fiyat uygun değil ama değer', '2026-04-26 20:00:00'),
(37, 6, 10, 'Hookies balık-cips 🐟 Sandy Shores''un gizli incisi. Herkese tavsiye ederim!', '2026-05-04 12:30:00'),
(38, 6, 7,  'Bean Machine — sabah rutini ☕ @aylin ile buluştuk, harika sohbet', '2026-05-11 09:00:00'),
(39, 6, 9,  'Pizza This tekrar! 🍕 Bu sefer quattro stagioni denedim. Mükemmel.', '2026-05-17 19:30:00'),
(40, 6, 8,  'Burger Shot gece yarısı 🌙🍔 Utanarak söylüyorum: ikinci kez bu hafta', '2026-05-23 01:00:00'),
(41, 6, 12, 'Del Perro Beach Hotel''in beach bar''ı 🍹 Havuz kenarı mükemmel', '2026-05-31 16:00:00'),
(42, 6, 9,  'Pizza This Cuma akşamı — kuyruk olmadan girdim, şanslı gün! 🍕🎉', '2026-06-07 19:00:00'),

-- Marcus (spor odaklı, 7 check-in)
(43, 7, 13, 'Muscle Sands sabah antrenmanı 🌅 Deniz kokusu ayrı motivasyon', '2026-04-17 06:30:00'),
(44, 7, 14, 'LS Fitness grup dersi 💪 CrossFit seansı beni mahvetti ama memnunum', '2026-04-24 08:00:00'),
(45, 7, 13, 'Muscle Sands akşam antrenmanı 🏋️‍♂️ Gün batımında dumbbell press', '2026-05-01 18:00:00'),
(46, 7, 7,  'Antrenman sonrası protein shake Bean Machine\'de ☕ İyi kombinasyon', '2026-05-07 09:30:00'),
(47, 7, 14, 'LS Fitness kişisel antrenör seansı 💪 Yeni program başladı', '2026-05-13 07:00:00'),
(48, 7, 3,  'Maze Bank Arena''da boks maçı 🥊 Atmosfer muhteşemdi', '2026-05-21 20:00:00'),
(49, 7, 13, 'Muscle Sands 50. check-in 🎉 Bu plaja ne kadar borçluyum', '2026-06-01 07:00:00'),

-- Luna (kültür & sanat odaklı, 8 check-in)
(50, 8, 16, 'LS Art Gallery açılış gecesi 🎨 Yerel sanatçıları destekleyin!', '2026-04-18 18:00:00'),
(51, 8, 15, 'Galileo Observatory''den gece manzarası 🔭 Şehir ışıkları büyüleyici', '2026-04-22 21:00:00'),
(52, 8, 2,  'Vinewood Bowl''da klasik müzik gecesi 🎻 Farklı ama muhteşemdi', '2026-04-29 19:00:00'),
(53, 8, 7,  'Bean Machine''de çalışma seansı 💻 Saatler nasıl geçti anlamadım', '2026-05-06 14:00:00'),
(54, 8, 16, 'Yeni sergi açıldı! 🖼️ Bu ay fotoğraf sergisi var. Kaçırmayın!', '2026-05-15 11:00:00'),
(55, 8, 11, 'Paradise Spa wellness weekendi 🧘‍♀️ Ruh ve beden dinlendi', '2026-05-26 13:00:00'),
(56, 8, 15, 'Gözlemevi yıldız gecesi etkinliği 🌟 Teleskopla Jüpiter gördüm!', '2026-06-05 22:00:00'),
(57, 8, 16, 'Art Gallery üçüncü kez 🎨 Her gelişte farklı bir şey keşfediyorum', '2026-06-10 10:00:00'),

-- Ryan (casino uzmanı, 5 check-in)
(58, 9, 1,  'Casino — ilk gece 🎰 Rulet masasında başladım. Kötü başlangıç...', '2026-04-20 22:00:00'),
(59, 9, 1,  'Pillbox Casino tekrar 🃏 Poker turnuvası! 3. sırada bittim', '2026-04-27 20:00:00'),
(60, 9, 6,  'Vanilla Unicorn''da kötü kazanan gibi kutlama 😎🎉', '2026-04-28 00:30:00'),
(61, 9, 1,  'Blackjack tablosu ezber oldu artık 🃏 Sistem var burada', '2026-05-10 21:00:00'),
(62, 9, 5,  'Bahama Mamas''da kazançları kutluyoruz 🍹💰 İyi bir hafta geçti', '2026-05-17 19:00:00'),

-- Emma (beach & bar odaklı, 6 check-in)
(63, 10, 5,  'Bahama Mamas gün batımı 🌅🍹 Hayatımın en güzel kokteylli akşamı', '2026-04-19 17:30:00'),
(64, 10, 12, 'Del Perro Beach Hotel havuzu 🏊‍♀️ VIP hissettiriyor, fiyatı makul', '2026-04-25 15:00:00'),
(65, 10, 5,  'Sahil bar akşamı 🌊 @carlos ile buluştuk, harika sohbet', '2026-05-03 18:00:00'),
(66, 10, 4,  'Tequi-la-la Cuma gecesi 🎸 @jake dans pistiyle karşılaştı 😄', '2026-05-09 22:30:00'),
(67, 10, 12, 'Beach hotel Pazar kahvaltısı 🍳 Deniz manzaralı masa rezervasyonu şart', '2026-05-19 10:00:00'),
(68, 10, 5,  'Bahama Mamas üçüncü kez bu ay 🍹 Favorim oldu kesinlikle', '2026-06-06 18:00:00');

-- ============================================================
-- BEĞENILER
-- ============================================================
INSERT INTO `post_likes` (`user_id`, `checkin_id`) VALUES
-- Carlos'un check-in'lerine beğeniler
(4, 1), (5, 1), (6, 1), (8, 1), (10, 1),
(4, 2), (5, 2), (9, 2),
(4, 3), (6, 3), (8, 3),
(4, 5), (5, 5), (10, 5),
(4, 6), (8, 6),
(5, 10), (9, 10),
(4, 12), (5, 12), (8, 12),
-- Aylin'in check-in'lerine beğeniler
(3, 13), (5, 13), (6, 13), (8, 13),
(3, 14), (8, 14), (10, 14),
(3, 16), (5, 16),
(3, 17), (5, 17), (10, 17),
(3, 18), (6, 18), (8, 18),
(3, 19), (8, 19),
(3, 22), (5, 22), (9, 22),
-- Jake'in check-in'lerine beğeniler
(3, 24), (4, 24), (9, 24), (10, 24),
(3, 25), (9, 25),
(4, 26), (10, 26),
(4, 27), (6, 27),
(3, 28), (4, 28), (8, 28),
(3, 29), (4, 29), (10, 29),
-- Sofia'nın check-in'lerine beğeniler
(3, 34), (4, 34), (7, 34), (10, 34),
(3, 36), (4, 36), (8, 36),
(3, 37), (4, 37), (7, 37),
(3, 39), (4, 39),
(3, 42), (4, 42), (7, 42),
-- Marcus'un check-in'lerine beğeniler
(3, 43), (5, 43), (7, 44), (8, 43),
(3, 49), (4, 49), (5, 49), (6, 49), (8, 49), (10, 49),
-- Luna'nın check-in'lerine beğeniler
(3, 50), (4, 50), (6, 50), (10, 50),
(3, 51), (4, 51),
(3, 54), (4, 54), (6, 54), (8, 54),
(3, 56), (4, 56), (8, 56),
-- Emma'nın check-in'lerine beğeniler
(3, 63), (4, 63), (5, 63), (7, 63),
(4, 64), (6, 64),
(3, 65), (4, 65),
(4, 66), (5, 66), (9, 66),
(4, 68), (5, 68), (6, 68), (7, 68);

-- ============================================================
-- YORUMLAR
-- ============================================================
INSERT INTO `post_comments`
  (`user_id`, `checkin_id`, `comment`, `created_at`)
VALUES
(4,  1,  'Keşke ben de orada olsaydım 😄 Bir dahaki poker gecesine ben de geleyim!', '2026-04-14 20:50:00'),
(5,  1,  'Poker masası için slot mu, masa oyunları mı? 🎰', '2026-04-14 21:10:00'),
(9,  1,  'Carlos abi strateji anlatır mısın sonra 😅', '2026-04-14 21:30:00'),
(3,  13, 'Oranın oat milk lattesi gerçekten farklı! En iyi barista orası @aylin haklı', '2026-04-15 08:30:00'),
(5,  13, 'Kahve bağımlılığı ilk adım kabul etmektir 😄', '2026-04-15 08:45:00'),
(3,  24, 'Jake bu hafta sonu Tequi-la-la''ya gidiyoruz mu? 🎸', '2026-04-15 22:20:00'),
(4,  24, 'Band ismi ne? Araştırayım 🎵', '2026-04-15 22:35:00'),
(3,  34, 'Sofia bu pizzacıyı sen söyledin gidiyorum kesinlikle! 🍕', '2026-04-16 19:20:00'),
(4,  34, 'Odun fırını pizzası başka bir şey... Rezervasyon lazım mı?', '2026-04-16 19:40:00'),
(6,  34, 'İtalyan olarak onaylıyorum 🇮🇹 Gerçekten kaliteli', '2026-04-16 20:00:00'),
(3,  5,  'Bahama Mamas gün batımı... Efsane! Ben de geliyorum 🍹', '2026-05-03 19:00:00'),
(10, 5,  'Gün batımı saatini sorduruyorum o manzara için 🌅', '2026-05-03 19:15:00'),
(4,  37, 'Hookies! Hiç gitmedim, bu yazın listesine ekledim 🐟', '2026-05-04 12:50:00'),
(3,  37, 'Sandy Shores''ta mı? Uzak ama değer gibi görünüyor', '2026-05-04 13:10:00'),
(3,  49, '50. check-in kutlu olsun! 🎉🎉🎉 Milestone!', '2026-06-01 07:20:00'),
(4,  49, 'Milestone geldi! Plaj seni seviyor Marcus 💪', '2026-06-01 07:35:00'),
(5,  49, 'Hedef 100''e devam bro! 🏋️', '2026-06-01 07:50:00'),
(8,  49, '50 kez aynı yere gitmek... Dedikasyon bu 🙌', '2026-06-01 08:00:00'),
(10, 49, 'Plaj ruhunu anlıyorum seni 🌊', '2026-06-01 08:15:00'),
(3,  56, 'Luna teleskopla ne gördün? Fotoğraf çektirdin mi? 🔭', '2026-06-05 22:20:00'),
(4,  56, 'Gözlemevi etkinliğine gitmek istiyorum, nasıl katılınıyor?', '2026-06-05 22:35:00'),
(3,  12, '@jake @nina bu Perşembe Tequi-la-la''ya gidiyor musunuz?', '2026-06-08 22:45:00'),
(5,  12, 'Kesinlikle! 🎶 Saat 10''da orada olacağım', '2026-06-08 23:00:00');

-- ============================================================
-- REPOSTLAR
-- ============================================================
INSERT INTO `post_reposts` (`user_id`, `checkin_id`, `quote`) VALUES
(4,  1,  'Poker gecesine katılmak için @carlos ile iletişime geçin! 🃏'),
(5,  1,  'Casino gecesi organizasyonu başlıyor mu? 🎰'),
(3,  24, 'Canlı müzik için Tequi-la-la kesinlikle bir numaralı adres 🎸'),
(8,  50, 'LS Art Gallery''yi destekleyin! Yerel sanatçılar için önemli 🎨'),
(10, 5,  'Bahama Mamas gün batımı kokteyller için şehrin en iyi noktası 🌅'),
(3,  49, 'Marcus 50 check-in milestone — ilham verici! 💪'),
(4,  37, 'Hookies Sandy Shores — gizli kalmış bir lezzet durağı 🐟');

-- ============================================================
-- TAKİP İLİŞKİLERİ
-- ============================================================
INSERT INTO `user_follows` (`follower_id`, `following_id`) VALUES
-- Carlos
(3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 10),
-- Aylin
(4, 3), (4, 5), (4, 6), (4, 8), (4, 10), (4, 14),
-- Jake
(5, 3), (5, 4), (5, 9), (5, 10), (5, 14),
-- Sofia
(6, 3), (6, 4), (6, 7), (6, 8),
-- Marcus
(7, 3), (7, 5), (7, 13), (7, 14),
-- Luna
(8, 3), (8, 4), (8, 6), (8, 15), (8, 16),
-- Ryan
(9, 3), (9, 5),
-- Emma
(10, 3), (10, 4), (10, 5), (10, 6), (10, 8),
-- Zara
(12, 3), (12, 4), (12, 5), (12, 6), (12, 7), (12, 8), (12, 10),
-- Tom
(13, 5), (13, 9), (13, 10),
-- Nina
(14, 3), (14, 4), (14, 5), (14, 8),
-- Alex
(15, 3), (15, 4), (15, 6);

-- ============================================================
-- BİLDİRİMLER
-- ============================================================
INSERT INTO `notifications`
  (`user_id`, `from_user_id`, `type`, `content`, `checkin_id`, `is_read`, `created_at`)
VALUES
(3, 4,  'like',    'Aylin_Demir gönderini beğendi.',          1,  1, '2026-04-14 20:50:00'),
(3, 5,  'like',    'Jake_Morrison gönderini beğendi.',         1,  1, '2026-04-14 21:10:00'),
(3, 4,  'comment', 'Aylin_Demir gönderine yorum yaptı.',      1,  1, '2026-04-14 20:50:00'),
(3, 5,  'comment', 'Jake_Morrison gönderine yorum yaptı.',    1,  1, '2026-04-14 21:10:00'),
(3, 9,  'comment', 'Ryan_Chase gönderine yorum yaptı.',       1,  0, '2026-04-14 21:30:00'),
(4, 3,  'like',    'Carlos_Mendoza gönderini beğendi.',       13,  1, '2026-04-15 08:30:00'),
(4, 3,  'follow',  'Carlos_Mendoza seni takip etmeye başladı.', NULL, 1, '2026-04-14 20:00:00'),
(4, 5,  'follow',  'Jake_Morrison seni takip etmeye başladı.',  NULL, 1, '2026-04-15 10:00:00'),
(5, 3,  'follow',  'Carlos_Mendoza seni takip etmeye başladı.', NULL, 1, '2026-04-14 20:00:00'),
(5, 3,  'comment', 'Carlos_Mendoza gönderine yorum yaptı.',   24, 1, '2026-04-15 22:20:00'),
(5, 4,  'comment', 'Aylin_Demir gönderine yorum yaptı.',      24, 1, '2026-04-15 22:35:00'),
(6, 3,  'comment', 'Carlos_Mendoza gönderine yorum yaptı.',   34, 1, '2026-04-16 19:20:00'),
(6, 4,  'follow',  'Aylin_Demir seni takip etmeye başladı.',  NULL, 0, '2026-05-05 09:00:00'),
(7, 3,  'like',    'Carlos_Mendoza gönderini beğendi.',       49, 0, '2026-06-01 07:20:00'),
(7, 4,  'like',    'Aylin_Demir gönderini beğendi.',          49, 0, '2026-06-01 07:35:00'),
(7, 3,  'comment', 'Carlos_Mendoza gönderine yorum yaptı.',   49, 0, '2026-06-01 07:20:00'),
(8, 3,  'follow',  'Carlos_Mendoza seni takip etmeye başladı.', NULL, 0, '2026-05-01 10:00:00'),
(3, 5,  'mention', 'Jake_Morrison senden bahsetti.',          12, 0, '2026-06-08 23:00:00'),
(14,3,  'mention', 'Carlos_Mendoza senden bahsetti.',         12, 0, '2026-06-08 22:45:00'),
(3, 10, 'repost',  'Emma_Stone gönderini repost''ladı.',      5,  0, '2026-05-03 19:30:00');

-- ============================================================
-- CÜZDANLAR
-- ============================================================
INSERT INTO `wallets` (`user_id`, `balance`) VALUES
(1,  250000.00),
(2,   15000.00),
(3,   47500.00),
(4,   12800.00),
(5,    8200.00),
(6,   31500.00),
(7,    5400.00),
(8,   19750.00),
(9,   85000.00),
(10,  11200.00),
(11,   3800.00),
(12,   6600.00),
(13,   4100.00),
(14,   9300.00),
(15,   1500.00);

-- ============================================================
-- İŞLEMLER
-- ============================================================
INSERT INTO `transactions`
  (`user_id`, `type`, `amount`, `description`, `reference_id`, `created_at`)
VALUES
(3, 'deposit',      50000.00, 'İlk yatırım — Fleeca Bank transferi',      'REF-2026-0110-C001', '2026-01-10 12:00:00'),
(3, 'withdraw',      2500.00, 'Premium abonelik (3 ay)',                   'REF-2026-0110-C002', '2026-01-10 12:05:00'),
(3, 'deposit',       5000.00, 'Bonus — Check-in milestone ödülü',         'REF-2026-0330-C003', '2026-03-30 10:00:00'),
(3, 'transfer_out',  5000.00, 'Para transferi → Aylin_Demir',             'REF-2026-0415-C004', '2026-04-15 15:00:00'),
(4, 'transfer_in',   5000.00, 'Para transferi ← Carlos_Mendoza',          'REF-2026-0415-A001', '2026-04-15 15:01:00'),
(4, 'deposit',      10000.00, 'Fleeca Bank yatırım',                      'REF-2026-0115-A002', '2026-01-15 14:00:00'),
(4, 'withdraw',      2200.00, 'Premium abonelik (1 ay)',                   'REF-2026-0201-A003', '2026-02-01 09:00:00'),
(5, 'deposit',      10000.00, 'İlk yatırım',                              'REF-2026-0201-J001', '2026-02-01 09:00:00'),
(5, 'withdraw',      1800.00, 'VIP giriş ücreti — Tequi-la-la',           'REF-2026-0319-J002', '2026-03-19 20:00:00'),
(6, 'deposit',      35000.00, 'İşletme tanıtım bütçesi',                  'REF-2026-0210-S001', '2026-02-10 16:00:00'),
(6, 'withdraw',      3500.00, 'Premium abonelik (6 ay)',                   'REF-2026-0210-S002', '2026-02-10 16:05:00'),
(7, 'deposit',       7000.00, 'İlk yatırım',                              'REF-2026-0220-M001', '2026-02-20 08:00:00'),
(7, 'withdraw',      1600.00, 'LS Fitness aylık üyelik',                  'REF-2026-0301-M002', '2026-03-01 09:00:00'),
(8, 'deposit',      20000.00, 'Sanat projesi fon transferi',               'REF-2026-0301-L001', '2026-03-01 11:00:00'),
(8, 'withdraw',       250.00, 'Premium abonelik (1 ay)',                   'REF-2026-0301-L002', '2026-03-01 11:05:00'),
(9, 'deposit',     100000.00, 'Casino kazancı — büyük el 🃏',             'REF-2026-0427-R001', '2026-04-27 23:00:00'),
(9, 'withdraw',     15000.00, 'Casino kaybı',                             'REF-2026-0510-R002', '2026-05-10 22:00:00'),
(10,'deposit',      12000.00, 'İlk yatırım',                              'REF-2026-0315-E001', '2026-03-15 15:00:00'),
(10,'withdraw',       800.00, 'Premium abonelik (1 ay)',                   'REF-2026-0315-E002', '2026-03-15 15:05:00'),
(12,'deposit',       7500.00, 'İlk yatırım',                              'REF-2026-0410-Z001', '2026-04-10 13:00:00'),
(12,'withdraw',       900.00, 'Premium abonelik (1 ay)',                   'REF-2026-0410-Z002', '2026-04-10 13:05:00');

-- ============================================================
-- MEKAN PUANLAMALARI
-- ============================================================
INSERT INTO `venue_ratings` (`venue_id`, `user_id`, `rating`) VALUES
-- Pillbox Grand Casino
(1, 3, 5), (1, 4, 4), (1, 5, 4), (1, 9, 5), (1, 10, 3), (1, 12, 4),
-- Vinewood Bowl
(2, 3, 5), (2, 5, 5), (2, 8, 5), (2, 14, 4),
-- Maze Bank Arena
(3, 5, 4), (3, 7, 5),
-- Tequi-la-la
(4, 3, 5), (4, 5, 5), (4, 10, 4), (4, 14, 5), (4, 4, 4), (4, 12, 3),
-- Bahama Mamas West
(5, 3, 5), (5, 4, 5), (5, 10, 5), (5, 6, 4), (5, 9, 4),
-- Vanilla Unicorn
(6, 5, 4), (6, 9, 5), (6, 13, 3),
-- Bean Machine
(7, 4, 5), (7, 3, 4), (7, 5, 4), (7, 6, 5), (7, 7, 4), (7, 8, 5),
-- Burger Shot
(8, 3, 3), (8, 6, 3), (8, 13, 4),
-- Pizza This
(9, 6, 5), (9, 3, 5), (9, 4, 5), (9, 7, 4),
-- Hookies
(10, 6, 5), (10, 3, 4), (10, 4, 4),
-- Paradise Hotel
(11, 3, 5), (11, 4, 5), (11, 6, 4), (11, 8, 5), (11, 11, 4),
-- Del Perro Beach Hotel
(12, 10, 5), (12, 4, 4), (12, 6, 5),
-- Muscle Sands
(13, 7, 5), (13, 3, 4), (13, 5, 3),
-- LS Fitness
(14, 7, 4), (14, 5, 4),
-- Galileo Observatory
(15, 8, 5), (15, 4, 5), (15, 3, 4),
-- LS Art Gallery
(16, 8, 5), (16, 4, 5), (16, 6, 4),
-- Rockford Hills Mall
(17, 3, 4), (17, 4, 3), (17, 6, 5);

-- ============================================================
-- MEKAN FAVORİLERİ
-- ============================================================
INSERT INTO `venue_favorites` (`user_id`, `venue_id`) VALUES
(3, 1), (3, 4), (3, 5), (3, 11),
(4, 7), (4, 16), (4, 11), (4, 15),
(5, 4), (5, 6), (5, 2),
(6, 9), (6, 10), (6, 11), (6, 12),
(7, 13), (7, 14),
(8, 16), (8, 15), (8, 2),
(9, 1), (9, 6),
(10, 5), (10, 12), (10, 4),
(12, 7), (12, 4), (12, 11),
(14, 4), (14, 2);

-- ============================================================
-- KAMPANYALAR
-- ============================================================
INSERT INTO `venue_campaigns`
  (`id`, `venue_id`, `title`, `description`,
   `trigger_type`, `trigger_value`,
   `reward_type`, `reward_value`, `reward_text`,
   `is_active`, `starts_at`, `ends_at`, `max_redemptions`, `created_at`)
VALUES
(1, 1, '10. Check-in Özel Chip Bonusu',
 'Pillbox Grand Casino''da 10. check-in''ini yap, 500 casino chip hediye kazan!',
 'nth_checkin', 10, 'discount_fixed', 500.00, '500 Casino Chip Hediye',
 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, '2026-01-15 10:00:00'),

(2, 4, 'Sadık Misafir — 5. Ziyaret',
 'Tequi-la-la''ya 5. gelişinde içeceğin bedava! Bartender seni tanıyacak.',
 'nth_checkin', 5, 'free_item', NULL, 'Bir İçecek Bedava',
 1, '2026-02-01 00:00:00', '2026-08-31 23:59:59', 50, '2026-02-01 10:00:00'),

(3, 7, 'Bean Machine Kahve Abonesi',
 '20. check-in''inde 1 aylık ücretsiz kahve aboneliği! Her gün bir özel içecek.',
 'nth_checkin', 20, 'custom', NULL, '1 Aylık Ücretsiz Kahve Aboneliği',
 1, '2026-01-01 00:00:00', NULL, NULL, '2026-01-20 08:00:00'),

(4, 11, 'Lüks Spa Paketi',
 'Paradise Hotel & Spa''da ilk check-in''inde %25 spa paketi indirimi.',
 'first_checkin', 1, 'discount_percent', 25.00, '%25 Spa Paketi İndirimi',
 1, '2026-03-01 00:00:00', '2026-09-30 23:59:59', 100, '2026-03-01 10:00:00'),

(5, 9, 'Pizza Tutkunu',
 '5. ziyaretinde büyük boy pizza bedava! Seçim senin.',
 'nth_checkin', 5, 'free_item', NULL, 'Büyük Boy Pizza Bedava',
 1, '2026-04-01 00:00:00', NULL, 30, '2026-04-01 10:00:00'),

(6, 5, 'Sunset Kokteyl Paketi',
 'Bahama Mamas''da 3. check-in''inde 2 kokteyl bedava! Gün batımı saatine denk getir.',
 'nth_checkin', 3, 'free_item', NULL, '2 Kokteyl Bedava (Sunset Saat)',
 1, '2026-05-01 00:00:00', '2026-10-31 23:59:59', NULL, '2026-05-01 10:00:00'),

(7, 2, 'VIP Konser Deneyimi',
 'Vinewood Bowl''da 3. biletini al, 4. biletin ücretsiz! Arkadaşını getir.',
 'total_checkins', 3, 'discount_percent', 100.00, 'Sonraki Bilet Ücretsiz',
 1, '2026-04-01 00:00:00', '2026-12-31 23:59:59', 200, '2026-04-01 09:00:00');

-- ============================================================
-- KAMPANYA KAZANIMLARI
-- ============================================================
INSERT INTO `campaign_redemptions`
  (`campaign_id`, `user_id`, `venue_id`, `code`, `status`, `earned_at`, `used_at`)
VALUES
(1, 3, 1, 'PILL-C3-XK9M', 'used',    '2026-05-30 21:05:00', '2026-06-01 20:00:00'),
(2, 5, 4, 'TEQU-J5-P2RN', 'earned',  '2026-05-28 21:35:00', NULL),
(3, 4, 7, 'BEAN-A4-L7QW', 'earned',  '2026-06-09 09:05:00', NULL),
(4, 4, 11,'PARA-A4-S8VT', 'used',    '2026-04-25 13:05:00', '2026-04-25 14:00:00'),
(4, 8, 11,'PARA-L8-R3YE', 'used',    '2026-05-26 13:05:00', '2026-05-26 14:00:00'),
(5, 6, 9, 'PIZZ-S6-M9KD', 'earned',  '2026-06-07 19:05:00', NULL),
(6, 3, 5, 'BAHA-C3-N5FJ', 'used',    '2026-05-03 18:50:00', '2026-05-03 19:00:00'),
(6, 10,5, 'BAHA-E10-Q1ZX','earned',  '2026-06-06 18:05:00', NULL);

-- ============================================================
-- GİZLİ MÜŞTERİLER
-- ============================================================
INSERT INTO `mystery_shoppers`
  (`user_id`, `status`, `motivation`, `admin_note`, `reviewed_by`, `applied_at`, `reviewed_at`)
VALUES
(4, 'approved',
 'Kafe ve mekan deneyimi konusunda 2 yıldır düzenli değerlendirmeler yapıyorum. Tarafsız ve detaylı raporlar hazırlayabilirim. Özellikle servis kalitesi ve hijyen konularında uzmanım.',
 'Profil incelendi. Aktif kullanıcı, güvenilir check-in geçmişi. Onaylandı.',
 1, '2026-03-10 09:00:00', '2026-03-15 14:00:00'),

(8, 'approved',
 'Sanat galerisi ve kültürel mekanlar konusunda deneyimliyim. Müşteri deneyimini ve mekan atmosferini değerlendirme konusunda kendimi yetersiz görmüyorum.',
 'Kültür-sanat odaklı mekanlar için ideal aday. Onaylandı.',
 1, '2026-04-01 10:00:00', '2026-04-05 11:00:00'),

(7, 'pending',
 'Spor tesisleri ve fitness mekanları hakkında kapsamlı değerlendirmeler yapabilirim. Ekipman kalitesi, hijyen ve antrenör profesyonelliği konularında tecrübeliyim.',
 NULL, NULL, '2026-06-08 08:00:00', NULL),

(6, 'rejected',
 'Restoran ve yemek alanında değerlendirme yapmak istiyorum.',
 'Çıkar çatışması — başvuran kişi hali hazırda restoranlarla iş ilişkisi içinde. Reddedildi.',
 2, '2026-02-20 15:00:00', '2026-02-25 10:00:00');

-- ============================================================
-- KULLANICI ROZETLERİ
-- ============================================================
INSERT INTO `user_badges` (`user_id`, `badge_key`, `week_start`, `earned_at`) VALUES
-- Carlos rozetleri
(3, 'explorer',      '2026-04-14', '2026-04-14 20:30:00'),
(3, 'social_butterfly','2026-04-14','2026-04-14 21:00:00'),
(3, 'night_owl',     '2026-04-14', '2026-04-14 23:00:00'),
(3, 'casino_regular','2026-04-27', '2026-04-27 21:00:00'),
(3, 'explorer',      '2026-05-05', '2026-05-05 18:45:00'),
(3, 'foodie',        '2026-06-03', '2026-06-03 19:30:00'),
-- Aylin rozetleri
(4, 'coffee_addict', '2026-04-14', '2026-04-15 08:15:00'),
(4, 'explorer',      '2026-04-21', '2026-04-22 11:00:00'),
(4, 'art_lover',     '2026-04-21', '2026-04-20 11:00:00'),
(4, 'coffee_addict', '2026-05-12', '2026-05-12 10:00:00'),
-- Jake rozetleri
(5, 'night_owl',     '2026-04-14', '2026-04-15 22:00:00'),
(5, 'party_animal',  '2026-04-14', '2026-04-19 23:30:00'),
(5, 'social_butterfly','2026-05-05','2026-05-08 22:00:00'),
(5, 'night_owl',     '2026-05-12', '2026-05-14 00:00:00'),
-- Sofia rozetleri
(6, 'foodie',        '2026-04-14', '2026-04-16 19:00:00'),
(6, 'explorer',      '2026-05-05', '2026-05-04 12:30:00'),
(6, 'foodie',        '2026-06-07', '2026-06-07 19:00:00'),
-- Marcus rozetleri
(7, 'fitness_freak', '2026-04-14', '2026-04-17 06:30:00'),
(7, 'early_bird',    '2026-04-21', '2026-04-24 08:00:00'),
(7, 'fitness_freak', '2026-06-01', '2026-06-01 07:00:00'),
-- Luna rozetleri
(8, 'art_lover',     '2026-04-14', '2026-04-18 18:00:00'),
(8, 'explorer',      '2026-04-28', '2026-04-29 19:00:00'),
(8, 'night_owl',     '2026-06-05', '2026-06-05 22:00:00');

-- ============================================================
-- PROFİL GÖRÜNTÜLEMELERİ
-- ============================================================
INSERT INTO `profile_views` (`profile_user_id`, `viewer_user_id`, `viewed_at`) VALUES
(3, 4, '2026-04-14 21:00:00'), (3, 5, '2026-04-15 10:00:00'),
(3, 6, '2026-04-16 12:00:00'), (3, 7, '2026-05-01 09:00:00'),
(3, 8, '2026-05-15 14:00:00'), (3, 9, '2026-04-27 20:00:00'),
(3, 10,'2026-05-03 19:00:00'), (3, 12,'2026-04-20 11:00:00'),
(3, 14,'2026-06-08 23:00:00'), (3, 15,'2026-06-01 12:00:00'),
(4, 3, '2026-04-15 08:30:00'), (4, 5, '2026-04-15 22:20:00'),
(4, 6, '2026-05-11 09:00:00'), (4, 8, '2026-04-18 18:00:00'),
(4, 10,'2026-05-19 10:00:00'), (4, 12,'2026-04-25 13:00:00'),
(5, 3, '2026-04-14 22:00:00'), (5, 4, '2026-04-15 22:00:00'),
(5, 9, '2026-04-28 00:30:00'), (5, 10,'2026-05-09 22:30:00'),
(6, 3, '2026-04-16 19:00:00'), (6, 4, '2026-05-11 09:00:00'),
(7, 3, '2026-06-01 07:20:00'), (7, 4, '2026-06-01 07:35:00'),
(8, 4, '2026-04-18 18:00:00'), (8, 6, '2026-05-15 11:00:00');

-- ============================================================
-- İÇERİK RAPORLARI
-- ============================================================
INSERT INTO `content_reports`
  (`reporter_id`, `entity_type`, `entity_id`, `reason`, `description`,
   `status`, `admin_id`, `admin_note`, `created_at`, `resolved_at`)
VALUES
(5, 'checkin', 58, 'fake_checkin',
 'Bu check-in sahte görünüyor, o saatlerde o mekan kapalıydı.',
 'resolved', 2, 'İncelendi. Mekan açıktı, check-in geçerli. Kapatıldı.', '2026-04-21 10:00:00', '2026-04-22 09:00:00'),

(7, 'comment', 3, 'spam',
 'Bu yorum alakasız reklam içeriyor.',
 'dismissed', 1, 'İncelendi, spam değil normal yorum. Kapat.', '2026-04-25 15:00:00', '2026-04-26 10:00:00'),

(12, 'user', 9, 'fraud',
 'Bu kullanıcı sahte casino kazancı bildiriyor olabilir, araştırılmasını istiyorum.',
 'pending', NULL, NULL, '2026-06-09 14:00:00', NULL),

(15, 'venue', 20, 'inappropriate',
 'Bu mekanın kategorisi hatalı gibi görünüyor, ayrıca içerik uygunsuz.',
 'pending', NULL, NULL, '2026-06-10 09:00:00', NULL);

-- ============================================================
-- REKLAMLAR
-- ============================================================
INSERT INTO `ads`
  (`id`, `title`, `image_url`, `link_url`, `position`, `is_active`, `sort_order`, `created_at`)
VALUES
(1, 'Pillbox Grand Casino — VIP Geceniz Sizi Bekliyor',
 'https://placehold.co/728x90/1a1a2e/e94560?text=Pillbox+Grand+Casino+%E2%80%94+VIP+Gece',
 'https://face.gta.world/pages/pillbox-casino',
 'carousel', 1, 1, '2026-02-01 00:00:00'),

(2, 'Paradise Hotel & Spa — Lüksün Yeni Adresi',
 'https://placehold.co/728x90/0f3460/e94560?text=Paradise+Hotel+%26+Spa',
 'https://face.gta.world/pages/paradise-hotel',
 'carousel', 1, 2, '2026-02-15 00:00:00'),

(3, 'Bean Machine Coffee — Her Sabahı Özel Yap',
 'https://placehold.co/300x250/2c1810/c4a35a?text=Bean+Machine+Coffee',
 'https://face.gta.world/pages/bean-machine',
 'sidebar_right', 1, 1, '2026-03-01 00:00:00'),

(4, 'Tequi-la-la — Cuma Geceleri Unutulmaz',
 'https://placehold.co/300x250/1a0533/9b59b6?text=Tequi-la-la+%F0%9F%8E%B8',
 'https://face.gta.world/pages/tequilala',
 'sidebar_left', 1, 1, '2026-03-15 00:00:00'),

(5, 'Sociaera Premium — Tüm Özellikleri Aç',
 'https://placehold.co/728x90/16213e/f5a623?text=Sociaera+Premium+%E2%AD%90',
 '/premium',
 'feed', 1, 10, '2026-04-01 00:00:00'),

(6, 'Bahama Mamas West — Sunset Kokteyller',
 'https://placehold.co/728x90/0d4f3c/27ae60?text=Bahama+Mamas+West+%F0%9F%8D%B9',
 'https://face.gta.world/pages/bahama-mamas',
 'footer_banner', 1, 1, '2026-04-15 00:00:00');

-- ============================================================
-- ADMİN İŞLEM LOGLARI
-- ============================================================
INSERT INTO `admin_logs`
  (`admin_id`, `action_type`, `target_type`, `target_id`, `details`,
   `old_value`, `new_value`, `ip`, `created_at`)
VALUES
(1, 'approve_venue',   'venue',   1, 'Pillbox Grand Casino onaylandı',          'pending', 'approved', '127.0.0.1', '2026-01-05 10:30:00'),
(1, 'approve_venue',   'venue',   2, 'Vinewood Bowl Amphitheater onaylandı',     'pending', 'approved', '127.0.0.1', '2026-01-20 12:30:00'),
(1, 'approve_venue',   'venue',   7, 'Bean Machine Mirror Park onaylandı',       'pending', 'approved', '127.0.0.1', '2026-01-06 11:30:00'),
(1, 'approve_venue',   'venue',  11, 'Paradise Hotel & Spa onaylandı',           'pending', 'approved', '127.0.0.1', '2026-01-12 13:30:00'),
(2, 'reject_venue',    'venue',  20, 'La Mesa Warehouse Club reddedildi — uygunsuz içerik', 'pending', 'rejected', '192.168.1.5', '2026-05-20 22:30:00'),
(1, 'approve_mystery', 'user',    4, 'Aylin_Demir gizli müşteri başvurusu onaylandı', 'pending', 'approved', '127.0.0.1', '2026-03-15 14:00:00'),
(1, 'approve_mystery', 'user',    8, 'Luna_Vega gizli müşteri başvurusu onaylandı', 'pending', 'approved', '127.0.0.1', '2026-04-05 11:00:00'),
(2, 'reject_mystery',  'user',    6, 'Sofia_Rossi gizli müşteri başvurusu reddedildi — çıkar çatışması', 'pending', 'rejected', '192.168.1.5', '2026-02-25 10:00:00'),
(2, 'resolve_report',  'content_report', 1, 'Rapor incelendi ve kapatıldı', 'pending', 'resolved', '192.168.1.5', '2026-04-22 09:00:00'),
(1, 'grant_premium',   'user',    3, 'Carlos_Mendoza premium üyelik verildi (3 ay)', NULL, 'premium', '127.0.0.1', '2026-01-10 12:10:00'),
(1, 'grant_premium',   'user',    4, 'Aylin_Demir premium üyelik verildi (1 ay)',   NULL, 'premium', '127.0.0.1', '2026-02-01 09:10:00');

-- ============================================================
-- ADMİN NOTLARI
-- ============================================================
INSERT INTO `admin_notes`
  (`admin_id`, `entity_type`, `entity_id`, `note`, `created_at`)
VALUES
(1, 'user',  9, 'Ryan Chase — büyük bakiye hareketi izleniyor. Şüpheli işlem yok şu an.', '2026-04-28 10:00:00'),
(2, 'venue', 20,'La Mesa Warehouse Club reddedildi. İşletme sahibine bildirim gönderildi.', '2026-05-20 22:35:00'),
(1, 'user',  3, 'VIP kullanıcı. Platform büyükelçisi adayı. Yakın takipte.', '2026-03-01 09:00:00'),
(2, 'checkin', 58, 'İçerik raporu incelendi. Check-in geçerli, mekan o saatte açıktı.', '2026-04-22 09:00:00');

-- ============================================================
-- FLEECA ÖDEMELERİ
-- ============================================================
INSERT INTO `fleeca_payments`
  (`payment_id`, `user_id`, `amount`, `status`, `mode`, `created_at`, `paid_at`)
VALUES
('fp-c001-2026-0110', 3, 2500.00, 'paid', 'live', '2026-01-10 12:00:00', '2026-01-10 12:03:00'),
('fp-a001-2026-0201', 4, 2200.00, 'paid', 'live', '2026-02-01 09:00:00', '2026-02-01 09:02:00'),
('fp-s001-2026-0210', 6, 3500.00, 'paid', 'live', '2026-02-10 16:00:00', '2026-02-10 16:01:00'),
('fp-e001-2026-0315', 10, 800.00, 'paid', 'live', '2026-03-15 15:00:00', '2026-03-15 15:02:00'),
('fp-z001-2026-0410', 12, 900.00, 'paid', 'live', '2026-04-10 13:00:00', '2026-04-10 13:01:00'),
('fp-l001-2026-0301', 8,  250.00, 'paid', 'live', '2026-03-01 11:00:00', '2026-03-01 11:02:00'),
('fp-n001-2026-0601', 14, 800.00, 'paid', 'live', '2026-06-01 17:00:00', '2026-06-01 17:03:00'),
('fp-t001-2026-0610', 15, 900.00, 'pending', 'live','2026-06-10 10:00:00', NULL);

-- ============================================================
-- SON KONTROL — Kayıt sayıları
-- ============================================================
SELECT 'users'               AS tablo, COUNT(*) AS kayit FROM users
UNION ALL SELECT 'venues',             COUNT(*) FROM venues
UNION ALL SELECT 'checkins',           COUNT(*) FROM checkins
UNION ALL SELECT 'post_likes',         COUNT(*) FROM post_likes
UNION ALL SELECT 'post_comments',      COUNT(*) FROM post_comments
UNION ALL SELECT 'post_reposts',       COUNT(*) FROM post_reposts
UNION ALL SELECT 'user_follows',       COUNT(*) FROM user_follows
UNION ALL SELECT 'notifications',      COUNT(*) FROM notifications
UNION ALL SELECT 'wallets',            COUNT(*) FROM wallets
UNION ALL SELECT 'transactions',       COUNT(*) FROM transactions
UNION ALL SELECT 'venue_ratings',      COUNT(*) FROM venue_ratings
UNION ALL SELECT 'venue_favorites',    COUNT(*) FROM venue_favorites
UNION ALL SELECT 'venue_campaigns',    COUNT(*) FROM venue_campaigns
UNION ALL SELECT 'campaign_redemptions',COUNT(*) FROM campaign_redemptions
UNION ALL SELECT 'mystery_shoppers',   COUNT(*) FROM mystery_shoppers
UNION ALL SELECT 'user_badges',        COUNT(*) FROM user_badges
UNION ALL SELECT 'profile_views',      COUNT(*) FROM profile_views
UNION ALL SELECT 'content_reports',    COUNT(*) FROM content_reports
UNION ALL SELECT 'admin_logs',         COUNT(*) FROM admin_logs
UNION ALL SELECT 'ads',                COUNT(*) FROM ads
UNION ALL SELECT 'fleeca_payments',    COUNT(*) FROM fleeca_payments;
