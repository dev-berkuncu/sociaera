<?php
/**
 * Sociaera — Profil Sayfası
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';
require_once __DIR__ . '/../app/Models/Badge.php';

Auth::requireLogin();

$userModel = new UserModel();
$checkinModel = new CheckinModel();

$username = $_GET['u'] ?? null;
if ($username) {
    $profileUser = $userModel->getByUsername($username);
} else {
    $profileUser = $userModel->getById(Auth::id());
}

if (!$profileUser) { Response::notFound('Kullanıcı bulunamadı.'); }

$isOwn = (int)$profileUser['id'] === Auth::id();

// Profil teması
$profileTheme = $profileUser['profile_theme'] ?? 'default';
$themeAccents = [
    'default' => '#ff9100',
    'ocean'   => '#0EA5E9',
    'sunset'  => '#F59E0B',
    'emerald' => '#10B981',
    'purple'  => '#8B5CF6',
    'crimson' => '#E11D48',
];
$accentColor = $themeAccents[$profileTheme] ?? '#ff9100';
$stats = $userModel->getStats($profileUser['id']);
$isFollowing = !$isOwn ? $userModel->isFollowing(Auth::id(), $profileUser['id']) : false;
$favVenue = $userModel->getFavoriteVenue($profileUser['id']);

// XSS: Whitelist ile güvenli hale getirildi
$allowedTabs = ['posts', 'journey', 'likes', 'reposts'];
$tab = in_array($_GET['tab'] ?? 'posts', $allowedTabs, true) ? ($_GET['tab'] ?? 'posts') : 'posts';
$page = max(1, (int)($_GET['page'] ?? 1));

switch ($tab) {
    case 'likes':
        $posts = $checkinModel->getLikedByUser($profileUser['id'], $page);
        break;
    case 'reposts':
        $posts = $checkinModel->getRepostedByUser($profileUser['id'], $page);
        break;
    default:
        $posts = $checkinModel->getUserCheckins($profileUser['id'], $page, 20, Auth::id());
        break;
}

// Gezi günlüğü verileri
$journey = $userModel->getCheckinJourney($profileUser['id']);
$categoryLabels = VenueModel::categories();

// Rozetler — HTML öncesinde çekiyoruz (hata mid-render'da olmasın)
$profileBadges = [];
$badgeDefs     = [];
try {
    $badgeModel    = new BadgeModel();
    $profileBadges = $badgeModel->getUserBadges($profileUser['id']);
    $badgeDefs     = BadgeModel::definitions();
} catch (Exception $e) {}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

// Profil görüntüleme kaydı (başkasının profili ise)
$profileViewCount = 0;
$profileViewers = [];
$db = Database::getConnection();
if (!$isOwn && Auth::check()) {
    try {
        // Görüntülemeyi kaydet (günde 1 kez, aynı kişi)
        $db->prepare("
            INSERT INTO profile_views (profile_user_id, viewer_user_id)
            SELECT ?, ? FROM DUAL
            WHERE NOT EXISTS (
                SELECT 1 FROM profile_views 
                WHERE profile_user_id = ? AND viewer_user_id = ? 
                AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
            )
        ")->execute([$profileUser['id'], Auth::id(), $profileUser['id'], Auth::id()]);
    } catch (\Throwable $e) {} // Tablo yoksa sessizce devam et
}

// Premium: Kim baktı istatistikleri (kendi profilim ise)
if ($isOwn && UserModel::isPremiumActive($profileUser)) {
    try {
        $stmt = $db->prepare("SELECT COUNT(DISTINCT viewer_user_id) FROM profile_views WHERE profile_user_id = ? AND viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute([$profileUser['id']]);
        $profileViewCount = (int) $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT u.id, u.username, u.tag, u.avatar, u.is_premium, pv.viewed_at
            FROM profile_views pv
            JOIN users u ON pv.viewer_user_id = u.id
            WHERE pv.profile_user_id = ?
            AND pv.viewed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY u.id
            ORDER BY MAX(pv.viewed_at) DESC
            LIMIT 10
        ");
        $stmt->execute([$profileUser['id']]);
        $profileViewers = $stmt->fetchAll();
    } catch (\Throwable $e) {}
}

// Premium detaylı istatistikler
$premiumStats = [];
if (UserModel::isPremiumActive($profileUser)) {
    try {
        // Haftalık trend (son 4 hafta)
        $stmt = $db->prepare("
            SELECT 
                YEARWEEK(created_at, 1) as yw,
                MIN(DATE(created_at)) as week_start,
                COUNT(*) as cnt
            FROM checkins 
            WHERE user_id = ? AND is_deleted = 0 
            AND created_at > DATE_SUB(NOW(), INTERVAL 4 WEEK)
            GROUP BY YEARWEEK(created_at, 1)
            ORDER BY yw ASC
        ");
        $stmt->execute([$profileUser['id']]);
        $premiumStats['weekly_trend'] = $stmt->fetchAll();

        // En aktif gün
        $stmt = $db->prepare("
            SELECT DAYNAME(created_at) as day_name, COUNT(*) as cnt
            FROM checkins WHERE user_id = ? AND is_deleted = 0
            GROUP BY DAYNAME(created_at)
            ORDER BY cnt DESC LIMIT 1
        ");
        $stmt->execute([$profileUser['id']]);
        $premiumStats['most_active_day'] = $stmt->fetch();

        // Check-in streak (ardışık gün)
        $stmt = $db->prepare("
            SELECT DISTINCT DATE(created_at) as d 
            FROM checkins WHERE user_id = ? AND is_deleted = 0 
            ORDER BY d DESC LIMIT 60
        ");
        $stmt->execute([$profileUser['id']]);
        $dates = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $streak = 0;
        $today = new \DateTime();
        foreach ($dates as $i => $d) {
            $expected = (clone $today)->modify("-{$i} days")->format('Y-m-d');
            if ($d === $expected) { $streak++; } else { break; }
        }
        $premiumStats['streak'] = $streak;
    } catch (\Throwable $e) {}
}

$pageTitle = $profileUser['username'];
$activeNav = 'profile';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0;">
<style>
/* ── Profile page-local styles ───────────────────────────── */

/* Profile Header Card */
.profile-header {
    background: #fff;
    border-radius: 20px;
    border: 1.5px solid var(--border-light);
    overflow: hidden;
    margin-bottom: 16px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.06);
}
.profile-header.is-premium {
    border-color: rgba(79, 70, 229, 0.3);
    box-shadow: 0 4px 24px rgba(79, 70, 229, 0.08);
}
.profile-premium-stripe {
    height: 3px;
    background: linear-gradient(90deg, transparent, #4F46E5, #7C3AED, transparent);
}

/* Banner */
.profile-banner {
    height: 180px;
    width: 100%;
    position: relative;
    overflow: hidden;
}
.profile-banner img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}

/* Info section */
.profile-info {
    padding: 0 20px 20px;
    position: relative;
}

/* Avatar wrap */
.profile-avatar-wrap {
    position: relative;
    display: inline-block;
    margin-top: -40px;
    margin-bottom: 12px;
}
.profile-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    display: block;
}
.profile-premium-ring {
    position: absolute;
    inset: -3px;
    border-radius: 50%;
    background: conic-gradient(#4F46E5, #7C3AED, #4F46E5);
    z-index: -1;
    opacity: 0.6;
}
.profile-premium-badge {
    position: absolute;
    bottom: -2px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(135deg, #4F46E5, #7C3AED);
    color: #fff;
    font-size: 10px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 20px;
    border: 2px solid #fff;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 3px;
    box-shadow: 0 2px 8px rgba(79,70,229,0.35);
}
.profile-premium-badge .material-symbols-outlined { font-size: 11px; font-variation-settings: 'FILL' 1; }

/* Name / tag */
.profile-name {
    font-size: 1.6rem;
    font-weight: 900;
    color: var(--text-1);
    display: flex;
    align-items: center;
    gap: 8px;
    line-height: 1.2;
}
.profile-tag {
    font-size: 14px;
    font-weight: 600;
    margin-top: 2px;
}
.profile-bio {
    font-size: 14px;
    color: var(--text-2);
    margin-top: 10px;
    line-height: 1.6;
    max-width: 520px;
}

/* Stats row */
.profile-stats {
    display: flex;
    gap: 0;
    margin-top: 16px;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    overflow: hidden;
    width: fit-content;
}
.profile-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 20px;
    border-right: 1.5px solid var(--border-light);
    min-width: 72px;
    cursor: default;
    transition: background .14s;
}
.profile-stat:last-child { border-right: none; }
.profile-stat:hover { background: #faf8f5; }
.profile-stat-value {
    font-size: 1.1rem;
    font-weight: 900;
    color: var(--text-1);
}
.profile-stat-label {
    font-size: 10px;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    font-weight: 700;
    margin-top: 2px;
}

/* Action buttons row */
.profile-actions { display: flex; gap: 10px; margin-top: 16px; }

/* Gamification bar */
.profile-gamification {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px 20px 14px;
    border-top: 1.5px solid var(--border-light);
    background: #fafaf8;
}
.gamification-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 12px;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 20px;
    border: 1.5px solid;
    white-space: nowrap;
}
.gamification-chip .material-symbols-outlined {
    font-size: 15px;
    font-variation-settings: 'FILL' 1;
}
.chip-streak  { color: #D97706; background: #FFF8EE; border-color: #FED7AA; }
.chip-rank    { color: var(--color-primary); background: #FFF4EE; border-color: #FFD5BB; }
.chip-badge   { border-color: var(--border-light); color: var(--text-2); background: #fff; }

/* Meta info row */
.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    margin-top: 14px;
    font-size: 13px;
    color: var(--text-3);
}
.profile-meta-item {
    display: flex;
    align-items: center;
    gap: 5px;
}
.profile-meta-item .material-symbols-outlined { font-size: 16px; }

/* Badges card */
.badge-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 16px 20px;
}
.badge-chip {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: default;
    transition: transform .15s;
}
.badge-chip:hover { transform: scale(1.12); }
.badge-chip .material-symbols-outlined { font-size: 22px; }
.badge-chip-count {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    background: var(--color-primary);
    color: #fff;
    font-size: 10px;
    font-weight: 900;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
    border: 2px solid #fff;
}

/* Journey stat cards */
.journey-stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px; }
@media (min-width: 600px) { .journey-stat-grid { grid-template-columns: repeat(4, 1fr); } }
.journey-stat-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    padding: 16px 12px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}
.journey-stat-card .material-symbols-outlined { font-size: 26px; font-variation-settings: 'FILL' 1; }
.journey-stat-value { font-size: 1.4rem; font-weight: 900; color: var(--text-1); }
.journey-stat-label { font-size: 10px; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }

/* Premium stat cards */
.premium-stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 14px; }
@media (min-width: 600px) { .premium-stat-grid { grid-template-columns: repeat(3, 1fr); } }
.premium-stat-card {
    border-radius: 14px;
    padding: 16px 12px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    border: 1.5px solid;
}
.premium-badge-pill {
    font-size: 10px;
    font-weight: 800;
    padding: 2px 8px;
    border-radius: 20px;
    border: 1px solid;
}

/* Weekly trend chart */
.trend-chart-wrap {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    padding: 18px 16px;
    margin-bottom: 14px;
}
.trend-chart-title {
    font-size: 14px;
    font-weight: 800;
    color: var(--text-1);
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 14px;
}
.trend-bars {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    height: 80px;
}
.trend-bar-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 4px; }
.trend-bar-count { font-size: 11px; font-weight: 700; color: var(--color-primary); }
.trend-bar-bg { width: 100%; background: #FFF0E6; border-radius: 6px 6px 0 0; overflow: hidden; }
.trend-bar-fill { width: 100%; background: linear-gradient(to top, var(--color-primary), #FFA040); border-radius: 6px 6px 0 0; }
.trend-bar-label { font-size: 9px; color: var(--text-3); text-transform: uppercase; }

/* Who viewed — list */
.viewer-list-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 14px;
}
.viewer-list-header {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.viewer-list-header h3 {
    font-size: 14px;
    font-weight: 800;
    color: var(--text-1);
    display: flex;
    align-items: center;
    gap: 6px;
}
.viewer-list-header h3 .material-symbols-outlined { font-size: 18px; color: #4F46E5; }
.viewer-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 18px;
    border-bottom: 1px solid var(--border-light);
    text-decoration: none;
    transition: background .12s;
}
.viewer-row:last-child { border-bottom: none; }
.viewer-row:hover { background: #faf8f5; }
.viewer-row img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; border: 1.5px solid var(--border-light); flex-shrink: 0; }

/* Category breakdown */
.cat-breakdown-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    padding: 18px 16px;
    margin-bottom: 14px;
}
.cat-row { margin-bottom: 12px; }
.cat-row:last-child { margin-bottom: 0; }
.cat-row-header { display: flex; justify-content: space-between; margin-bottom: 5px; font-size: 13px; font-weight: 600; color: var(--text-1); }
.cat-row-meta { font-size: 12px; color: var(--text-3); }
.cat-bar-bg { height: 7px; background: #f0ede9; border-radius: 20px; overflow: hidden; }
.cat-bar-fill { height: 100%; border-radius: 20px; transition: width .6s; }

/* Venue list rows */
.venue-list-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 14px;
}
.venue-list-header { padding: 14px 18px; border-bottom: 1px solid var(--border-light); font-size: 14px; font-weight: 800; color: var(--text-1); display: flex; align-items: center; gap: 6px; }
.venue-list-header .material-symbols-outlined { font-size: 18px; color: var(--text-3); }
.venue-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    border-bottom: 1px solid var(--border-light);
    text-decoration: none;
    transition: background .12s;
}
.venue-row:last-child { border-bottom: none; }
.venue-row:hover { background: #faf8f5; }
.venue-row-thumb {
    width: 46px; height: 46px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    background: #FFF4EE;
    border: 1.5px solid #FFE0CC;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-primary);
}
.venue-row-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Fav venue + top category cards */
.highlight-card-grid { display: grid; grid-template-columns: 1fr; gap: 10px; margin-bottom: 14px; }
@media (min-width: 600px) { .highlight-card-grid { grid-template-columns: 1fr 1fr; } }
.highlight-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 14px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow .15s;
}
.highlight-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.07); }
.highlight-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.highlight-icon .material-symbols-outlined { font-size: 22px; font-variation-settings: 'FILL' 1; }

/* Empty states */
.profile-empty {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 16px;
    padding: 48px 24px;
    text-align: center;
    color: var(--text-3);
}
.profile-empty .material-symbols-outlined { font-size: 48px; opacity: 0.3; display: block; margin-bottom: 10px; }

/* Load more */
.load-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 99px;
    padding: 10px 22px;
    font-weight: 700;
    font-size: 14px;
    color: var(--text-1);
    text-decoration: none;
    transition: box-shadow .15s, border-color .15s;
    margin-top: 12px;
}
.load-more-btn:hover { border-color: var(--color-primary); box-shadow: 0 2px 12px rgba(240,109,31,0.12); }
</style>

<?php
$isPremium = !empty($profileUser['is_premium']);
$pAvatar   = safeAvatarUrl($profileUser['avatar'] ?? null, $profileUser['username']);

// Banner background
if (bannerUrl($profileUser['banner'] ?? null)) {
    $bannerHtml = '<img src="' . bannerUrl($profileUser['banner']) . '" class="w-full h-full object-cover" width="800" height="180" alt="Banner">';
} else {
    $bannerStyle = 'background: linear-gradient(135deg, ' . $accentColor . '33, #fff8f4);';
    $bannerHtml  = '<div style="width:100%;height:100%;' . $bannerStyle . '"></div>';
}
?>

<!-- ── Profile Header Card ──────────────────────────────── -->
<div class="profile-header <?php echo $isPremium ? 'is-premium' : ''; ?>">

    <?php if ($isPremium): ?>
    <div class="profile-premium-stripe"></div>
    <?php endif; ?>

    <!-- Banner -->
    <div class="profile-banner">
        <?php echo $bannerHtml; ?>
    </div>

    <!-- Info -->
    <div class="profile-info">

        <!-- Avatar + action buttons row -->
        <div style="display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;">

            <!-- Avatar -->
            <div class="profile-avatar-wrap">
                <?php if ($isPremium): ?>
                <div class="profile-premium-ring"></div>
                <?php endif; ?>
                <img src="<?php echo $pAvatar; ?>" alt="<?php echo escape($profileUser['username']); ?>"
                     class="profile-avatar" width="88" height="88">
                <?php if ($isPremium): ?>
                <div class="profile-premium-badge">
                    <span class="material-symbols-outlined">diamond</span> Premium
                </div>
                <?php endif; ?>
            </div>

            <!-- Action buttons -->
            <div class="profile-actions" style="margin-top:0;margin-bottom:8px;">
                <?php if (!$isOwn): ?>
                <button class="btn <?php echo $isFollowing ? 'btn-ghost' : 'btn-primary'; ?> btn-sm"
                        onclick="App.toggleFollow(this, <?php echo $profileUser['id']; ?>)">
                    <span class="material-symbols-outlined" style="font-size:16px;">
                        <?php echo $isFollowing ? 'person_check' : 'person_add'; ?>
                    </span>
                    <?php echo $isFollowing ? 'Takip Ediliyor' : 'Takip Et'; ?>
                </button>
                <?php else: ?>
                <a href="<?php echo BASE_URL; ?>/settings" class="btn btn-ghost btn-sm">
                    <span class="material-symbols-outlined" style="font-size:16px;">edit</span>
                    Profili Düzenle
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Name + tag -->
        <h1 class="profile-name" style="<?php echo ($profileTheme !== 'default' && $isPremium) ? 'color:' . $accentColor . ';' : ''; ?>">
            <?php echo escape($profileUser['username']); ?>
            <?php if ($isPremium): ?>
            <span class="material-symbols-outlined" style="font-size:22px;color:#4F46E5;font-variation-settings:'FILL' 1;" title="Premium">verified</span>
            <?php endif; ?>
        </h1>
        <?php if (!empty($profileUser['tag'])): ?>
        <div class="profile-tag" style="color:<?php echo $accentColor; ?>">@<?php echo escape($profileUser['tag']); ?></div>
        <?php endif; ?>

        <!-- Bio -->
        <?php if (!empty($profileUser['bio'])): ?>
        <p class="profile-bio"><?php echo nl2brSafe($profileUser['bio']); ?></p>
        <?php endif; ?>

        <!-- Stats -->
        <div class="profile-stats">
            <div class="profile-stat">
                <span class="profile-stat-value"><?php echo shortNumber($stats['checkins']); ?></span>
                <span class="profile-stat-label">Check-in</span>
            </div>
            <div class="profile-stat">
                <span class="profile-stat-value"><?php echo shortNumber($stats['venues']); ?></span>
                <span class="profile-stat-label">Mekan</span>
            </div>
            <div class="profile-stat">
                <span class="profile-stat-value"><?php echo shortNumber($stats['following']); ?></span>
                <span class="profile-stat-label">Takip</span>
            </div>
            <div class="profile-stat">
                <span class="profile-stat-value"><?php echo shortNumber($stats['followers']); ?></span>
                <span class="profile-stat-label">Takipçi</span>
            </div>
        </div>

        <!-- Meta info -->
        <div class="profile-meta">
            <?php if (!empty($profileUser['gta_character_name'])): ?>
            <div class="profile-meta-item">
                <span class="material-symbols-outlined" style="color:var(--text-3);">sports_esports</span>
                <?php echo escape($profileUser['gta_character_name']); ?>
            </div>
            <?php endif; ?>
            <?php if ($favVenue): ?>
            <div class="profile-meta-item">
                <span class="material-symbols-outlined" style="color:var(--color-primary);font-variation-settings:'FILL' 1;">star</span>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $favVenue['id']; ?>"
                   style="color:var(--text-2);font-weight:600;text-decoration:none;" 
                   onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-2)'">
                    <?php echo escape($favVenue['name']); ?>
                </a>
            </div>
            <?php endif; ?>
            <div class="profile-meta-item">
                <span class="material-symbols-outlined" style="color:var(--text-3);">calendar_month</span>
                <?php echo formatDate($profileUser['created_at']); ?> katıldı
            </div>
            <?php if ($isOwn): ?>
            <?php
                if (!class_exists('WalletModel')) {
                    require_once __DIR__ . '/../app/Models/Wallet.php';
                }
                $walletModel = new WalletModel();
                $walletModel->ensureWallet(Auth::id());
                $balance = $walletModel->getBalance(Auth::id());
            ?>
            <div class="profile-meta-item">
                <span class="material-symbols-outlined" style="color:#10b981;font-variation-settings:'FILL' 1;">account_balance_wallet</span>
                <span style="color:var(--text-2);font-weight:600;">Bakiye:</span>
                <span style="color:#10b981;font-weight:900;">$<?php echo number_format($balance, 2, ',', '.'); ?></span>
                <a href="<?php echo BASE_URL; ?>/wallet"
                   style="font-size:11px;background:rgba(16,185,129,.1);color:#10b981;border:1px solid rgba(16,185,129,.25);border-radius:20px;padding:2px 8px;font-weight:800;text-decoration:none;display:inline-flex;align-items:center;gap:2px;">
                    Cüzdan <span class="material-symbols-outlined" style="font-size:12px;">arrow_forward</span>
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /profile-info -->

    <!-- Gamification chips -->
    <?php
    $hasGamificationChips = !empty($premiumStats['streak']) || !empty($headerRank) || !empty($profileBadges);
    // For non-owner we use locally computed streak if premium
    $displayStreak = $premiumStats['streak'] ?? 0;
    ?>
    <?php if ($displayStreak > 0 || !empty($profileBadges)): ?>
    <div class="profile-gamification">
        <?php if ($displayStreak > 0): ?>
        <div class="gamification-chip chip-streak">
            <span class="material-symbols-outlined">local_fire_department</span>
            <?php echo $displayStreak; ?> günlük seri
        </div>
        <?php endif; ?>

        <?php if (!empty($profileBadges)): ?>
            <?php foreach (array_slice($profileBadges, 0, 4) as $pb):
                $def = $badgeDefs[$pb['badge_key']] ?? null;
                if (!$def) continue;
                $count = (int)($pb['total_count'] ?? 1);
            ?>
            <div class="gamification-chip chip-badge" title="<?php echo escape($def['name'] . ' — ' . $def['desc'] . ($count > 1 ? ' (x' . $count . ')' : '')); ?>"
                 style="color:<?php echo $def['color']; ?>;background:<?php echo $def['color']; ?>10;border-color:<?php echo $def['color']; ?>40;">
                <span class="material-symbols-outlined" style="font-size:14px;color:<?php echo $def['color']; ?>"><?php echo $def['icon']; ?></span>
                <?php echo escape($def['name']); ?>
                <?php if ($count > 1): ?><span style="font-size:10px;opacity:.7;">×<?php echo $count; ?></span><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (count($profileBadges) > 4): ?>
            <a href="<?php echo BASE_URL; ?>/missions" class="gamification-chip chip-badge" style="color:var(--text-3);">
                +<?php echo count($profileBadges) - 4; ?> daha
            </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div><!-- /profile-header -->

<!-- ── Tabs ─────────────────────────────────────────────── -->
<?php $uParam = escape($profileUser['tag'] ?: $profileUser['username']); ?>
<div class="swarm-tabs" style="margin-bottom:14px;">
    <a href="?u=<?php echo $uParam; ?>&tab=posts"
       class="swarm-tab <?php echo $tab === 'posts'   ? 'active' : ''; ?>">Gönderiler</a>
    <a href="?u=<?php echo $uParam; ?>&tab=journey"
       class="swarm-tab <?php echo $tab === 'journey' ? 'active' : ''; ?>">Günlüğüm</a>
    <a href="?u=<?php echo $uParam; ?>&tab=likes"
       class="swarm-tab <?php echo $tab === 'likes'   ? 'active' : ''; ?>">Beğeniler</a>
    <a href="?u=<?php echo $uParam; ?>&tab=reposts"
       class="swarm-tab <?php echo $tab === 'reposts' ? 'active' : ''; ?>">Paylaşımlar</a>
</div>

<!-- ── Tab Content ───────────────────────────────────────── -->
<div style="display:flex;flex-direction:column;gap:10px;padding-bottom:40px;">

<?php if ($tab === 'journey'): ?>
    <!-- ── Gezi Günlüğü ──────────────────────────────── -->

    <?php if ($stats['checkins'] === 0): ?>
        <div class="profile-empty">
            <span class="material-symbols-outlined">explore</span>
            <p style="font-size:16px;font-weight:700;margin-bottom:4px;">Henüz hiç check-in yok</p>
            <p style="font-size:13px;">Check-in yaparak kişisel gezi günlüğünü oluştur!</p>
        </div>
    <?php else: ?>

        <!-- Journey stat cards -->
        <div class="journey-stat-grid">
            <div class="journey-stat-card">
                <span class="material-symbols-outlined" style="color:var(--color-primary);">edit_note</span>
                <div class="journey-stat-value"><?php echo shortNumber($stats['checkins']); ?></div>
                <div class="journey-stat-label">Toplam Check-in</div>
            </div>
            <div class="journey-stat-card">
                <span class="material-symbols-outlined" style="color:#10b981;">location_on</span>
                <div class="journey-stat-value"><?php echo shortNumber($journey['unique_venues']); ?></div>
                <div class="journey-stat-label">Keşfedilen Mekan</div>
            </div>
            <div class="journey-stat-card">
                <span class="material-symbols-outlined" style="color:#3b82f6;">calendar_month</span>
                <div class="journey-stat-value"><?php echo $journey['this_month']; ?></div>
                <div class="journey-stat-label">Bu Ay</div>
            </div>
            <div class="journey-stat-card">
                <span class="material-symbols-outlined" style="color:#f59e0b;">local_fire_department</span>
                <div class="journey-stat-value"><?php echo $journey['last_7_days']; ?></div>
                <div class="journey-stat-label">Son 7 Gün</div>
            </div>
        </div>

        <!-- Premium stats -->
        <?php if (!empty($premiumStats)): ?>
        <div class="premium-stat-grid">
            <?php if (!empty($premiumStats['streak'])): ?>
            <div class="premium-stat-card" style="background:#FFFBEB;border-color:#FDE68A;">
                <span class="material-symbols-outlined" style="color:#D97706;font-size:26px;font-variation-settings:'FILL' 1;">local_fire_department</span>
                <div class="journey-stat-value" style="color:#D97706;"><?php echo $premiumStats['streak']; ?></div>
                <div class="journey-stat-label" style="color:#D97706;">Gün Streak 🔥</div>
            </div>
            <?php endif; ?>

            <?php if (!empty($premiumStats['most_active_day'])): ?>
            <?php
                $dayNames = ['Monday'=>'Pazartesi','Tuesday'=>'Salı','Wednesday'=>'Çarşamba','Thursday'=>'Perşembe','Friday'=>'Cuma','Saturday'=>'Cumartesi','Sunday'=>'Pazar'];
                $dayTr = $dayNames[$premiumStats['most_active_day']['day_name']] ?? $premiumStats['most_active_day']['day_name'];
            ?>
            <div class="premium-stat-card" style="background:#FAF5FF;border-color:#DDD6FE;">
                <span class="material-symbols-outlined" style="color:#7C3AED;font-size:26px;font-variation-settings:'FILL' 1;">today</span>
                <div class="journey-stat-value" style="color:#7C3AED;font-size:1rem;"><?php echo $dayTr; ?></div>
                <div class="journey-stat-label" style="color:#7C3AED;">En Aktif Gün</div>
            </div>
            <?php endif; ?>

            <?php if ($isOwn && $profileViewCount > 0): ?>
            <div class="premium-stat-card" style="background:#EFF6FF;border-color:#BFDBFE;">
                <span class="material-symbols-outlined" style="color:#2563EB;font-size:26px;font-variation-settings:'FILL' 1;">visibility</span>
                <div class="journey-stat-value" style="color:#2563EB;"><?php echo $profileViewCount; ?></div>
                <div class="journey-stat-label" style="color:#2563EB;">Profil Ziyaretçi (7g)</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Weekly trend chart -->
        <?php if (!empty($premiumStats['weekly_trend'])): ?>
        <div class="trend-chart-wrap">
            <div class="trend-chart-title">
                <span class="material-symbols-outlined" style="font-size:18px;color:var(--color-primary);">trending_up</span>
                Haftalık Trend
                <span class="premium-badge-pill" style="color:#4F46E5;background:#EEF2FF;border-color:#C7D2FE;">Premium 💎</span>
            </div>
            <div class="trend-bars">
                <?php
                $maxCnt = max(array_column($premiumStats['weekly_trend'], 'cnt'));
                foreach ($premiumStats['weekly_trend'] as $wt):
                    $heightPct = $maxCnt > 0 ? round(($wt['cnt'] / $maxCnt) * 100) : 10;
                    $weekLabel = date('d M', strtotime($wt['week_start']));
                ?>
                <div class="trend-bar-col">
                    <span class="trend-bar-count"><?php echo $wt['cnt']; ?></span>
                    <div class="trend-bar-bg" style="flex:1;">
                        <div class="trend-bar-fill" style="height:<?php echo max(8, $heightPct); ?>%;"></div>
                    </div>
                    <span class="trend-bar-label"><?php echo $weekLabel; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; /* premiumStats */ ?>

        <!-- Who viewed (Premium) -->
        <?php if ($isOwn && !empty($profileViewers)): ?>
        <div class="viewer-list-card">
            <div class="viewer-list-header">
                <h3>
                    <span class="material-symbols-outlined">visibility</span>
                    Kim Profilime Baktı
                    <span class="premium-badge-pill" style="color:#4F46E5;background:#EEF2FF;border-color:#C7D2FE;">Premium 💎</span>
                </h3>
                <span style="font-size:12px;color:var(--text-3);">Son 7 gün</span>
            </div>
            <?php foreach ($profileViewers as $pv):
                $pvAvatar = safeAvatarUrl($pv['avatar'] ?? null, $pv['username']);
            ?>
            <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($pv['tag'] ?: $pv['username']); ?>" class="viewer-row">
                <img src="<?php echo $pvAvatar; ?>" alt="<?php echo escape($pv['username']); ?>" width="36" height="36" loading="lazy">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:700;color:var(--text-1);"><?php echo escape($pv['username']); ?></div>
                    <div style="font-size:11px;color:var(--text-3);">@<?php echo escape($pv['tag'] ?: $pv['username']); ?></div>
                </div>
                <span style="font-size:11px;color:var(--text-3);"><?php echo timeAgo($pv['viewed_at']); ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Fav venue + top category -->
        <div class="highlight-card-grid">
            <?php if ($favVenue): ?>
            <div class="highlight-card">
                <div class="highlight-icon" style="background:#FFF4EE;color:var(--color-primary);">
                    <span class="material-symbols-outlined">star</span>
                </div>
                <div style="min-width:0;">
                    <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:3px;">En Çok Gidilen</div>
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $favVenue['id']; ?>"
                       style="font-weight:800;color:var(--text-1);text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                       onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-1)'">
                        <?php echo escape($favVenue['name']); ?>
                    </a>
                    <div style="font-size:12px;color:var(--color-primary);font-weight:600;"><?php echo (int)$favVenue['cnt']; ?> ziyaret</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($journey['top_category']): ?>
            <?php
                $catKey   = $journey['top_category']['category'];
                $catLabel = $categoryLabels[$catKey] ?? ucfirst($catKey);
                $catIcons = ['restoran'=>'restaurant','kafe'=>'local_cafe','bar'=>'local_bar','otel'=>'hotel','alisveris'=>'shopping_bag','eglence'=>'celebration','spor'=>'fitness_center','saglik'=>'spa','kultur'=>'museum','diger'=>'place'];
                $catIcon  = $catIcons[$catKey] ?? 'category';
            ?>
            <div class="highlight-card">
                <div class="highlight-icon" style="background:#FAF5FF;color:#7C3AED;">
                    <span class="material-symbols-outlined"><?php echo $catIcon; ?></span>
                </div>
                <div>
                    <div style="font-size:11px;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;font-weight:700;margin-bottom:3px;">En Sevdiğin Tür</div>
                    <div style="font-weight:800;color:var(--text-1);"><?php echo escape($catLabel); ?></div>
                    <div style="font-size:12px;color:#7C3AED;font-weight:600;"><?php echo (int)$journey['top_category']['cnt']; ?> check-in</div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Category breakdown -->
        <?php if (!empty($journey['category_breakdown'])): ?>
        <div class="cat-breakdown-card">
            <div style="font-size:14px;font-weight:800;color:var(--text-1);margin-bottom:14px;display:flex;align-items:center;gap:6px;">
                <span class="material-symbols-outlined" style="font-size:18px;color:var(--text-3);">donut_large</span>
                Mekan Türü Dağılımı
            </div>
            <?php
                $totalCats = array_sum(array_column($journey['category_breakdown'], 'cnt'));
                $barColorsHex = ['#F06D1F','#7C3AED','#3b82f6','#10b981','#f59e0b'];
            ?>
            <?php foreach ($journey['category_breakdown'] as $ci => $cat):
                $pct   = $totalCats > 0 ? round(($cat['cnt'] / $totalCats) * 100) : 0;
                $label = $categoryLabels[$cat['category']] ?? ucfirst($cat['category']);
                $color = $barColorsHex[$ci % count($barColorsHex)];
            ?>
            <div class="cat-row">
                <div class="cat-row-header">
                    <span><?php echo escape($label); ?></span>
                    <span class="cat-row-meta"><?php echo $cat['cnt']; ?> ziyaret · %<?php echo $pct; ?></span>
                </div>
                <div class="cat-bar-bg">
                    <div class="cat-bar-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>;"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Recent venues -->
        <?php if (!empty($journey['recent_venues'])): ?>
        <div class="venue-list-card">
            <div class="venue-list-header">
                <span class="material-symbols-outlined">history</span>
                Son Ziyaretler
            </div>
            <?php foreach ($journey['recent_venues'] as $rv):
                $rvCat = $categoryLabels[$rv['category']] ?? ucfirst($rv['category'] ?: 'Mekan');
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $rv['id']; ?>" class="venue-row">
                <div class="venue-row-thumb">
                    <?php if ($rv['image']): ?>
                    <img src="<?php echo escapeUrl(uploadUrl('venues', $rv['image'])); ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <span class="material-symbols-outlined" style="font-size:20px;font-variation-settings:'FILL' 1;">location_on</span>
                    <?php endif; ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-weight:700;font-size:14px;color:var(--text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape($rv['name']); ?></div>
                    <div style="font-size:12px;color:var(--text-3);margin-top:2px;">
                        <?php echo escape($rvCat); ?> · <?php echo (int)$rv['visit_count']; ?>× ziyaret
                    </div>
                </div>
                <div style="font-size:12px;color:var(--text-3);flex-shrink:0;text-align:right;">
                    <span class="material-symbols-outlined" style="font-size:13px;vertical-align:middle;">schedule</span>
                    <?php echo timeAgo($rv['last_visit']); ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    <?php endif; /* checkins === 0 */ ?>
    <!-- ── /Gezi Günlüğü ──────────────────────────────── -->

<?php else: /* posts, likes, reposts */ ?>

    <?php if (empty($posts)): ?>
        <div class="profile-empty">
            <span class="material-symbols-outlined">post_add</span>
            <p style="font-size:15px;font-weight:600;">Henüz gönderi yok.</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
        <?php endforeach; ?>

        <?php if (count($posts) >= 20): ?>
        <div style="text-align:center;margin-top:8px;">
            <a href="?u=<?php echo $uParam; ?>&tab=<?php echo $tab; ?>&page=<?php echo $page + 1; ?>"
               class="load-more-btn">
                <span class="material-symbols-outlined">arrow_downward</span>
                Daha Fazla Yükle
            </a>
        </div>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

</div><!-- /tab content -->
</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
