-- ============================================================
-- Sociaera — DEMO DATA (GÜVENLİ — mevcut veriyi SİLMEZ)
-- Tüm satırlar INSERT IGNORE — çakışan kayıtlar atlanır.
-- Şifre: test123
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- KULLANICILAR
-- ============================================================
INSERT IGNORE INTO `users`
  (`id`, `username`, `tag`, `email`, `password_hash`, `bio`,
   `is_admin`, `admin_role`, `is_active`, `is_premium`,
   `premium_until`, `badge`, `gta_character_name`, `last_login_at`, `created_at`)
VALUES
(1,  'SociaAdmin',     'sociaadmin', 'admin@sociaera.online',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Platform yöneticisi. Her şeyi görür, her şeyi bilir. 👁',
     1, 'super_admin', 1, 1, '2027-12-31 23:59:59', 'verified',
     NULL, NOW(), '2026-01-01 00:00:00'),

(2,  'ModeMaxwell',    'modermax',   'mod@sociaera.online',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'İçerik moderatörü. Kaliteyi koruyoruz.',
     1, 'moderator', 1, 1, '2027-06-30 23:59:59', 'moderator',
     NULL, NOW(), '2026-01-05 09:00:00'),

(3,  'Carlos_Mendoza', 'carlos',     'carlos@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Los Santos''un en aktif kaşifi 🗺️ | 150+ mekan | Premium üye',
     0, NULL, 1, 1, '2027-03-01 00:00:00', 'gold',
     'Carlos Mendoza', NOW(), '2026-01-10 12:00:00'),

(4,  'Aylin_Demir',    'aylin',      'aylin@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Kafe bağımlısı ☕ | Fotoğrafçı 📸 | Her köşeyi keşfediyorum',
     0, NULL, 1, 1, '2027-01-15 00:00:00', 'explorer',
     'Aylin Demir', NOW(), '2026-01-15 14:30:00'),

(5,  'Jake_Morrison',  'jake',       'jake@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Gece kulübü hayranı 🕺 | DJ severler burada | Nightlife ambassador',
     0, NULL, 1, 0, NULL, NULL,
     'Jake Morrison', NOW(), '2026-02-01 09:00:00'),

(6,  'Sofia_Rossi',    'sofia',      'sofia@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'İtalyan gurme 🍕 | Restoran kritiği | Premium yemek rehberi',
     0, NULL, 1, 1, '2026-12-31 00:00:00', 'foodie',
     'Sofia Rossi', NOW(), '2026-02-10 16:00:00'),

(7,  'Marcus_Webb',    'marcus',     'marcus@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Spor kulüpleri & fitness 💪 | Sağlıklı yaşam bloggeri',
     0, NULL, 1, 0, NULL, NULL,
     'Marcus Webb', NOW(), '2026-02-20 08:00:00'),

(8,  'Luna_Vega',      'luna',       'luna@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Sanat galerisi & kültür 🎨 | Müze gezgini | Şehrin nabzını tutuyorum',
     0, NULL, 1, 1, '2026-11-30 00:00:00', 'silver',
     'Luna Vega', NOW(), '2026-03-01 11:00:00'),

(9,  'Ryan_Chase',     'ryan',       'ryan@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Casino & poker pro 🃏 | Yüksek riskli, yüksek kazanç',
     0, NULL, 1, 0, NULL, NULL,
     'Ryan Chase', NOW(), '2026-03-10 20:00:00'),

(10, 'Emma_Stone',     'emma',       'emma@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Beach lover 🏖️ | Kokteyller & gün batımları | Hayat kısa, keyif uzun',
     0, NULL, 1, 1, '2027-02-28 00:00:00', 'explorer',
     'Emma Stone', NOW(), '2026-03-15 15:00:00'),

(11, 'Diego_Reyes',    'diego',      'diego@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Lüks otel & spa 🧖‍♂️ | VIP deneyimler',
     0, NULL, 1, 0, NULL, NULL,
     'Diego Reyes', NOW(), '2026-04-01 10:00:00'),

(12, 'Zara_Khan',      'zara',       'zara@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Yeni keşifler peşinde 🔍 | İlk hafta Premium oldum, pişman değilim!',
     0, NULL, 1, 1, '2026-08-01 00:00:00', NULL,
     'Zara Khan', NOW(), '2026-04-10 13:00:00'),

(13, 'Tom_Fletcher',   'tom',        'tom@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Bira & pub kültürü 🍺 | 80+ bar ziyareti',
     0, NULL, 1, 0, NULL, NULL,
     'Tom Fletcher', NOW(), '2026-04-20 19:00:00'),

(14, 'Nina_Park',      'nina',       'nina@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Canlı müzik takipçisi 🎵 | Konser fotoğrafçısı',
     0, NULL, 1, 1, '2026-09-30 00:00:00', NULL,
     'Nina Park', NOW(), '2026-05-01 17:00:00'),

(15, 'Alex_Torres',    'alex',       'alex@demo.com',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
     'Yeni kullanıcı 🆕 | Şehri tanımaya başlıyorum',
     0, NULL, 1, 0, NULL, NULL,
     'Alex Torres', NOW(), '2026-06-01 10:00:00');

-- ============================================================
-- MEKANLAR
-- ============================================================
INSERT IGNORE INTO `venues`
  (`id`, `name`, `description`, `address`, `phone`, `hours`,
   `is_open`, `category`, `facebrowser_url`, `status`, `is_active`,
   `created_by`, `created_at`)
VALUES
(101, 'Pillbox Grand Casino',
     'Los Santos''un en prestijli kumarhanesi. Blackjack, Teksas Hold''em, Rulet ve 200+ slot makinesi.',
     'Pillbox Hill, Los Santos', '(555) 800-2500', 'Pzt-Paz 00:00-24:00',
     1, 'eglence', 'https://face.gta.world/pages/pillbox-casino', 'approved', 1, 1, '2026-01-05 10:00:00'),

(102, 'Vinewood Bowl Amphitheater',
     'Açık hava amfitiyatro. Canlı konserler, stand-up gösterileri ve sinema geceleri.',
     'Vinewood Hills, Los Santos', '(555) 900-1111', 'Etkinlik günleri',
     1, 'eglence', 'https://face.gta.world/pages/vinewood-bowl', 'approved', 1, 1, '2026-01-20 12:00:00'),

(103, 'Maze Bank Arena',
     'Los Santos''un dev spor & etkinlik arenası. Basketbol, boks maçları, dev konserler.',
     'LSIA District, Los Santos', '(555) 700-2736', 'Etkinliğe göre',
     0, 'eglence', 'https://face.gta.world/pages/maze-bank-arena', 'approved', 1, 1, '2026-02-01 09:00:00'),

(104, 'Tequi-la-la',
     'Vinewood''un ikonik rock bar''ı. Canlı müzik, dans pisti ve efsanevi kokteyller.',
     'West Vinewood, Los Santos', '(555) 433-2522', 'Sal-Paz 20:00-04:00',
     1, 'bar', 'https://face.gta.world/pages/tequilala', 'approved', 1, 1, '2026-01-08 15:00:00'),

(105, 'Bahama Mamas West',
     'Del Perro sahilinde tropikal temalı beach bar. Sunset kokteyller ve DJ performansları.',
     'Del Perro Beach, Los Santos', '(555) 226-2626', 'Pzt-Paz 16:00-02:00',
     1, 'bar', 'https://face.gta.world/pages/bahama-mamas', 'approved', 1, 1, '2026-01-10 09:00:00'),

(106, 'Vanilla Unicorn Lounge',
     'Strawberry''nin en popüler karanlık bar''ı. Cuma ve Cumartesi geceleri özel DJ setleri.',
     'Strawberry Ave, Los Santos', '(555) 100-8865', 'Çar-Paz 21:00-06:00',
     1, 'bar', 'https://face.gta.world/pages/vanilla-unicorn', 'approved', 1, 2, '2026-02-05 20:00:00'),

(107, 'Bean Machine Coffee',
     'Şehrin en sevilen specialty coffee zinciri. Single origin espresso ve taze pastane ürünleri.',
     'Mirror Park Blvd, Los Santos', '(555) 232-2327', 'Pzt-Paz 07:00-22:00',
     1, 'kafe', 'https://face.gta.world/pages/bean-machine', 'approved', 1, 1, '2026-01-06 11:00:00'),

(108, 'Burger Shot Rockford',
     'GTA''nın en iyi fast food zinciri. Dev burgerleri ve gece yarısı açık olmasıyla efsane.',
     'Rockford Hills, Los Santos', '(555) 267-8437', 'Pzt-Paz 00:00-24:00',
     1, 'restoran', 'https://face.gta.world/pages/burger-shot', 'approved', 1, 1, '2026-01-12 08:00:00'),

(109, 'Pizza This Downtown',
     'İtalyan usulü el yapımı pizza. Odun fırını, taze malzemeler.',
     'Downtown, Los Santos', '(555) 749-8324', 'Pzt-Cmt 11:00-23:00',
     1, 'restoran', 'https://face.gta.world/pages/pizza-this', 'approved', 1, 3, '2026-02-01 14:00:00'),

(110, 'Hookies Sandy Shores',
     'Blaine County''nin balık-cips spesyalisti. Sandy Shores''un sakin atmosferinde deniz mahsulleri.',
     'Sandy Shores, Blaine County', '(555) 465-3726', 'Sal-Paz 12:00-21:00',
     1, 'restoran', 'https://face.gta.world/pages/hookies', 'approved', 1, 2, '2026-02-15 10:00:00'),

(111, 'Paradise Hotel & Spa',
     'Rockford Hills''in 5 yıldızlı lüks oteli. Infinity havuz, tam donanımlı spa.',
     'Rockford Hills, Los Santos', '(555) 747-3337', 'Pzt-Paz 00:00-24:00',
     1, 'otel', 'https://face.gta.world/pages/paradise-hotel', 'approved', 1, 1, '2026-01-12 13:00:00'),

(112, 'Del Perro Beach Hotel',
     'Sahil kenarında butik otel. Sonsuzluk havuzu, beach bar ve günbatımı manzarası.',
     'Del Perro, Los Santos', '(555) 375-7243', 'Pzt-Paz 00:00-24:00',
     1, 'otel', 'https://face.gta.world/pages/del-perro-hotel', 'approved', 1, 4, '2026-03-01 11:00:00'),

(113, 'Muscle Sands Gym',
     'Vespucci sahilinde açık hava spor alanı. Ücretsiz dumbbells ve Pacificko manzarası.',
     'Vespucci Beach, Los Santos', NULL, 'Pzt-Paz 06:00-22:00',
     1, 'spor', 'https://face.gta.world/pages/muscle-sands', 'approved', 1, 1, '2026-01-25 07:00:00'),

(114, 'LS Fitness Center',
     'Premium spor merkezi. 300+ ekipman, grup dersleri, kişisel antrenörler ve sauna.',
     'Downtown, Los Santos', '(555) 547-8348', 'Pzt-Cmt 05:00-23:00',
     1, 'spor', 'https://face.gta.world/pages/ls-fitness', 'approved', 1, 1, '2026-02-10 06:00:00'),

(115, 'Galileo Observatory',
     'Vinewood Hills''in ikonik gözlemevi. Şehrin en iyi manzarası, gece yıldız etkinlikleri.',
     'Vinewood Hills, Los Santos', '(555) 924-9244', 'Pzt-Paz 10:00-22:00',
     1, 'kultur', 'https://face.gta.world/pages/galileo-obs', 'approved', 1, 2, '2026-02-20 10:00:00'),

(116, 'LS Art Gallery',
     'Çağdaş sanat galerisi. Yerel ve uluslararası sanatçıların eserleri. Her ay yeni sergi.',
     'Little Seoul, Los Santos', '(555) 534-5343', 'Sal-Paz 10:00-19:00',
     1, 'kultur', 'https://face.gta.world/pages/ls-art-gallery', 'approved', 1, 2, '2026-03-05 09:00:00'),

(117, 'Rockford Hills Mall',
     'Lüks alışveriş merkezi. Gucci, Prada, Hermes ve daha fazlası.',
     'Rockford Hills, Los Santos', '(555) 764-7368', 'Pzt-Paz 09:00-22:00',
     1, 'alisveris', 'https://face.gta.world/pages/rh-mall', 'approved', 1, 1, '2026-03-15 10:00:00'),

(118, 'Sandy Shores Motel',
     'Sade ama temiz konaklama. Blaine County''ye yakın, makul fiyat.',
     'Sandy Shores, Blaine County', '(555) 726-3726', 'Pzt-Paz 00:00-24:00',
     1, 'otel', NULL, 'pending', 1, 15, '2026-06-01 12:00:00'),

(119, 'Downtown Diner',
     'Ev yemekleri ve kahvaltı spesiyalisti. 1960''lar retro atmosferi.',
     'Downtown, Los Santos', '(555) 346-3463', 'Pzt-Paz 07:00-20:00',
     1, 'restoran', NULL, 'pending', 1, 12, '2026-06-05 09:00:00'),

(120, 'La Mesa Warehouse Club',
     'La Mesa''nın yeraltı elektronik müzik kulübü.',
     'La Mesa, Los Santos', NULL, 'Cum-Paz 23:00-08:00',
     0, 'bar', NULL, 'rejected', 0, 13, '2026-05-20 22:00:00');

-- ============================================================
-- CHECK-IN'LER
-- ============================================================
INSERT IGNORE INTO `checkins`
  (`id`, `user_id`, `venue_id`, `note`, `created_at`)
VALUES
-- Carlos (12 check-in)
(1001, 3, 101, 'Akşam poker masasındaydım 🎰 Tam 3 saat oturdum. Harika bir gece!', '2026-04-14 20:30:00'),
(1002, 3, 104, 'Tequi-la-la hiç değişmemiş 🎸 Canlı müzik efsaneydi bu gece!', '2026-04-18 22:00:00'),
(1003, 3, 107, 'Sabah kahvem Bean Machine''de ☕ Latte art''ı görülmeye değer', '2026-04-22 09:15:00'),
(1004, 3, 111, 'Paradise Spa''da bir hafta sonu 🧖‍♂️ İş stresi tamamen gitti', '2026-04-28 14:00:00'),
(1005, 3, 105, 'Gün batımında Bahama Mamas 🍹 Manzara inanılmaz @aylin gel bir ara!', '2026-05-03 18:45:00'),
(1006, 3, 102, 'Vinewood Bowl''da konser gecesi 🎤 Sahne kurulumu muhteşem', '2026-05-10 21:00:00'),
(1007, 3, 108, 'Gece yarısı Burger Shot 🍔 Suçluluk duyuyorum ama pişman değilim 😂', '2026-05-15 01:30:00'),
(1008, 3, 117, 'Rockford Hills Mall alışveriş seansı 🛍️ Cüzdanım ağladı', '2026-05-20 14:00:00'),
(1009, 3, 113, 'Muscle Sands sabah antrenmanı 💪 Deniz havası ayrı güzel', '2026-05-25 07:30:00'),
(1010, 3, 101, 'Blackjack masasında şansım yaver gitti 💰 +5000 🤑', '2026-05-30 21:00:00'),
(1011, 3, 109, 'Pizza This''in odun fırını pizzası... Tanrım 🍕 Sözcük bulamıyorum', '2026-06-03 19:30:00'),
(1012, 3, 104, 'Perşembe geceleri Tequi-la-la''da olmak şart 🎶 @jake @nina gelin', '2026-06-08 22:30:00'),

-- Aylin (11 check-in)
(1013, 4, 107, 'Sabah kahvesi burada içilir ☕ Oat milk latte = hayat', '2026-04-15 08:15:00'),
(1014, 4, 116, 'LS Art Gallery''deki yeni sergiye gittim 🎨 Türk sanatçının eserleri vardı!', '2026-04-20 11:00:00'),
(1015, 4, 111, 'Paradise Spa doğum günü hediyem 🎁 Harika bir deneyim', '2026-04-25 13:00:00'),
(1016, 4, 101, 'Blackjack masasında şansım yaver gitti 💰 @carlos öğretti', '2026-04-30 21:00:00'),
(1017, 4, 105, 'Bahama Mamas''da sunset 🌅 Bu manzarayı görmeden yaşama', '2026-05-05 18:00:00'),
(1018, 4, 107, 'Bean Machine yeni menüsü gelmiş ☕ Pistachio latte 10/10', '2026-05-12 10:00:00'),
(1019, 4, 115, 'Galileo Observatory''den şehir manzarası 😍 Gece ayrı güzel', '2026-05-18 20:00:00'),
(1020, 4, 109, 'Pizza This hafta sonu öğle yemeği 🍕 40 dk kuyruk beklettim ama değdi', '2026-05-24 13:30:00'),
(1021, 4, 116, 'Art Gallery tekrar geldim 🖼️ Sergi değişmiş, yeni eserler var', '2026-05-29 15:00:00'),
(1022, 4, 104, 'Tequi-la-la''da DJ gecesi 🎧 @jake dans pisti yaktı', '2026-06-04 23:00:00'),
(1023, 4, 107, 'Üçüncü Bean Machine bu hafta 😅 Bağımlıyım buna artık', '2026-06-09 09:00:00'),

-- Jake (10 check-in)
(1024, 5, 104, 'Muhteşem canlı müzik performansı! 🎸 Bu band gelecek vaat ediyor', '2026-04-15 22:00:00'),
(1025, 5, 106, 'Vanilla Unicorn''un friday night seti 🔥 Saat 04''e kadar dans ettim', '2026-04-19 23:30:00'),
(1026, 5, 105, 'Bahama Mamas beach session 🏖️ Summer vibes başladı', '2026-04-23 17:00:00'),
(1027, 5, 107, 'Bean Machine her zamanki gibi harika ☕ @aylin haklıymış', '2026-04-27 10:30:00'),
(1028, 5, 102, 'Vinewood Bowl open air concert 🎵 5000 kişilik kalabalık inanılmaz', '2026-05-02 20:00:00'),
(1029, 5, 104, 'Perşembe geceleri Tequi-la-la''da olmak şart! 🎶', '2026-05-08 22:00:00'),
(1030, 5, 106, 'Vanilla Unicorn underground set 🎧 En iyi DJ Los Santos''ta burada', '2026-05-14 00:00:00'),
(1031, 5, 103, 'Maze Bank Arena''da basketbol maçı 🏀 Ev sahibi takım kazandı!', '2026-05-22 19:00:00'),
(1032, 5, 104, 'Tequi-la-la''dan tekrar selamlar 🎸 Bu hafta 2. gelişim 😄', '2026-05-28 21:30:00'),
(1033, 5, 114, 'LS Fitness sabah seansı 💪 Jake Morrison kas yapmayı öğreniyor', '2026-06-02 07:00:00'),

-- Sofia (9 check-in)
(1034, 6, 109, 'Pizza This odun fırını pizzası 🍕 İtalya''dan daha iyi, yemin ederim', '2026-04-16 19:00:00'),
(1035, 6, 108, 'Burger Shot kirli guilty pleasure 🍔 Bazen insan kendini ödüllendirmeli', '2026-04-21 13:00:00'),
(1036, 6, 111, 'Paradise Hotel restoranı 🍽️ Fiyat uygun değil ama değer', '2026-04-26 20:00:00'),
(1037, 6, 110, 'Hookies balık-cips 🐟 Sandy Shores''un gizli incisi. Herkese tavsiye!', '2026-05-04 12:30:00'),
(1038, 6, 107, 'Bean Machine sabah rutini ☕ @aylin ile buluştuk, harika sohbet', '2026-05-11 09:00:00'),
(1039, 6, 109, 'Pizza This tekrar! 🍕 Bu sefer quattro stagioni. Mükemmel.', '2026-05-17 19:30:00'),
(1040, 6, 108, 'Burger Shot gece yarısı 🌙🍔 Utanarak söylüyorum: ikinci kez bu hafta', '2026-05-23 01:00:00'),
(1041, 6, 112, 'Del Perro Beach Hotel''in beach bar''ı 🍹 Havuz kenarı mükemmel', '2026-05-31 16:00:00'),
(1042, 6, 109, 'Pizza This Cuma akşamı kuyruk olmadan girdim, şanslı gün! 🍕🎉', '2026-06-07 19:00:00'),

-- Marcus (7 check-in)
(1043, 7, 113, 'Muscle Sands sabah antrenmanı 🌅 Deniz kokusu ayrı motivasyon', '2026-04-17 06:30:00'),
(1044, 7, 114, 'LS Fitness grup dersi 💪 CrossFit seansı beni mahvetti ama memnunum', '2026-04-24 08:00:00'),
(1045, 7, 113, 'Muscle Sands akşam antrenmanı 🏋️‍♂️ Gün batımında dumbbell press', '2026-05-01 18:00:00'),
(1046, 7, 107, 'Antrenman sonrası protein shake Bean Machine''de ☕ İyi kombinasyon', '2026-05-07 09:30:00'),
(1047, 7, 114, 'LS Fitness kişisel antrenör seansı 💪 Yeni program başladı', '2026-05-13 07:00:00'),
(1048, 7, 103, 'Maze Bank Arena''da boks maçı 🥊 Atmosfer muhteşemdi', '2026-05-21 20:00:00'),
(1049, 7, 113, 'Muscle Sands 50. check-in 🎉 Bu plaja ne kadar borçluyum', '2026-06-01 07:00:00'),

-- Luna (8 check-in)
(1050, 8, 116, 'LS Art Gallery açılış gecesi 🎨 Yerel sanatçıları destekleyin!', '2026-04-18 18:00:00'),
(1051, 8, 115, 'Galileo Observatory''den gece manzarası 🔭 Şehir ışıkları büyüleyici', '2026-04-22 21:00:00'),
(1052, 8, 102, 'Vinewood Bowl''da klasik müzik gecesi 🎻 Farklı ama muhteşemdi', '2026-04-29 19:00:00'),
(1053, 8, 107, 'Bean Machine''de çalışma seansı 💻 Saatler nasıl geçti anlamadım', '2026-05-06 14:00:00'),
(1054, 8, 116, 'Yeni sergi açıldı! 🖼️ Bu ay fotoğraf sergisi var. Kaçırmayın!', '2026-05-15 11:00:00'),
(1055, 8, 111, 'Paradise Spa wellness weekendi 🧘‍♀️ Ruh ve beden dinlendi', '2026-05-26 13:00:00'),
(1056, 8, 115, 'Gözlemevi yıldız gecesi etkinliği 🌟 Teleskopla Jüpiter gördüm!', '2026-06-05 22:00:00'),
(1057, 8, 116, 'Art Gallery üçüncü kez 🎨 Her gelişte farklı bir şey keşfediyorum', '2026-06-10 10:00:00'),

-- Ryan (5 check-in)
(1058, 9, 101, 'Casino ilk gece 🎰 Rulet masasında başladım. Kötü başlangıç...', '2026-04-20 22:00:00'),
(1059, 9, 101, 'Pillbox Casino tekrar 🃏 Poker turnuvası! 3. sırada bittim', '2026-04-27 20:00:00'),
(1060, 9, 106, 'Vanilla Unicorn''da kötü kazanan gibi kutlama 😎🎉', '2026-04-28 00:30:00'),
(1061, 9, 101, 'Blackjack tablosu ezber oldu artık 🃏 Sistem var burada', '2026-05-10 21:00:00'),
(1062, 9, 105, 'Bahama Mamas''da kazançları kutluyoruz 🍹💰 İyi bir hafta geçti', '2026-05-17 19:00:00'),

-- Emma (6 check-in)
(1063, 10, 105, 'Bahama Mamas gün batımı 🌅🍹 Hayatımın en güzel kokteylli akşamı', '2026-04-19 17:30:00'),
(1064, 10, 112, 'Del Perro Beach Hotel havuzu 🏊‍♀️ VIP hissettiriyor, fiyatı makul', '2026-04-25 15:00:00'),
(1065, 10, 105, 'Sahil bar akşamı 🌊 @carlos ile buluştuk, harika sohbet', '2026-05-03 18:00:00'),
(1066, 10, 104, 'Tequi-la-la Cuma gecesi 🎸 @jake dans pistiyle karşılaştı 😄', '2026-05-09 22:30:00'),
(1067, 10, 112, 'Beach hotel Pazar kahvaltısı 🍳 Deniz manzaralı masa rezervasyonu şart', '2026-05-19 10:00:00'),
(1068, 10, 105, 'Bahama Mamas üçüncü kez bu ay 🍹 Favorim oldu kesinlikle', '2026-06-06 18:00:00');

-- ============================================================
-- BEĞENİLER
-- ============================================================
INSERT IGNORE INTO `post_likes` (`user_id`, `checkin_id`) VALUES
(4, 1001), (5, 1001), (6, 1001), (8, 1001), (10, 1001),
(4, 1002), (5, 1002), (9, 1002),
(4, 1003), (6, 1003), (8, 1003),
(4, 1005), (5, 1005), (10, 1005),
(5, 1010), (9, 1010),
(4, 1012), (5, 1012), (8, 1012),
(3, 1013), (5, 1013), (6, 1013), (8, 1013),
(3, 1014), (8, 1014), (10, 1014),
(3, 1017), (5, 1017), (10, 1017),
(3, 1018), (6, 1018), (8, 1018),
(3, 1019), (8, 1019),
(3, 1022), (5, 1022), (9, 1022),
(3, 1024), (4, 1024), (9, 1024), (10, 1024),
(3, 1025), (9, 1025),
(4, 1026), (10, 1026),
(4, 1027), (6, 1027),
(3, 1028), (4, 1028), (8, 1028),
(3, 1029), (4, 1029), (10, 1029),
(3, 1034), (4, 1034), (7, 1034), (10, 1034),
(3, 1036), (4, 1036), (8, 1036),
(3, 1037), (4, 1037), (7, 1037),
(3, 1042), (4, 1042), (7, 1042),
(3, 1043), (5, 1043), (8, 1043),
(3, 1049), (4, 1049), (5, 1049), (6, 1049), (8, 1049), (10, 1049),
(3, 1050), (4, 1050), (6, 1050), (10, 1050),
(3, 1054), (4, 1054), (6, 1054), (8, 1054),
(3, 1056), (4, 1056), (8, 1056),
(3, 1063), (4, 1063), (5, 1063), (7, 1063),
(4, 1064), (6, 1064),
(3, 1065), (4, 1065),
(4, 1066), (5, 1066), (9, 1066),
(4, 1068), (5, 1068), (6, 1068), (7, 1068);

-- ============================================================
-- YORUMLAR
-- ============================================================
INSERT IGNORE INTO `post_comments`
  (`user_id`, `checkin_id`, `comment`, `created_at`)
VALUES
(4,  1001, 'Keşke ben de orada olsaydım 😄 Bir dahaki poker gecesine ben de geleyim!', '2026-04-14 20:50:00'),
(5,  1001, 'Poker masası için slot mu, masa oyunları mı? 🎰', '2026-04-14 21:10:00'),
(9,  1001, 'Carlos abi strateji anlatır mısın sonra 😅', '2026-04-14 21:30:00'),
(3,  1013, 'Oranın oat milk lattesi gerçekten farklı! En iyi barista orası', '2026-04-15 08:30:00'),
(5,  1013, 'Kahve bağımlılığı — ilk adım kabul etmektir 😄', '2026-04-15 08:45:00'),
(3,  1024, 'Jake bu hafta sonu Tequi-la-la''ya gidiyoruz mu? 🎸', '2026-04-15 22:20:00'),
(4,  1024, 'Band ismi ne? Araştırayım 🎵', '2026-04-15 22:35:00'),
(3,  1034, 'Sofia bu pizzacıyı sen söyledin gidiyorum kesinlikle! 🍕', '2026-04-16 19:20:00'),
(4,  1034, 'Odun fırını pizzası başka bir şey... Rezervasyon lazım mı?', '2026-04-16 19:40:00'),
(6,  1034, 'İtalyan olarak onaylıyorum 🇮🇹 Gerçekten kaliteli', '2026-04-16 20:00:00'),
(3,  1005, 'Bahama Mamas gün batımı... Efsane! Ben de geliyorum 🍹', '2026-05-03 19:00:00'),
(10, 1005, 'Gün batımı saatini sorduruyorum o manzara için 🌅', '2026-05-03 19:15:00'),
(3,  1037, 'Hookies! Hiç gitmedim, bu yazın listesine ekledim 🐟', '2026-05-04 12:50:00'),
(4,  1037, 'Sandy Shores''ta mı? Uzak ama değer gibi görünüyor', '2026-05-04 13:10:00'),
(3,  1049, 'Marcus 50. check-in kutlu olsun! 🎉🎉🎉 Milestone!', '2026-06-01 07:20:00'),
(4,  1049, 'Milestone geldi! Plaj seni seviyor 💪', '2026-06-01 07:35:00'),
(5,  1049, 'Hedef 100''e devam bro! 🏋️', '2026-06-01 07:50:00'),
(8,  1049, '50 kez aynı yere gitmek... Dedikasyon bu 🙌', '2026-06-01 08:00:00'),
(10, 1049, 'Plaj ruhunu anlıyorum seni 🌊', '2026-06-01 08:15:00'),
(3,  1056, 'Luna teleskopla ne gördün? Fotoğraf çektirdin mi? 🔭', '2026-06-05 22:20:00'),
(4,  1056, 'Gözlemevi etkinliğine nasıl katılınıyor?', '2026-06-05 22:35:00'),
(3,  1012, '@jake @nina bu Perşembe Tequi-la-la''ya gidiyor musunuz?', '2026-06-08 22:45:00'),
(5,  1012, 'Kesinlikle! 🎶 Saat 10''da orada olacağım', '2026-06-08 23:00:00');

-- ============================================================
-- REPOSTLAR
-- ============================================================
INSERT IGNORE INTO `post_reposts` (`user_id`, `checkin_id`, `quote`) VALUES
(4,  1001, 'Poker gecesine katılmak için @carlos ile iletişime geçin! 🃏'),
(5,  1001, 'Casino gecesi organizasyonu başlıyor mu? 🎰'),
(3,  1024, 'Canlı müzik için Tequi-la-la kesinlikle bir numaralı adres 🎸'),
(8,  1050, 'LS Art Gallery''yi destekleyin! Yerel sanatçılar için önemli 🎨'),
(10, 1005, 'Bahama Mamas gün batımı kokteyller için şehrin en iyi noktası 🌅'),
(3,  1049, 'Marcus 50 check-in milestone — ilham verici! 💪'),
(4,  1037, 'Hookies Sandy Shores — gizli kalmış bir lezzet durağı 🐟');

-- ============================================================
-- TAKİP İLİŞKİLERİ
-- ============================================================
INSERT IGNORE INTO `user_follows` (`follower_id`, `following_id`) VALUES
(3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 10),
(4, 3), (4, 5), (4, 6), (4, 8), (4, 10), (4, 14),
(5, 3), (5, 4), (5, 9), (5, 10), (5, 14),
(6, 3), (6, 4), (6, 7), (6, 8),
(7, 3), (7, 5), (7, 13), (7, 14),
(8, 3), (8, 4), (8, 6), (8, 15), (8, 16),
(9, 3), (9, 5),
(10, 3), (10, 4), (10, 5), (10, 6), (10, 8),
(12, 3), (12, 4), (12, 5), (12, 6), (12, 7), (12, 8), (12, 10),
(13, 5), (13, 9), (13, 10),
(14, 3), (14, 4), (14, 5), (14, 8),
(15, 3), (15, 4), (15, 6);

-- ============================================================
-- BİLDİRİMLER
-- ============================================================
INSERT IGNORE INTO `notifications`
  (`user_id`, `from_user_id`, `type`, `content`, `checkin_id`, `is_read`, `created_at`)
VALUES
(3, 4,  'like',    'Aylin_Demir gönderini beğendi.',           1001, 1, '2026-04-14 20:50:00'),
(3, 5,  'like',    'Jake_Morrison gönderini beğendi.',          1001, 1, '2026-04-14 21:10:00'),
(3, 4,  'comment', 'Aylin_Demir gönderine yorum yaptı.',       1001, 1, '2026-04-14 20:50:00'),
(3, 5,  'comment', 'Jake_Morrison gönderine yorum yaptı.',     1001, 1, '2026-04-14 21:10:00'),
(3, 9,  'comment', 'Ryan_Chase gönderine yorum yaptı.',        1001, 0, '2026-04-14 21:30:00'),
(4, 3,  'like',    'Carlos_Mendoza gönderini beğendi.',        1013, 1, '2026-04-15 08:30:00'),
(4, 3,  'follow',  'Carlos_Mendoza seni takip etmeye başladı.', NULL, 1, '2026-04-14 20:00:00'),
(4, 5,  'follow',  'Jake_Morrison seni takip etmeye başladı.',  NULL, 1, '2026-04-15 10:00:00'),
(5, 3,  'follow',  'Carlos_Mendoza seni takip etmeye başladı.', NULL, 1, '2026-04-14 20:00:00'),
(5, 3,  'comment', 'Carlos_Mendoza gönderine yorum yaptı.',    1024, 1, '2026-04-15 22:20:00'),
(5, 4,  'comment', 'Aylin_Demir gönderine yorum yaptı.',       1024, 1, '2026-04-15 22:35:00'),
(6, 3,  'comment', 'Carlos_Mendoza gönderine yorum yaptı.',    1034, 1, '2026-04-16 19:20:00'),
(6, 4,  'follow',  'Aylin_Demir seni takip etmeye başladı.',   NULL, 0, '2026-05-05 09:00:00'),
(7, 3,  'like',    'Carlos_Mendoza gönderini beğendi.',        1049, 0, '2026-06-01 07:20:00'),
(7, 4,  'like',    'Aylin_Demir gönderini beğendi.',           1049, 0, '2026-06-01 07:35:00'),
(7, 3,  'comment', 'Carlos_Mendoza gönderine yorum yaptı.',    1049, 0, '2026-06-01 07:20:00'),
(8, 3,  'follow',  'Carlos_Mendoza seni takip etmeye başladı.', NULL, 0, '2026-05-01 10:00:00'),
(3, 5,  'mention', 'Jake_Morrison senden bahsetti.',           1012, 0, '2026-06-08 23:00:00'),
(14,3,  'mention', 'Carlos_Mendoza senden bahsetti.',          1012, 0, '2026-06-08 22:45:00'),
(3, 10, 'repost',  'Emma_Stone gönderini repost''ladı.',       1005, 0, '2026-05-03 19:30:00');

-- ============================================================
-- CÜZDANLAR
-- ============================================================
INSERT IGNORE INTO `wallets` (`user_id`, `balance`) VALUES
(1,  250000.00), (2,  15000.00), (3,  47500.00),
(4,   12800.00), (5,   8200.00), (6,  31500.00),
(7,    5400.00), (8,  19750.00), (9,  85000.00),
(10,  11200.00), (11,  3800.00), (12,  6600.00),
(13,   4100.00), (14,  9300.00), (15,  1500.00);

-- ============================================================
-- İŞLEMLER
-- ============================================================
INSERT IGNORE INTO `transactions`
  (`user_id`, `type`, `amount`, `description`, `reference_id`, `created_at`)
VALUES
(3,  'deposit',      50000.00, 'İlk yatırım — Fleeca Bank',            'DMO-C001', '2026-01-10 12:00:00'),
(3,  'withdraw',      2500.00, 'Premium abonelik (3 ay)',               'DMO-C002', '2026-01-10 12:05:00'),
(3,  'deposit',       5000.00, 'Check-in milestone ödülü',             'DMO-C003', '2026-03-30 10:00:00'),
(3,  'transfer_out',  5000.00, 'Para transferi → Aylin_Demir',         'DMO-C004', '2026-04-15 15:00:00'),
(4,  'transfer_in',   5000.00, 'Para transferi ← Carlos_Mendoza',      'DMO-A001', '2026-04-15 15:01:00'),
(4,  'deposit',      10000.00, 'Fleeca Bank yatırım',                  'DMO-A002', '2026-01-15 14:00:00'),
(4,  'withdraw',      2200.00, 'Premium abonelik (1 ay)',               'DMO-A003', '2026-02-01 09:00:00'),
(5,  'deposit',      10000.00, 'İlk yatırım',                         'DMO-J001', '2026-02-01 09:00:00'),
(5,  'withdraw',      1800.00, 'VIP giriş — Tequi-la-la',             'DMO-J002', '2026-03-19 20:00:00'),
(6,  'deposit',      35000.00, 'İşletme tanıtım bütçesi',             'DMO-S001', '2026-02-10 16:00:00'),
(6,  'withdraw',      3500.00, 'Premium abonelik (6 ay)',              'DMO-S002', '2026-02-10 16:05:00'),
(7,  'deposit',       7000.00, 'İlk yatırım',                         'DMO-M001', '2026-02-20 08:00:00'),
(7,  'withdraw',      1600.00, 'LS Fitness aylık üyelik',             'DMO-M002', '2026-03-01 09:00:00'),
(8,  'deposit',      20000.00, 'Sanat projesi fon transferi',          'DMO-L001', '2026-03-01 11:00:00'),
(9,  'deposit',     100000.00, 'Casino kazancı — büyük el 🃏',         'DMO-R001', '2026-04-27 23:00:00'),
(9,  'withdraw',     15000.00, 'Casino kaybı',                        'DMO-R002', '2026-05-10 22:00:00'),
(10, 'deposit',      12000.00, 'İlk yatırım',                         'DMO-E001', '2026-03-15 15:00:00'),
(12, 'deposit',       7500.00, 'İlk yatırım',                         'DMO-Z001', '2026-04-10 13:00:00'),
(12, 'withdraw',       900.00, 'Premium abonelik (1 ay)',              'DMO-Z002', '2026-04-10 13:05:00');

-- ============================================================
-- MEKAN PUANLAMALARI
-- ============================================================
INSERT IGNORE INTO `venue_ratings` (`venue_id`, `user_id`, `rating`) VALUES
(101, 3, 5), (101, 4, 4), (101, 5, 4), (101, 9, 5), (101, 10, 3),
(102, 3, 5), (102, 5, 5), (102, 8, 5),
(103, 5, 4), (103, 7, 5),
(104, 3, 5), (104, 5, 5), (104, 10, 4), (104, 14, 5), (104, 4, 4),
(105, 3, 5), (105, 4, 5), (105, 10, 5), (105, 6, 4), (105, 9, 4),
(106, 5, 4), (106, 9, 5), (106, 13, 3),
(107, 4, 5), (107, 3, 4), (107, 5, 4), (107, 6, 5), (107, 7, 4), (107, 8, 5),
(108, 3, 3), (108, 6, 3), (108, 13, 4),
(109, 6, 5), (109, 3, 5), (109, 4, 5), (109, 7, 4),
(110, 6, 5), (110, 3, 4), (110, 4, 4),
(111, 3, 5), (111, 4, 5), (111, 6, 4), (111, 8, 5),
(112, 10, 5),(112, 4, 4), (112, 6, 5),
(113, 7, 5), (113, 3, 4), (113, 5, 3),
(114, 7, 4), (114, 5, 4),
(115, 8, 5), (115, 4, 5), (115, 3, 4),
(116, 8, 5), (116, 4, 5), (116, 6, 4),
(117, 3, 4), (117, 4, 3), (117, 6, 5);

-- ============================================================
-- MEKAN FAVORİLERİ
-- ============================================================
INSERT IGNORE INTO `venue_favorites` (`user_id`, `venue_id`) VALUES
(3, 101), (3, 104), (3, 105), (3, 111),
(4, 107), (4, 116), (4, 111), (4, 115),
(5, 104), (5, 106), (5, 102),
(6, 109), (6, 110), (6, 111), (6, 112),
(7, 113), (7, 114),
(8, 116), (8, 115), (8, 102),
(9, 101), (9, 106),
(10, 105),(10, 112),(10, 104),
(12, 107),(12, 104),(12, 111),
(14, 104),(14, 102);

-- ============================================================
-- KAMPANYALAR
-- ============================================================
INSERT IGNORE INTO `venue_campaigns`
  (`id`, `venue_id`, `title`, `description`,
   `trigger_type`, `trigger_value`,
   `reward_type`, `reward_value`, `reward_text`,
   `is_active`, `starts_at`, `ends_at`, `max_redemptions`, `created_at`)
VALUES
(101, 101, '10. Check-in Özel Chip Bonusu',
 'Pillbox Grand Casino''da 10. check-in''ini yap, 500 casino chip hediye kazan!',
 'nth_checkin', 10, 'discount_fixed', 500.00, '500 Casino Chip Hediye',
 1, '2026-01-01 00:00:00', '2026-12-31 23:59:59', NULL, '2026-01-15 10:00:00'),

(102, 104, 'Sadık Misafir — 5. Ziyaret',
 'Tequi-la-la''ya 5. gelişinde içeceğin bedava!',
 'nth_checkin', 5, 'free_item', NULL, 'Bir İçecek Bedava',
 1, '2026-02-01 00:00:00', '2026-08-31 23:59:59', 50, '2026-02-01 10:00:00'),

(103, 107, 'Bean Machine Kahve Abonesi',
 '20. check-in''inde 1 aylık ücretsiz kahve aboneliği!',
 'nth_checkin', 20, 'custom', NULL, '1 Aylık Ücretsiz Kahve Aboneliği',
 1, '2026-01-01 00:00:00', NULL, NULL, '2026-01-20 08:00:00'),

(104, 111, 'Lüks Spa Paketi',
 'Paradise Hotel & Spa''da ilk check-in''inde %25 spa paketi indirimi.',
 'first_checkin', 1, 'discount_percent', 25.00, '%25 Spa Paketi İndirimi',
 1, '2026-03-01 00:00:00', '2026-09-30 23:59:59', 100, '2026-03-01 10:00:00'),

(105, 109, 'Pizza Tutkunu',
 '5. ziyaretinde büyük boy pizza bedava!',
 'nth_checkin', 5, 'free_item', NULL, 'Büyük Boy Pizza Bedava',
 1, '2026-04-01 00:00:00', NULL, 30, '2026-04-01 10:00:00'),

(106, 105, 'Sunset Kokteyl Paketi',
 'Bahama Mamas''da 3. check-in''inde 2 kokteyl bedava!',
 'nth_checkin', 3, 'free_item', NULL, '2 Kokteyl Bedava (Sunset Saat)',
 1, '2026-05-01 00:00:00', '2026-10-31 23:59:59', NULL, '2026-05-01 10:00:00'),

(107, 102, 'VIP Konser Deneyimi',
 'Vinewood Bowl''da 3. biletini al, 4. biletin ücretsiz!',
 'total_checkins', 3, 'discount_percent', 100.00, 'Sonraki Bilet Ücretsiz',
 1, '2026-04-01 00:00:00', '2026-12-31 23:59:59', 200, '2026-04-01 09:00:00');

-- ============================================================
-- KAMPANYA KAZANIMLARI
-- ============================================================
INSERT IGNORE INTO `campaign_redemptions`
  (`campaign_id`, `user_id`, `venue_id`, `code`, `status`, `earned_at`, `used_at`)
VALUES
(101, 3, 101, 'PILL-C3-XK9M', 'used',   '2026-05-30 21:05:00', '2026-06-01 20:00:00'),
(102, 5, 104, 'TEQU-J5-P2RN', 'earned', '2026-05-28 21:35:00', NULL),
(103, 4, 107, 'BEAN-A4-L7QW', 'earned', '2026-06-09 09:05:00', NULL),
(104, 4, 111, 'PARA-A4-S8VT', 'used',   '2026-04-25 13:05:00', '2026-04-25 14:00:00'),
(104, 8, 111, 'PARA-L8-R3YE', 'used',   '2026-05-26 13:05:00', '2026-05-26 14:00:00'),
(105, 6, 109, 'PIZZ-S6-M9KD', 'earned', '2026-06-07 19:05:00', NULL),
(106, 3, 105, 'BAHA-C3-N5FJ', 'used',   '2026-05-03 18:50:00', '2026-05-03 19:00:00'),
(106, 10,105, 'BAHA-E10-Q1ZX','earned', '2026-06-06 18:05:00', NULL);

-- ============================================================
-- GİZLİ MÜŞTERİLER
-- ============================================================
INSERT IGNORE INTO `mystery_shoppers`
  (`user_id`, `status`, `motivation`, `admin_note`, `reviewed_by`, `applied_at`, `reviewed_at`)
VALUES
(4, 'approved',
 'Kafe ve mekan deneyimi konusunda 2 yıldır düzenli değerlendirmeler yapıyorum. Tarafsız ve detaylı raporlar hazırlayabilirim.',
 'Aktif kullanıcı, güvenilir geçmiş. Onaylandı.',
 1, '2026-03-10 09:00:00', '2026-03-15 14:00:00'),

(8, 'approved',
 'Sanat galerisi ve kültürel mekanlar konusunda deneyimliyim.',
 'Kültür-sanat mekanları için ideal aday. Onaylandı.',
 1, '2026-04-01 10:00:00', '2026-04-05 11:00:00'),

(7, 'pending',
 'Spor tesisleri ve fitness mekanları hakkında kapsamlı değerlendirmeler yapabilirim.',
 NULL, NULL, '2026-06-08 08:00:00', NULL),

(6, 'rejected',
 'Restoran ve yemek alanında değerlendirme yapmak istiyorum.',
 'Çıkar çatışması — reddedildi.',
 2, '2026-02-20 15:00:00', '2026-02-25 10:00:00');

-- ============================================================
-- KULLANICI ROZETLERİ
-- ============================================================
INSERT IGNORE INTO `user_badges` (`user_id`, `badge_key`, `week_start`, `earned_at`) VALUES
(3, 'explorer',       '2026-04-14', '2026-04-14 20:30:00'),
(3, 'social_butterfly','2026-04-14','2026-04-14 21:00:00'),
(3, 'night_owl',      '2026-04-14', '2026-04-14 23:00:00'),
(3, 'casino_regular', '2026-04-27', '2026-04-27 21:00:00'),
(3, 'foodie',         '2026-06-03', '2026-06-03 19:30:00'),
(4, 'coffee_addict',  '2026-04-14', '2026-04-15 08:15:00'),
(4, 'explorer',       '2026-04-21', '2026-04-22 11:00:00'),
(4, 'art_lover',      '2026-04-21', '2026-04-20 11:00:00'),
(4, 'coffee_addict',  '2026-05-12', '2026-05-12 10:00:00'),
(5, 'night_owl',      '2026-04-14', '2026-04-15 22:00:00'),
(5, 'party_animal',   '2026-04-14', '2026-04-19 23:30:00'),
(5, 'social_butterfly','2026-05-05','2026-05-08 22:00:00'),
(5, 'night_owl',      '2026-05-12', '2026-05-14 00:00:00'),
(6, 'foodie',         '2026-04-14', '2026-04-16 19:00:00'),
(6, 'explorer',       '2026-05-05', '2026-05-04 12:30:00'),
(7, 'fitness_freak',  '2026-04-14', '2026-04-17 06:30:00'),
(7, 'early_bird',     '2026-04-21', '2026-04-24 08:00:00'),
(7, 'fitness_freak',  '2026-06-01', '2026-06-01 07:00:00'),
(8, 'art_lover',      '2026-04-14', '2026-04-18 18:00:00'),
(8, 'explorer',       '2026-04-28', '2026-04-29 19:00:00'),
(8, 'night_owl',      '2026-06-05', '2026-06-05 22:00:00');

-- ============================================================
-- PROFİL GÖRÜNTÜLEMELERİ
-- ============================================================
INSERT IGNORE INTO `profile_views` (`profile_user_id`, `viewer_user_id`, `viewed_at`) VALUES
(3, 4,  '2026-04-14 21:00:00'), (3, 5,  '2026-04-15 10:00:00'),
(3, 6,  '2026-04-16 12:00:00'), (3, 7,  '2026-05-01 09:00:00'),
(3, 8,  '2026-05-15 14:00:00'), (3, 9,  '2026-04-27 20:00:00'),
(3, 10, '2026-05-03 19:00:00'), (3, 12, '2026-04-20 11:00:00'),
(3, 14, '2026-06-08 23:00:00'), (3, 15, '2026-06-01 12:00:00'),
(4, 3,  '2026-04-15 08:30:00'), (4, 5,  '2026-04-15 22:20:00'),
(4, 6,  '2026-05-11 09:00:00'), (4, 8,  '2026-04-18 18:00:00'),
(4, 10, '2026-05-19 10:00:00'), (4, 12, '2026-04-25 13:00:00'),
(5, 3,  '2026-04-14 22:00:00'), (5, 4,  '2026-04-15 22:00:00'),
(5, 9,  '2026-04-28 00:30:00'), (5, 10, '2026-05-09 22:30:00'),
(6, 3,  '2026-04-16 19:00:00'), (6, 4,  '2026-05-11 09:00:00'),
(7, 3,  '2026-06-01 07:20:00'), (7, 4,  '2026-06-01 07:35:00'),
(8, 4,  '2026-04-18 18:00:00'), (8, 6,  '2026-05-15 11:00:00');

-- ============================================================
-- İÇERİK RAPORLARI
-- ============================================================
INSERT IGNORE INTO `content_reports`
  (`reporter_id`, `entity_type`, `entity_id`, `reason`, `description`,
   `status`, `admin_id`, `admin_note`, `created_at`, `resolved_at`)
VALUES
(5,  'checkin', 1058, 'fake_checkin',
 'Bu check-in sahte görünüyor, o saatlerde o mekan kapalıydı.',
 'resolved', 2, 'İncelendi. Mekan açıktı, check-in geçerli.', '2026-04-21 10:00:00', '2026-04-22 09:00:00'),
(12, 'user', 9, 'fraud',
 'Bu kullanıcı sahte casino kazancı bildiriyor olabilir.',
 'pending', NULL, NULL, '2026-06-09 14:00:00', NULL),
(15, 'venue', 120, 'inappropriate',
 'Bu mekanın kategorisi hatalı ve içerik uygunsuz.',
 'pending', NULL, NULL, '2026-06-10 09:00:00', NULL);

-- ============================================================
-- REKLAMLAR
-- ============================================================
INSERT IGNORE INTO `ads`
  (`id`, `title`, `image_url`, `link_url`, `position`, `is_active`, `sort_order`, `created_at`)
VALUES
(101, 'Pillbox Grand Casino — VIP Geceniz Sizi Bekliyor',
 'https://placehold.co/728x90/1a1a2e/e94560?text=Pillbox+Grand+Casino',
 'https://face.gta.world/pages/pillbox-casino', 'carousel', 1, 1, '2026-02-01 00:00:00'),
(102, 'Paradise Hotel & Spa — Lüksün Yeni Adresi',
 'https://placehold.co/728x90/0f3460/e94560?text=Paradise+Hotel+%26+Spa',
 'https://face.gta.world/pages/paradise-hotel', 'carousel', 1, 2, '2026-02-15 00:00:00'),
(103, 'Bean Machine Coffee — Her Sabahı Özel Yap',
 'https://placehold.co/300x250/2c1810/c4a35a?text=Bean+Machine+Coffee',
 'https://face.gta.world/pages/bean-machine', 'sidebar_right', 1, 1, '2026-03-01 00:00:00'),
(104, 'Tequi-la-la — Cuma Geceleri Unutulmaz',
 'https://placehold.co/300x250/1a0533/9b59b6?text=Tequi-la-la',
 'https://face.gta.world/pages/tequilala', 'sidebar_left', 1, 1, '2026-03-15 00:00:00'),
(105, 'Sociaera Premium — Tüm Özellikleri Aç',
 'https://placehold.co/728x90/16213e/f5a623?text=Sociaera+Premium',
 '/premium', 'feed', 1, 10, '2026-04-01 00:00:00'),
(106, 'Bahama Mamas West — Sunset Kokteyller',
 'https://placehold.co/728x90/0d4f3c/27ae60?text=Bahama+Mamas+West',
 'https://face.gta.world/pages/bahama-mamas', 'footer_banner', 1, 1, '2026-04-15 00:00:00');

-- ============================================================
-- ADMİN NOTLARI & LOGLAR
-- ============================================================
INSERT IGNORE INTO `admin_notes`
  (`admin_id`, `entity_type`, `entity_id`, `note`, `created_at`)
VALUES
(1, 'user',  9,   'Ryan Chase — büyük bakiye hareketi izleniyor.', '2026-04-28 10:00:00'),
(2, 'venue', 120, 'La Mesa Warehouse Club reddedildi.', '2026-05-20 22:35:00'),
(1, 'user',  3,   'VIP kullanıcı. Platform büyükelçisi adayı.', '2026-03-01 09:00:00');

INSERT IGNORE INTO `admin_logs`
  (`admin_id`, `action_type`, `target_type`, `target_id`, `details`, `old_value`, `new_value`, `ip`, `created_at`)
VALUES
(1, 'approve_venue',   'venue', 101, 'Pillbox Grand Casino onaylandı', 'pending', 'approved', '127.0.0.1', '2026-01-05 10:30:00'),
(1, 'approve_venue',   'venue', 107, 'Bean Machine onaylandı',         'pending', 'approved', '127.0.0.1', '2026-01-06 11:30:00'),
(1, 'approve_venue',   'venue', 111, 'Paradise Hotel onaylandı',       'pending', 'approved', '127.0.0.1', '2026-01-12 13:30:00'),
(2, 'reject_venue',    'venue', 120, 'La Mesa Warehouse reddedildi',   'pending', 'rejected', '192.168.1.5', '2026-05-20 22:30:00'),
(1, 'approve_mystery', 'user',  4,   'Aylin_Demir gizli müşteri onaylandı', 'pending', 'approved', '127.0.0.1', '2026-03-15 14:00:00'),
(1, 'approve_mystery', 'user',  8,   'Luna_Vega gizli müşteri onaylandı',   'pending', 'approved', '127.0.0.1', '2026-04-05 11:00:00'),
(2, 'reject_mystery',  'user',  6,   'Sofia_Rossi reddedildi — çıkar çatışması', 'pending', 'rejected', '192.168.1.5', '2026-02-25 10:00:00'),
(1, 'grant_premium',   'user',  3,   'Carlos_Mendoza premium verildi (3 ay)', NULL, 'premium', '127.0.0.1', '2026-01-10 12:10:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- Özet
-- ============================================================
SELECT 'users'                AS tablo, COUNT(*) AS kayit FROM users
UNION ALL SELECT 'venues',              COUNT(*) FROM venues
UNION ALL SELECT 'checkins',            COUNT(*) FROM checkins
UNION ALL SELECT 'post_likes',          COUNT(*) FROM post_likes
UNION ALL SELECT 'post_comments',       COUNT(*) FROM post_comments
UNION ALL SELECT 'user_follows',        COUNT(*) FROM user_follows
UNION ALL SELECT 'venue_campaigns',     COUNT(*) FROM venue_campaigns
UNION ALL SELECT 'venue_ratings',       COUNT(*) FROM venue_ratings
UNION ALL SELECT 'mystery_shoppers',    COUNT(*) FROM mystery_shoppers
UNION ALL SELECT 'wallets',             COUNT(*) FROM wallets;
