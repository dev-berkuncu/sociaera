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
$stats = $userModel->getStats($profileUser['id']);
$isFollowing = !$isOwn ? $userModel->isFollowing(Auth::id(), $profileUser['id']) : false;
$favVenue = $userModel->getFavoriteVenue($profileUser['id']);

$tab = $_GET['tab'] ?? 'posts';
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

$pageTitle = $profileUser['username'];
$activeNav = 'profile';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed" style="max-width:720px;">
        <!-- Profile Header -->
        <div class="profile-header-card">
            <div class="profile-banner" <?php if (bannerUrl($profileUser['banner'] ?? null)): ?>style="background-image:url('<?php echo bannerUrl($profileUser['banner']); ?>')"<?php endif; ?>></div>
            <div class="profile-info">
                <?php if (!$isOwn): ?>
                <div class="profile-action-row">
                    <button class="<?php echo $isFollowing ? 'btn-outline-orange' : 'btn-primary-orange'; ?> btn-sm <?php echo $isFollowing ? 'following' : ''; ?>"
                            onclick="App.toggleFollow(this, <?php echo $profileUser['id']; ?>)">
                        <i class="bi bi-<?php echo $isFollowing ? 'person-check-fill' : 'person-plus'; ?>"></i>
                        <?php echo $isFollowing ? 'Takip Ediliyor' : 'Takip Et'; ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="profile-action-row">
                    <a href="<?php echo BASE_URL; ?>/settings" class="btn-secondary-soft btn-sm"><i class="bi bi-pencil"></i> Düzenle</a>
                </div>
                <?php endif; ?>

                <div class="profile-avatar-wrap">
                    <?php echo avatarHtml($profileUser['avatar'] ?? null, $profileUser['username'], '100'); ?>
                </div>
                <div class="profile-names">
                    <h1><?php echo escape($profileUser['username']); ?></h1>
                    <?php if (!empty($profileUser['tag'])): ?>
                        <span class="profile-tag">@<?php echo escape($profileUser['tag']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($profileUser['bio'])): ?>
                    <p class="profile-bio"><?php echo nl2brSafe($profileUser['bio']); ?></p>
                <?php endif; ?>

                <div class="profile-meta">
                    <?php if (!empty($profileUser['gta_character_name'])): ?>
                        <div class="profile-meta-item"><i class="bi bi-controller"></i> <?php echo escape($profileUser['gta_character_name']); ?></div>
                    <?php endif; ?>
                    <?php if ($favVenue): ?>
                        <div class="profile-meta-item"><i class="bi bi-geo-alt-fill"></i> <?php echo escape($favVenue['name']); ?></div>
                    <?php endif; ?>
                    <div class="profile-meta-item"><i class="bi bi-calendar3"></i> <?php echo formatDate($profileUser['created_at']); ?> tarihinde katıldı</div>
                </div>

                <div class="profile-stats">
                    <div class="profile-stat">
                        <span class="profile-stat-num"><?php echo shortNumber($stats['following']); ?></span>
                        <span class="profile-stat-label">Takip</span>
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat-num"><?php echo shortNumber($stats['followers']); ?></span>
                        <span class="profile-stat-label">Takipçi</span>
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat-num"><?php echo shortNumber($stats['checkins']); ?></span>
                        <span class="profile-stat-label">Check-in</span>
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat-num"><?php echo shortNumber($stats['venues']); ?></span>
                        <span class="profile-stat-label">Mekan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="profile-tabs">
            <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=posts" class="profile-tab <?php echo $tab === 'posts' ? 'active' : ''; ?>">Gönderiler</a>
            <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=likes" class="profile-tab <?php echo $tab === 'likes' ? 'active' : ''; ?>">Beğeniler</a>
            <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=reposts" class="profile-tab <?php echo $tab === 'reposts' ? 'active' : ''; ?>">Paylaşımlar</a>
        </div>

        <!-- Posts -->
        <?php if (empty($posts)): ?>
            <div class="card-box empty-state">
                <i class="bi bi-file-earmark-text"></i>
                <p>Henüz gönderi yok.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/partials/_post_card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
