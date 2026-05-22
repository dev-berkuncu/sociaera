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

$pageTitle = $profileUser['username'];
$activeNav = 'profile';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <?php 
        $isPremium = !empty($profileUser['is_premium']); 
        $premiumBorder = $isPremium ? 'border-[#7bd0ff]/40 shadow-[0_0_30px_-5px_rgba(123,208,255,0.25)]' : 'border-white/10 shadow-[0_20px_40px_-15px_rgba(15,23,42,0.5)]';
        $premiumBg = $isPremium ? 'bg-[#1E293B]/90' : 'bg-[#1E293B]/80';
    ?>
    <!-- Profile Header -->
    <div class="<?php echo $premiumBg; ?> backdrop-blur-[20px] border <?php echo $premiumBorder; ?> rounded-2xl overflow-hidden relative mb-4">
        
        <?php if ($isPremium): ?>
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#7bd0ff] to-transparent z-30"></div>
        <?php endif; ?>

        <!-- Banner -->
        <div class="h-56 md:h-72 w-full bg-surface-container relative">
            <div class="absolute inset-0 bg-gradient-to-t from-[#1E293B]/95 via-[#1E293B]/30 to-transparent z-10"></div>
            <?php if ($isPremium): ?>
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/stardust.png')] opacity-30 mix-blend-overlay z-10 pointer-events-none"></div>
            <?php endif; ?>
            
            <?php if (bannerUrl($profileUser['banner'] ?? null)): ?>
                <img src="<?php echo bannerUrl($profileUser['banner']); ?>" class="w-full h-full object-cover" width="800" height="288">
            <?php else: ?>
                <div class="w-full h-full bg-gradient-to-r from-primary-container/40 to-surface-container-high"></div>
            <?php endif; ?>
        </div>
        
        <!-- Profile Info -->
        <div class="px-6 pb-8 relative z-20 md:px-10">
            <div class="flex flex-col md:flex-row md:justify-between md:items-end gap-4 -mt-20 md:-mt-24 mb-6">
                <!-- Avatar -->
                <div class="relative inline-block">
                    <?php $pAvatar = safeAvatarUrl($profileUser['avatar'] ?? null, $profileUser['username']); ?>
                    <img src="<?php echo $pAvatar; ?>" class="w-32 h-32 md:w-40 md:h-40 rounded-full object-cover border-4 <?php echo $isPremium ? 'border-[#1E293B] shadow-[0_0_20px_rgba(123,208,255,0.3)]' : 'border-[#1E293B] shadow-xl'; ?> bg-[#1E293B] relative z-10" width="160" height="160">
                    
                    <?php if ($isPremium): ?>
                        <div class="absolute inset-0 rounded-full bg-[#7bd0ff] blur-md -z-10 opacity-40"></div>
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 z-20 bg-[#7bd0ff]/20 text-[#7bd0ff] text-[10px] font-black px-3 py-0.5 rounded-full border border-[#7bd0ff]/30 uppercase tracking-widest whitespace-nowrap shadow-lg flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]">diamond</span> Premium
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 w-full md:w-auto mt-2 md:mt-0">
                    <?php if (!$isOwn): ?>
                        <button class="flex-1 md:flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 <?php echo $isFollowing ? 'bg-white/5 border border-primary-container text-primary-container hover:bg-white/10' : 'bg-primary-container text-white hover:bg-primary-container/90 shadow-[0_0_15px_rgba(255,107,53,0.3)] active:scale-95'; ?>"
                                onclick="App.toggleFollow(this, <?php echo $profileUser['id']; ?>)">
                            <span class="material-symbols-outlined text-[18px]"><?php echo $isFollowing ? 'person_check' : 'person_add'; ?></span>
                            <?php echo $isFollowing ? 'Takip Ediliyor' : 'Takip Et'; ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo BASE_URL; ?>/settings" class="flex-1 md:flex-none px-6 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 bg-white/10 hover:bg-white/20 text-white border border-white/10 backdrop-blur">
                            <span class="material-symbols-outlined text-[18px]">edit</span> Profili Düzenle
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-2">
                <h1 class="text-3xl md:text-4xl font-black <?php echo $isPremium ? 'text-white drop-shadow-md' : 'text-on-surface'; ?> tracking-tight flex items-center gap-2">
                    <?php echo escape($profileUser['username']); ?>
                    <?php if ($isPremium): ?>
                        <span class="material-symbols-outlined text-[#7bd0ff] text-[24px]" title="Premium">verified</span>
                    <?php endif; ?>
                </h1>
                <?php if (!empty($profileUser['tag'])): ?>
                    <span class="text-primary-container font-medium text-lg block mt-1">@<?php echo escape($profileUser['tag']); ?></span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($profileUser['bio'])): ?>
                <p class="mt-5 text-slate-300 leading-relaxed font-body-md text-lg max-w-2xl"><?php echo nl2brSafe($profileUser['bio']); ?></p>
            <?php endif; ?>
            
            <div class="flex flex-wrap gap-y-3 gap-x-6 mt-6 text-sm text-slate-400 bg-white/5 border border-white/5 rounded-xl p-4 w-fit">
                <?php if (!empty($profileUser['gta_character_name'])): ?>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-slate-500">sports_esports</span>
                        <span class="text-slate-300"><?php echo escape($profileUser['gta_character_name']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($favVenue): ?>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[18px] text-primary-container">star</span>
                        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $favVenue['id']; ?>" class="hover:text-primary-container text-slate-300 transition-colors"><?php echo escape($favVenue['name']); ?></a>
                    </div>
                <?php endif; ?>
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">calendar_month</span>
                    <span class="text-slate-300"><?php echo formatDate($profileUser['created_at']); ?> katıldı</span>
                </div>
            </div>
            
            <!-- Rozetler (logic bölümünden çekildi) -->
            <?php if (!empty($profileBadges)): ?>
            <div class="flex flex-wrap items-center gap-2 mt-6 pt-6 border-t border-white/10">
                <span class="text-sm font-bold text-slate-400 mr-2">Kazanılan Rozetler:</span>
                <?php foreach ($profileBadges as $pb):
                    $def = $badgeDefs[$pb['badge_key']] ?? null;
                    if (!$def) continue;
                    $count = (int)($pb['total_count'] ?? 1);
                ?>
                <div class="relative flex items-center justify-center w-10 h-10 rounded-xl transition-transform hover:scale-110 cursor-default" style="background: <?php echo $def['color']; ?>15; border: 1.5px solid <?php echo $def['color']; ?>40;" title="<?php echo escape($def['name'] . ' — ' . $def['desc'] . ($count > 1 ? ' (x' . $count . ')' : '')); ?>">
                    <span class="material-symbols-outlined text-[20px]" style="color: <?php echo $def['color']; ?>"><?php echo $def['icon']; ?></span>
                    <?php if ($count > 1): ?>
                    <span class="absolute -top-1.5 -right-1.5 min-w-[18px] h-5 flex items-center justify-center bg-primary-container text-white text-[10px] font-black rounded-full px-1 shadow-[0_2px_8px_rgba(255,107,53,0.5)]"><?php echo $count; ?></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <a href="<?php echo BASE_URL; ?>/missions" class="flex items-center justify-center w-10 h-10 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition-colors text-slate-500 hover:text-slate-300 ml-1" title="Tüm görevleri gör">
                    <span class="material-symbols-outlined text-[20px]">arrow_forward</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-white/10">
                <div class="flex flex-col items-center justify-center bg-surface-container/50 border border-white/5 rounded-xl py-3 hover:bg-white/5 transition-colors">
                    <span class="text-2xl font-black text-on-surface"><?php echo shortNumber($stats['following']); ?></span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-1">Takip</span>
                </div>
                <div class="flex flex-col items-center justify-center bg-surface-container/50 border border-white/5 rounded-xl py-3 hover:bg-white/5 transition-colors">
                    <span class="text-2xl font-black text-on-surface"><?php echo shortNumber($stats['followers']); ?></span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-1">Takipçi</span>
                </div>
                <div class="flex flex-col items-center justify-center bg-surface-container/50 border border-white/5 rounded-xl py-3 hover:bg-white/5 transition-colors">
                    <span class="text-2xl font-black text-on-surface"><?php echo shortNumber($stats['checkins']); ?></span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-1">Check-in</span>
                </div>
                <div class="flex flex-col items-center justify-center bg-surface-container/50 border border-white/5 rounded-xl py-3 hover:bg-white/5 transition-colors">
                    <span class="text-2xl font-black text-on-surface"><?php echo shortNumber($stats['venues']); ?></span>
                    <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold mt-1">Mekan</span>
                </div>
            </div>
        </div>
    </div>


    <div class="flex items-center border-b border-white/10 mb-2 mt-4 px-2 overflow-x-auto custom-scrollbar">
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=posts" class="px-6 py-4 font-bold text-sm transition-all border-b-2 whitespace-nowrap <?php echo $tab === 'posts' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Gönderiler</a>
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=journey" class="px-6 py-4 font-bold text-sm transition-all border-b-2 whitespace-nowrap <?php echo $tab === 'journey' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Günlüğüm</a>
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=likes" class="px-6 py-4 font-bold text-sm transition-all border-b-2 whitespace-nowrap <?php echo $tab === 'likes' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Beğeniler</a>
        <a href="?u=<?php echo escape($profileUser['tag'] ?: $profileUser['username']); ?>&tab=reposts" class="px-6 py-4 font-bold text-sm transition-all border-b-2 whitespace-nowrap <?php echo $tab === 'reposts' ? 'text-primary-container border-primary-container' : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">Paylaşımlar</a>
    </div>

    <!-- Content -->
    <div class="flex flex-col gap-stack-md pb-container-padding">

        <?php if ($tab === 'journey'): ?>
        <!-- ── Gezi Günlüğü ────────────────────────────── -->

        <?php if ($stats['checkins'] === 0): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-10 text-center text-slate-400 mt-4">
                <span class="material-symbols-outlined text-[48px] mb-3 opacity-50 block">explore</span>
                <p class="text-lg font-semibold mb-1">Henüz hiç check-in yok</p>
                <p class="text-sm">Check-in yaparak kişisel gezi günlüğünü oluştur!</p>
            </div>
        <?php else: ?>

        <!-- İstatistik Kartları -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 flex flex-col items-center justify-center gap-1 hover:border-white/20 transition-colors">
                <span class="material-symbols-outlined text-primary-container text-[28px]">edit_note</span>
                <span class="text-2xl font-black text-on-surface"><?php echo shortNumber($stats['checkins']); ?></span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Toplam Check-in</span>
            </div>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 flex flex-col items-center justify-center gap-1 hover:border-white/20 transition-colors">
                <span class="material-symbols-outlined text-emerald-400 text-[28px]">location_on</span>
                <span class="text-2xl font-black text-on-surface"><?php echo shortNumber($journey['unique_venues']); ?></span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Keşfedilen Mekan</span>
            </div>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 flex flex-col items-center justify-center gap-1 hover:border-white/20 transition-colors">
                <span class="material-symbols-outlined text-blue-400 text-[28px]">calendar_month</span>
                <span class="text-2xl font-black text-on-surface"><?php echo $journey['this_month']; ?></span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Bu Ay</span>
            </div>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-5 flex flex-col items-center justify-center gap-1 hover:border-white/20 transition-colors">
                <span class="material-symbols-outlined text-amber-400 text-[28px]">local_fire_department</span>
                <span class="text-2xl font-black text-on-surface"><?php echo $journey['last_7_days']; ?></span>
                <span class="text-[10px] text-slate-400 uppercase tracking-widest font-semibold">Son 7 Gün</span>
            </div>
        </div>

        <!-- En Çok Gidilen Mekan + Kategori -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <?php if ($favVenue): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-primary-container/20 rounded-xl p-5 flex items-center gap-4 hover:border-primary-container/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-primary-container/15 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-primary-container text-[24px]">star</span>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mb-1">En Çok Gidilen Mekan</div>
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $favVenue['id']; ?>" class="font-black text-on-surface hover:text-primary-container transition-colors truncate block"><?php echo escape($favVenue['name']); ?></a>
                    <span class="text-xs text-primary-container font-semibold"><?php echo (int)$favVenue['cnt']; ?> ziyaret</span>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($journey['top_category']): ?>
            <?php
                $catKey = $journey['top_category']['category'];
                $catLabel = $categoryLabels[$catKey] ?? ucfirst($catKey);
                $catIcons = [
                    'restoran'  => 'restaurant',
                    'kafe'      => 'local_cafe',
                    'bar'       => 'local_bar',
                    'otel'      => 'hotel',
                    'alisveris' => 'shopping_bag',
                    'eglence'   => 'celebration',
                    'spor'      => 'fitness_center',
                    'saglik'    => 'spa',
                    'kultur'    => 'museum',
                    'diger'     => 'place',
                ];
                $catIcon = $catIcons[$catKey] ?? 'category';
            ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-purple-500/20 rounded-xl p-5 flex items-center gap-4 hover:border-purple-500/40 transition-colors">
                <div class="w-12 h-12 rounded-xl bg-purple-500/15 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-purple-400 text-[24px]"><?php echo $catIcon; ?></span>
                </div>
                <div>
                    <div class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mb-1">En Sevdiğin Tür</div>
                    <div class="font-black text-on-surface"><?php echo escape($catLabel); ?></div>
                    <span class="text-xs text-purple-400 font-semibold"><?php echo (int)$journey['top_category']['cnt']; ?> check-in</span>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Kategori Dağılımı -->
        <?php if (!empty($journey['category_breakdown'])): ?>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6">
            <h3 class="text-base font-bold text-on-surface mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px] text-slate-400">donut_large</span>
                Mekan Türü Dağılımı
            </h3>
            <?php
                $totalCats = array_sum(array_column($journey['category_breakdown'], 'cnt'));
            ?>
            <div class="space-y-3">
                <?php foreach ($journey['category_breakdown'] as $cat):
                    $pct = $totalCats > 0 ? round(($cat['cnt'] / $totalCats) * 100) : 0;
                    $label = $categoryLabels[$cat['category']] ?? ucfirst($cat['category']);
                    $barColors = ['bg-primary-container','bg-purple-500','bg-blue-500','bg-emerald-500','bg-amber-500'];
                    $colorIdx = array_search($cat, $journey['category_breakdown']);
                    $barColor = $barColors[$colorIdx % count($barColors)];
                ?>
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-semibold text-on-surface"><?php echo escape($label); ?></span>
                        <span class="text-xs text-slate-400"><?php echo $cat['cnt']; ?> ziyaret · %<?php echo $pct; ?></span>
                    </div>
                    <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                        <div class="h-full <?php echo $barColor; ?>/70 rounded-full transition-all duration-700" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Son Ziyaretler -->
        <?php if (!empty($journey['recent_venues'])): ?>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden">
            <div class="px-6 py-4 border-b border-white/5">
                <h3 class="text-base font-bold text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-[18px] text-slate-400">history</span>
                    Son Ziyaretler
                </h3>
            </div>
            <div class="divide-y divide-white/5">
                <?php foreach ($journey['recent_venues'] as $rv):
                    $rvCat = $categoryLabels[$rv['category']] ?? ucfirst($rv['category'] ?: 'Mekan');
                ?>
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $rv['id']; ?>" class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.03] transition-colors group">
                    <!-- Thumbnail -->
                    <div class="w-12 h-12 rounded-xl overflow-hidden flex-shrink-0 bg-white/5">
                        <?php if ($rv['image']): ?>
                            <img src="<?php echo escapeUrl(uploadUrl('venues', $rv['image'])); ?>" alt="" class="w-full h-full object-cover" loading="lazy">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-slate-600 text-[20px]">location_on</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Info -->
                    <div class="flex-grow min-w-0">
                        <div class="font-semibold text-on-surface group-hover:text-primary-container transition-colors truncate"><?php echo escape($rv['name']); ?></div>
                        <div class="text-xs text-slate-500 flex items-center gap-2 mt-0.5">
                            <span><?php echo escape($rvCat); ?></span>
                            <span class="text-slate-700">·</span>
                            <span><?php echo (int)$rv['visit_count']; ?>× ziyaret</span>
                        </div>
                    </div>
                    <!-- Son Ziyaret -->
                    <div class="text-xs text-slate-500 flex-shrink-0 text-right">
                        <span class="material-symbols-outlined text-[14px] align-middle mr-0.5">schedule</span>
                        <?php echo timeAgo($rv['last_visit']); ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php endif; // stats['checkins'] === 0 ?>
        <!-- ── /Gezi Günlüğü ────────────────────────────── -->

        <?php else: /* posts, likes, reposts tabs */ ?>

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

        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
