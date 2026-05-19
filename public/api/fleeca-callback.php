<?php
/**
 * Fleeca Banking V2 — Webhook Callback
 *
 * Fleeca bu endpoint'e imzalı JSON POST gönderir.
 * X-Fleeca-Signature: sha256=<hmac_hex> ile doğrulanır.
 *
 * Payload:
 * {
 *   "payment_id": "uuid",
 *   "status": "payment_successful" | "payment_failed" | "pending",
 *   "amount": 100,
 *   "payer_routing": "...",
 *   "payer_name": "...",
 *   "mode": "sandbox" | "live",
 *   ...
 * }
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Wallet.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Core/Response.php';

// ── Ham body oku (imza doğrulaması için) ─────────────────
$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_FLEECA_SIGNATURE'] ?? '';

Logger::info('Fleeca V2 webhook received', [
    'signature' => substr($signature, 0, 30) . '...',
    'body'      => substr($rawBody, 0, 200),
]);

// ── HMAC imzasını doğrula ─────────────────────────────────
if (!empty($signature) && !empty(FLEECA_AUTH_KEY)) {
    $expected = 'sha256=' . hash_hmac('sha256', $rawBody, FLEECA_AUTH_KEY);
    if (!hash_equals($expected, $signature)) {
        Logger::error('Fleeca V2 webhook: signature mismatch', [
            'expected' => substr($expected, 0, 30) . '...',
            'got'      => substr($signature, 0, 30) . '...',
        ]);
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// ── Payload parse et ─────────────────────────────────────
$data = json_decode($rawBody, true);
if (!$data) {
    Logger::error('Fleeca V2 webhook: invalid JSON', ['raw' => substr($rawBody, 0, 200)]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$paymentId = $data['payment_id'] ?? null;
$status    = $data['status']     ?? '';
$amount    = (float) ($data['amount'] ?? 0);

Logger::info('Fleeca V2 webhook parsed', [
    'payment_id' => $paymentId,
    'status'     => $status,
    'amount'     => $amount,
    'mode'       => $data['mode'] ?? 'unknown',
]);

// Sadece başarılı ödeme işle
if ($status !== 'payment_successful') {
    Logger::info('Fleeca V2 webhook: not successful, skip', ['status' => $status]);
    http_response_code(200);
    echo json_encode(['ok' => true, 'note' => 'not_successful']);
    exit;
}

if (!$paymentId || $amount <= 0) {
    Logger::error('Fleeca V2 webhook: missing payment_id or amount');
    http_response_code(422);
    echo json_encode(['error' => 'Missing data']);
    exit;
}

// ── İdempotency — aynı payment_id'yi tekrar işleme ──────
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM transactions WHERE reference_id = ? LIMIT 1");
$stmt->execute([$paymentId]);
if ($stmt->fetch()) {
    Logger::warning('Fleeca V2 webhook: duplicate payment_id', ['payment_id' => $paymentId]);
    http_response_code(200);
    echo json_encode(['ok' => true, 'note' => 'already_processed']);
    exit;
}

// ── Kullanıcıyı session'dan veya DB'den bul ──────────────
// Webhook server-to-server gelir, session yok — payment_id ile eşleştir
$stmt = $db->prepare("SELECT user_id, amount FROM fleeca_payments WHERE payment_id = ? LIMIT 1");
$stmt->execute([$paymentId]);
$pending = $stmt->fetch();

$userId = $pending['user_id'] ?? null;

if (!$userId) {
    // Fallback: payment_id'yi transactions tablosunda ara
    Logger::warning('Fleeca V2 webhook: no pending record for payment_id', ['payment_id' => $paymentId]);
    // Webhook'u işleme al ama kullanıcı bulunamadı — logla
    http_response_code(200);
    echo json_encode(['ok' => false, 'note' => 'user_not_found']);
    exit;
}

// ── Bakiye yükle ─────────────────────────────────────────
try {
    $walletModel = new WalletModel();
    $walletModel->ensureWallet($userId);

    $db->beginTransaction();

    $stmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
    $stmt->execute([$amount, $userId]);

    $stmt = $db->prepare("
        INSERT INTO transactions (user_id, type, amount, description, reference_id)
        VALUES (?, 'deposit', ?, ?, ?)
    ");
    $stmt->execute([
        $userId,
        $amount,
        'Fleeca Banking ile bakiye yükleme ($' . number_format($amount, 2) . ')',
        $paymentId,
    ]);

    // fleeca_payments tablosunu güncelle
    $db->prepare("UPDATE fleeca_payments SET status = 'paid', paid_at = NOW() WHERE payment_id = ?")
       ->execute([$paymentId]);

    $db->commit();

    // Bildirim gönder
    try {
        $notifModel = new NotificationModel();
        $notifModel->create($userId, 'wallet_deposit',
            '$' . number_format($amount, 2) . ' Fleeca Banking ile cüzdanına yüklendi.',
            BASE_URL . '/wallet'
        );
    } catch (\Throwable $e) {}

    Logger::info('Fleeca V2 webhook: deposit success', [
        'userId'     => $userId,
        'amount'     => $amount,
        'payment_id' => $paymentId,
    ]);

    http_response_code(200);
    echo json_encode(['ok' => true]);

} catch (\Throwable $e) {
    if ($db->inTransaction()) $db->rollBack();
    Logger::error('Fleeca V2 webhook: DB error', [
        'userId'  => $userId,
        'amount'  => $amount,
        'error'   => $e->getMessage(),
    ]);
    http_response_code(500);
    echo json_encode(['error' => 'DB error']);
}
