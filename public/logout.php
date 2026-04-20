<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
Auth::logout();
session_start();
Auth::setFlash('success', 'Başarıyla çıkış yaptınız.');
header('Location: ' . BASE_URL . '/login');
exit;
