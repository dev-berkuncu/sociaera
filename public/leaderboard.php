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

<div style="min-width:0;">
<style>
/* ── Leaderboard page-local styles ───────────────────────── */
.lb-page-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--text-1);
    margin: 0 0 4px;
}
.lb-page-title .material-symbols-outlined {
    font-size: 32px;
    color: var(--color-primary);
    font-variation-settings: 'FILL' 1;
}
.lb-week-label {
    font-size: 13px;
    color: var(--text-3);
    margin-bottom: 20px;
}

/* My rank sticky banner */
.lb-my-rank-banner {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fff;
    border: 1.5px solid var(--color-primary);
    border-radius: 16px;
    padding: 14px 18px;
    margin-bottom: 20px;
    box-shadow: 0 4px 20px rgba(240, 109, 31, 0.12);
}
.lb-my-rank-circle {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: var(--color-primary);
    color: #fff;
    font-size: 1.2rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(240, 109, 31, 0.35);
}
.lb-my-rank-info strong {
    display: block;
    font-size: 1rem;
    font-weight: 800;
    color: var(--text-1);
}
.lb-my-rank-info span {
    font-size: 13px;
    color: var(--text-3);
}

/* Section headers */
.lb-section-heading {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.1rem;
    font-weight: 800;
    color: var(--text-1);
    margin: 24px 0 12px;
}
.lb-section-heading .material-symbols-outlined {
    font-size: 22px;
    color: var(--color-primary);
    font-variation-settings: 'FILL' 1;
}

/* ── Podium (top 3) ──────────────────────────────────────── */
.lb-podium {
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}
.lb-podium-card {
    flex: 1;
    background: #fff;
    border-radius: 16px;
    border: 1.5px solid var(--border-light);
    padding: 18px 10px 14px;
    text-align: center;
    position: relative;
    transition: box-shadow .18s, transform .18s;
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}
.lb-podium-card:hover {
    box-shadow: 0 8px 28px rgba(240, 109, 31, 0.13);
    transform: translateY(-2px);
}
.lb-podium-card.rank-1 {
    border-color: #FFD700;
    box-shadow: 0 4px 20px rgba(255, 215, 0, 0.18);
    order: 2; /* center */
    padding-top: 26px;
}
.lb-podium-card.rank-2 {
    border-color: #C0C0C0;
    box-shadow: 0 4px 16px rgba(192, 192, 192, 0.18);
    order: 1;
}
.lb-podium-card.rank-3 {
    border-color: #CD7F32;
    box-shadow: 0 4px 16px rgba(205, 127, 50, 0.14);
    order: 3;
}
.lb-podium-rank-badge {
    position: absolute;
    top: -14px;
    left: 50%;
    transform: translateX(-50%);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 900;
    font-size: 13px;
    border: 2.5px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.lb-podium-rank-badge.gold   { background: #FFD700; color: #7a5c00; }
.lb-podium-rank-badge.silver { background: #C0C0C0; color: #4a4a4a; }
.lb-podium-rank-badge.bronze { background: #CD7F32; color: #fff; }

.lb-podium-avatar {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 10px rgba(0,0,0,0.10);
}
.lb-podium-card.rank-1 .lb-podium-avatar {
    width: 70px;
    height: 70px;
}
.lb-podium-username {
    font-weight: 800;
    font-size: 13px;
    color: var(--text-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.lb-podium-count {
    font-weight: 900;
    font-size: 1.1rem;
    color: var(--color-primary);
    line-height: 1;
}
.lb-podium-count-label {
    font-size: 10px;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 700;
}
.lb-premium-dot {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-size: 10px;
    font-weight: 800;
    color: #4F46E5;
    background: rgba(79, 70, 229, 0.08);
    border: 1px solid rgba(79, 70, 229, 0.2);
    border-radius: 20px;
    padding: 1px 6px;
}

/* ── List rows (ranks 4–10) ──────────────────────────────── */
.lb-list-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 16px;
    overflow: hidden;
    margin-bottom: 10px;
}
.lb-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 16px;
    border-bottom: 1px solid var(--border-light);
    text-decoration: none;
    transition: background .14s;
}
.lb-row:last-child { border-bottom: none; }
.lb-row:hover { background: #faf8f5; }

.lb-rank {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    flex-shrink: 0;
    border: 1.5px solid var(--border-light);
    color: var(--text-2);
    background: var(--bg-app);
}
.lb-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid var(--border-light);
}
.lb-user-info { flex: 1; min-width: 0; }
.lb-user-name {
    font-weight: 700;
    font-size: 14px;
    color: var(--text-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 6px;
}
.lb-user-tag {
    font-size: 12px;
    color: var(--text-3);
}
.lb-count {
    font-weight: 900;
    font-size: 1.15rem;
    color: var(--color-primary);
    text-align: right;
}
.lb-count-label {
    font-size: 10px;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    text-align: right;
}

/* ── Venue list ──────────────────────────────────────────── */
.lb-venue-img {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    object-fit: cover;
    border: 1.5px solid var(--border-light);
    flex-shrink: 0;
}
.lb-venue-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: #FFF4EE;
    border: 1.5px solid #FFE0CC;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: var(--color-primary);
}
.lb-venue-name {
    font-weight: 700;
    font-size: 14px;
    color: var(--text-1);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lb-venue-cat {
    font-size: 12px;
    color: var(--text-3);
}

/* Empty state */
.lb-empty {
    text-align: center;
    padding: 48px 24px;
    color: var(--text-3);
}
.lb-empty .material-symbols-outlined {
    font-size: 48px;
    opacity: 0.35;
    display: block;
    margin-bottom: 10px;
}
</style>

<div style="padding-bottom: 40px;">

    <!-- Page title -->
    <h1 class="lb-page-title">
        <span class="material-symbols-outlined">emoji_events</span>
        Sıralama
    </h1>
    <p class="lb-week-label">
        <?php echo formatDate($week['start']); ?> — <?php echo formatDate($week['end']); ?>
    </p>

    <!-- ── Kendi Sıran ──────────────────────────────────── -->
    <?php if ($myRank): ?>
    <div class="lb-my-rank-banner">
        <div class="lb-my-rank-circle">#<?php echo $myRank; ?></div>
        <div class="lb-my-rank-info">
            <strong>Senin Sıran: #<?php echo $myRank; ?></strong>
            <span>Bu hafta <?php echo (new CheckinModel())->getWeeklyCheckinCount(Auth::id()); ?> check-in</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── En Aktif Kullanıcılar ───────────────────────── -->
    <h2 class="lb-section-heading">
        <span class="material-symbols-outlined">groups</span>
        En Aktif Kullanıcılar
    </h2>

    <?php if (empty($topUsers)): ?>
        <div class="swarm-card lb-empty">
            <span class="material-symbols-outlined">emoji_events</span>
            <p>Bu hafta henüz check-in yok.</p>
        </div>
    <?php else: ?>

        <!-- ── Podium: top 3 ──────────────────────────── -->
        <?php $top3 = array_slice($topUsers, 0, 3); ?>
        <div class="lb-podium">
            <?php foreach ($top3 as $i => $u):
                $podiumRank = $i + 1;
                $rankClass  = ['rank-1','rank-2','rank-3'][$i];
                $badgeClass = ['gold','silver','bronze'][$i];
                $uAvatar    = safeAvatarUrl($u['avatar'] ?? null, $u['username']);
                $uIsPremium = !empty($u['is_premium']);
            ?>
            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($u['tag'] ?: $u['username']); ?>"
               class="lb-podium-card <?php echo $rankClass; ?>">
                <div class="lb-podium-rank-badge <?php echo $badgeClass; ?>"><?php echo $podiumRank; ?></div>
                <img src="<?php echo $uAvatar; ?>" alt="<?php echo escape($u['username']); ?>" class="lb-podium-avatar">
                <div class="lb-podium-username"><?php echo escape($u['username']); ?></div>
                <?php if ($uIsPremium): ?>
                <div class="lb-premium-dot">
                    <span class="material-symbols-outlined" style="font-size:11px;font-variation-settings:'FILL' 1;">diamond</span>
                    PRO
                </div>
                <?php endif; ?>
                <div class="lb-podium-count"><?php echo $u['checkin_count']; ?></div>
                <div class="lb-podium-count-label">check-in</div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- ── List: ranks 4-10 ───────────────────────── -->
        <?php $rest = array_slice($topUsers, 3); ?>
        <?php if (!empty($rest)): ?>
        <div class="lb-list-card">
            <?php foreach ($rest as $i => $u):
                $rank       = $i + 4;
                $uAvatar    = safeAvatarUrl($u['avatar'] ?? null, $u['username']);
                $uIsPremium = !empty($u['is_premium']);
            ?>
            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($u['tag'] ?: $u['username']); ?>"
               class="lb-row">
                <div class="lb-rank"><?php echo $rank; ?></div>
                <img src="<?php echo $uAvatar; ?>" alt="<?php echo escape($u['username']); ?>" class="lb-avatar">
                <div class="lb-user-info">
                    <div class="lb-user-name">
                        <?php echo escape($u['username']); ?>
                        <?php if ($uIsPremium): ?>
                        <span class="lb-premium-dot">
                            <span class="material-symbols-outlined" style="font-size:10px;font-variation-settings:'FILL' 1;">diamond</span>
                            PRO
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($u['tag']): ?>
                    <div class="lb-user-tag">@<?php echo escape($u['tag']); ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="lb-count"><?php echo $u['checkin_count']; ?></div>
                    <div class="lb-count-label">check-in</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- ── En Popüler Mekanlar ─────────────────────────── -->
    <h2 class="lb-section-heading" style="margin-top:32px;">
        <span class="material-symbols-outlined">store</span>
        En Popüler Mekanlar
    </h2>

    <?php if (empty($topVenues)): ?>
        <div class="swarm-card lb-empty">
            <span class="material-symbols-outlined">location_off</span>
            <p>Bu hafta henüz check-in yok.</p>
        </div>
    <?php else: ?>
        <div class="lb-list-card">
            <?php foreach ($topVenues as $i => $v):
                $vRank      = $i + 1;
                $badgeClass = ['gold','silver','bronze'][$i] ?? null;
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
               class="lb-row">

                <!-- Rank badge -->
                <?php if ($badgeClass): ?>
                <div class="lb-rank" style="background:<?php echo ['gold'=>'#FFD700','silver'=>'#C0C0C0','bronze'=>'#CD7F32'][$badgeClass]; ?>;color:<?php echo $badgeClass==='silver'?'#4a4a4a':'#fff'; ?>;border-color:transparent;">
                    <?php echo $vRank; ?>
                </div>
                <?php else: ?>
                <div class="lb-rank"><?php echo $vRank; ?></div>
                <?php endif; ?>

                <!-- Venue image / icon -->
                <?php if (!empty($v['image'])): ?>
                    <img src="<?php echo uploadUrl('posts', $v['image']); ?>" class="lb-venue-img" alt="<?php echo escape($v['name']); ?>">
                <?php else: ?>
                    <div class="lb-venue-icon">
                        <span class="material-symbols-outlined" style="font-size:22px;">store</span>
                    </div>
                <?php endif; ?>

                <div class="lb-user-info">
                    <div class="lb-venue-name"><?php echo escape($v['name']); ?></div>
                    <div class="lb-venue-cat"><?php echo escape($v['category'] ?? 'Genel'); ?></div>
                </div>

                <div>
                    <div class="lb-count"><?php echo $v['checkin_count']; ?></div>
                    <div class="lb-count-label">check-in</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
