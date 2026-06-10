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

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:768px; width:100%;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:8px;">
        <h1 style="font-size:1.75rem; font-weight:800; display:flex; align-items:center; gap:8px; color:var(--text-1); margin:0;">
            <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-primary); font-variation-settings:'FILL' 1;">emoji_events</span>
            Görevler &amp; Rozetler
        </h1>
        <div style="padding:6px 16px; border-radius:999px; display:flex; align-items:center; gap:8px; background:rgba(240,109,31,0.12); color:var(--color-primary); border:1px solid rgba(240,109,31,0.25); font-size:13px; font-weight:700;">
            <span class="material-symbols-outlined" style="font-size:18px;">military_tech</span>
            <?php echo $earnedCount; ?>/<?php echo $totalCount; ?> kazanıldı
            <?php if ($thisWeekCount > 0): ?>
            <span style="font-size:10px; color:#10b981; font-weight:800;">(<?php echo $thisWeekCount; ?> bu hafta)</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kazanılan Rozetler -->
    <?php $earnedBadges = array_filter($progress, fn($b) => $b['earned']); ?>
    <?php if (!empty($earnedBadges)): ?>
    <div>
        <h2 style="font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:8px; margin:0 0 12px; color:var(--text-1);">
            <span class="material-symbols-outlined" style="color:#10b981;">verified</span>
            Kazanılan Rozetler
        </h2>
        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(72px, 1fr)); gap:12px;">
            <?php foreach ($earnedBadges as $b): ?>
            <div style="position:relative; display:flex; flex-direction:column; align-items:center; gap:6px; padding:12px; border-radius:12px; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.06);" title="<?php echo escape($b['name'] . ' — ' . $b['desc']); ?>">
                <div style="position:relative; width:48px; height:48px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:<?php echo $b['color']; ?>20; border:2px solid <?php echo $b['color']; ?>50;">
                    <span class="material-symbols-outlined" style="font-size:24px; color:<?php echo $b['color']; ?>;"><?php echo $b['icon']; ?></span>
                    <?php if ($b['total_count'] > 1): ?>
                    <span style="position:absolute; top:-4px; right:-4px; min-width:16px; height:16px; display:flex; align-items:center; justify-content:center; background:var(--color-primary); color:#fff; font-size:9px; font-weight:900; border-radius:999px; padding:0 4px; border:2px solid #fff;"><?php echo $b['total_count']; ?></span>
                    <?php endif; ?>
                </div>
                <span style="font-size:10px; font-weight:600; text-align:center; line-height:1.3; color:var(--text-3);"><?php echo escape($b['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Haftalık Bilgi -->
    <div style="border-radius:12px; padding:16px; display:flex; align-items:center; gap:12px; background:rgba(123,208,255,0.06); border:1px solid rgba(123,208,255,0.2);">
        <span class="material-symbols-outlined" style="color:#7bd0ff; flex-shrink:0;">info</span>
        <p style="font-size:13px; color:var(--text-2); margin:0; line-height:1.5;">
            Rozetler <strong style="color:#5BAFD6;">her hafta sıfırlanır</strong>. Aynı rozeti birden fazla hafta kazandığında profilinde sayı olarak görünür.
        </p>
    </div>

    <!-- İlerleme Barı -->
    <div style="border-radius:12px; padding:20px; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
            <span style="font-size:13px; font-weight:600; color:var(--text-3);">Genel İlerleme</span>
            <span style="font-weight:900; font-size:1.1rem; color:var(--text-1);"><?php echo round(($earnedCount / max(1, $totalCount)) * 100); ?>%</span>
        </div>
        <div style="width:100%; border-radius:999px; height:12px; overflow:hidden; background:var(--bg-section);">
            <div style="height:100%; border-radius:999px; transition:width .7s ease; width:<?php echo round(($earnedCount / max(1, $totalCount)) * 100); ?>%; background:var(--color-primary);"></div>
        </div>
    </div>

    <!-- Tüm Görevler -->
    <div>
        <h2 style="font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:8px; margin:0 0 12px; color:var(--text-1);">
            <span class="material-symbols-outlined" style="color:var(--color-primary);">flag</span>
            Tüm Görevler
        </h2>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <?php foreach ($progress as $b): ?>
            <div style="border-radius:12px; padding:20px; display:flex; align-items:center; gap:16px; transition:all .15s; background:<?php echo $b['earned'] ? 'rgba(16,185,129,0.06)' : '#fff'; ?>; border:1px solid <?php echo $b['earned'] ? 'rgba(16,185,129,0.2)' : 'var(--border)'; ?>; box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <!-- İkon -->
                <div style="width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; position:relative; opacity:<?php echo $b['earned'] ? '1' : '0.6'; ?>; background:<?php echo $b['color']; ?><?php echo $b['earned'] ? '30' : '15'; ?>; border:2px solid <?php echo $b['color']; ?><?php echo $b['earned'] ? '60' : '20'; ?>;">
                    <span class="material-symbols-outlined" style="font-size:28px; color:<?php echo $b['color']; ?>;"><?php echo $b['icon']; ?></span>
                    <?php if ($b['earned']): ?>
                    <div style="position:absolute; top:-4px; right:-4px; width:20px; height:20px; background:#10b981; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 0 8px rgba(16,185,129,0.5);">
                        <span class="material-symbols-outlined" style="font-size:12px; color:#fff;">check</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- İçerik -->
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px; flex-wrap:wrap;">
                        <span style="font-weight:700; color:var(--text-1); opacity:<?php echo $b['earned'] ? '1' : '0.7'; ?>;"><?php echo escape($b['name']); ?></span>
                        <?php if (!empty($b['premium_only'])): ?>
                        <span style="background:rgba(59,130,246,0.15); color:#60a5fa; border:1px solid rgba(59,130,246,0.25); font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px;">💎 Premium</span>
                        <?php endif; ?>
                        <?php if ($b['total_count'] > 0): ?>
                        <span style="background:rgba(123,208,255,0.15); color:#7bd0ff; border:1px solid rgba(123,208,255,0.25); font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px;">x<?php echo $b['total_count']; ?></span>
                        <?php endif; ?>
                        <?php if ($b['this_week']): ?>
                        <span style="background:rgba(16,185,129,0.2); color:#34d399; border:1px solid rgba(16,185,129,0.3); font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:.05em;">Bu Hafta ✓</span>
                        <?php elseif ($b['earned']): ?>
                        <span style="background:var(--bg-section); color:var(--text-3); border:1px solid var(--border); font-size:9px; font-weight:800; padding:2px 6px; border-radius:4px; text-transform:uppercase; letter-spacing:.05em;">Kazanıldı</span>
                        <?php endif; ?>
                    </div>
                    <p style="font-size:13px; color:var(--text-3); margin:0 0 8px; line-height:1.4;"><?php echo escape($b['desc']); ?></p>

                    <!-- Progress Bar -->
                    <div style="display:flex; align-items:center; gap:12px;">
                        <div style="flex:1; border-radius:999px; height:8px; overflow:hidden; background:var(--bg-section);">
                            <div style="height:100%; border-radius:999px; transition:width .5s ease; width:<?php echo $b['percent']; ?>%; background:<?php echo $b['earned'] ? '#10b981' : $b['color']; ?>;"></div>
                        </div>
                        <span style="font-size:11px; font-weight:700; flex-shrink:0; width:48px; text-align:right; color:<?php echo $b['earned'] ? '#10b981' : 'var(--text-3)'; ?>;"><?php echo $b['current']; ?>/<?php echo $b['goal']; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
