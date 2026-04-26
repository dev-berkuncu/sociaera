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

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Mekanlar';
$activeNav = 'venues';

require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-4xl w-full mx-auto lg:mx-0">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <h1 class="text-2xl font-bold flex items-center gap-2"><span class="material-symbols-outlined text-primary-container text-[28px]">location_on</span> Mekanlar</h1>
        <a href="<?php echo BASE_URL; ?>/add-venue" class="bg-primary-container text-white px-4 py-2 rounded-lg font-label-md text-label-md shadow-[0_0_10px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-all flex items-center gap-2 active:scale-95"><span class="material-symbols-outlined text-[18px]">add</span> Mekan Ekle</a>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-4 mb-2">
        <form method="GET" class="flex-1 relative group">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-primary-container transition-colors">search</span>
            <input type="text" name="q" placeholder="Mekan ara..." value="<?php echo escape($search); ?>" class="w-full bg-[#1E293B]/80 border border-white/10 rounded-lg pl-10 pr-4 py-2 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
            <?php if ($category): ?><input type="hidden" name="cat" value="<?php echo escape($category); ?>"><?php endif; ?>
        </form>
        <div class="flex gap-2 flex-wrap items-center">
            <a href="?cat=&q=<?php echo urlencode($search); ?>" class="px-4 py-2 rounded-lg font-label-sm text-label-sm transition-colors border border-white/10 <?php echo !$category ? 'bg-primary-container/20 text-primary-container border-primary-container/50' : 'bg-[#1E293B]/80 text-slate-300 hover:bg-white/5'; ?>">Tümü</a>
            <?php foreach ($categories as $key => $label): ?>
                <a href="?cat=<?php echo urlencode($key); ?>&q=<?php echo urlencode($search); ?>" class="px-4 py-2 rounded-lg font-label-sm text-label-sm transition-colors border border-white/10 <?php echo $category === $key ? 'bg-primary-container/20 text-primary-container border-primary-container/50' : 'bg-[#1E293B]/80 text-slate-300 hover:bg-white/5'; ?>"><?php echo escape($label); ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($venues)): ?>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-8 text-center text-slate-400 mt-4">
            <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">location_off</span>
            <p>Mekan bulunamadı.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-6 mt-4">
            <?php foreach ($venues as $v): ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>" class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden hover:border-primary-container/50 hover:shadow-[0_10px_30px_-10px_rgba(255,107,53,0.15)] transition-all group">
                <div class="h-48 bg-surface-container relative overflow-hidden">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-slate-600 bg-surface-container-high group-hover:scale-105 transition-transform duration-500"><span class="material-symbols-outlined text-[48px]">store</span></div>
                    <?php endif; ?>
                    <?php if ($v['category']): ?>
                        <span class="absolute top-3 right-3 bg-black/60 backdrop-blur text-white text-[11px] font-bold px-2 py-1 rounded-md border border-white/10"><?php echo escape($categories[$v['category']] ?? $v['category']); ?></span>
                    <?php endif; ?>
                    <!-- Açık/Kapalı badge -->
                    <?php if (isset($v['is_open'])): ?>
                    <span class="absolute top-3 left-3 flex items-center gap-1.5 bg-black/60 backdrop-blur text-[11px] font-bold px-2 py-1 rounded-md border border-white/10 <?php echo $v['is_open'] ? 'text-emerald-400' : 'text-red-400'; ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?php echo $v['is_open'] ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                        <?php echo $v['is_open'] ? 'Açık' : 'Kapalı'; ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="p-5">
                    <div class="font-headline-sm text-xl text-on-surface font-semibold mb-2 truncate group-hover:text-primary-container transition-colors"><?php echo escape($v['name']); ?></div>
                    <?php if (!empty($v['address'])): ?>
                        <div class="flex items-start gap-1.5 text-slate-400 text-sm mb-2">
                            <span class="material-symbols-outlined text-[16px] mt-0.5 shrink-0">pin_drop</span>
                            <span class="line-clamp-2 leading-relaxed"><?php echo escape(truncate($v['address'], 80)); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($v['hours'])): ?>
                        <div class="flex items-center gap-1.5 text-slate-400 text-xs mb-2">
                            <span class="material-symbols-outlined text-[14px]">schedule</span>
                            <?php echo escape($v['hours']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($v['phone'])): ?>
                        <div class="flex items-center gap-1.5 text-slate-400 text-xs mb-3">
                            <span class="material-symbols-outlined text-[14px]">call</span>
                            <?php echo escape($v['phone']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 text-primary-container text-sm bg-primary-container/10 w-fit px-3 py-1.5 rounded-lg border border-primary-container/20">
                        <span class="material-symbols-outlined text-[18px]">verified_user</span>
                        <span class="font-bold"><?php echo (int)($v['checkin_count'] ?? 0); ?></span> check-in
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
