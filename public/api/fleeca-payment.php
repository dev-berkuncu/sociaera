<?php
/**
 * API: Fleeca Banking — Gateway Token Üret ve Yönlendir
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

// POST kontrolü
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Geçersiz istek metodu.', 405);
}

// Auth kontrolü
if (!Auth::check()) {
    Response::error('Oturum açmanız gerekiyor.', 401);
}

// CSRF kontrolü
Csrf::requireValid();

$amount = (float) ($_POST['amount'] ?? 0);

// Validasyonlar
if ($amount < 1) {
    Response::error('Minimum yükleme tutarı $1\'dir.');
}
if ($amount > 10000) {
    Response::error('Tek seferde en fazla $10.000 yükleyebilirsiniz.');
}

$userId = Auth::id();

// ── Fleeca API'den encrypted token üret ──────────────────
$generateUrl = 'https://banking-tr.gta.world/gateway_token/generateToken?' . http_build_query([
    'price' => (int) $amount,
    'type'  => 0,
]);

Logger::info('Fleeca generateToken request', ['url' => $generateUrl, 'amount' => $amount]);

$ch = curl_init($generateUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . FLEECA_AUTH_KEY,
        'Accept: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

Logger::info('Fleeca generateToken response', [
    'http_code' => $httpCode,
    'response'  => substr($response, 0, 200),
    'curl_err'  => $curlError,
]);

if ($curlError) {
    Response::error('Fleeca Banking bağlantı hatası: ' . $curlError);
}

if ($httpCode < 200 || $httpCode >= 300) {
    Response::error('Fleeca Banking token üretilemedi. (HTTP ' . $httpCode . ')');
}

// Token'ı parse et
$token = $response;

// JSON yanıt olabilir
$decoded = json_decode($response, true);
if (is_array($decoded)) {
    $token = $decoded['token'] ?? $decoded['data'] ?? $decoded[0] ?? $response;
}

// Tırnak temizle
$token = trim($token, "\" \n\r\t");

if (empty($token)) {
    Response::error('Fleeca Banking token alınamadı.');
}

Logger::info('Fleeca token generated', [
    'userId'      => $userId,
    'amount'      => $amount,
    'token_start' => substr($token, 0, 20),
]);

// ── Session'a kaydet ─────────────────────────────────────
$_SESSION['fleeca_pending'] = [
    'user_id' => $userId,
    'amount'  => $amount,
    'token'   => $token,
    'time'    => time(),
];

// Gateway URL
$gatewayUrl = 'https://banking-tr.gta.world/gateway/' . rawurlencode($token);

Response::success([
    'gateway_url' => $gatewayUrl,
], 'Token üretildi.');
