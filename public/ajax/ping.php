<?php
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Auth.php';
require_once __DIR__ . '/../../app/Models/User.php';

header('Content-Type: application/json');

if (!Auth::check()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userModel = new UserModel();
$currentUserId = Auth::id();

// Update current user's last_seen_at
$userModel->updateLastSeen($currentUserId);

// If there are specific user IDs requested to check their online status
$checkUserIds = $_POST['user_ids'] ?? [];
$onlineStatus = [];

if (!empty($checkUserIds) && is_array($checkUserIds)) {
    // Sanitize to integers
    $checkUserIds = array_map('intval', $checkUserIds);
    $checkUserIds = array_filter($checkUserIds);
    
    if (!empty($checkUserIds)) {
        $lastSeenMap = $userModel->getLastSeenTimes($checkUserIds);
        foreach ($lastSeenMap as $uId => $lastSeenAt) {
            $onlineStatus[$uId] = UserModel::isOnline($lastSeenAt);
        }
    }
}

echo json_encode([
    'success' => true,
    'statuses' => $onlineStatus
]);
