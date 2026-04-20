<?php
/**
 * API: Like, Unlike, Repost, Unrepost, Comment, Follow, Delete
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

// GET requests — yorumları çek
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $checkinId = (int) ($_GET['checkin_id'] ?? 0);

    if ($action === 'get_comments' && $checkinId) {
        if (!Auth::check()) Response::error('Giriş gerekli.', 401);
        $checkinModel = new CheckinModel();
        $comments = $checkinModel->getComments($checkinId);

        // timeAgo ekle
        $result = [];
        foreach ($comments as $c) {
            $c['time_ago'] = timeAgo($c['created_at']);
            $result[] = $c;
        }
        Response::success($result);
    }
    Response::error('Geçersiz istek.', 400);
}

Response::requirePost();
Response::requireAuthApi();
Csrf::requireValid();

$action    = $_POST['action'] ?? '';
$checkinId = (int) ($_POST['checkin_id'] ?? 0);
$userId    = (int) ($_POST['user_id'] ?? 0);

$checkinModel = new CheckinModel();
$userModel = new UserModel();

switch ($action) {
    case 'like':
        if (!$checkinId) Response::error('Gönderi ID gerekli.');
        $checkinModel->like(Auth::id(), $checkinId);
        Response::success(null, 'Beğenildi.');
        break;

    case 'unlike':
        if (!$checkinId) Response::error('Gönderi ID gerekli.');
        $checkinModel->unlike(Auth::id(), $checkinId);
        Response::success(null, 'Beğeni kaldırıldı.');
        break;

    case 'repost':
        if (!$checkinId) Response::error('Gönderi ID gerekli.');
        $quote = trim($_POST['quote'] ?? '');
        $checkinModel->repost(Auth::id(), $checkinId, $quote ?: null);
        Response::success(null, 'Paylaşıldı.');
        break;

    case 'unrepost':
        if (!$checkinId) Response::error('Gönderi ID gerekli.');
        $checkinModel->unrepost(Auth::id(), $checkinId);
        Response::success(null, 'Paylaşım kaldırıldı.');
        break;

    case 'comment':
        if (!$checkinId) Response::error('Gönderi ID gerekli.');
        $comment = trim($_POST['comment'] ?? '');
        if (empty($comment)) Response::error('Yorum boş olamaz.');
        if (mb_strlen($comment) > 500) Response::error('Yorum en fazla 500 karakter olabilir.');

        $commentImage = null;
        if (!empty($_FILES['image']['name'])) {
            $uploader = new ImageUploader();
            $res = $uploader->upload($_FILES['image'], 'posts', ['maxSize' => MAX_POST_SIZE, 'outputFormat' => 'webp']);
            if ($res['success']) $commentImage = $res['filename'];
        }

        $commentId = $checkinModel->addComment(Auth::id(), $checkinId, $comment, $commentImage);
        Response::success(['comment_id' => $commentId], 'Yorum eklendi.');
        break;

    case 'follow':
        if (!$userId) Response::error('Kullanıcı ID gerekli.');
        if ($userId === Auth::id()) Response::error('Kendinizi takip edemezsiniz.');

        if ($userModel->isFollowing(Auth::id(), $userId)) {
            $userModel->unfollow(Auth::id(), $userId);
            Response::success(['following' => false], 'Takipten çıkıldı.');
        } else {
            $userModel->follow(Auth::id(), $userId);
            // Follow bildirimi
            $from = $userModel->getById(Auth::id());
            $notifModel = new NotificationModel();
            $notifModel->create($userId, Auth::id(), 'follow', ($from['username'] ?? 'Birisi') . ' seni takip etmeye başladı.');
            Response::success(['following' => true], 'Takip edildi.');
        }
        break;

    case 'delete':
        if (!$checkinId) Response::error('Gönderi ID gerekli.');
        $checkinModel->softDelete($checkinId, Auth::id());
        Response::success(null, 'Gönderi silindi.');
        break;

    default:
        Response::error('Geçersiz işlem.', 400);
}
