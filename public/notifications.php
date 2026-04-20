<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$notifModel = new NotificationModel();

// Tümünü okundu olarak işaretle
$notifModel->markAllRead(Auth::id());
$notifications = $notifModel->getForUser(Auth::id(), 50);

$pageTitle = 'Bildirimler';
$activeNav = 'notifications';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed">
        <div class="page-header" style="display:flex; align-items:center; justify-content:space-between;">
            <h1><i class="bi bi-bell" style="color:var(--primary)"></i> Bildirimler</h1>
            <?php if (!empty($notifications)): ?>
                <button class="btn-secondary-soft btn-sm" onclick="App.clearNotifications(this)">
                    <i class="bi bi-trash3"></i> Temizle
                </button>
            <?php endif; ?>
        </div>

        <div class="card-box notif-list" style="overflow:hidden;">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <p>Yeni bildirim yok.</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n):
                    $iconClass = $n['type'] ?? 'info';
                    $iconMap = [
                        'like'    => 'bi-heart-fill',
                        'comment' => 'bi-chat-fill',
                        'follow'  => 'bi-person-plus-fill',
                        'repost'  => 'bi-arrow-repeat',
                        'mention' => 'bi-at',
                    ];
                    $link = $n['checkin_id'] ? BASE_URL . '/post?id=' . $n['checkin_id'] : ($n['from_user_id'] ? BASE_URL . '/profile?u=' . escape($n['from_username'] ?? '') : '#');
                ?>
                <a href="<?php echo $link; ?>" class="notif-item <?php echo !$n['is_read'] ? 'unread' : ''; ?>" style="text-decoration:none; color:inherit;">
                    <div class="notif-icon <?php echo $iconClass; ?>">
                        <i class="bi <?php echo $iconMap[$n['type']] ?? 'bi-info-circle'; ?>"></i>
                    </div>
                    <div class="notif-text">
                        <?php if (!empty($n['from_avatar'])): ?>
                            <span style="margin-right:6px;"><?php echo avatarHtml($n['from_avatar'], $n['from_username'] ?? '', '20'); ?></span>
                        <?php endif; ?>
                        <?php echo escape($n['content']); ?>
                    </div>
                    <span class="notif-time"><?php echo timeAgo($n['created_at']); ?></span>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
