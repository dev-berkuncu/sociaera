<?php
/**
 * API: Notifications (clear, mark read)
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Response::requirePost();
Response::requireAuthApi();
Csrf::requireValid();

$action = $_POST['action'] ?? '';
$notifModel = new NotificationModel();

switch ($action) {
    case 'clear':
        $notifModel->clearAll(Auth::id());
        Response::success(null, 'Bildirimler temizlendi.');
        break;
    case 'mark_read':
        $notifModel->markAllRead(Auth::id());
        Response::success(null, 'Okundu olarak işaretlendi.');
        break;
    case 'count':
        Response::success(['count' => $notifModel->getUnreadCount(Auth::id())]);
        break;
    default:
        Response::error('Geçersiz işlem.');
}
