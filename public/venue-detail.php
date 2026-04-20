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

$venueId = (int)($_GET['id'] ?? 0);
if (!$venueId) Response::notFound('Mekan bulunamadı.');

$venueModel = new VenueModel();
$venue = $venueModel->getById($venueId);
if (!$venue || $venue['status'] !== 'approved') Response::notFound('Mekan bulunamadı.');

$checkinModel = new CheckinModel();
$checkinCount = $venueModel->getCheckinCount($venueId);
$posts = $checkinModel->getVenueCheckins($venueId, 1, 30, Auth::id());

$pageTitle = $venue['name'];
$activeNav = 'venues';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed" style="max-width:720px;">
        <a href="<?php echo BASE_URL; ?>/venues" class="btn-secondary-soft btn-sm" style="margin-bottom:16px;">
            <i class="bi bi-arrow-left"></i> Mekanlar
        </a>

        <div class="card-box" style="margin-bottom:20px; overflow:hidden;">
            <div class="profile-banner" style="height:180px; <?php if (!empty($venue['image'])): ?>background-image:url('<?php echo uploadUrl('posts', $venue['image']); ?>')<?php endif; ?>"></div>
            <div style="padding:24px;">
                <?php if ($venue['category']): ?>
                    <span class="venue-card-cat"><?php echo escape(VenueModel::categories()[$venue['category']] ?? $venue['category']); ?></span>
                <?php endif; ?>
                <h1 style="font-size:1.5rem; font-weight:800; margin:8px 0;"><?php echo escape($venue['name']); ?></h1>
                <?php if ($venue['description']): ?>
                    <p style="color:var(--text-secondary); margin-bottom:16px;"><?php echo nl2brSafe($venue['description']); ?></p>
                <?php endif; ?>
                <div class="profile-meta">
                    <?php if ($venue['address']): ?>
                        <div class="profile-meta-item"><i class="bi bi-geo-alt"></i> <?php echo escape($venue['address']); ?></div>
                    <?php endif; ?>
                    <?php if ($venue['website']): ?>
                        <div class="profile-meta-item"><i class="bi bi-globe2"></i> <a href="<?php echo escape($venue['website']); ?>" target="_blank"><?php echo escape($venue['website']); ?></a></div>
                    <?php endif; ?>
                    <?php if ($venue['facebrowser_url']): ?>
                        <div class="profile-meta-item"><i class="bi bi-link-45deg"></i> <a href="<?php echo escape($venue['facebrowser_url']); ?>" target="_blank">Facebrowser</a></div>
                    <?php endif; ?>
                </div>
                <div style="margin-top:16px; padding-top:16px; border-top:1px solid var(--border-light); display:flex; gap:24px;">
                    <div class="profile-stat">
                        <span class="profile-stat-num"><?php echo $checkinCount; ?></span>
                        <span class="profile-stat-label">Check-in</span>
                    </div>
                </div>
            </div>
        </div>

        <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:16px;">Son Check-in'ler</h2>

        <?php if (empty($posts)): ?>
            <div class="card-box empty-state">
                <i class="bi bi-pin-map"></i>
                <p>Bu mekanda henüz check-in yok.</p>
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
