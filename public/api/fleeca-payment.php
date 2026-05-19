<?php
/**
 * API: Fleeca Banking V2 — Ödeme Oluştur
 * POST /api/v2/payment → payment_id + payment_link
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Geçersiz istek metodu.', 405);
}
if (!Auth::check()) {
    Response::error('Oturum açmanız gerekiyor.', 401);
}
Csrf::requireValid();

$amount = (int) ($_POST['amount'] ?? 0);

if ($amount < 1)     Response::error('Minimum yükleme tutarı $1\'dir.');
if ($amount > 99999) Response::error('Tek seferde en fazla $99.999 yükleyebilirsiniz.');

$userId = Auth::id();

// ── Fleeca V2 Ödeme Oluştur ───────────────────────────────
$apiUrl  = 'https://banking-tr.gta.world/api/v2/payment';
$mode    = (int) env('FLEECA_MODE', 0); // 0=sandbox, 1=live
$body    = json_encode([
    'amount'      => $amount,
    'mode'        => $mode,
    'description' => 'Sociaera Cüzdan Yükleme — $' . number_format($amount, 2),
]);

Logger::info('Fleeca V2 payment create', ['amount' => $amount, 'mode' => $mode]);

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . FLEECA_AUTH_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ],
]);
$response  = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

Logger::info('Fleeca V2 response', ['http_code' => $httpCode, 'body' => substr($response, 0, 300)]);

if ($curlError) {
    Response::error('Fleeca Banking bağlantı hatası: ' . $curlError);
}

if ($httpCode !== 201) {
    $errBody = json_decode($response, true);
    Response::error('Fleeca ödeme oluşturulamadı. (HTTP ' . $httpCode . ') — ' . ($errBody['message'] ?? substr($response, 0, 100)));
}

$data = json_decode($response, true);
$paymentId   = $data['payment_id']   ?? null;
$paymentLink = $data['payment_link'] ?? null;

if (!$paymentId || !$paymentLink) {
    Logger::error('Fleeca V2: missing payment_id or payment_link', ['response' => $data]);
    Response::error('Fleeca\'dan geçersiz yanıt alındı.');
}

// ── Payment'ı DB'ye kaydet (webhook user eşleştirmesi için) ──
try {
    $db = Database::getConnection();
    $db->prepare("
        INSERT INTO fleeca_payments (payment_id, user_id, amount, status, mode)
        VALUES (?, ?, ?, 'pending', ?)
        ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)
    ")->execute([
        $paymentId,
        $userId,
        $amount,
        env('FLEECA_MODE', 0) == 1 ? 'live' : 'sandbox',
    ]);
} catch (\Throwable $e) {
    Logger::warning('Fleeca V2: DB insert failed', ['error' => $e->getMessage()]);
}

// Session'a kaydet
$_SESSION['fleeca_pending'] = [
    'user_id'    => $userId,
    'amount'     => $amount,
    'payment_id' => $paymentId,
    'time'       => time(),
];

Logger::info('Fleeca V2 payment created', ['userId' => $userId, 'payment_id' => $paymentId]);

Response::success(['gateway_url' => $paymentLink], 'Ödeme oluşturuldu.');
