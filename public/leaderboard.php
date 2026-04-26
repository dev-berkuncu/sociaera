<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
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

$lb = new LeaderboardModel();
$topUsers = $lb->getTopUsers(10);
$topVenues = $lb->getTopVenues(10);
$myRank = $lb->getUserRank(Auth::id());
$week = LeaderboardModel::getWeekRange();

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = $lb->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Sıralama';
$activeNav = 'leaderboard';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <div class="mb-6">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface mb-2"><span class="material-symbols-outlined text-primary-container text-[32px]">emoji_events</span> Haftalık Sıralama</h1>
        <p class="text-slate-400 font-label-md text-label-md"><?php echo formatDate($week['start']); ?> — <?php echo formatDate($week['end']); ?></p>
    </div>

    <?php if ($myRank): ?>
    <div class="bg-gradient-to-r from-primary-container/20 to-[#1E293B]/80 backdrop-blur-[20px] border border-primary-container/30 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(255,107,53,0.15)] flex items-center gap-5 mb-4">
        <div class="w-14 h-14 rounded-full bg-primary-container text-white flex items-center justify-center font-bold text-xl shadow-[0_0_15px_rgba(255,107,53,0.5)] flex-shrink-0">
            #<?php echo $myRank; ?>
        </div>
        <div>
            <div class="font-bold text-lg text-on-surface">Senin Sıran: #<?php echo $myRank; ?></div>
            <div class="text-sm text-primary-fixed-dim mt-1">Bu hafta <?php echo (new CheckinModel())->getWeeklyCheckinCount(Auth::id()); ?> check-in</div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Top Kullanıcılar -->
    <h2 class="text-xl font-bold flex items-center gap-2 text-on-surface mt-2 mb-2"><span class="material-symbols-outlined text-primary-container">groups</span> En Aktif Kullanıcılar</h2>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] mb-8">
        <?php if (empty($topUsers)): ?>
            <div class="p-8 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">emoji_events</span>
                <p>Bu hafta henüz check-in yok.</p>
            </div>
        <?php else: ?>
            <div class="flex flex-col">
                <?php foreach ($topUsers as $i => $u):
                    $isTop3 = $i < 3;
                    $rankColor = 'text-slate-400 bg-surface-container border-white/10';
                    $rowBg = 'hover:bg-white/5';
                    if ($i === 0) {
                        $rankColor = 'text-white bg-[#FFD700] border-[#FFD700] shadow-[0_0_10px_rgba(255,215,0,0.5)]';
                        $rowBg = 'bg-[#FFD700]/5 hover:bg-[#FFD700]/10 border-b border-white/5';
                    } elseif ($i === 1) {
                        $rankColor = 'text-slate-800 bg-[#C0C0C0] border-[#C0C0C0] shadow-[0_0_10px_rgba(192,192,192,0.5)]';
                        $rowBg = 'bg-[#C0C0C0]/5 hover:bg-[#C0C0C0]/10 border-b border-white/5';
                    } elseif ($i === 2) {
                        $rankColor = 'text-white bg-[#CD7F32] border-[#CD7F32] shadow-[0_0_10px_rgba(205,127,50,0.5)]';
                        $rowBg = 'bg-[#CD7F32]/5 hover:bg-[#CD7F32]/10 border-b border-white/5';
                    } else {
                        $rowBg = 'hover:bg-white/5 border-b border-white/5 last:border-0';
                    }
                ?>
                <?php
                    $uIsPremium = !empty($u['is_premium']);
                ?>
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($u['tag'] ?: $u['username']); ?>" class="flex items-center gap-4 p-4 transition-colors <?php echo $rowBg; ?> group relative <?php echo $uIsPremium ? 'ring-1 ring-inset ring-[#7bd0ff]/20' : ''; ?>">
                    <?php if ($uIsPremium): ?>
                    <div class="absolute inset-0 bg-gradient-to-r from-[#7bd0ff]/5 via-transparent to-[#7bd0ff]/5 pointer-events-none"></div>
                    <?php endif; ?>

                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg border flex-shrink-0 <?php echo $rankColor; ?> transition-transform group-hover:scale-110 relative z-10"><?php echo $i + 1; ?></div>
                    
                    <div class="relative flex-shrink-0 z-10">
                        <?php $uAvatar = $u['avatar'] ? BASE_URL . '/uploads/avatars/' . $u['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($u['username']) . '&background=random'; ?>
                        <img alt="User avatar" class="w-12 h-12 rounded-full object-cover border-2 <?php echo $uIsPremium ? 'border-[#7bd0ff]/50 shadow-[0_0_12px_rgba(123,208,255,0.3)]' : 'border-white/10'; ?> group-hover:border-primary-container/50 transition-all" src="<?php echo $uAvatar; ?>"/>
                        <?php if ($uIsPremium): ?>
                        <div class="absolute -bottom-0.5 -right-0.5 w-5 h-5 bg-[#0f172a] rounded-full flex items-center justify-center border border-[#7bd0ff]/40">
                            <span class="material-symbols-outlined text-[12px] text-[#7bd0ff]">diamond</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-grow min-w-0 relative z-10">
                        <div class="font-bold text-on-surface group-hover:text-primary-container transition-colors truncate text-lg flex items-center gap-2">
                            <?php echo escape($u['username']); ?>
                            <?php if ($uIsPremium): ?>
                            <span class="bg-[#7bd0ff]/15 text-[#7bd0ff] text-[9px] font-black px-1.5 py-0.5 rounded border border-[#7bd0ff]/25 uppercase tracking-wider">PRO</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($u['tag']): ?><div class="text-sm text-slate-400 truncate">@<?php echo escape($u['tag']); ?></div><?php endif; ?>
                    </div>
                    
                    <div class="text-right flex-shrink-0 relative z-10">
                        <div class="font-black text-xl text-primary-container"><?php echo $u['checkin_count']; ?></div>
                        <div class="text-xs text-slate-500 uppercase tracking-wider font-semibold">check-in</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Top Mekanlar -->
    <h2 class="text-xl font-bold flex items-center gap-2 text-on-surface mb-2"><span class="material-symbols-outlined text-primary-container">store</span> En Popüler Mekanlar</h2>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] mb-8">
        <?php if (empty($topVenues)): ?>
            <div class="p-8 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">location_off</span>
                <p>Bu hafta henüz check-in yok.</p>
            </div>
        <?php else: ?>
            <div class="flex flex-col">
                <?php foreach ($topVenues as $i => $v):
                    $rankColor = 'text-slate-400 bg-surface-container border-white/10';
                    $rowBg = 'hover:bg-white/5 border-b border-white/5 last:border-0';
                    if ($i === 0) {
                        $rankColor = 'text-white bg-[#FFD700] border-[#FFD700] shadow-[0_0_10px_rgba(255,215,0,0.5)]';
                        $rowBg = 'bg-[#FFD700]/5 hover:bg-[#FFD700]/10 border-b border-white/5';
                    } elseif ($i === 1) {
                        $rankColor = 'text-slate-800 bg-[#C0C0C0] border-[#C0C0C0] shadow-[0_0_10px_rgba(192,192,192,0.5)]';
                        $rowBg = 'bg-[#C0C0C0]/5 hover:bg-[#C0C0C0]/10 border-b border-white/5';
                    } elseif ($i === 2) {
                        $rankColor = 'text-white bg-[#CD7F32] border-[#CD7F32] shadow-[0_0_10px_rgba(205,127,50,0.5)]';
                        $rowBg = 'bg-[#CD7F32]/5 hover:bg-[#CD7F32]/10 border-b border-white/5';
                    }
                ?>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>" class="flex items-center gap-4 p-4 transition-colors <?php echo $rowBg; ?> group">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg border flex-shrink-0 <?php echo $rankColor; ?> transition-transform group-hover:scale-110"><?php echo $i + 1; ?></div>
                    
                    <div class="w-12 h-12 rounded-lg bg-surface-container-high flex items-center justify-center text-primary-container border border-white/10 group-hover:border-primary-container/50 transition-colors flex-shrink-0 relative overflow-hidden">
                        <?php if(!empty($v['image'])): ?>
                            <img src="<?php echo uploadUrl('posts', $v['image']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="material-symbols-outlined">store</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-grow min-w-0">
                        <div class="font-bold text-on-surface group-hover:text-primary-container transition-colors truncate text-lg"><?php echo escape($v['name']); ?></div>
                        <div class="text-sm text-slate-400 truncate"><?php echo escape($v['category'] ?? 'Genel'); ?></div>
                    </div>
                    
                    <div class="text-right flex-shrink-0">
                        <div class="font-black text-xl text-primary-container"><?php echo $v['checkin_count']; ?></div>
                        <div class="text-xs text-slate-500 uppercase tracking-wider font-semibold">check-in</div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
