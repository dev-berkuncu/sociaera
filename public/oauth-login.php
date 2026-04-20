<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Services/OAuthGtaWorld.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

$authUrl = OAuthGtaWorld::getAuthorizationUrl();
header('Location: ' . $authUrl);
exit;
