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

<div style="min-width:0;" class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-2 hover:opacity-70 transition-opacity w-fit mb-2" style="color:var(--text-3);text-decoration:none;">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span> Geri Dön
    </a>

    <!-- Ana Gönderi -->
    <div class="mb-4">
        <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
    </div>

    <!-- Yorum Formu -->
    <div class="rounded-xl p-6 mb-4" id="comment-form-card" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <h3 class="font-bold text-lg mb-4" style="color:var(--text-1);">Yorum Yap</h3>
        <form onsubmit="App.submitComment(this, <?php echo $post['id']; ?>); return false;" class="flex items-start gap-4">
            <?php $myAvatarUrl = safeAvatarUrl($_SESSION['avatar'] ?? null, Auth::username()); ?>
            <img src="<?php echo $myAvatarUrl; ?>" class="w-10 h-10 rounded-full object-cover flex-shrink-0" style="border:1px solid var(--border);" width="40" height="40">
            <div class="flex-grow">
                <textarea class="w-full rounded-xl px-4 py-3 focus:outline-none transition-colors resize-y min-h-[80px]" style="background:var(--bg-section);border:1px solid var(--border);color:var(--text-1);" placeholder="Yorumunu yaz..." maxlength="500"></textarea>
                <div class="flex justify-end mt-3">
                    <button type="submit" class="bg-primary-container text-white px-6 py-2 rounded-lg font-bold shadow-[0_0_10px_rgba(255,145,0,0.2)] hover:bg-primary-container/90 transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span> Gönder
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Yorumlar Listesi -->
    <h2 class="text-xl font-bold flex items-center gap-2 mb-2 font-mono" style="color:var(--text-1);"><span class="material-symbols-outlined" style="color:var(--color-primary);">terminal</span> Telsiz Anonsları</h2>
    <div class="radio-log-container shadow-[0_15px_30px_-15px_rgba(19,19,20,0.3)]">
        <?php if (empty($comments)): ?>
            <div class="p-8 text-center font-mono text-xs" style="color:var(--text-3);">
                [TELSİZ KAYDI] Henüz telsiz anonsu alınmadı.
            </div>
        <?php else: ?>
            <div class="flex flex-col">
                <?php foreach ($comments as $c): ?>
                <div class="radio-log-item py-3">
                    <span class="flex-shrink-0" style="color:var(--text-3);">[RADIO]</span>
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($c['tag'] ?: $c['username']); ?>" class="radio-tag hover:underline">@<?php echo escape($c['tag'] ?: $c['username']); ?></a>
                    <span class="font-bold flex-shrink-0" style="color:var(--text-3);">:</span>
                    <div class="flex-grow min-w-0">
                        <span class="radio-msg"><?php echo linkify(parseMentions($c['comment'])); ?></span>
                        
                        <?php if (!empty($c['image'])): ?>
                            <div class="mt-3 rounded-lg overflow-hidden max-w-sm" style="border:1px solid var(--border);background:var(--bg-section);">
                                <img src="<?php echo uploadUrl('posts', $c['image']); ?>" loading="lazy" class="block w-full max-w-full h-auto max-h-[250px] object-contain" width="400" height="250">
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="radio-time text-[10px]"><?php echo strtoupper(timeAgo($c['created_at'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
