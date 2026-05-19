<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
echo '<pre>';
echo 'FLEECA_AUTH_KEY: ' . substr(FLEECA_AUTH_KEY, 0, 12) . '...' . "\n";
echo 'FLEECA_MODE: ' . FLEECA_MODE . "\n";
echo '.env path: ' . dirname(__DIR__) . '/.env' . "\n";
echo '.env exists: ' . (file_exists(dirname(__DIR__) . '/.env') ? 'YES' : 'NO') . "\n";
echo '</pre>';
