<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once "app/Config/env.php";
loadEnv(".env");
require_once "app/Config/database.php";

try {
    $db = Database::getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("DELETE FROM users WHERE id > 1");
    $db->exec("UPDATE wallets SET balance = 0");
    $db->exec("DELETE FROM ads WHERE title LIKE '%Tequi-la-la%' OR title LIKE '%Paradise%'");

    require_once "app/Models/Ad.php";
    $adModel = new AdModel();
    $adModel->create('Tequi-la-la — Cuma Geceleri Özel Kampanyası', '/assets/img/sponsors/colosseum.png', 'https://face-tr.gta.world/page/colosseum', 'carousel', 0, 'image');
    $adModel->create('Paradise Group — Lüks ve Eğlence Sponsoru', '/assets/img/sponsors/paradise-group.png', 'https://face-tr.gta.world/page/paradise', 'carousel', 1, 'image');

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Success! Tüm adminler temizlendi, reklamlar eklendi.";
    unlink(__FILE__);
} catch (Exception $e) {
    echo "Hata oluştu: " . $e->getMessage();
}