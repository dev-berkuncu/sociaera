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
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$userModel = new UserModel();
$result = $userModel->getMembers($page, 24, $search);

$pageTitle = 'Üyeler';
$activeNav = 'members';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <?php require_once __DIR__ . '/partials/sidebar-left.php'; ?>

    <main class="main-feed" style="max-width:900px;">
        <div class="page-header">
            <h1><i class="bi bi-people" style="color:var(--primary)"></i> Üyeler</h1>
            <p><?php echo $result['total']; ?> üye</p>
        </div>

        <form method="GET" class="search-bar">
            <i class="bi bi-search"></i>
            <input type="text" name="q" placeholder="Kullanıcı ara..." value="<?php echo escape($search); ?>">
        </form>

        <?php if (empty($result['members'])): ?>
            <div class="card-box empty-state">
                <i class="bi bi-people"></i>
                <p>Üye bulunamadı.</p>
            </div>
        <?php else: ?>
            <div class="member-grid">
                <?php foreach ($result['members'] as $m):
                    $mIsFollowing = (Auth::id() !== (int)$m['id']) ? $userModel->isFollowing(Auth::id(), $m['id']) : false;
                ?>
                <div class="member-card">
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>">
                        <?php echo avatarHtml($m['avatar'] ?? null, $m['username'], '64'); ?>
                    </a>
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>" style="display:block; font-weight:700; margin-top:10px; color:var(--text-primary);"><?php echo escape($m['username']); ?></a>
                    <?php if ($m['tag']): ?><div style="font-size:0.82rem; color:var(--text-muted);">@<?php echo escape($m['tag']); ?></div><?php endif; ?>
                    <div style="display:flex; gap:16px; justify-content:center; margin:12px 0; font-size:0.82rem; color:var(--text-muted);">
                        <span><strong style="color:var(--text-primary);"><?php echo (int)($m['follower_count'] ?? 0); ?></strong> takipçi</span>
                        <span><strong style="color:var(--text-primary);"><?php echo (int)($m['checkin_count'] ?? 0); ?></strong> check-in</span>
                    </div>
                    <?php if (Auth::id() !== (int)$m['id']): ?>
                    <button class="<?php echo $mIsFollowing ? 'btn-outline-orange' : 'btn-primary-orange'; ?> btn-sm btn-full <?php echo $mIsFollowing ? 'following' : ''; ?>"
                            onclick="App.toggleFollow(this, <?php echo $m['id']; ?>)">
                        <?php echo $mIsFollowing ? '<i class="bi bi-person-check-fill"></i> Takip Ediliyor' : '<i class="bi bi-person-plus"></i> Takip Et'; ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($result['pages'] > 1): ?>
            <div style="display:flex; justify-content:center; gap:8px; margin-top:24px;">
                <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                    <a href="?q=<?php echo escape($search); ?>&page=<?php echo $p; ?>" class="btn-secondary-soft btn-sm" style="<?php echo $p === $page ? 'background:var(--primary-light);color:var(--primary);' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/partials/sidebar-right.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
