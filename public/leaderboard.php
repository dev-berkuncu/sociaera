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

$pageTitle = 'Sıralama';
$activeNav = 'leaderboard';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed" style="max-width:720px;">
        <div class="page-header">
            <h1><i class="bi bi-trophy" style="color:var(--primary)"></i> Haftalık Sıralama</h1>
            <p><?php echo formatDate($week['start']); ?> — <?php echo formatDate($week['end']); ?></p>
        </div>

        <?php if ($myRank): ?>
        <div class="card-box" style="padding:20px; margin-bottom:20px; display:flex; align-items:center; gap:16px;">
            <div class="lb-rank-lg" style="width:48px;height:48px;font-size:1.1rem;">
                <?php echo $myRank; ?>
            </div>
            <div>
                <div style="font-weight:700;">Senin Sıran: #<?php echo $myRank; ?></div>
                <div style="font-size:0.85rem; color:var(--text-muted);">Bu hafta <?php echo (new CheckinModel())->getWeeklyCheckinCount(Auth::id()); ?> check-in</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top Kullanıcılar -->
        <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:12px;"><i class="bi bi-people" style="color:var(--primary)"></i> En Aktif Kullanıcılar</h2>
        <div class="leaderboard-table" style="margin-bottom:24px;">
            <?php if (empty($topUsers)): ?>
                <div class="empty-state"><i class="bi bi-trophy"></i><p>Bu hafta henüz check-in yok.</p></div>
            <?php else: ?>
                <?php foreach ($topUsers as $i => $u):
                    $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                ?>
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($u['tag'] ?: $u['username']); ?>" class="leaderboard-row">
                    <div class="lb-rank-lg <?php echo $rankClass; ?>"><?php echo $i + 1; ?></div>
                    <?php echo avatarHtml($u['avatar'] ?? null, $u['username'], '40'); ?>
                    <div style="flex:1;">
                        <div style="font-weight:600;"><?php echo escape($u['username']); ?></div>
                        <?php if ($u['tag']): ?><div style="font-size:0.8rem; color:var(--text-muted);">@<?php echo escape($u['tag']); ?></div><?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700; color:var(--primary); font-size:1.1rem;"><?php echo $u['checkin_count']; ?></div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">check-in</div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Top Mekanlar -->
        <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:12px;"><i class="bi bi-geo-alt" style="color:var(--primary)"></i> En Popüler Mekanlar</h2>
        <div class="leaderboard-table">
            <?php if (empty($topVenues)): ?>
                <div class="empty-state"><i class="bi bi-geo-alt"></i><p>Bu hafta henüz check-in yok.</p></div>
            <?php else: ?>
                <?php foreach ($topVenues as $i => $v):
                    $rankClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                ?>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>" class="leaderboard-row">
                    <div class="lb-rank-lg <?php echo $rankClass; ?>"><?php echo $i + 1; ?></div>
                    <div style="flex:1;">
                        <div style="font-weight:600;"><?php echo escape($v['name']); ?></div>
                        <div style="font-size:0.8rem; color:var(--text-muted);"><?php echo escape($v['category'] ?? ''); ?></div>
                    </div>
                    <div style="text-align:right;">
                        <div style="font-weight:700; color:var(--primary); font-size:1.1rem;"><?php echo $v['checkin_count']; ?></div>
                        <div style="font-size:0.75rem; color:var(--text-muted);">check-in</div>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
