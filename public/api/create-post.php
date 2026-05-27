<?php
/**
 * API: Check-in (Post) Oluştur
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/RateLimit.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Services/ImageUploader.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Checkin.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Settings.php';

require_once __DIR__ . '/../../app/Models/Badge.php';
require_once __DIR__ . '/../../app/Models/Campaign.php';

Response::requirePost();
Response::requireAuthApi();
Csrf::requireValid();

$venueId = (int) ($_POST['venue_id'] ?? 0);
$note    = trim($_POST['note'] ?? '');

if (!$venueId) Response::error('Mekan seçmelisiniz.');
if (empty($note) && empty($_FILES['image']['name'])) Response::error('Bir metin veya fotoğraf paylaşmalısınız.');
if (mb_strlen($note) > 500) Response::error('Not en fazla 500 karakter olabilir.');

// Mekan kontrolü
$venueModel = new VenueModel();
$venue = $venueModel->getById($venueId);
if (!$venue || $venue['status'] !== 'approved') Response::error('Geçersiz veya onaylanmamış mekan.');

// Resim yükleme
$imageName = null;
if (!empty($_FILES['image']['name'])) {
    $uploader = new ImageUploader();
    $uploadMaxSize = MAX_POST_SIZE; // 10MB default
    if (UserModel::isPremiumActive((new UserModel())->getById(Auth::id()))) {
        $uploadMaxSize = 20 * 1024 * 1024; // 20MB for premium
    }
    $result = $uploader->upload($_FILES['image'], 'posts', [
        'maxSize'      => $uploadMaxSize,
        'outputFormat' => 'webp',
        'quality'      => 80,
    ]);
    if (!$result['success']) Response::error($result['error']);
    $imageName = $result['filename'];
}

// Check-in oluştur
$checkinModel = new CheckinModel();
$result = $checkinModel->create(Auth::id(), $venueId, $note ?: null, $imageName);

if ($result['ok']) {
    Logger::info('Check-in created', ['user_id' => Auth::id(), 'venue_id' => $venueId, 'checkin_id' => $result['checkin_id']]);
    
    // Rozet kontrolü
    $newBadges = [];
    try {
        $badgeModel = new BadgeModel();
        $awarded = $badgeModel->checkAndAward(Auth::id());
        foreach ($awarded as $b) {
            $newBadges[] = ['name' => $b['name'], 'icon' => $b['icon'], 'color' => $b['color']];
        }
    } catch (\Throwable $e) {}

    // Premium kullanıcılar 2x check-in ödülü
    try {
        $checkinReward = 10; // Base reward per check-in
        $rewardUser = (new UserModel())->getById(Auth::id());
        if (UserModel::isPremiumActive($rewardUser)) {
            $checkinReward *= 2;
        }
        $walletModel = new WalletModel();
        $walletModel->deposit(Auth::id(), $checkinReward, 'Check-in ödülü' . (UserModel::isPremiumActive($rewardUser) ? ' (2x Premium)' : ''));
    } catch (\Throwable $e) {
        error_log('Check-in wallet reward error: ' . $e->getMessage());
    }

    
    $msg = 'Check-in başarılı! 📍';
    if (!empty($newBadges)) {
        $names = array_column($newBadges, 'name');
        $msg .= ' 🏆 Yeni rozet: ' . implode(', ', $names);
    }

    // Kampanya kontrolü
    $earnedCampaigns = [];
    try {
        $campaignModel   = new CampaignModel();
        $earnedCampaigns = $campaignModel->checkAndAwardCampaigns(Auth::id(), $venueId);
        $notifModel      = new NotificationModel();
        foreach ($earnedCampaigns as $ec) {
            $msg .= ' 🎁 Kampanya kazandın: ' . $ec['title'] . ' (Kod: ' . $ec['code'] . ')';

            // Kullanıcıya kalıcı bildirim
            $notifModel->create(
                Auth::id(),
                null,
                'campaign_earned',
                '🎁 "' . $ec['title'] . '" kampanyasını kazandın! Kodun: ' . $ec['code'] . ' — ' . $venue['name']
            );

            // Mekan sahibine bildirim
            if (!empty($venue['created_by']) && (int)$venue['created_by'] !== Auth::id()) {
                $currentUser = (new UserModel())->getById(Auth::id());
                $notifModel->create(
                    (int)$venue['created_by'],
                    Auth::id(),
                    'campaign_earned',
                    ($currentUser['username'] ?? 'Bir kullanıcı') . ' "' . $ec['title'] . '" kampanyasını kazandı!'
                );
            }
        }
    } catch (\Throwable $e) {}

    Response::success([
        'checkin_id'       => $result['checkin_id'],
        'new_badges'       => $newBadges,
        'earned_campaigns' => $earnedCampaigns,
    ], $msg);
} else {
    Response::error($result['error']);
}
