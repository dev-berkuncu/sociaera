<?php
// GEÇİCİ DEBUG — Kullandıktan sonra sil!
require_once __DIR__ . '/../app/Config/env.php';
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);
require_once __DIR__ . '/../app/Config/app.php';

echo '<pre>';
echo '.env aranan yol: ' . $envPath . "\n";
echo '.env exists: ' . (file_exists($envPath) ? 'YES' : 'NO') . "\n";
echo '__DIR__: ' . __DIR__ . "\n";
echo 'dirname(__DIR__): ' . dirname(__DIR__) . "\n\n";
echo 'OAUTH_CLIENT_ID: ' . OAUTH_CLIENT_ID . "\n";
echo 'OAUTH_CLIENT_SECRET: ' . substr(OAUTH_CLIENT_SECRET, 0, 8) . '...' . "\n";
echo '</pre>';
