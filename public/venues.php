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

$venueModel = new VenueModel();
$search = trim($_GET['q'] ?? '');
$category = trim($_GET['cat'] ?? '');
$venues = $venueModel->getApproved($search, $category);
$categories = VenueModel::categories();

$pageTitle = 'Mekanlar';
$activeNav = 'venues';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed" style="max-width:900px;">
        <div class="page-header" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <h1><i class="bi bi-geo-alt" style="color:var(--primary)"></i> Mekanlar</h1>
            <a href="<?php echo BASE_URL; ?>/add-venue" class="btn-primary-orange btn-sm"><i class="bi bi-plus-lg"></i> Mekan Ekle</a>
        </div>

        <!-- Filters -->
        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
            <form method="GET" class="search-bar" style="flex:1; min-width:200px; margin-bottom:0;">
                <i class="bi bi-search"></i>
                <input type="text" name="q" placeholder="Mekan ara..." value="<?php echo escape($search); ?>">
            </form>
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                <a href="?cat=" class="btn-secondary-soft btn-sm <?php echo !$category ? 'active' : ''; ?>" style="<?php echo !$category ? 'background:var(--primary-light);color:var(--primary);border-color:var(--primary);' : ''; ?>">Tümü</a>
                <?php foreach ($categories as $key => $label): ?>
                    <a href="?cat=<?php echo $key; ?>" class="btn-secondary-soft btn-sm" style="<?php echo $category === $key ? 'background:var(--primary-light);color:var(--primary);border-color:var(--primary);' : ''; ?>"><?php echo escape($label); ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($venues)): ?>
            <div class="card-box empty-state">
                <i class="bi bi-geo-alt"></i>
                <p>Mekan bulunamadı.</p>
            </div>
        <?php else: ?>
            <div class="venue-grid">
                <?php foreach ($venues as $v): ?>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>" class="venue-card" style="text-decoration:none; color:inherit;">
                    <div class="venue-card-img" <?php if (!empty($v['image'])): ?>style="background-image:url('<?php echo uploadUrl('posts', $v['image']); ?>')"<?php endif; ?>></div>
                    <div class="venue-card-body">
                        <?php if ($v['category']): ?>
                            <span class="venue-card-cat"><?php echo escape($categories[$v['category']] ?? $v['category']); ?></span>
                        <?php endif; ?>
                        <div class="venue-card-name"><?php echo escape($v['name']); ?></div>
                        <?php if ($v['address']): ?>
                            <div class="venue-card-addr"><i class="bi bi-pin-map"></i> <?php echo escape(truncate($v['address'], 50)); ?></div>
                        <?php endif; ?>
                        <div class="venue-card-stats">
                            <span class="venue-card-stat"><strong><?php echo (int)($v['checkin_count'] ?? 0); ?></strong> check-in</span>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
