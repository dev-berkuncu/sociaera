<?php
/**
 * API: Fleeca Banking Ödeme Talebi
 * Kullanıcının bakiye yükleme talebini kaydeder.
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/RateLimit.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Wallet.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Settings.php';

Response::requirePost();
Response::requireAuthApi();
Csrf::requireValid();

$amount = (float) ($_POST['amount'] ?? 0);

// Validasyonlar
if ($amount <= 0) {
    Response::error('Geçerli bir tutar girin.');
}
if ($amount > 10000) {
    Response::error('Tek seferde en fazla $10.000 yükleyebilirsiniz.');
}
if ($amount < 1) {
    Response::error('Minimum yükleme tutarı $1\'dir.');
}

$userId = Auth::id();
$walletModel = new WalletModel();

// İşlemi kaydet - deposit olarak ekle
try {
    $walletModel->ensureWallet($userId);
    $walletModel->deposit($userId, $amount, 'Fleeca Banking ile bakiye yükleme');

    // Bildirim oluştur
    $notifModel = new NotificationModel();
    $notifModel->create(
        $userId,
        null,
        'deposit',
        '$' . number_format($amount, 2) . ' bakiye yüklendi.'
    );

    Logger::info('Fleeca payment processed', [
        'user_id' => $userId,
        'amount' => $amount,
    ]);

    Response::success([
        'amount' => $amount,
        'new_balance' => $walletModel->getBalance($userId),
    ], 'Bakiye başarıyla yüklendi.');

} catch (\Throwable $e) {
    Logger::error('Fleeca payment error', [
        'user_id' => $userId,
        'amount' => $amount,
        'error' => $e->getMessage(),
    ]);
    Response::error('Ödeme işlemi sırasında bir hata oluştu.');
}
