<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE venues");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage();
    echo "<br><br>";
    echo "<b>Dosya:</b> " . $e->getFile() . " (Satır: " . $e->getLine() . ")<br><br>";
    echo "<b>Stack Trace:</b><br><pre>" . $e->getTraceAsString() . "</pre>";
}
