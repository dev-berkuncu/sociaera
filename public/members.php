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
            <?php
                $mIsPremium = UserModel::isPremiumActive($m);
            ?>
            <div class="<?php echo $mIsPremium
                ? 'bg-[#1E293B]/90 backdrop-blur-[20px] border border-[#7bd0ff]/40 rounded-2xl shadow-[0_0_20px_-5px_rgba(123,208,255,0.3)] flex flex-col items-center text-center hover:border-[#7bd0ff]/80 hover:shadow-[0_0_30px_rgba(123,208,255,0.4)] transition-all duration-300 group relative overflow-hidden transform hover:-translate-y-1'
                : 'bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl shadow-[0_15px_30px_-15px_rgba(15,23,42,0.5)] flex flex-col items-center text-center hover:border-primary-container/40 hover:shadow-[0_15px_40px_-10px_rgba(255,107,53,0.2)] transition-all duration-300 group relative overflow-hidden transform hover:-translate-y-1'; ?>">
                
                <!-- Mini Banner -->
                <div class="w-full h-20 absolute top-0 left-0 z-0 <?php echo $mIsPremium ? 'bg-gradient-to-r from-[#0f1f3d] via-[#1a365d] to-[#0f1f3d]' : 'bg-surface-container-high/50'; ?>">
                    <?php if ($mIsPremium): ?>
                        <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-30 mix-blend-overlay"></div>
                    <?php endif; ?>
                </div>

                <?php if ($mIsPremium): ?>
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#7bd0ff] to-transparent z-20"></div>
                <div class="absolute top-3 right-3 z-20 bg-black/40 backdrop-blur p-1.5 rounded-full border border-white/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[14px] text-[#7bd0ff] animate-pulse" title="Premium">diamond</span>
                </div>
                <?php endif; ?>

                <!-- Avatar -->
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>" class="relative mt-8 mb-3 block z-10">
                    <?php $mAvatar = $m['avatar'] ? BASE_URL . '/uploads/avatars/' . $m['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($m['username']) . '&background=random'; ?>
                    <div class="relative inline-block">
                        <img alt="User avatar" class="w-24 h-24 rounded-full object-cover border-4 <?php echo $mIsPremium ? 'border-[#1E293B] group-hover:border-[#7bd0ff]/30' : 'border-[#1E293B] group-hover:border-primary-container/30'; ?> transition-all shadow-xl bg-[#1E293B]" src="<?php echo $mAvatar; ?>"/>
                        <?php if ($mIsPremium): ?>
                            <!-- Glowing ring behind avatar for premium -->
                            <div class="absolute inset-0 rounded-full bg-[#7bd0ff] blur-md -z-10 opacity-20 group-hover:opacity-40 transition-opacity"></div>
                        <?php endif; ?>
                    </div>
                </a>
                
                <!-- Info -->
                <div class="z-10 w-full px-4">
                    <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($m['tag'] ?: $m['username']); ?>" class="font-black text-xl <?php echo $mIsPremium ? 'text-white' : 'text-on-surface hover:text-primary-container'; ?> transition-colors truncate w-full block drop-shadow-md">
                        <?php echo escape($m['username']); ?>
                    </a>
                    
                    <div class="h-5 mt-0.5 mb-4">
                        <?php if ($m['tag']): ?>
                            <span class="text-sm text-slate-400 font-medium">@<?php echo escape($m['tag']); ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-between items-center bg-surface-container/50 rounded-xl p-3 mb-5 border border-white/5 w-full">
                        <div class="flex flex-col items-center flex-1">
                            <span class="font-black text-on-surface text-lg"><?php echo (int)($m['follower_count'] ?? 0); ?></span>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-0.5">Takipçi</span>
                        </div>
                        <div class="w-px h-8 bg-white/10"></div>
                        <div class="flex flex-col items-center flex-1">
                            <span class="font-black text-on-surface text-lg"><?php echo (int)($m['checkin_count'] ?? 0); ?></span>
                            <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-0.5">Check-in</span>
                        </div>
                    </div>
                </div>
                
                <!-- Action Button -->
                <div class="mt-auto w-full px-5 pb-5 z-10">
                    <?php if (Auth::id() !== (int)$m['id']): ?>
                    <button class="w-full py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 <?php echo $mIsFollowing ? 'bg-white/5 text-primary-container border border-primary-container/30 hover:bg-white/10' : 'bg-primary-container text-white hover:bg-primary-container/90 shadow-[0_0_15px_rgba(255,107,53,0.3)] active:scale-95'; ?>"
                            onclick="App.toggleFollow(this, <?php echo $m['id']; ?>)">
                        <?php if ($mIsFollowing): ?>
                            <span class="material-symbols-outlined text-[18px]">person_check</span> Takipte
                        <?php else: ?>
                            <span class="material-symbols-outlined text-[18px]">person_add</span> Takip Et
                        <?php endif; ?>
                    </button>
                    <?php else: ?>
                    <div class="w-full py-2.5 rounded-xl font-bold text-sm text-slate-500 border border-white/5 bg-white/5 cursor-default flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-[18px]">account_circle</span> Bu Sensin
                    </div>
                    <?php endif; ?>
                </div>
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
