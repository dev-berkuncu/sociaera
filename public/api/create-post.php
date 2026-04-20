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
    $result = $uploader->upload($_FILES['image'], 'posts', [
        'maxSize'      => MAX_POST_SIZE,
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
    Response::success(['checkin_id' => $result['checkin_id']], 'Check-in başarılı! 📍');
} else {
    Response::error($result['error']);
}
