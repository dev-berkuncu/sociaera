<?php
/**
 * Migration runner — bank_account ve transactions.status kolonlarını ekler
 * Tek kullanımlık, admin erişimiyle çalıştır: /run-migration.php
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';

Auth::requireAdmin();

$db = Database::getConnection();
$results = [];

$migrations = [
    'bank_account kolonu' =>
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS bank_account VARCHAR(100) DEFAULT NULL AFTER bio",
    'transactions.status kolonu' =>
        "ALTER TABLE transactions ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER reference_id",
];

foreach ($migrations as $label => $sql) {
    try {
        $db->exec($sql);
        $results[] = "✅ $label — OK";
    } catch (PDOException $e) {
        $results[] = "❌ $label — " . $e->getMessage();
    }
}

// bank_account kontrolü
$check = $db->query("SELECT bank_account FROM users LIMIT 1");
$results[] = $check ? "✅ bank_account kolonu mevcut ve okunabilir" : "❌ bank_account okunamadı";

header('Content-Type: text/plain; charset=utf-8');
echo implode("\n", $results) . "\n";
