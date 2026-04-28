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

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <a href="<?php echo BASE_URL; ?>/dashboard" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors w-fit mb-2">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span> Geri Dön
    </a>

    <!-- Ana Gönderi -->
    <div class="mb-4">
        <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
    </div>

    <!-- Yorum Formu -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] mb-4">
        <h3 class="font-bold text-lg mb-4 text-on-surface">Yorum Yap</h3>
        <form onsubmit="App.submitComment(this, <?php echo $post['id']; ?>); return false;" class="flex items-start gap-4">
            <?php $myAvatar = $_SESSION['avatar'] ?? null; $myAvatarUrl = $myAvatar ? BASE_URL . '/uploads/avatars/' . $myAvatar : 'https://ui-avatars.com/api/?name=' . urlencode(Auth::username()) . '&background=random'; ?>
            <img src="<?php echo $myAvatarUrl; ?>" class="w-10 h-10 rounded-full object-cover border border-white/10 flex-shrink-0">
            <div class="flex-grow">
                <textarea class="w-full bg-background border border-white/10 rounded-xl px-4 py-3 text-on-surface focus:border-primary-container focus:outline-none transition-colors resize-y min-h-[80px]" placeholder="Yorumunu yaz..." maxlength="500"></textarea>
                <div class="flex justify-end mt-3">
                    <button type="submit" class="bg-primary-container text-white px-6 py-2 rounded-lg font-bold shadow-[0_0_10px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-all active:scale-95 flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">send</span> Gönder
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Yorumlar Listesi -->
    <h2 class="text-xl font-bold flex items-center gap-2 text-on-surface mb-2"><span class="material-symbols-outlined text-primary-container">forum</span> Yorumlar</h2>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <?php if (empty($comments)): ?>
            <div class="p-10 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">chat_bubble_outline</span>
                <p>Henüz yorum yok. İlk yorumu sen yaz!</p>
            </div>
        <?php else: ?>
            <div class="flex flex-col">
                <?php foreach ($comments as $c): ?>
                <div class="flex gap-4 p-5 border-b border-white/5 last:border-0 hover:bg-white/5 transition-colors">
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($c['tag'] ?: $c['username']); ?>" class="flex-shrink-0 mt-1">
                        <?php $cAvatar = $c['avatar'] ? BASE_URL . '/uploads/avatars/' . $c['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($c['username']) . '&background=random'; ?>
                        <img src="<?php echo $cAvatar; ?>" class="w-10 h-10 rounded-full object-cover border border-white/10">
                    </a>
                    
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center flex-wrap gap-2 mb-1">
                            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($c['tag'] ?: $c['username']); ?>" class="font-bold text-on-surface hover:text-primary-container transition-colors"><?php echo escape($c['username']); ?></a>
                            <span class="text-xs text-slate-500">• <?php echo timeAgo($c['created_at']); ?></span>
                        </div>
                        
                        <p class="text-slate-300 leading-relaxed text-[15px]"><?php echo linkify(parseMentions($c['comment'])); ?></p>
                        
                        <?php if (!empty($c['image'])): ?>
                            <div class="mt-3 rounded-lg overflow-hidden border border-white/10 max-w-sm bg-black/10">
                                <img src="<?php echo uploadUrl('posts', $c['image']); ?>" loading="lazy" class="block w-full max-w-full h-auto max-h-[300px] object-contain">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
