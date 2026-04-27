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
require_once __DIR__ . '/../app/Models/Badge.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$badgeModel = new BadgeModel();

// Sayfa ziyaretinde de rozetleri kontrol et (geçmişte tamamlananlar için)
$badgeModel->checkAndAward(Auth::id());

$progress = $badgeModel->getProgress(Auth::id());
$earnedCount = count(array_filter($progress, fn($b) => $b['earned']));
$totalCount = count($progress);

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Görevler';
$activeNav = 'missions';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-2">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[32px]">emoji_events</span> Görevler & Rozetler</h1>
        <div class="bg-gradient-to-r from-primary-container/20 to-surface-container text-primary-container px-4 py-1.5 rounded-full font-label-md text-label-md border border-primary-container/30 flex items-center gap-2">
            <span class="material-symbols-outlined text-[18px]">military_tech</span>
            <?php echo $earnedCount; ?>/<?php echo $totalCount; ?> bu hafta
        </div>
    </div>

    <!-- Kazanılan Rozetler -->
    <?php $earnedBadges = array_filter($progress, fn($b) => $b['earned']); ?>
    <?php if (!empty($earnedBadges)): ?>
    <div>
        <h2 class="text-lg font-bold text-on-surface flex items-center gap-2 mb-3"><span class="material-symbols-outlined text-emerald-400">verified</span> Kazanılan Rozetler</h2>
        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3">
            <?php foreach ($earnedBadges as $b): ?>
            <div class="relative flex flex-col items-center gap-1.5 p-3 bg-[#1E293B]/80 border border-white/10 rounded-xl hover:border-white/20 transition-colors group" title="<?php echo escape($b['name'] . ' — ' . $b['desc']); ?>">
                <div class="relative w-12 h-12 rounded-full flex items-center justify-center shadow-[0_0_15px_rgba(0,0,0,0.3)]" style="background: <?php echo $b['color']; ?>20; border: 2px solid <?php echo $b['color']; ?>50;">
                    <span class="material-symbols-outlined text-[24px] group-hover:scale-110 transition-transform" style="color: <?php echo $b['color']; ?>"><?php echo $b['icon']; ?></span>
                    <?php if ($b['total_count'] > 1): ?>
                    <span class="absolute -top-1 -right-1 min-w-[16px] h-4 flex items-center justify-center bg-primary-container text-white text-[9px] font-black rounded-full px-1 shadow-[0_2px_6px_rgba(255,107,53,0.4)]"><?php echo $b['total_count']; ?></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] text-slate-400 font-semibold text-center leading-tight"><?php echo escape($b['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Haftalık Bilgi -->
    <div class="bg-[#7bd0ff]/5 border border-[#7bd0ff]/20 rounded-xl p-4 flex items-center gap-3">
        <span class="material-symbols-outlined text-[#7bd0ff]">info</span>
        <p class="text-sm text-slate-300">Rozetler <span class="text-[#7bd0ff] font-bold">her hafta sıfırlanır</span>. Aynı rozeti birden fazla hafta kazandığında profilinde sayı olarak görünür.</p>
    </div>

    <!-- İlerleme Barı -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-400 text-sm font-semibold">Genel İlerleme</span>
            <span class="text-on-surface font-black text-lg"><?php echo round(($earnedCount / max(1, $totalCount)) * 100); ?>%</span>
        </div>
        <div class="w-full bg-white/5 rounded-full h-3 overflow-hidden">
            <div class="h-full bg-gradient-to-r from-primary-container to-[#ff9e7d] rounded-full transition-all duration-700" style="width: <?php echo round(($earnedCount / max(1, $totalCount)) * 100); ?>%"></div>
        </div>
    </div>

    <!-- Tüm Görevler -->
    <div>
        <h2 class="text-lg font-bold text-on-surface flex items-center gap-2 mb-3"><span class="material-symbols-outlined text-primary-container">flag</span> Tüm Görevler</h2>
        <div class="flex flex-col gap-3">
            <?php foreach ($progress as $b): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border rounded-xl p-5 shadow-[0_10px_25px_-15px_rgba(15,23,42,0.3)] flex items-center gap-4 transition-all <?php echo $b['earned'] ? 'border-emerald-500/20 bg-emerald-500/5' : 'border-white/10 hover:border-white/20'; ?>">
                <!-- İkon -->
                <div class="w-14 h-14 rounded-xl flex items-center justify-center flex-shrink-0 relative <?php echo $b['earned'] ? 'shadow-[0_0_20px_rgba(0,0,0,0.3)]' : 'opacity-60'; ?>" style="background: <?php echo $b['color']; ?><?php echo $b['earned'] ? '30' : '15'; ?>; border: 2px solid <?php echo $b['color']; ?><?php echo $b['earned'] ? '60' : '20'; ?>;">
                    <span class="material-symbols-outlined text-[28px]" style="color: <?php echo $b['color']; ?>"><?php echo $b['icon']; ?></span>
                    <?php if ($b['earned']): ?>
                    <div class="absolute -top-1 -right-1 w-5 h-5 bg-emerald-500 rounded-full flex items-center justify-center shadow-[0_0_8px_rgba(16,185,129,0.5)]">
                        <span class="material-symbols-outlined text-[12px] text-white">check</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- İçerik -->
                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-on-surface <?php echo $b['earned'] ? '' : 'text-slate-300'; ?>"><?php echo escape($b['name']); ?></span>
                        <?php if ($b['total_count'] > 0): ?>
                        <span class="bg-[#7bd0ff]/15 text-[#7bd0ff] text-[9px] font-black px-1.5 py-0.5 rounded border border-[#7bd0ff]/25">x<?php echo $b['total_count']; ?></span>
                        <?php endif; ?>
                        <?php if ($b['earned']): ?>
                        <span class="bg-emerald-500/20 text-emerald-400 text-[9px] font-black px-1.5 py-0.5 rounded border border-emerald-500/30 uppercase tracking-wider">Bu Hafta ✓</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-slate-400 mb-2"><?php echo escape($b['desc']); ?></p>
                    
                    <!-- Progress Bar -->
                    <div class="flex items-center gap-3">
                        <div class="flex-grow bg-white/5 rounded-full h-2 overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" style="width: <?php echo $b['percent']; ?>%; background: <?php echo $b['earned'] ? '#10b981' : $b['color']; ?>;"></div>
                        </div>
                        <span class="text-xs font-bold <?php echo $b['earned'] ? 'text-emerald-400' : 'text-slate-500'; ?> flex-shrink-0 w-12 text-right"><?php echo $b['current']; ?>/<?php echo $b['goal']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
