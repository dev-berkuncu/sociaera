<?php
// GEÇİCİ DEBUG — Kullandıktan sonra sil!
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';

echo '<pre>';
echo 'OAUTH_CLIENT_ID: ' . OAUTH_CLIENT_ID . "\n";
echo 'OAUTH_CLIENT_SECRET: ' . substr(OAUTH_CLIENT_SECRET, 0, 8) . '...' . "\n";
echo 'OAUTH_REDIRECT_URI: ' . OAUTH_REDIRECT_URI . "\n";
echo '.env exists: ' . (file_exists(dirname(__DIR__) . '/.env') ? 'YES' : 'NO') . "\n";
echo '</pre>';
