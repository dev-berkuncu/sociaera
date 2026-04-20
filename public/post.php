<?php
/**
 * Sociaera — Post Detay / Yorum Sayfası
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$postId = (int)($_GET['id'] ?? 0);
if (!$postId) Response::notFound('Gönderi bulunamadı.');

$checkinModel = new CheckinModel();
$post = $checkinModel->getById($postId, Auth::id());
if (!$post) Response::notFound('Gönderi bulunamadı.');

$comments = $checkinModel->getComments($postId);

$pageTitle = $post['username'] . '\'in Gönderisi';
$activeNav = '';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed">
        <a href="<?php echo BASE_URL; ?>/dashboard" class="btn-secondary-soft btn-sm" style="margin-bottom:16px;">
            <i class="bi bi-arrow-left"></i> Geri
        </a>

        <?php include __DIR__ . '/partials/_post_card.php'; ?>

        <!-- Yorum Formu -->
        <div class="compose-box">
            <form onsubmit="App.submitComment(this, <?php echo $post['id']; ?>); return false;">
                <div class="compose-top">
                    <div class="compose-avatar">
                        <?php echo avatarHtml($_SESSION['avatar'] ?? null, Auth::username(), '36'); ?>
                    </div>
                    <div class="compose-input-area">
                        <textarea class="compose-textarea comment-input" placeholder="Yorumunu yaz..." maxlength="500" style="min-height:38px;"></textarea>
                    </div>
                    <button type="submit" class="btn-primary-orange btn-sm">Yorum</button>
                </div>
            </form>
        </div>

        <!-- Yorumlar -->
        <div class="card-box" style="padding:0;">
            <?php if (empty($comments)): ?>
                <div class="empty-state" style="padding:32px;">
                    <i class="bi bi-chat"></i>
                    <p>Henüz yorum yok. İlk yorumu sen yaz!</p>
                </div>
            <?php else: ?>
                <?php foreach ($comments as $c): ?>
                <div class="notif-item" style="cursor:default;">
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($c['tag'] ?: $c['username']); ?>">
                        <?php echo avatarHtml($c['avatar'] ?? null, $c['username'], '36'); ?>
                    </a>
                    <div style="flex:1; min-width:0;">
                        <div>
                            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($c['tag'] ?: $c['username']); ?>" class="post-username" style="font-size:0.88rem;"><?php echo escape($c['username']); ?></a>
                            <span class="post-time" style="margin-left:6px;">· <?php echo timeAgo($c['created_at']); ?></span>
                        </div>
                        <p class="post-text" style="font-size:0.9rem; margin-top:4px;"><?php echo linkify(parseMentions($c['comment'])); ?></p>
                        <?php if (!empty($c['image'])): ?>
                            <img src="<?php echo uploadUrl('posts', $c['image']); ?>" alt="" style="max-height:200px; border-radius:var(--radius-md); margin-top:8px;" loading="lazy">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
