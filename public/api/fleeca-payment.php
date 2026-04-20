<?php
/**
 * API: Fleeca Banking — Gateway Token Üret ve Yönlendir
 * 
 * 1. Amount alır
 * 2. Fleeca API'den encrypted token üretir
 * 3. Session'a userId + amount kaydeder
 * 4. Gateway URL döndürür
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Response::requirePost();
Response::requireAuthApi();
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

// ── Fleeca API'den token üret ────────────────────────────
$generateUrl = FLEECA_VERIFY_BASE . '/generateToken?' . http_build_query([
    'price' => $amount,
    'type'  => 0,
]);

$ch = curl_init($generateUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . FLEECA_AUTH_KEY,
        'Accept: application/json',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    Logger::error('Fleeca generateToken curl error', ['error' => $curlError]);
    Response::error('Fleeca Banking bağlantı hatası.');
}

if ($httpCode < 200 || $httpCode >= 300) {
    Logger::error('Fleeca generateToken failed', [
        'http_code' => $httpCode,
        'response'  => $response,
    ]);
    Response::error('Fleeca Banking token üretilemedi. (HTTP ' . $httpCode . ')');
}

// Token'ı parse et (JSON string veya düz string olabilir)
$token = json_decode($response, true);
if (is_array($token)) {
    // JSON yanıt — token alanını al
    $token = $token['token'] ?? $token['data'] ?? $response;
}
// JSON string ise tırnak temizle
$token = trim($token, '"');

if (empty($token)) {
    Logger::error('Fleeca generateToken empty', ['response' => $response]);
    Response::error('Fleeca Banking token alınamadı.');
}

Logger::info('Fleeca token generated', [
    'userId' => $userId,
    'amount' => $amount,
    'token'  => substr($token, 0, 20) . '...',
]);

// ── Session'a kaydet (callback'te userId'yi bulmak için) ──
$_SESSION['fleeca_pending'] = [
    'user_id' => $userId,
    'amount'  => $amount,
    'token'   => $token,
    'time'    => time(),
];

// Gateway URL'i döndür
$gatewayUrl = FLEECA_GATEWAY_BASE . '/' . rawurlencode($token);

Response::success([
    'gateway_url' => $gatewayUrl,
], 'Token üretildi.');
