-- ============================================================
-- Sociaera — DEMO DATA v2 (ÇAKIŞMASIZ)
-- Kullanıcı ID: 101-115 | Mekan ID: 201-220
-- Mevcut veriyle SIFIR çakışma. INSERT IGNORE ile güvenli.
-- Şifre: test123
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- KULLANICILAR (ID 101-115)
-- ============================================================
INSERT IGNORE INTO `users`
  (`id`, `username`, `tag`, `email`, `password_hash`, `bio`,
   `is_admin`, `admin_role`, `is_active`, `is_premium`,
   `premium_until`, `badge`, `gta_character_name`, `last_login_at`, `created_at`)
VALUES
(101, 'Demo_Admin',    'demoadmin',  'demoadmin@sociaera.online',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Demo admin hesabı.',
     1, 'super_admin', 1, 1, '2027-12-31 23:59:59', 'verified',
     NULL, NOW(), '2026-01-01 00:00:00'),

(102, 'Demo_Moderator','demomod',    'demomod@sociaera.online',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Demo moderatör hesabı.',
     1, 'moderator', 1, 1, '2027-06-30 23:59:59', 'moderator',
     NULL, NOW(), '2026-01-05 09:00:00'),

(103, 'Carlos_M',      'carlosm',    'carlos.m@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Los Santos''un en aktif kaşifi 🗺️ | 150+ mekan | Premium üye',
     0, NULL, 1, 1, '2027-03-01 00:00:00', 'gold',
     'Carlos Mendoza', NOW(), '2026-01-10 12:00:00'),

(104, 'Aylin_D',       'aylind',     'aylin.d@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Kafe bağımlısı ☕ | Fotoğrafçı 📸 | Her köşeyi keşfediyorum',
     0, NULL, 1, 1, '2027-01-15 00:00:00', 'explorer',
     'Aylin Demir', NOW(), '2026-01-15 14:30:00'),

(105, 'Jake_M',        'jakem',      'jake.m@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Gece kulübü hayranı 🕺 | DJ severler burada | Nightlife ambassador',
     0, NULL, 1, 0, NULL, NULL,
     'Jake Morrison', NOW(), '2026-02-01 09:00:00'),

(106, 'Sofia_R',       'sofiar',     'sofia.r@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'İtalyan gurme 🍕 | Restoran kritiği | Premium yemek rehberi',
     0, NULL, 1, 1, '2026-12-31 00:00:00', 'foodie',
     'Sofia Rossi', NOW(), '2026-02-10 16:00:00'),

(107, 'Marcus_W',      'marcusw',    'marcus.w@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Spor kulüpleri & fitness 💪 | Sağlıklı yaşam bloggeri',
     0, NULL, 1, 0, NULL, NULL,
     'Marcus Webb', NOW(), '2026-02-20 08:00:00'),

(108, 'Luna_V',        'lunav',      'luna.v@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Sanat galerisi & kültür 🎨 | Müze gezgini | Şehrin nabzını tutuyorum',
     0, NULL, 1, 1, '2026-11-30 00:00:00', 'silver',
     'Luna Vega', NOW(), '2026-03-01 11:00:00'),

(109, 'Ryan_C',        'ryanc',      'ryan.c@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Casino & poker pro 🃏 | Yüksek riskli, yüksek kazanç',
     0, NULL, 1, 0, NULL, NULL,
     'Ryan Chase', NOW(), '2026-03-10 20:00:00'),

(110, 'Emma_S',        'emmas',      'emma.s@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Beach lover 🏖️ | Kokteyller & gün batımları | Hayat kısa, keyif uzun',
     0, NULL, 1, 1, '2027-02-28 00:00:00', 'explorer',
     'Emma Stone', NOW(), '2026-03-15 15:00:00'),

(111, 'Diego_R',       'diegor',     'diego.r@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Lüks otel & spa 🧖‍♂️ | VIP deneyimler',
     0, NULL, 1, 0, NULL, NULL,
     'Diego Reyes', NOW(), '2026-04-01 10:00:00'),

(112, 'Zara_K',        'zarak',      'zara.k@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Yeni keşifler peşinde 🔍 | İlk hafta Premium oldum, pişman değilim!',
     0, NULL, 1, 1, '2026-08-01 00:00:00', NULL,
     'Zara Khan', NOW(), '2026-04-10 13:00:00'),

(113, 'Tom_F',         'tomf',       'tom.f@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Bira & pub kültürü 🍺 | 80+ bar ziyareti',
     0, NULL, 1, 0, NULL, NULL,
     'Tom Fletcher', NOW(), '2026-04-20 19:00:00'),

(114, 'Nina_P',        'ninap',      'nina.p@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Canlı müzik takipçisi 🎵 | Konser fotoğrafçısı',
     0, NULL, 1, 1, '2026-09-30 00:00:00', NULL,
     'Nina Park', NOW(), '2026-05-01 17:00:00'),

(115, 'Alex_T',        'alext',      'alex.t@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Yeni kullanıcı 🆕 | Şehri tanımaya başlıyorum',
     0, NULL, 1, 0, NULL, NULL,
     'Alex Torres', NOW(), '2026-06-01 10:00:00');

-- ============================================================
-- MEKANLAR (ID 201-220)
-- ============================================================
INSERT IGNORE INTO `venues`
  (`id`, `name`, `description`, `address`, `phone`, `hours`,
   `is_open`, `category`, `facebrowser_url`, `status`, `is_active`,
   `created_by`, `created_at`)
VALUES
(201, 'Pillbox Grand Casino',
     'Los Santos''un en prestijli kumarhanesi. Blackjack, Teksas Hold''em, Rulet ve 200+ slot makinesi.',
     'Pillbox Hill, Los Santos', '(555) 800-2500', 'Pzt-Paz 00:00-24:00',
     1, 'eglence', 'https://face.gta.world/pages/pillbox-casino', 'approved', 1, 101, '2026-01-05 10:00:00'),

(202, 'Vinewood Bowl Amphitheater',
     'Açık hava amfitiyatro. Canlı konserler, stand-up gösterileri ve sinema geceleri.',
     'Vinewood Hills, Los Santos', '(555) 900-1111', 'Etkinlik günleri',
     1, 'eglence', 'https://face.gta.world/pages/vinewood-bowl', 'approved', 1, 101, '2026-01-20 12:00:00'),

(203, 'Maze Bank Arena',
     'Los Santos''un dev spor & etkinlik arenası. Basketbol, boks maçları, dev konserler.',
     'LSIA District, Los Santos', '(555) 700-2736', 'Etkinliğe göre',
     0, 'eglence', 'https://face.gta.world/pages/maze-bank-arena', 'approved', 1, 101, '2026-02-01 09:00:00'),

(204, 'Tequi-la-la',
     'Vinewood''un ikonik rock bar''ı. Canlı müzik, dans pisti ve efsanevi kokteyller. Perşembe Ladies Night.',
     'West Vinewood, Los Santos', '(555) 433-2522', 'Sal-Paz 20:00-04:00',
     1, 'bar', 'https://face.gta.world/pages/tequilala', 'approved', 1, 101, '2026-01-08 15:00:00'),

(205, 'Bahama Mamas West',
     'Del Perro sahilinde tropikal temalı beach bar. Sunset kokteyller ve DJ performansları.',
     'Del Perro Beach, Los Santos', '(555) 226-2626', 'Pzt-Paz 16:00-02:00',
     1, 'bar', 'https://face.gta.world/pages/bahama-mamas', 'approved', 1, 101, '2026-01-10 09:00:00'),

(206, 'Vanilla Unicorn Lounge',
     'Strawberry''nin en popüler karanlık bar''ı. Cuma ve Cumartesi geceleri özel DJ setleri.',
     'Strawberry Ave, Los Santos', '(555) 100-8865', 'Çar-Paz 21:00-06:00',
     1, 'bar', 'https://face.gta.world/pages/vanilla-unicorn', 'approved', 1, 102, '2026-02-05 20:00:00'),

(207, 'Bean Machine Coffee',
     'Şehrin en sevilen specialty coffee zinciri. Single origin espresso ve taze pastane ürünleri.',
     'Mirror Park Blvd, Los Santos', '(555) 232-2327', 'Pzt-Paz 07:00-22:00',
     1, 'kafe', 'https://face.gta.world/pages/bean-machine', 'approved', 1, 101, '2026-01-06 11:00:00'),

(208, 'Burger Shot Rockford',
     'GTA''nın en iyi fast food zinciri. Dev burgerleri ve gece yarısı açık olmasıyla efsane.',
     'Rockford Hills, Los Santos', '(555) 267-8437', 'Pzt-Paz 00:00-24:00',
     1, 'restoran', 'https://face.gta.world/pages/burger-shot', 'approved', 1, 101, '2026-01-12 08:00:00'),

(209, 'Pizza This Downtown',
     'İtalyan usulü el yapımı pizza. Odun fırını, taze malzemeler.',
     'Downtown, Los Santos', '(555) 749-8324', 'Pzt-Cmt 11:00-23:00',
     1, 'restoran', 'https://face.gta.world/pages/pizza-this', 'approved', 1, 103, '2026-02-01 14:00:00'),

(210, 'Hookies Sandy Shores',
     'Blaine County''nin balık-cips spesyalisti.',
     'Sandy Shores, Blaine County', '(555) 465-3726', 'Sal-Paz 12:00-21:00',
     1, 'restoran', 'https://face.gta.world/pages/hookies', 'approved', 1, 102, '2026-02-15 10:00:00'),

(211, 'Paradise Hotel & Spa',
     'Rockford Hills''in 5 yıldızlı lüks oteli. Infinity havuz ve tam donanımlı spa.',
     'Rockford Hills, Los Santos', '(555) 747-3337', 'Pzt-Paz 00:00-24:00',
     1, 'otel', 'https://face.gta.world/pages/paradise-hotel', 'approved', 1, 101, '2026-01-12 13:00:00'),

(212, 'Del Perro Beach Hotel',
     'Sahil kenarında butik otel. Sonsuzluk havuzu, beach bar ve günbatımı manzarası.',
     'Del Perro, Los Santos', '(555) 375-7243', 'Pzt-Paz 00:00-24:00',
     1, 'otel', 'https://face.gta.world/pages/del-perro-hotel', 'approved', 1, 104, '2026-03-01 11:00:00'),

(213, 'Muscle Sands Gym',
     'Vespucci sahilinde açık hava spor alanı. Ücretsiz dumbbells ve Pacificko manzarası.',
     'Vespucci Beach, Los Santos', NULL, 'Pzt-Paz 06:00-22:00',
     1, 'spor', 'https://face.gta.world/pages/muscle-sands', 'approved', 1, 101, '2026-01-25 07:00:00'),

(214, 'LS Fitness Center',
     'Premium spor merkezi. 300+ ekipman, grup dersleri, kişisel antrenörler ve sauna.',
     'Downtown, Los Santos', '(555) 547-8348', 'Pzt-Cmt 05:00-23:00',
     1, 'spor', 'https://face.gta.world/pages/ls-fitness', 'approved', 1, 101, '2026-02-10 06:00:00'),

(215, 'Galileo Observatory',
     'Vinewood Hills''in ikonik gözlemevi. Şehrin en iyi manzarası ve gece yıldız etkinlikleri.',
     'Vinewood Hills, Los Santos', '(555) 924-9244', 'Pzt-Paz 10:00-22:00',
     1, 'kultur', 'https://face.gta.world/pages/galileo-obs', 'approved', 1, 102, '2026-02-20 10:00:00'),

(216, 'LS Art Gallery',
     'Çağdaş sanat galerisi. Yerel ve uluslararası sanatçıların eserleri. Her ay yeni sergi.',
     'Little Seoul, Los Santos', '(555) 534-5343', 'Sal-Paz 10:00-19:00',
     1, 'kultur', 'https://face.gta.world/pages/ls-art-gallery', 'approved', 1, 102, '2026-03-05 09:00:00'),

(217, 'Rockford Hills Mall',
     'Lüks alışveriş merkezi. Gucci, Prada, Hermes ve daha fazlası.',
     'Rockford Hills, Los Santos', '(555) 764-7368', 'Pzt-Paz 09:00-22:00',
     1, 'alisveris', 'https://face.gta.world/pages/rh-mall', 'approved', 1, 101, '2026-03-15 10:00:00'),

(218, 'Sandy Shores Motel',
     'Sade ama temiz konaklama. Blaine County''ye yakın, makul fiyat.',
     'Sandy Shores, Blaine County', '(555) 726-3726', 'Pzt-Paz 00:00-24:00',
     1, 'otel', NULL, 'pending', 1, 115, '2026-06-01 12:00:00'),

(219, 'Downtown Diner',
     'Ev yemekleri ve kahvaltı spesiyalisti. 1960''lar retro atmosferi.',
     'Downtown, Los Santos', '(555) 346-3463', 'Pzt-Paz 07:00-20:00',
     1, 'restoran', NULL, 'pending', 1, 112, '2026-06-05 09:00:00'),

(220, 'La Mesa Warehouse Club',
     'La Mesa''nın yeraltı elektronik müzik kulübü.',
     'La Mesa, Los Santos', NULL, 'Cum-Paz 23:00-08:00',
     0, 'bar', NULL, 'rejected', 0, 113, '2026-05-20 22:00:00');

-- ============================================================
-- CHECK-IN'LER (ID 2001+)
-- ============================================================
INSERT IGNORE INTO `checkins`
  (`id`, `user_id`, `venue_id`, `note`, `created_at`)
VALUES
-- Carlos_M (103) — 12 check-in
(2001, 103, 201, 'Akşam poker masasındaydım 🎰 Tam 3 saat oturdum. Harika bir gece!', '2026-04-14 20:30:00'),
(2002, 103, 204, 'Tequi-la-la hiç değişmemiş 🎸 Canlı müzik efsaneydi bu gece!', '2026-04-18 22:00:00'),
(2003, 103, 207, 'Sabah kahvem Bean Machine''de ☕ Latte art''ı görülmeye değer', '2026-04-22 09:15:00'),
(2004, 103, 211, 'Paradise Spa''da bir hafta sonu 🧖‍♂️ İş stresi tamamen gitti', '2026-04-28 14:00:00'),
(2005, 103, 205, 'Gün batımında Bahama Mamas 🍹 Manzara inanılmaz @aylind gel bir ara!', '2026-05-03 18:45:00'),
(2006, 103, 202, 'Vinewood Bowl''da konser gecesi 🎤 Sahne kurulumu muhteşem', '2026-05-10 21:00:00'),
(2007, 103, 208, 'Gece yarısı Burger Shot 🍔 Suçluluk duyuyorum ama pişman değilim 😂', '2026-05-15 01:30:00'),
(2008, 103, 217, 'Rockford Hills Mall alışveriş seansı 🛍️ Cüzdanım ağladı', '2026-05-20 14:00:00'),
(2009, 103, 213, 'Muscle Sands sabah antrenmanı 💪 Deniz havası ayrı güzel', '2026-05-25 07:30:00'),
(2010, 103, 201, 'Blackjack masasında şansım yaver gitti 💰 +5000 🤑', '2026-05-30 21:00:00'),
(2011, 103, 209, 'Pizza This''in odun fırını pizzası... Tanrım 🍕 Sözcük bulamıyorum', '2026-06-03 19:30:00'),
(2012, 103, 204, 'Perşembe geceleri Tequi-la-la''da olmak şart 🎶 @jakem @ninap gelin', '2026-06-08 22:30:00'),

-- Aylin_D (104) — 11 check-in
(2013, 104, 207, 'Sabah kahvesi burada içilir ☕ Oat milk latte = hayat', '2026-04-15 08:15:00'),
(2014, 104, 216, 'LS Art Gallery''deki yeni sergiye gittim 🎨 Türk sanatçının eserleri vardı!', '2026-04-20 11:00:00'),
(2015, 104, 211, 'Paradise Spa doğum günü hediyem 🎁 Harika bir deneyim', '2026-04-25 13:00:00'),
(2016, 104, 201, 'Blackjack masasında şansım yaver gitti 💰 @carlosm öğretti', '2026-04-30 21:00:00'),
(2017, 104, 205, 'Bahama Mamas''da sunset 🌅 Bu manzarayı görmeden yaşama', '2026-05-05 18:00:00'),
(2018, 104, 207, 'Bean Machine yeni menüsü gelmiş ☕ Pistachio latte 10/10', '2026-05-12 10:00:00'),
(2019, 104, 215, 'Galileo Observatory''den şehir manzarası 😍 Gece ayrı güzel', '2026-05-18 20:00:00'),
(2020, 104, 209, 'Pizza This hafta sonu öğle yemeği 🍕 40 dk kuyruk beklettim ama değdi', '2026-05-24 13:30:00'),
(2021, 104, 216, 'Art Gallery tekrar geldim 🖼️ Sergi değişmiş, yeni eserler var', '2026-05-29 15:00:00'),
(2022, 104, 204, 'Tequi-la-la''da DJ gecesi 🎧 @jakem dans pisti yaktı', '2026-06-04 23:00:00'),
(2023, 104, 207, 'Üçüncü Bean Machine bu hafta 😅 Bağımlıyım buna artık', '2026-06-09 09:00:00'),

-- Jake_M (105) — 10 check-in
(2024, 105, 204, 'Muhteşem canlı müzik performansı! 🎸 Bu band gelecek vaat ediyor', '2026-04-15 22:00:00'),
(2025, 105, 206, 'Vanilla Unicorn''un friday night seti 🔥 Saat 04''e kadar dans ettim', '2026-04-19 23:30:00'),
(2026, 105, 205, 'Bahama Mamas beach session 🏖️ Summer vibes başladı', '2026-04-23 17:00:00'),
(2027, 105, 207, 'Bean Machine her zamanki gibi harika ☕ @aylind haklıymış', '2026-04-27 10:30:00'),
(2028, 105, 202, 'Vinewood Bowl open air concert 🎵 5000 kişilik kalabalık inanılmaz', '2026-05-02 20:00:00'),
(2029, 105, 204, 'Perşembe geceleri Tequi-la-la''da olmak şart! 🎶', '2026-05-08 22:00:00'),
(2030, 105, 206, 'Vanilla Unicorn underground set 🎧 En iyi DJ Los Santos''ta burada', '2026-05-14 00:00:00'),
(2031, 105, 203, 'Maze Bank Arena''da basketbol maçı 🏀 Ev sahibi takım kazandı!', '2026-05-22 19:00:00'),
(2032, 105, 204, 'Tequi-la-la''dan tekrar selamlar 🎸 Bu hafta 2. gelişim 😄', '2026-05-28 21:30:00'),
(2033, 105, 214, 'LS Fitness sabah seansı 💪 Jake Morrison kas yapmayı öğreniyor', '2026-06-02 07:00:00'),

-- Sofia_R (106) — 9 check-in
(2034, 106, 209, 'Pizza This odun fırını pizzası 🍕 İtalya''dan daha iyi, yemin ederim', '2026-04-16 19:00:00'),
(2035, 106, 208, 'Burger Shot kirli guilty pleasure 🍔 Bazen insan kendini ödüllendirmeli', '2026-04-21 13:00:00'),
(2036, 106, 211, 'Paradise Hotel restoranı 🍽️ Fiyat uygun değil ama değer', '2026-04-26 20:00:00'),
(2037, 106, 210, 'Hookies balık-cips 🐟 Sandy Shores''un gizli incisi. Herkese tavsiye!', '2026-05-04 12:30:00'),
(2038, 106, 207, 'Bean Machine sabah rutini ☕ @aylind ile buluştuk, harika sohbet', '2026-05-11 09:00:00'),
(2039, 106, 209, 'Pizza This tekrar! 🍕 Bu sefer quattro stagioni. Mükemmel.', '2026-05-17 19:30:00'),
(2040, 106, 208, 'Burger Shot gece yarısı 🌙🍔 Utanarak söylüyorum: ikinci kez bu hafta', '2026-05-23 01:00:00'),
(2041, 106, 212, 'Del Perro Beach Hotel''in beach bar''ı 🍹 Havuz kenarı mükemmel', '2026-05-31 16:00:00'),
(2042, 106, 209, 'Pizza This Cuma akşamı kuyruk olmadan girdim, şanslı gün! 🍕🎉', '2026-06-07 19:00:00'),

-- Marcus_W (107) — 7 check-in
(2043, 107, 213, 'Muscle Sands sabah antrenmanı 🌅 Deniz kokusu ayrı motivasyon', '2026-04-17 06:30:00'),
(2044, 107, 214, 'LS Fitness grup dersi 💪 CrossFit seansı beni mahvetti ama memnunum', '2026-04-24 08:00:00'),
(2045, 107, 213, 'Muscle Sands akşam antrenmanı 🏋️‍♂️ Gün batımında dumbbell press', '2026-05-01 18:00:00'),
(2046, 107, 207, 'Antrenman sonrası protein shake Bean Machine''de ☕ İyi kombinasyon', '2026-05-07 09:30:00'),
(2047, 107, 214, 'LS Fitness kişisel antrenör seansı 💪 Yeni program başladı', '2026-05-13 07:00:00'),
(2048, 107, 203, 'Maze Bank Arena''da boks maçı 🥊 Atmosfer muhteşemdi', '2026-05-21 20:00:00'),
(2049, 107, 213, 'Muscle Sands 50. check-in 🎉 Bu plaja ne kadar borçluyum', '2026-06-01 07:00:00'),

-- Luna_V (108) — 8 check-in
(2050, 108, 216, 'LS Art Gallery açılış gecesi 🎨 Yerel sanatçıları destekleyin!', '2026-04-18 18:00:00'),
(2051, 108, 215, 'Galileo Observatory''den gece manzarası 🔭 Şehir ışıkları büyüleyici', '2026-04-22 21:00:00'),
(2052, 108, 202, 'Vinewood Bowl''da klasik müzik gecesi 🎻 Farklı ama muhteşemdi', '2026-04-29 19:00:00'),
(2053, 108, 207, 'Bean Machine''de çalışma seansı 💻 Saatler nasıl geçti anlamadım', '2026-05-06 14:00:00'),
(2054, 108, 216, 'Yeni sergi açıldı! 🖼️ Bu ay fotoğraf sergisi var. Kaçırmayın!', '2026-05-15 11:00:00'),
(2055, 108, 211, 'Paradise Spa wellness weekendi 🧘‍♀️ Ruh ve beden dinlendi', '2026-05-26 13:00:00'),
(2056, 108, 215, 'Gözlemevi yıldız gecesi etkinliği 🌟 Teleskopla Jüpiter gördüm!', '2026-06-05 22:00:00'),
(2057, 108, 216, 'Art Gallery üçüncü kez 🎨 Her gelişte farklı bir şey keşfediyorum', '2026-06-10 10:00:00'),

-- Ryan_C (109) — 5 check-in
(2058, 109, 201, 'Casino ilk gece 🎰 Rulet masasında başladım. Kötü başlangıç...', '2026-04-20 22:00:00'),
(2059, 109, 201, 'Pillbox Casino tekrar 🃏 Poker turnuvası! 3. sırada bittim', '2026-04-27 20:00:00'),
(2060, 109, 206, 'Vanilla Unicorn''da kötü kazanan gibi kutlama 😎🎉', '2026-04-28 00:30:00'),
(2061, 109, 201, 'Blackjack tablosu ezber oldu artık 🃏 Sistem var burada', '2026-05-10 21:00:00'),
(2062, 109, 205, 'Bahama Mamas''da kazançları kutluyoruz 🍹💰 İyi bir hafta geçti', '2026-05-17 19:00:00'),

-- Emma_S (110) — 6 check-in
(2063, 110, 205, 'Bahama Mamas gün batımı 🌅🍹 Hayatımın en güzel kokteylli akşamı', '2026-04-19 17:30:00'),
(2064, 110, 212, 'Del Perro Beach Hotel havuzu 🏊‍♀️ VIP hissettiriyor, fiyatı makul', '2026-04-25 15:00:00'),
(2065, 110, 205, 'Sahil bar akşamı 🌊 @carlosm ile buluştuk, harika sohbet', '2026-05-03 18:00:00'),
(2066, 110, 204, 'Tequi-la-la Cuma gecesi 🎸 @jakem dans pistiyle karşılaştı 😄', '2026-05-09 22:30:00'),
(2067, 110, 212, 'Beach hotel Pazar kahvaltısı 🍳 Deniz manzaralı masa rezervasyonu şart', '2026-05-19 10:00:00'),
(2068, 110, 205, 'Bahama Mamas üçüncü kez bu ay 🍹 Favorim oldu kesinlikle', '2026-06-06 18:00:00');

-- ============================================================
-- BEĞENİLER
-- ============================================================
INSERT IGNORE INTO `post_likes` (`user_id`, `checkin_id`) VALUES
(104,2001),(105,2001),(106,2001),(108,2001),(110,2001),
(104,2002),(105,2002),(109,2002),
(104,2003),(106,2003),(108,2003),
(104,2005),(105,2005),(110,2005),
(105,2010),(109,2010),
(104,2012),(105,2012),(108,2012),
(103,2013),(105,2013),(106,2013),(108,2013),
(103,2014),(108,2014),(110,2014),
(103,2017),(105,2017),(110,2017),
(103,2018),(106,2018),(108,2018),
(103,2019),(108,2019),
(103,2022),(105,2022),(109,2022),
(103,2024),(104,2024),(109,2024),(110,2024),
(103,2025),(109,2025),
(104,2026),(110,2026),
(104,2027),(106,2027),
(103,2028),(104,2028),(108,2028),
(103,2029),(104,2029),(110,2029),
(103,2034),(104,2034),(107,2034),(110,2034),
(103,2036),(104,2036),(108,2036),
(103,2037),(104,2037),(107,2037),
(103,2042),(104,2042),(107,2042),
(103,2043),(105,2043),(108,2043),
(103,2049),(104,2049),(105,2049),(106,2049),(108,2049),(110,2049),
(103,2050),(104,2050),(106,2050),(110,2050),
(103,2054),(104,2054),(106,2054),(108,2054),
(103,2056),(104,2056),(108,2056),
(103,2063),(104,2063),(105,2063),(107,2063),
(104,2064),(106,2064),
(103,2065),(104,2065),
(104,2066),(105,2066),(109,2066),
(104,2068),(105,2068),(106,2068),(107,2068);

-- ============================================================
-- YORUMLAR
-- ============================================================
INSERT IGNORE INTO `post_comments`
  (`user_id`, `checkin_id`, `comment`, `created_at`)
VALUES
(104, 2001, 'Keşke ben de orada olsaydım 😄 Bir dahaki poker gecesine ben de geleyim!', '2026-04-14 20:50:00'),
(105, 2001, 'Poker masası için slot mu, masa oyunları mı? 🎰', '2026-04-14 21:10:00'),
(109, 2001, 'Carlos abi strateji anlatır mısın sonra 😅', '2026-04-14 21:30:00'),
(103, 2013, 'Oranın oat milk lattesi gerçekten farklı! @aylind haklı', '2026-04-15 08:30:00'),
(105, 2013, 'Kahve bağımlılığı — ilk adım kabul etmektir 😄', '2026-04-15 08:45:00'),
(103, 2024, 'Jake bu hafta sonu Tequi-la-la''ya gidiyoruz mu? 🎸', '2026-04-15 22:20:00'),
(104, 2024, 'Band ismi ne? Araştırayım 🎵', '2026-04-15 22:35:00'),
(103, 2034, 'Sofia bu pizzacıyı sen söyledin gidiyorum kesinlikle! 🍕', '2026-04-16 19:20:00'),
(104, 2034, 'Odun fırını pizzası başka bir şey... Rezervasyon lazım mı?', '2026-04-16 19:40:00'),
(106, 2034, 'İtalyan olarak onaylıyorum 🇮🇹 Gerçekten kaliteli', '2026-04-16 20:00:00'),
(103, 2005, 'Bahama Mamas gün batımı... Efsane! Ben de geliyorum 🍹', '2026-05-03 19:00:00'),
(110, 2005, 'Gün batımı saatini sorduruyorum o manzara için 🌅', '2026-05-03 19:15:00'),
(103, 2037, 'Hookies! Hiç gitmedim, bu yazın listesine ekledim 🐟', '2026-05-04 12:50:00'),
(104, 2037, 'Sandy Shores''ta mı? Uzak ama değer gibi görünüyor', '2026-05-04 13:10:00'),
(103, 2049, 'Marcus 50. check-in kutlu olsun! 🎉🎉🎉 Milestone!', '2026-06-01 07:20:00'),
(104, 2049, 'Milestone geldi! Plaj seni seviyor 💪', '2026-06-01 07:35:00'),
(105, 2049, 'Hedef 100''e devam bro! 🏋️', '2026-06-01 07:50:00'),
(108, 2049, '50 kez aynı yere gitmek... Dedikasyon bu 🙌', '2026-06-01 08:00:00'),
(110, 2049, 'Plaj ruhunu anlıyorum seni 🌊', '2026-06-01 08:15:00'),
(103, 2056, 'Luna teleskopla ne gördün? Fotoğraf çektirdin mi? 🔭', '2026-06-05 22:20:00'),
(104, 2056, 'Gözlemevi etkinliğine nasıl katılınıyor?', '2026-06-05 22:35:00'),
(103, 2012, '@jakem @ninap bu Perşembe Tequi-la-la''ya gidiyor musunuz?', '2026-06-08 22:45:00'),
(105, 2012, 'Kesinlikle! 🎶 Saat 10''da orada olacağım', '2026-06-08 23:00:00');

-- ============================================================
-- REPOSTLAR
-- ============================================================
INSERT IGNORE INTO `post_reposts` (`user_id`, `checkin_id`, `quote`) VALUES
(104, 2001, 'Poker gecesine katılmak için @carlosm ile iletişime geçin! 🃏'),
(105, 2001, 'Casino gecesi organizasyonu başlıyor mu? 🎰'),
(103, 2024, 'Canlı müzik için Tequi-la-la kesinlikle bir numaralı adres 🎸'),
(108, 2050, 'LS Art Gallery''yi destekleyin! Yerel sanatçılar için önemli 🎨'),
(110, 2005, 'Bahama Mamas gün batımı — şehrin en iyi sunset noktası 🌅'),
(103, 2049, 'Marcus 50 check-in milestone — ilham verici! 💪'),
(104, 2037, 'Hookies Sandy Shores — gizli kalmış bir lezzet durağı 🐟');

-- ============================================================
-- TAKİP İLİŞKİLERİ
-- ============================================================
INSERT IGNORE INTO `user_follows` (`follower_id`, `following_id`) VALUES
(103,104),(103,105),(103,106),(103,107),(103,108),(103,110),
(104,103),(104,105),(104,106),(104,108),(104,110),(104,114),
(105,103),(105,104),(105,109),(105,110),(105,114),
(106,103),(106,104),(106,107),(106,108),
(107,103),(107,105),(107,113),(107,114),
(108,103),(108,104),(108,106),(108,115),(108,116),
(109,103),(109,105),
(110,103),(110,104),(110,105),(110,106),(110,108),
(112,103),(112,104),(112,105),(112,106),(112,107),(112,108),(112,110),
(113,105),(113,109),(113,110),
(114,103),(114,104),(114,105),(114,108),
(115,103),(115,104),(115,106);

-- ============================================================
-- BİLDİRİMLER
-- ============================================================
INSERT IGNORE INTO `notifications`
  (`user_id`, `from_user_id`, `type`, `content`, `checkin_id`, `is_read`, `created_at`)
VALUES
(103,104,'like',    'Aylin_D gönderini beğendi.',           2001, 1, '2026-04-14 20:50:00'),
(103,105,'like',    'Jake_M gönderini beğendi.',            2001, 1, '2026-04-14 21:10:00'),
(103,104,'comment', 'Aylin_D gönderine yorum yaptı.',       2001, 1, '2026-04-14 20:50:00'),
(103,105,'comment', 'Jake_M gönderine yorum yaptı.',        2001, 1, '2026-04-14 21:10:00'),
(103,109,'comment', 'Ryan_C gönderine yorum yaptı.',        2001, 0, '2026-04-14 21:30:00'),
(104,103,'like',    'Carlos_M gönderini beğendi.',          2013, 1, '2026-04-15 08:30:00'),
(104,103,'follow',  'Carlos_M seni takip etmeye başladı.',  NULL, 1, '2026-04-14 20:00:00'),
(104,105,'follow',  'Jake_M seni takip etmeye başladı.',    NULL, 1, '2026-04-15 10:00:00'),
(105,103,'follow',  'Carlos_M seni takip etmeye başladı.',  NULL, 1, '2026-04-14 20:00:00'),
(105,103,'comment', 'Carlos_M gönderine yorum yaptı.',      2024, 1, '2026-04-15 22:20:00'),
(105,104,'comment', 'Aylin_D gönderine yorum yaptı.',       2024, 1, '2026-04-15 22:35:00'),
(106,103,'comment', 'Carlos_M gönderine yorum yaptı.',      2034, 1, '2026-04-16 19:20:00'),
(106,104,'follow',  'Aylin_D seni takip etmeye başladı.',   NULL, 0, '2026-05-05 09:00:00'),
(107,103,'like',    'Carlos_M gönderini beğendi.',          2049, 0, '2026-06-01 07:20:00'),
(107,104,'like',    'Aylin_D gönderini beğendi.',           2049, 0, '2026-06-01 07:35:00'),
(107,103,'comment', 'Carlos_M gönderine yorum yaptı.',      2049, 0, '2026-06-01 07:20:00'),
(108,103,'follow',  'Carlos_M seni takip etmeye başladı.',  NULL, 0, '2026-05-01 10:00:00'),
(103,105,'mention', 'Jake_M senden bahsetti.',              2012, 0, '2026-06-08 23:00:00'),
(114,103,'mention', 'Carlos_M senden bahsetti.',            2012, 0, '2026-06-08 22:45:00'),
(103,110,'repost',  'Emma_S gönderini repost''ladı.',       2005, 0, '2026-05-03 19:30:00');

-- ============================================================
-- CÜZDANLAR
-- ============================================================
INSERT IGNORE INTO `wallets` (`user_id`, `balance`) VALUES
(101,250000.00),(102,15000.00),(103,47500.00),(104,12800.00),
(105,8200.00),  (106,31500.00),(107,5400.00), (108,19750.00),
(109,85000.00), (110,11200.00),(111,3800.00), (112,6600.00),
(113,4100.00),  (114,9300.00), (115,1500.00);

-- ============================================================
-- İŞLEMLER
-- ============================================================
INSERT IGNORE INTO `transactions`
  (`user_id`, `type`, `amount`, `description`, `reference_id`, `created_at`)
VALUES
(103,'deposit',     50000.00,'İlk yatırım — Fleeca Bank',       'D2-C001','2026-01-10 12:00:00'),
(103,'withdraw',     2500.00,'Premium abonelik (3 ay)',          'D2-C002','2026-01-10 12:05:00'),
(103,'deposit',      5000.00,'Check-in milestone ödülü',        'D2-C003','2026-03-30 10:00:00'),
(103,'transfer_out', 5000.00,'Para transferi → Aylin_D',        'D2-C004','2026-04-15 15:00:00'),
(104,'transfer_in',  5000.00,'Para transferi ← Carlos_M',       'D2-A001','2026-04-15 15:01:00'),
(104,'deposit',     10000.00,'Fleeca Bank yatırım',             'D2-A002','2026-01-15 14:00:00'),
(104,'withdraw',     2200.00,'Premium abonelik (1 ay)',          'D2-A003','2026-02-01 09:00:00'),
(105,'deposit',     10000.00,'İlk yatırım',                    'D2-J001','2026-02-01 09:00:00'),
(105,'withdraw',     1800.00,'VIP giriş — Tequi-la-la',        'D2-J002','2026-03-19 20:00:00'),
(106,'deposit',     35000.00,'İşletme tanıtım bütçesi',        'D2-S001','2026-02-10 16:00:00'),
(106,'withdraw',     3500.00,'Premium abonelik (6 ay)',         'D2-S002','2026-02-10 16:05:00'),
(107,'deposit',      7000.00,'İlk yatırım',                    'D2-M001','2026-02-20 08:00:00'),
(107,'withdraw',     1600.00,'LS Fitness aylık üyelik',        'D2-M002','2026-03-01 09:00:00'),
(108,'deposit',     20000.00,'Sanat projesi fon transferi',     'D2-L001','2026-03-01 11:00:00'),
(109,'deposit',    100000.00,'Casino kazancı — büyük el 🃏',    'D2-R001','2026-04-27 23:00:00'),
(109,'withdraw',    15000.00,'Casino kaybı',                   'D2-R002','2026-05-10 22:00:00'),
(110,'deposit',     12000.00,'İlk yatırım',                    'D2-E001','2026-03-15 15:00:00'),
(112,'deposit',      7500.00,'İlk yatırım',                    'D2-Z001','2026-04-10 13:00:00'),
(112,'withdraw',      900.00,'Premium abonelik (1 ay)',         'D2-Z002','2026-04-10 13:05:00');

-- ============================================================
-- MEKAN PUANLAMALARI
-- ============================================================
INSERT IGNORE INTO `venue_ratings` (`venue_id`, `user_id`, `rating`) VALUES
(201,103,5),(201,104,4),(201,105,4),(201,109,5),(201,110,3),
(202,103,5),(202,105,5),(202,108,5),
(203,105,4),(203,107,5),
(204,103,5),(204,105,5),(204,110,4),(204,114,5),(204,104,4),
(205,103,5),(205,104,5),(205,110,5),(205,106,4),(205,109,4),
(206,105,4),(206,109,5),(206,113,3),
(207,104,5),(207,103,4),(207,105,4),(207,106,5),(207,107,4),(207,108,5),
(208,103,3),(208,106,3),(208,113,4),
(209,106,5),(209,103,5),(209,104,5),(209,107,4),
(210,106,5),(210,103,4),(210,104,4),
(211,103,5),(211,104,5),(211,106,4),(211,108,5),
(212,110,5),(212,104,4),(212,106,5),
(213,107,5),(213,103,4),(213,105,3),
(214,107,4),(214,105,4),
(215,108,5),(215,104,5),(215,103,4),
(216,108,5),(216,104,5),(216,106,4),
(217,103,4),(217,104,3),(217,106,5);

-- ============================================================
-- MEKAN FAVORİLERİ
-- ============================================================
INSERT IGNORE INTO `venue_favorites` (`user_id`, `venue_id`) VALUES
(103,201),(103,204),(103,205),(103,211),
(104,207),(104,216),(104,211),(104,215),
(105,204),(105,206),(105,202),
(106,209),(106,210),(106,211),(106,212),
(107,213),(107,214),
(108,216),(108,215),(108,202),
(109,201),(109,206),
(110,205),(110,212),(110,204),
(112,207),(112,204),(112,211),
(114,204),(114,202);

-- ============================================================
-- KAMPANYALAR
-- ============================================================
INSERT IGNORE INTO `venue_campaigns`
  (`id`, `venue_id`, `title`, `description`,
   `trigger_type`, `trigger_value`,
   `reward_type`, `reward_value`, `reward_text`,
   `is_active`, `starts_at`, `ends_at`, `max_redemptions`, `created_at`)
VALUES
(201, 201, '10. Check-in Özel Chip Bonusu',
 'Pillbox Grand Casino''da 10. check-in''ini yap, 500 casino chip kazan!',
 'nth_checkin', 10, 'discount_fixed', 500.00, '500 Casino Chip Hediye',
 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, '2026-01-15 10:00:00'),

(202, 204, 'Sadık Misafir — 5. Ziyaret',
 'Tequi-la-la''ya 5. gelişinde içeceğin bedava!',
 'nth_checkin', 5, 'free_item', NULL, 'Bir İçecek Bedava',
 1, '2026-02-01 00:00:00', '2026-08-31 23:59:59', 50, '2026-02-01 10:00:00'),

(203, 207, 'Bean Machine Kahve Abonesi',
 '20. check-in''inde 1 aylık ücretsiz kahve aboneliği!',
 'nth_checkin', 20, 'custom', NULL, '1 Aylık Ücretsiz Kahve Aboneliği',
 1, '2026-01-01 00:00:00', NULL, NULL, '2026-01-20 08:00:00'),

(204, 211, 'Lüks Spa Paketi',
 'Paradise Hotel & Spa''da ilk check-in''inde %25 spa paketi indirimi.',
 'first_checkin', 1, 'discount_percent', 25.00, '%25 Spa Paketi İndirimi',
 1, '2026-03-01 00:00:00', '2026-09-30 23:59:59', 100, '2026-03-01 10:00:00'),

(205, 209, 'Pizza Tutkunu',
 '5. ziyaretinde büyük boy pizza bedava!',
 'nth_checkin', 5, 'free_item', NULL, 'Büyük Boy Pizza Bedava',
 1, '2026-04-01 00:00:00', NULL, 30, '2026-04-01 10:00:00'),

(206, 205, 'Sunset Kokteyl Paketi',
 'Bahama Mamas''da 3. check-in''inde 2 kokteyl bedava!',
 'nth_checkin', 3, 'free_item', NULL, '2 Kokteyl Bedava (Sunset Saat)',
 1, '2026-05-01 00:00:00', '2026-10-31 23:59:59', NULL, '2026-05-01 10:00:00'),

(207, 202, 'VIP Konser Deneyimi',
 'Vinewood Bowl''da 3. biletini al, 4. biletin ücretsiz!',
 'total_checkins', 3, 'discount_percent', 100.00, 'Sonraki Bilet Ücretsiz',
 1, '2026-04-01 00:00:00', '2026-12-31 23:59:59', 200, '2026-04-01 09:00:00');

-- ============================================================
-- KAMPANYA KAZANIMLARI
-- ============================================================
INSERT IGNORE INTO `campaign_redemptions`
  (`campaign_id`, `user_id`, `venue_id`, `code`, `status`, `earned_at`, `used_at`)
VALUES
(201,103,201,'PILL-C103-XK9M','used',   '2026-05-30 21:05:00','2026-06-01 20:00:00'),
(202,105,204,'TEQU-J105-P2RN','earned', '2026-05-28 21:35:00', NULL),
(203,104,207,'BEAN-A104-L7QW','earned', '2026-06-09 09:05:00', NULL),
(204,104,211,'PARA-A104-S8VT','used',   '2026-04-25 13:05:00','2026-04-25 14:00:00'),
(204,108,211,'PARA-L108-R3YE','used',   '2026-05-26 13:05:00','2026-05-26 14:00:00'),
(205,106,209,'PIZZ-S106-M9KD','earned', '2026-06-07 19:05:00', NULL),
(206,103,205,'BAHA-C103-N5FJ','used',   '2026-05-03 18:50:00','2026-05-03 19:00:00'),
(206,110,205,'BAHA-E110-Q1ZX','earned', '2026-06-06 18:05:00', NULL);

-- ============================================================
-- GİZLİ MÜŞTERİLER
-- ============================================================
INSERT IGNORE INTO `mystery_shoppers`
  (`user_id`, `status`, `motivation`, `admin_note`, `reviewed_by`, `applied_at`, `reviewed_at`)
VALUES
(104,'approved',
 'Kafe ve mekan deneyimi konusunda 2 yıldır düzenli değerlendirmeler yapıyorum. Tarafsız ve detaylı raporlar hazırlayabilirim.',
 'Aktif kullanıcı, güvenilir geçmiş. Onaylandı.', 101, '2026-03-10 09:00:00','2026-03-15 14:00:00'),

(108,'approved',
 'Sanat galerisi ve kültürel mekanlar konusunda deneyimliyim.',
 'Kültür-sanat mekanları için ideal aday. Onaylandı.', 101, '2026-04-01 10:00:00','2026-04-05 11:00:00'),

(107,'pending',
 'Spor tesisleri ve fitness mekanları hakkında kapsamlı değerlendirmeler yapabilirim.',
 NULL, NULL, '2026-06-08 08:00:00', NULL),

(106,'rejected',
 'Restoran ve yemek alanında değerlendirme yapmak istiyorum.',
 'Çıkar çatışması — reddedildi.', 102, '2026-02-20 15:00:00','2026-02-25 10:00:00');

-- ============================================================
-- KULLANICI ROZETLERİ
-- ============================================================
INSERT IGNORE INTO `user_badges` (`user_id`, `badge_key`, `week_start`, `earned_at`) VALUES
(103,'explorer',        '2026-04-14','2026-04-14 20:30:00'),
(103,'social_butterfly','2026-04-14','2026-04-14 21:00:00'),
(103,'night_owl',       '2026-04-14','2026-04-14 23:00:00'),
(103,'casino_regular',  '2026-04-27','2026-04-27 21:00:00'),
(103,'foodie',          '2026-06-03','2026-06-03 19:30:00'),
(104,'coffee_addict',   '2026-04-14','2026-04-15 08:15:00'),
(104,'explorer',        '2026-04-21','2026-04-22 11:00:00'),
(104,'art_lover',       '2026-04-21','2026-04-20 11:00:00'),
(104,'coffee_addict',   '2026-05-12','2026-05-12 10:00:00'),
(105,'night_owl',       '2026-04-14','2026-04-15 22:00:00'),
(105,'party_animal',    '2026-04-14','2026-04-19 23:30:00'),
(105,'social_butterfly','2026-05-05','2026-05-08 22:00:00'),
(105,'night_owl',       '2026-05-12','2026-05-14 00:00:00'),
(106,'foodie',          '2026-04-14','2026-04-16 19:00:00'),
(106,'explorer',        '2026-05-05','2026-05-04 12:30:00'),
(107,'fitness_freak',   '2026-04-14','2026-04-17 06:30:00'),
(107,'early_bird',      '2026-04-21','2026-04-24 08:00:00'),
(107,'fitness_freak',   '2026-06-01','2026-06-01 07:00:00'),
(108,'art_lover',       '2026-04-14','2026-04-18 18:00:00'),
(108,'explorer',        '2026-04-28','2026-04-29 19:00:00'),
(108,'night_owl',       '2026-06-05','2026-06-05 22:00:00');

-- ============================================================
-- PROFİL GÖRÜNTÜLEMELERİ
-- ============================================================
INSERT IGNORE INTO `profile_views` (`profile_user_id`, `viewer_user_id`, `viewed_at`) VALUES
(103,104,'2026-04-14 21:00:00'),(103,105,'2026-04-15 10:00:00'),
(103,106,'2026-04-16 12:00:00'),(103,107,'2026-05-01 09:00:00'),
(103,108,'2026-05-15 14:00:00'),(103,109,'2026-04-27 20:00:00'),
(103,110,'2026-05-03 19:00:00'),(103,112,'2026-04-20 11:00:00'),
(103,114,'2026-06-08 23:00:00'),(103,115,'2026-06-01 12:00:00'),
(104,103,'2026-04-15 08:30:00'),(104,105,'2026-04-15 22:20:00'),
(104,106,'2026-05-11 09:00:00'),(104,108,'2026-04-18 18:00:00'),
(104,110,'2026-05-19 10:00:00'),(104,112,'2026-04-25 13:00:00'),
(105,103,'2026-04-14 22:00:00'),(105,104,'2026-04-15 22:00:00'),
(105,109,'2026-04-28 00:30:00'),(105,110,'2026-05-09 22:30:00'),
(106,103,'2026-04-16 19:00:00'),(106,104,'2026-05-11 09:00:00'),
(107,103,'2026-06-01 07:20:00'),(107,104,'2026-06-01 07:35:00'),
(108,104,'2026-04-18 18:00:00'),(108,106,'2026-05-15 11:00:00');

-- ============================================================
-- İÇERİK RAPORLARI
-- ============================================================
INSERT IGNORE INTO `content_reports`
  (`reporter_id`, `entity_type`, `entity_id`, `reason`, `description`,
   `status`, `admin_id`, `admin_note`, `created_at`, `resolved_at`)
VALUES
(105, 'checkin', 2058, 'fake_checkin',
 'Bu check-in sahte görünüyor, o saatlerde mekan kapalıydı.',
 'resolved', 102, 'İncelendi. Mekan açıktı, check-in geçerli.', '2026-04-21 10:00:00','2026-04-22 09:00:00'),
(112, 'user', 109, 'fraud',
 'Bu kullanıcı sahte casino kazancı bildiriyor olabilir.',
 'pending', NULL, NULL, '2026-06-09 14:00:00', NULL),
(115, 'venue', 220, 'inappropriate',
 'Bu mekanın kategorisi hatalı ve içerik uygunsuz.',
 'pending', NULL, NULL, '2026-06-10 09:00:00', NULL);

-- ============================================================
-- REKLAMLAR
-- ============================================================
INSERT IGNORE INTO `ads`
  (`id`, `title`, `image_url`, `link_url`, `position`, `is_active`, `sort_order`, `created_at`)
VALUES
(201,'Pillbox Grand Casino — VIP Geceniz Sizi Bekliyor',
 'https://placehold.co/728x90/1a1a2e/e94560?text=Pillbox+Grand+Casino',
 'https://face.gta.world/pages/pillbox-casino','carousel',1,1,'2026-02-01 00:00:00'),
(202,'Paradise Hotel & Spa — Lüksün Yeni Adresi',
 'https://placehold.co/728x90/0f3460/e94560?text=Paradise+Hotel+%26+Spa',
 'https://face.gta.world/pages/paradise-hotel','carousel',1,2,'2026-02-15 00:00:00'),
(203,'Bean Machine Coffee — Her Sabahı Özel Yap',
 'https://placehold.co/300x250/2c1810/c4a35a?text=Bean+Machine+Coffee',
 'https://face.gta.world/pages/bean-machine','sidebar_right',1,1,'2026-03-01 00:00:00'),
(204,'Tequi-la-la — Cuma Geceleri Unutulmaz',
 'https://placehold.co/300x250/1a0533/9b59b6?text=Tequi-la-la',
 'https://face.gta.world/pages/tequilala','sidebar_left',1,1,'2026-03-15 00:00:00'),
(205,'Sociaera Premium — Tüm Özellikleri Aç',
 'https://placehold.co/728x90/16213e/f5a623?text=Sociaera+Premium',
 '/premium','feed',1,10,'2026-04-01 00:00:00'),
(206,'Bahama Mamas West — Sunset Kokteyller',
 'https://placehold.co/728x90/0d4f3c/27ae60?text=Bahama+Mamas+West',
 'https://face.gta.world/pages/bahama-mamas','footer_banner',1,1,'2026-04-15 00:00:00');

-- ============================================================
-- ADMİN NOTLARI & LOGLAR
-- ============================================================
INSERT IGNORE INTO `admin_notes`
  (`admin_id`, `entity_type`, `entity_id`, `note`, `created_at`)
VALUES
(101,'user',  109,'Ryan_C — büyük bakiye hareketi izleniyor.','2026-04-28 10:00:00'),
(102,'venue', 220,'La Mesa Warehouse Club reddedildi.','2026-05-20 22:35:00'),
(101,'user',  103,'VIP kullanıcı. Platform büyükelçisi adayı.','2026-03-01 09:00:00');

INSERT IGNORE INTO `admin_logs`
  (`admin_id`, `action_type`, `target_type`, `target_id`, `details`, `old_value`, `new_value`, `ip`, `created_at`)
VALUES
(101,'approve_venue',  'venue',201,'Pillbox Grand Casino onaylandı','pending','approved','127.0.0.1','2026-01-05 10:30:00'),
(101,'approve_venue',  'venue',207,'Bean Machine onaylandı',        'pending','approved','127.0.0.1','2026-01-06 11:30:00'),
(101,'approve_venue',  'venue',211,'Paradise Hotel onaylandı',      'pending','approved','127.0.0.1','2026-01-12 13:30:00'),
(102,'reject_venue',   'venue',220,'La Mesa Warehouse reddedildi',  'pending','rejected','192.168.1.5','2026-05-20 22:30:00'),
(101,'approve_mystery','user', 104,'Aylin_D gizli müşteri onaylandı','pending','approved','127.0.0.1','2026-03-15 14:00:00'),
(101,'approve_mystery','user', 108,'Luna_V gizli müşteri onaylandı','pending','approved','127.0.0.1','2026-04-05 11:00:00'),
(102,'reject_mystery', 'user', 106,'Sofia_R reddedildi — çıkar çatışması','pending','rejected','192.168.1.5','2026-02-25 10:00:00'),
(101,'grant_premium',  'user', 103,'Carlos_M premium verildi (3 ay)',NULL,'premium','127.0.0.1','2026-01-10 12:10:00');

SET FOREIGN_KEY_CHECKS = 1;

-- Özet
SELECT 'users'            AS tablo, COUNT(*) AS kayit FROM users
UNION ALL SELECT 'venues',           COUNT(*) FROM venues
UNION ALL SELECT 'checkins',         COUNT(*) FROM checkins
UNION ALL SELECT 'post_likes',       COUNT(*) FROM post_likes
UNION ALL SELECT 'post_comments',    COUNT(*) FROM post_comments
UNION ALL SELECT 'user_follows',     COUNT(*) FROM user_follows
UNION ALL SELECT 'wallets',          COUNT(*) FROM wallets
UNION ALL SELECT 'venue_campaigns',  COUNT(*) FROM venue_campaigns
UNION ALL SELECT 'venue_ratings',    COUNT(*) FROM venue_ratings;
