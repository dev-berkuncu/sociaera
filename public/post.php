<?php
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

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = $post['username'] . '\'in Gönderisi';
$activeNav = '';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0; display:flex; flex-direction:column; gap:16px; max-width:768px; width:100%; padding-bottom:40px;">
    <a href="<?php echo BASE_URL; ?>/dashboard"
       style="display:inline-flex; align-items:center; gap:6px; color:var(--text-3); text-decoration:none; font-size:13px; font-weight:600; margin-bottom:4px; transition:color .15s;"
       onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-3)'">
        <span class="material-symbols-outlined" style="font-size:20px;">arrow_back</span> Geri Dön
    </a>

    <!-- Ana Gönderi -->
    <div class="mb-4">
        <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
    </div>

    <!-- Yorum Formu -->
    <div style="border-radius:12px; padding:20px; margin-bottom:4px; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);" id="comment-form-card">
        <h3 style="font-weight:700; font-size:1.1rem; margin:0 0 16px; color:var(--text-1);">Yorum Yap</h3>
        <form onsubmit="App.submitComment(this, <?php echo $post['id']; ?>); return false;" style="display:flex; align-items:flex-start; gap:16px;">
            <?php $myAvatarUrl = safeAvatarUrl($_SESSION['avatar'] ?? null, Auth::username()); ?>
            <img src="<?php echo $myAvatarUrl; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover; flex-shrink:0; border:1px solid var(--border);" width="40" height="40">
            <div style="flex:1; min-width:0;">
                <textarea style="width:100%; border-radius:12px; padding:12px 16px; outline:none; transition:border-color .2s; resize:vertical; min-height:80px; background:var(--bg-section); border:1px solid var(--border); color:var(--text-1); font-family:inherit; font-size:14px; line-height:1.5; box-sizing:border-box;" placeholder="Yorumunu yaz..." maxlength="500"></textarea>
                <div style="display:flex; justify-content:flex-end; margin-top:10px;">
                    <button type="submit"
                            style="background:var(--color-primary); color:#fff; padding:8px 20px; border-radius:10px; font-weight:700; font-size:13px; box-shadow:0 2px 10px rgba(240,109,31,0.2); transition:opacity .15s; display:flex; align-items:center; gap:8px; border:none; cursor:pointer; font-family:inherit;"
                            onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        <span class="material-symbols-outlined" style="font-size:18px;">send</span> Gönder
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Yorumlar Listesi -->
    <h2 style="font-size:1.25rem; font-weight:800; display:flex; align-items:center; gap:8px; margin:0 0 8px; color:var(--text-1); font-family:monospace;"><span class="material-symbols-outlined" style="color:var(--color-primary);">terminal</span> Telsiz Anonsları</h2>
    <div class="radio-log-container shadow-[0_15px_30px_-15px_rgba(19,19,20,0.3)]">
        <?php if (empty($comments)): ?>
            <div style="padding:32px; text-align:center; font-family:monospace; font-size:12px; color:var(--text-3);">
                [TELSIİZ KAYDI] Henüz telsiz anonsu alınmadı.
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column;">
                <?php foreach ($comments as $c): ?>
                <div class="radio-log-item py-3">
                    <span style="flex-shrink:0; color:var(--text-3);">[RADIO]</span>
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($c['tag'] ?: $c['username']); ?>" class="radio-tag" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">@<?php echo escape($c['tag'] ?: $c['username']); ?></a>
                    <span style="font-weight:700; flex-shrink:0; color:var(--text-3);">:</span>
                    <div style="flex:1; min-width:0;">
                        <span class="radio-msg"><?php echo linkify(parseMentions($c['comment'])); ?></span>

                        <?php if (!empty($c['image'])): ?>
                            <div style="margin-top:12px; border-radius:8px; overflow:hidden; max-width:360px; border:1px solid var(--border); background:var(--bg-section);">
                                <img src="<?php echo uploadUrl('posts', $c['image']); ?>" loading="lazy" style="display:block; width:100%; max-width:100%; height:auto; max-height:250px; object-fit:contain;" width="400" height="250" onerror="this.onerror=null; this.style.background='#f0f0f0'; this.style.minHeight='120px'; this.alt='Görsel yüklenemedi';">
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="radio-time" style="font-size:10px;"><?php echo strtoupper(timeAgo($c['created_at'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
