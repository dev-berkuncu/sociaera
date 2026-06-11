-- ============================================================
-- Sociaera — DEMO FOTOĞRAFLAR
-- demo_seed_v2.sql'den SONRA çalıştırın.
-- Mevcut check-in'lere placehold.co görselleri ekler.
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- CHECK-IN FOTOĞRAFLARI (harici URL — uploadUrl ile çalışır)
-- Gerçekçi mekan görselleri: picsum.photos rastgele ama sabit (seed ile)
-- ============================================================

-- Carlos'un check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/casino-poker/800/450'    WHERE `id` = 2001;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/tequilala-night/800/450' WHERE `id` = 2002;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/coffee-latte/800/450'    WHERE `id` = 2003;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/spa-pool/800/450'        WHERE `id` = 2004;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/bahama-sunset/800/450'   WHERE `id` = 2005;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/concert-stage/800/450'   WHERE `id` = 2006;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/burger-gece/800/450'     WHERE `id` = 2007;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/shopping-mall/800/450'   WHERE `id` = 2010;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/pizza-wood/800/450'      WHERE `id` = 2011;

-- Aylin'in check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/oat-latte/800/450'       WHERE `id` = 2013;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/art-gallery/800/450'     WHERE `id` = 2014;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/spa-luxury/800/450'      WHERE `id` = 2015;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-sunset/800/450'    WHERE `id` = 2017;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/pistachio-latte/800/450' WHERE `id` = 2018;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/city-view-night/800/450' WHERE `id` = 2019;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/pizza-quattro/800/450'   WHERE `id` = 2020;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/art-new-exhibit/800/450' WHERE `id` = 2021;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/dj-booth/800/450'        WHERE `id` = 2022;

-- Jake'in check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/live-band/800/450'       WHERE `id` = 2024;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/club-dance/800/450'      WHERE `id` = 2025;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-bar/800/450'       WHERE `id` = 2026;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/outdoor-concert/800/450' WHERE `id` = 2028;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/underground-dj/800/450'  WHERE `id` = 2030;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/basketball-arena/800/450'WHERE `id` = 2031;

-- Sofia'nın check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/wood-pizza/800/450'      WHERE `id` = 2034;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/hotel-restaurant/800/450'WHERE `id` = 2036;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/fish-chips/800/450'      WHERE `id` = 2037;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/pizza-margherita/800/450'WHERE `id` = 2039;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-hotel-pool/800/450'WHERE `id` = 2041;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/pizza-friday/800/450'    WHERE `id` = 2042;

-- Marcus'un check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-workout/800/450'   WHERE `id` = 2043;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/crossfit-gym/800/450'    WHERE `id` = 2044;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/sunset-dumbbell/800/450' WHERE `id` = 2045;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/boxing-arena/800/450'    WHERE `id` = 2048;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-milestone/800/450' WHERE `id` = 2049;

-- Luna'nın check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/gallery-opening/800/450' WHERE `id` = 2050;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/observatory-night/800/450'WHERE `id` = 2051;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/classical-concert/800/450'WHERE `id` = 2052;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/photo-exhibition/800/450' WHERE `id` = 2054;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/wellness-spa/800/450'     WHERE `id` = 2055;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/telescope-stars/800/450'  WHERE `id` = 2056;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/art-gallery-visit/800/450'WHERE `id` = 2057;

-- Ryan'ın check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/casino-chips/800/450'    WHERE `id` = 2058;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/poker-table/800/450'     WHERE `id` = 2059;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/casino-win/800/450'      WHERE `id` = 2061;

-- Emma'nın check-in'leri
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/sunset-cocktail/800/450' WHERE `id` = 2063;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/hotel-infinity-pool/800/450'WHERE `id` = 2064;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-bar-evening/800/450'WHERE `id` = 2065;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-breakfast/800/450' WHERE `id` = 2067;
UPDATE `checkins` SET `image` = 'https://picsum.photos/seed/beach-cocktail-fav/800/450'WHERE `id` = 2068;

-- ============================================================
-- MEKAN GÖRSELLERİ (venues.image — venue-detail sayfasında görünür)
-- ============================================================
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/casino-exterior/800/450'    WHERE `id` = 201;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/amphitheater/800/450'       WHERE `id` = 202;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/sports-arena/800/450'       WHERE `id` = 203;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/rock-bar/800/450'           WHERE `id` = 204;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/tropical-bar/800/450'       WHERE `id` = 205;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/dark-lounge/800/450'        WHERE `id` = 206;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/coffee-shop/800/450'        WHERE `id` = 207;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/fastfood/800/450'           WHERE `id` = 208;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/italian-pizza/800/450'      WHERE `id` = 209;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/seaside-restaurant/800/450' WHERE `id` = 210;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/luxury-hotel/800/450'       WHERE `id` = 211;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/beach-hotel/800/450'        WHERE `id` = 212;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/outdoor-gym/800/450'        WHERE `id` = 213;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/fitness-center/800/450'     WHERE `id` = 214;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/observatory/800/450'        WHERE `id` = 215;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/art-museum/800/450'         WHERE `id` = 216;
UPDATE `venues` SET `image` = 'https://picsum.photos/seed/luxury-mall/800/450'        WHERE `id` = 217;

-- ============================================================
-- Kontrol
-- ============================================================
SELECT id, LEFT(note,40) AS note, LEFT(image,60) AS image_url
FROM checkins
WHERE id BETWEEN 2001 AND 2068
  AND image IS NOT NULL
ORDER BY id;
