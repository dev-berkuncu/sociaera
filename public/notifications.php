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

Auth::requireLogin();

$notifModel = new NotificationModel();

// Tümünü okundu olarak işaretle
$notifModel->markAllRead(Auth::id());
$notifications = $notifModel->getForUser(Auth::id(), 50);

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Bildirimler';
$activeNav = 'notifications';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[32px]">notifications</span> Bildirimler</h1>
        <?php if (!empty($notifications)): ?>
            <button class="bg-surface-container hover:bg-white/10 text-slate-300 border border-white/10 px-4 py-2 rounded-lg text-sm font-bold transition-colors flex items-center gap-2" onclick="App.clearNotifications(this)">
                <span class="material-symbols-outlined text-[18px]">delete</span> Temizle
            </button>
        <?php endif; ?>
    </div>

    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <?php if (empty($notifications)): ?>
            <div class="p-12 text-center text-slate-400">
                <span class="material-symbols-outlined text-[64px] mb-4 opacity-50">notifications_off</span>
                <p class="text-lg">Yeni bildirim yok.</p>
            </div>
        <?php else: ?>
            <div class="flex flex-col">
                <?php foreach ($notifications as $n):
                    $type = $n['type'] ?? 'info';
                    $icon = 'info';
                    $iconColor = 'text-slate-400 bg-surface-container';
                    
                    if ($type === 'like') { $icon = 'favorite'; $iconColor = 'text-[#ef4444] bg-[#ef4444]/10'; }
                    elseif ($type === 'comment') { $icon = 'chat_bubble'; $iconColor = 'text-[#3b82f6] bg-[#3b82f6]/10'; }
                    elseif ($type === 'follow') { $icon = 'person_add'; $iconColor = 'text-[#10b981] bg-[#10b981]/10'; }
                    elseif ($type === 'repost') { $icon = 'repeat'; $iconColor = 'text-[#8b5cf6] bg-[#8b5cf6]/10'; }
                    elseif ($type === 'mention') { $icon = 'alternate_email'; $iconColor = 'text-[#f59e0b] bg-[#f59e0b]/10'; }
                    
                    $link = $n['checkin_id'] ? BASE_URL . '/post?id=' . $n['checkin_id'] : ($n['from_user_id'] ? BASE_URL . '/profile?u=' . escape($n['from_username'] ?? '') : '#');
                ?>
                <a href="<?php echo $link; ?>" class="flex items-center gap-4 p-4 border-b border-white/5 hover:bg-white/5 transition-colors last:border-0 <?php echo !$n['is_read'] ? 'bg-primary-container/5' : ''; ?>">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 <?php echo $iconColor; ?>">
                        <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                    </div>
                    
                    <div class="flex-grow min-w-0 text-[15px] leading-relaxed">
                        <div class="text-on-surface">
                            <?php if (!empty($n['from_avatar'])): ?>
                                <span class="inline-block align-middle mr-1.5">
                                    <?php echo avatarHtml($n['from_avatar'], $n['from_username'] ?? '', '24'); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo escape($n['content']); ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">schedule</span>
                            <?php echo timeAgo($n['created_at']); ?>
                        </div>
                    </div>
                    
                    <?php if (!$n['is_read']): ?>
                        <div class="w-2.5 h-2.5 rounded-full bg-primary-container flex-shrink-0 shadow-[0_0_8px_rgba(255,107,53,0.8)]"></div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
