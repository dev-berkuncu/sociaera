<?php
require "app/Config/env.php";
loadEnv(".env");
require "app/Config/database.php";
$db = Database::getConnection();

$db->exec("SET FOREIGN_KEY_CHECKS = 0");
// Sadece Admin (id=1) kalsın, diğerlerini sil. (Bu, premium vs ne varsa temizler)
$db->exec("DELETE FROM users WHERE id > 1");

// Kalan tek adminin cüzdanını sıfırla
$db->exec("UPDATE wallets SET balance = 0");

// Var olan mock reklamları DB'ye ekle
$db->exec("DELETE FROM ads WHERE title LIKE '%Tequi-la-la%' OR title LIKE '%Paradise%'");
$db->exec("INSERT INTO ads (user_id, title, image_url, link_url, position, status, created_at) VALUES 
(1, 'Tequi-la-la — Cuma Geceleri Özel Kampanyası', '/assets/img/sponsors/colosseum.png', 'https://face-tr.gta.world/page/colosseum', 'sidebar', 'active', NOW()), 
(1, 'Paradise Group — Lüks ve Eğlence Sponsoru', '/assets/img/sponsors/paradise-group.png', 'https://face-tr.gta.world/page/paradise', 'sidebar', 'active', NOW())");
$db->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "Success! Tüm diğer adminler silindi, reklamlar eklendi.";
unlink(__FILE__);