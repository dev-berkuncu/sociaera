<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/User.php';

try {
    echo "Veritabanı bağlantısı test ediliyor...<br>";
    $db = Database::getConnection();
    echo "Bağlantı başarılı.<br><br>";

    echo "Feed sorgusu test ediliyor...<br>";
    $model = new CheckinModel();
    $posts = $model->getGlobalFeed(1, 1);
    
    echo "Sorgu başarılı! " . count($posts) . " post çekildi.<br>";
    echo "<pre>";
    print_r($posts);
    echo "</pre>";

} catch (Throwable $e) {
    echo "<h2 style='color:red;'>Bir Hata Oluştu!</h2>";
    echo "<b>Hata Mesajı:</b> " . $e->getMessage() . "<br><br>";
    echo "<b>Dosya:</b> " . $e->getFile() . " (Satır: " . $e->getLine() . ")<br><br>";
    echo "<b>Stack Trace:</b><br><pre>" . $e->getTraceAsString() . "</pre>";
}
