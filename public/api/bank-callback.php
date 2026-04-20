<?php
/**
 * API: Fleeca Bank Callback (Stub)
 * Gerçek banka entegrasyonu yapıldığında bu endpoint kullanılacak.
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';

// Auth key doğrula
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$providedKey = str_replace('Bearer ', '', $authHeader);

if (empty($providedKey) || $providedKey !== FLEECA_AUTH_KEY) {
    Logger::warning('Bank callback: invalid auth key', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    Response::error('Unauthorized', 401);
}

// Veri al
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);

if (!$data) {
    Response::error('Invalid JSON payload', 400);
}

Logger::info('Bank callback received', ['data' => $data]);

// TODO: İşlem mantığı burada uygulanacak
// Örnek: deposit, withdraw vb.

Response::success(null, 'Callback received.');
