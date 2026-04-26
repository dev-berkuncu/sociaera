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

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = $profileUser['username'];
$activeNav = 'profile';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <!-- Profile Header -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] relative">
        <!-- Banner -->
        <div class="h-48 md:h-64 w-full bg-surface-container relative">
            <?php if (bannerUrl($profileUser['banner'] ?? null)): ?>
                <img src="<?php echo bannerUrl($profileUser['banner']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full bg-gradient-to-r from-primary-container/40 to-surface-container-high"></div>
            <?php endif; ?>
        </div>
        
        <!-- Profile Info -->
        <div class="px-6 pb-6 relative md:px-8 md:pb-8">
            <div class="flex justify-between items-start">
                <!-- Avatar -->
                <div class="-mt-16 md:-mt-20 relative z-10 p-1 bg-[#1E293B] rounded-full border border-white/5 inline-block">
                    <?php $pAvatar = $profileUser['avatar'] ? BASE_URL . '/uploads/avatars/' . $profileUser['avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($profileUser['username']) . '&background=random'; ?>
                    <img src="<?php echo $pAvatar; ?>" class="w-28 h-28 md:w-36 md:h-36 rounded-full object-cover border-4 border-[#1E293B]">
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 flex gap-2">
                    <?php if (!$isOwn): ?>
                        <button class="px-6 py-2 rounded-full font-bold text-sm transition-all flex items-center gap-2 <?php echo $isFollowing ? 'bg-transparent border border-primary-container text-primary-container hover:bg-primary-container/10' : 'bg-primary-container text-white hover:bg-primary-container/90 shadow-[0_0_10px_rgba(255,107,53,0.3)]'; ?>"
                                onclick="App.toggleFollow(this, <?php echo $profileUser['id']; ?>)">
                            <span class="material-symbols-outlined text-[18px]"><?php echo $isFollowing ? 'person_check' : 'person_add'; ?></span>
                            <?php echo $isFollowing ? 'Takip Ediliyor' : 'Takip Et'; ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/settings" class="px-6 py-2 rounded-full font-bold text-sm transition-all flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white border border-white/10 backdrop-blur">
                            <span class="material-symbols-outlined text-[18px]">edit</span> Düzenle
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-4">
                <h1 class="text-3xl font-black text-on-surface"><?php echo escape($profileUser['username']); ?></h1>
                <?php if (!empty($profileUser['tag'])): ?>
                    <span class="text-primary-container font-medium text-lg block mt-1">@<?php echo escape($profileUser['tag']); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($profileUser['bio'])): ?>
                <p class="mt-4 text-slate-300 leading-relaxed font-body-md"><?php echo nl2brSafe($profileUser['bio']); ?></p>
            <?php endif; ?>
            
            <div class="flex flex-col sm:flex-row gap-y-3 gap-x-6 mt-6 text-sm text-slate-400">
                <?php if (!empty($profileUser['gta_character_name'])): ?>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-slate-500">sports_esports</span>
                        <span><?php echo escape($profileUser['gta_character_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($favVenue): ?>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary-container">star</span>
                        <span><?php echo escape($favVenue['name']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">calendar_month</span>
                    <span><?php echo formatDate($profileUser['created_at']); ?> katıldı</span>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-6 mt-8 pt-6 border-t border-white/10">
                <div class="flex flex-col">
                    <span class="text-xl font-bold text-on-surface"><?php echo shortNumber($stats['following']); ?></span>
                    <span class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Takip</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold text-on-surface"><?php echo shortNumber($stats['followers']); ?></span>
                    <span class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Takipçi</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold text-on-surface"><?php echo shortNumber($stats['checkins']); ?></span>
                    <span class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Check-in</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-bold text-on-surface"><?php echo shortNumber($stats['venues']); ?></span>
                    <span class="text-xs text-slate-500 uppercase tracking-widest font-semibold">Mekan</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kazanılan Rozetler -->
    <?php
    $badgeModel = new BadgeModel();
    $profileBadges = $badgeModel->getUserBadges($profileUser['id']);
    $badgeDefs = BadgeModel::definitions();
    ?>
    <?php if (!empty($profileBadges)): ?>
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-3 flex items-center gap-2"><span class="material-symbols-outlined text-[16px] text-primary-container">emoji_events</span> Rozetler (<?php echo count($profileBadges); ?>)</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($profileBadges as $pb):
                $def = $badgeDefs[$pb['badge_key']] ?? null;
                if (!$def) continue;
            ?>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full border transition-colors hover:bg-white/5" style="background: <?php echo $def['color']; ?>10; border-color: <?php echo $def['color']; ?>30;" title="<?php echo escape($def['name'] . ' — ' . $def['desc']); ?>">
                <span class="material-symbols-outlined text-[16px]" style="color: <?php echo $def['color']; ?>"><?php echo $def['icon']; ?></span>
                <span class="text-xs font-bold text-slate-300"><?php echo escape($def['name']); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabs -->
    <div class="flex items-center border-b border-white/10 mb-2 mt-4 px-2">
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=posts" class="px-6 py-4 font-bold text-sm transition-all border-b-2 <?php echo $tab === 'posts' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Gönderiler</a>
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=likes" class="px-6 py-4 font-bold text-sm transition-all border-b-2 <?php echo $tab === 'likes' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Beğeniler</a>
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=reposts" class="px-6 py-4 font-bold text-sm transition-all border-b-2 <?php echo $tab === 'reposts' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Paylaşımlar</a>
    </div>

    <!-- Posts -->
    <div class="flex flex-col gap-stack-md pb-container-padding">
        <?php if (empty($posts)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-10 text-center text-slate-400 mt-4 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
                <span class="material-symbols-outlined text-[48px] mb-3 opacity-50">post_add</span>
                <p class="text-lg">Henüz gönderi yok.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
            <?php endforeach; ?>
            
            <?php if (count($posts) >= 20): ?>
                <div class="text-center mt-4">
                    <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=<?php echo $tab; ?>&page=<?php echo $page + 1; ?>" class="inline-flex items-center gap-2 bg-white/5 hover:bg-white/10 text-on-surface px-6 py-3 rounded-full transition-colors border border-white/10 font-bold">
                        <span class="material-symbols-outlined">arrow_downward</span> Daha Fazla Yükle
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
