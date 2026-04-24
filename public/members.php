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

<section class="flex-1 flex flex-col gap-stack-md max-w-5xl w-full mx-auto lg:mx-0">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[32px]">groups</span> Üyeler</h1>
        <div class="bg-surface-container text-slate-300 px-4 py-1.5 rounded-full font-label-md text-label-md border border-white/10"><?php echo $result['total']; ?> üye</div>
    </div>

    <form method="GET" class="relative mb-6">
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">search</span>
        <input type="text" name="q" placeholder="Kullanıcı ara..." value="<?php echo escape($search); ?>" class="w-full bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl pl-12 pr-4 py-3.5 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-[0_10px_30px_-15px_rgba(15,23,42,0.3)]">
    </form>

    <?php if (empty($result['members'])): ?>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-10 text-center text-slate-400">
            <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">person_off</span>
            <p>Üye bulunamadı.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($result['members'] as $m):
                $mIsFollowing = (Auth::id() !== (int)$m['id']) ? $userModel->isFollowing(Auth::id(), $m['id']) : false;
            ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] flex flex-col items-center text-center hover:border-primary-container/30 transition-colors group">
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>" class="relative mb-3 block">
                    <?php $mAvatar = $m['avatar'] ? BASE_URL . '/uploads/avatars/' . $m['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['username']) . '&background=random'; ?>
                    <img alt="User avatar" class="w-20 h-20 rounded-full object-cover border-2 border-white/10 group-hover:border-primary-container/50 transition-colors" src="<?php echo $mAvatar; ?>"/>
                </a>
                
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>" class="font-bold text-lg text-on-surface hover:text-primary-container transition-colors truncate w-full"><?php echo escape($m['username']); ?></a>
                
                <div class="h-5">
                    <?php if ($m['tag']): ?><div class="text-sm text-slate-400 truncate">@<?php echo escape($m['tag']); ?></div><?php endif; ?>
                </div>
                
                <div class="flex gap-4 items-center justify-center my-4 w-full text-sm text-slate-400">
                    <div class="flex flex-col items-center bg-surface-container w-full py-1.5 rounded-lg border border-white/5">
                        <span class="font-bold text-on-surface"><?php echo (int)($m['follower_count'] ?? 0); ?></span>
                        <span class="text-[10px] uppercase tracking-wider">Takipçi</span>
                    </div>
                    <div class="flex flex-col items-center bg-surface-container w-full py-1.5 rounded-lg border border-white/5">
                        <span class="font-bold text-on-surface"><?php echo (int)($m['checkin_count'] ?? 0); ?></span>
                        <span class="text-[10px] uppercase tracking-wider">Check-in</span>
                    </div>
                </div>
                
                <?php if (Auth::id() !== (int)$m['id']): ?>
                <button class="w-full py-2 rounded-lg font-label-md text-label-md transition-all flex items-center justify-center gap-2 <?php echo $mIsFollowing ? 'bg-primary-container/20 text-primary-container border border-primary-container/30 hover:bg-primary-container/30' : 'bg-primary-container text-white hover:bg-primary-container/90 shadow-[0_0_10px_rgba(255,107,53,0.2)] active:scale-95'; ?>"
                        onclick="App.toggleFollow(this, <?php echo $m['id']; ?>)">
                    <?php if ($mIsFollowing): ?>
                        <span class="material-symbols-outlined text-[18px]">person_check</span> Takipte
                    <?php else: ?>
                        <span class="material-symbols-outlined text-[18px]">person_add</span> Takip Et
                    <?php endif; ?>
                </button>
                <?php else: ?>
                <div class="w-full py-2 mt-auto"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($result['pages'] > 1): ?>
        <div class="flex justify-center gap-2 mt-8">
            <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                <a href="?q=<?php echo urlencode($search); ?>&page=<?php echo $p; ?>" class="w-10 h-10 flex items-center justify-center rounded-lg transition-colors font-bold <?php echo $p === $page ? 'bg-primary-container text-white shadow-[0_0_10px_rgba(255,107,53,0.3)]' : 'bg-surface-container text-slate-400 hover:text-white hover:bg-white/10 border border-white/5'; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
