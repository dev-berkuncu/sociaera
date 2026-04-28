<?php
/**
 * API: Venue Rating — Mekana puan ver / güncelle
 * POST: venue_id, rating (1-5), csrf_token
 * GET:  venue_id → ortalama puan, toplam oy, kullanıcının puanı
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';

// GET — Rating bilgilerini çek
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $venueId = (int) ($_GET['venue_id'] ?? 0);
    if (!$venueId) Response::error('Mekan ID gerekli.', 400);

    $venueModel = new VenueModel();
    $venue = $venueModel->getById($venueId);
    if (!$venue) Response::error('Mekan bulunamadı.', 404);

    $ratingData = $venueModel->getVenueRating($venueId);
    $userRating = 0;
    if (Auth::check()) {
        $userRating = $venueModel->getUserRating($venueId, Auth::id());
    }

    Response::success([
        'average_rating' => round((float) $ratingData['average_rating'], 1),
        'rating_count'   => (int) $ratingData['rating_count'],
        'user_rating'    => (int) $userRating,
    ]);
}

// POST — Puan ver / güncelle
Response::requirePost();
Response::requireAuthApi();
Csrf::requireValid();

$venueId = (int) ($_POST['venue_id'] ?? 0);
$rating  = (int) ($_POST['rating'] ?? 0);

if (!$venueId) Response::error('Mekan ID gerekli.');
if ($rating < 1 || $rating > 5) Response::error('Puan 1 ile 5 arasında olmalıdır.');

$venueModel = new VenueModel();
$venue = $venueModel->getById($venueId);
if (!$venue || $venue['status'] !== 'approved') Response::error('Mekan bulunamadı.', 404);

$venueModel->upsertRating($venueId, Auth::id(), $rating);

// Güncel verileri döndür
$ratingData = $venueModel->getVenueRating($venueId);

Response::success([
    'average_rating' => round((float) $ratingData['average_rating'], 1),
    'rating_count'   => (int) $ratingData['rating_count'],
    'user_rating'    => $rating,
], 'Puanınız kaydedildi.');
