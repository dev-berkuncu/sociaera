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
$earnedCount   = count(array_filter($progress, fn($b) => $b['earned']));
$thisWeekCount = count(array_filter($progress, fn($b) => $b['this_week']));
$totalCount    = count($progress);

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

<div style="min-width:0;" class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-2">
        <h1 class="text-3xl font-bold flex items-center gap-2" style="color:var(--text-1);"><span class="material-symbols-outlined text-[32px]" style="color:var(--color-primary);">emoji_events</span> Görevler &amp; Rozetler</h1>
        <div class="px-4 py-1.5 rounded-full font-label-md text-label-md flex items-center gap-2" style="background:rgba(240,109,31,0.12);color:var(--color-primary);border:1px solid rgba(240,109,31,0.25);">
            <span class="material-symbols-outlined text-[18px]">military_tech</span>
            <?php echo $earnedCount; ?>/<?php echo $totalCount; ?> kazanıldı
            <?php if ($thisWeekCount > 0): ?>
            <span class="text-[10px] text-emerald-400 font-bold">(<?php echo $thisWeekCount; ?> bu hafta)</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kazanılan Rozetler -->
    <?php $earnedBadges = array_filter($progress, fn($b) => $b['earned']); ?>
    <?php if (!empty($earnedBadges)): ?>
    <div>
        <h2 class="text-lg font-bold flex items-center gap-2 mb-3" style="color:var(--text-1);"><span class="material-symbols-outlined text-emerald-400">verified</span> Kazanılan Rozetler</h2>
        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3">
            <?php foreach ($earnedBadges as $b): ?>
            <div class="relative flex flex-col items-center gap-1.5 p-3 rounded-xl hover:border-opacity-60 transition-colors group" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.06);" title="<?php echo escape($b['name'] . ' — ' . $b['desc']); ?>">
                <div class="relative w-12 h-12 rounded-full flex items-center justify-center shadow-[0_0_15px_rgba(0,0,0,0.3)]" style="background: <?php echo $b['color']; ?>20; border: 2px solid <?php echo $b['color']; ?>50;">
                    <span class="material-symbols-outlined text-[24px] group-hover:scale-110 transition-transform" style="color: <?php echo $b['color']; ?>"><?php echo $b['icon']; ?></span>
                    <?php if ($b['total_count'] > 1): ?>
                    <span class="absolute -top-1 -right-1 min-w-[16px] h-4 flex items-center justify-center bg-primary-container text-white text-[9px] font-black rounded-full px-1 shadow-[0_2px_6px_rgba(255,145,0,0.4)]"><?php echo $b['total_count']; ?></span>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] font-semibold text-center leading-tight" style="color:var(--text-3);"><?php echo escape($b['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Haftalık Bilgi -->
    <div class="rounded-xl p-4 flex items-center gap-3" style="background:rgba(123,208,255,0.06);border:1px solid rgba(123,208,255,0.2);">
        <span class="material-symbols-outlined text-[#7bd0ff]">info</span>
        <p class="text-sm" style="color:var(--text-2);">Rozetler <span class="font-bold" style="color:#5BAFD6;">her hafta sıfırlanır</span>. Aynı rozeti birden fazla hafta kazandığında profilinde sayı olarak görünür.</p>
    </div>

    <!-- İlerleme Barı -->
    <div class="rounded-xl p-5" style="background:#fff;border:1px solid var(--border);box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold" style="color:var(--text-3);">Genel İlerleme</span>
            <span class="font-black text-lg" style="color:var(--text-1);"><?php echo round(($earnedCount / max(1, $totalCount)) * 100); ?>%</span>
        </div>
        <div class="w-full rounded-full h-3 overflow-hidden" style="background:var(--bg-section);">
            <div class="h-full rounded-full transition-all duration-700" style="width: <?php echo round(($earnedCount / max(1, $totalCount)) * 100); ?>%; background: var(--color-primary);"></div>
        </div>
    </div>

    <!-- Tüm Görevler -->
    <div>
        <h2 class="text-lg font-bold flex items-center gap-2 mb-3" style="color:var(--text-1);"><span class="material-symbols-outlined" style="color:var(--color-primary);">flag</span> Tüm Görevler</h2>
        <div class="flex flex-col gap-3">
            <?php foreach ($progress as $b): ?>
            <div class="rounded-xl p-5 flex items-center gap-4 transition-all" style="background:<?php echo $b['earned'] ? 'rgba(16,185,129,0.06)' : '#fff'; ?>;border:1px solid <?php echo $b['earned'] ? 'rgba(16,185,129,0.2)' : 'var(--border)'; ?>;box-shadow:0 1px 3px rgba(0,0,0,.06);">
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
                        <span class="font-bold <?php echo $b['earned'] ? '' : 'opacity-70'; ?>" style="color:var(--text-1);"><?php echo escape($b['name']); ?></span>
                        <?php if (!empty($b['premium_only'])): ?>
                        <span style="background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.25);" class="text-[9px] font-black px-1.5 py-0.5 rounded">💎 Premium</span>
                        <?php endif; ?>
                        <?php if ($b['total_count'] > 0): ?>
                        <span style="background:rgba(123,208,255,0.15);color:#7bd0ff;border:1px solid rgba(123,208,255,0.25);" class="text-[9px] font-black px-1.5 py-0.5 rounded">x<?php echo $b['total_count']; ?></span>
                        <?php endif; ?>
                        <?php if ($b['this_week']): ?>
                        <span style="background:rgba(16,185,129,0.2);color:#34d399;border:1px solid rgba(16,185,129,0.3);" class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider">Bu Hafta ✓</span>
                        <?php elseif ($b['earned']): ?>
                        <span class="text-[9px] font-black px-1.5 py-0.5 rounded uppercase tracking-wider" style="background:var(--bg-section);color:var(--text-3);border:1px solid var(--border);">Kazanıldı</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm mb-2" style="color:var(--text-3);"><?php echo escape($b['desc']); ?></p>
                    
                    <!-- Progress Bar -->
                    <div class="flex items-center gap-3">
                        <div class="flex-grow rounded-full h-2 overflow-hidden" style="background:var(--bg-section);">
                            <div class="h-full rounded-full transition-all duration-500" style="width: <?php echo $b['percent']; ?>%; background: <?php echo $b['earned'] ? '#10b981' : $b['color']; ?>;"></div>
                        </div>
                        <span class="text-xs font-bold flex-shrink-0 w-12 text-right" style="color:<?php echo $b['earned'] ? '#10b981' : 'var(--text-3)'; ?>"><?php echo $b['current']; ?>/<?php echo $b['goal']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
