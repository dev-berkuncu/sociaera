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

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Üyeler';
$activeNav = 'members';
require_once __DIR__ . '/partials/app_header.php';
?>

<style>
/* Members page responsive grid */
.members-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
@media (min-width: 640px) {
    .members-grid { grid-template-columns: repeat(3, 1fr); }
}
@media (min-width: 900px) {
    .members-grid { grid-template-columns: repeat(4, 1fr); }
}
.member-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    transition: transform .2s;
    position: relative;
    overflow: hidden;
}
.member-card:hover { transform: translateY(-2px); }
.member-card.premium {
    border: 2px solid rgba(79,70,229,0.3);
    box-shadow: 0 4px 20px rgba(79,70,229,0.12);
}
</style>

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; padding-bottom:40px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
        <h1 style="font-size:1.75rem; font-weight:900; display:flex; align-items:center; gap:8px; color:var(--text-1); margin:0;">
            <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:32px; font-variation-settings:'FILL' 1;">groups</span>
            Üyeler
        </h1>
        <div style="background:var(--bg-section); color:var(--text-2); padding:4px 14px; border-radius:999px; font-size:.875rem; font-weight:600; border:1.5px solid var(--border);">
            <?php echo $result['total']; ?> üye
        </div>
    </div>

    <form method="GET" style="position:relative; margin-bottom:24px;">
        <span class="material-symbols-outlined" style="position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--text-3); pointer-events:none;">search</span>
        <input type="text" name="q" placeholder="Kullanıcı ara..." value="<?php echo escape($search); ?>"
               style="width:100%; box-sizing:border-box; background:#fff; border:1.5px solid var(--border); border-radius:14px; padding:12px 16px 12px 44px; color:var(--text-1); font-size:.875rem; outline:none; transition:border-color .2s; font-family:inherit;"
               onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
    </form>

    <?php if (empty($result['members'])): ?>
        <div style="background:#fff; border:1.5px solid var(--border); border-radius:14px; padding:40px 24px; text-align:center; color:var(--text-3);">
            <span class="material-symbols-outlined" style="font-size:48px; display:block; margin-bottom:8px; opacity:.5;">person_off</span>
            <p style="margin:0;">Üye bulunamadı.</p>
        </div>
    <?php else: ?>
        <div class="members-grid">
            <?php foreach ($result['members'] as $m):
                $mIsFollowing = (Auth::id() !== (int)$m['id']) ? $userModel->isFollowing(Auth::id(), $m['id']) : false;
                $mIsPremium = UserModel::isPremiumActive($m);
            ?>
            <div class="member-card <?php echo $mIsPremium ? 'premium' : ''; ?>">
                <!-- Premium stripe -->
                <?php if ($mIsPremium): ?>
                <div style="position:absolute; top:0; left:0; width:100%; height:3px; background:linear-gradient(90deg,transparent,#4F46E5,transparent); z-index:2;"></div>
                <div style="position:absolute; top:12px; right:12px; z-index:2; background:rgba(79,70,229,0.1); padding:6px; border-radius:50%; border:1px solid rgba(79,70,229,0.2); display:flex; align-items:center; justify-content:center;">
                    <span class="material-symbols-outlined" style="font-size:14px; color:#4F46E5;" title="Premium">diamond</span>
                </div>
                <?php endif; ?>

                <!-- Mini Banner -->
                <div style="width:100%; height:80px; position:absolute; top:0; left:0; z-index:0; background:<?php echo $mIsPremium ? 'linear-gradient(135deg,#EEF2FF,#FAF5FF)' : 'var(--bg-section)'; ?>;"></div>

                <!-- Avatar -->
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>" style="position:relative; margin-top:32px; margin-bottom:12px; display:inline-block; z-index:1; text-decoration:none;">
                    <?php $mAvatar = safeAvatarUrl($m['avatar'] ?? null, $m['username']); ?>
                    <div style="position:relative; display:inline-block;">
                        <img alt="<?php echo escape($m['username']); ?>"
                             style="width:96px; height:96px; border-radius:50%; object-fit:cover; border:4px solid #fff; box-shadow:0 4px 16px rgba(0,0,0,0.12); display:block;"
                             src="<?php echo $mAvatar; ?>" width="96" height="96" loading="lazy">
                        <?php if ($mIsPremium): ?>
                        <div style="position:absolute; inset:0; border-radius:50%; background:#4F46E5; filter:blur(12px); z-index:-1; opacity:.15;"></div>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- Info -->
                <div style="z-index:1; width:100%; padding:0 16px;">
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>"
                       style="font-weight:900; font-size:1.1rem; color:<?php echo $mIsPremium ? '#4F46E5' : 'var(--text-1)'; ?>; text-decoration:none; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; transition:color .15s;"
                       onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='<?php echo $mIsPremium ? '#4F46E5' : 'var(--text-1)'; ?>'">
                        <?php echo escape($m['username']); ?>
                    </a>
                    <div style="height:20px; margin-top:2px; margin-bottom:16px;">
                        <?php if ($m['tag']): ?>
                        <span style="font-size:.875rem; color:var(--text-3); font-weight:500;">@<?php echo escape($m['tag']); ?></span>
                        <?php endif; ?>
                    </div>

                    <div style="display:flex; justify-content:space-between; align-items:center; background:var(--bg-section); border-radius:12px; padding:12px; margin-bottom:20px; border:1.5px solid var(--border-light); width:100%; box-sizing:border-box;">
                        <div style="display:flex; flex-direction:column; align-items:center; flex:1;">
                            <span style="font-weight:900; color:var(--text-1); font-size:1.1rem;"><?php echo (int)($m['follower_count'] ?? 0); ?></span>
                            <span style="font-size:10px; color:var(--text-3); text-transform:uppercase; letter-spacing:.06em; font-weight:700; margin-top:2px;">Takipçi</span>
                        </div>
                        <div style="width:1px; height:32px; background:var(--border-light);"></div>
                        <div style="display:flex; flex-direction:column; align-items:center; flex:1;">
                            <span style="font-weight:900; color:var(--text-1); font-size:1.1rem;"><?php echo (int)($m['checkin_count'] ?? 0); ?></span>
                            <span style="font-size:10px; color:var(--text-3); text-transform:uppercase; letter-spacing:.06em; font-weight:700; margin-top:2px;">Check-in</span>
                        </div>
                    </div>
                </div>

                <!-- Action Button -->
                <div style="margin-top:auto; width:100%; padding:0 20px 20px; z-index:1;">
                    <?php if (Auth::id() !== (int)$m['id']): ?>
                    <button style="width:100%; padding:10px; border-radius:12px; font-weight:700; font-size:.875rem; transition:all .15s; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; font-family:inherit; <?php echo $mIsFollowing ? 'background:var(--bg-section); color:var(--color-primary); border:1.5px solid rgba(240,109,31,0.3);' : 'background:var(--color-primary); color:#fff; border:none; box-shadow:0 4px 16px rgba(240,109,31,0.25);'; ?>"
                            onclick="App.toggleFollow(this, <?php echo $m['id']; ?>)">
                        <?php if ($mIsFollowing): ?>
                            <span class="material-symbols-outlined" style="font-size:18px;">person_check</span> Takipte
                        <?php else: ?>
                            <span class="material-symbols-outlined" style="font-size:18px;">person_add</span> Takip Et
                        <?php endif; ?>
                    </button>
                    <?php else: ?>
                    <div style="width:100%; padding:10px; border-radius:12px; font-weight:700; font-size:.875rem; color:var(--text-3); border:1.5px solid var(--border-light); background:var(--bg-section); display:flex; align-items:center; justify-content:center; gap:8px;">
                        <span class="material-symbols-outlined" style="font-size:18px;">account_circle</span> Bu Sensin
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($result['pages'] > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:32px; flex-wrap:wrap;">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <a href="?q=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>"
               style="width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:8px; font-weight:700; transition:all .15s; text-decoration:none; <?php echo $p === $page ? 'background:var(--color-primary); color:#fff; box-shadow:0 4px 12px rgba(240,109,31,0.25);' : 'background:var(--bg-section); color:var(--text-3); border:1.5px solid var(--border-light);'; ?>">
                <?php echo $p; ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
