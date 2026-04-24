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

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = $venue['name'];
$activeNav = 'venues';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-3xl w-full mx-auto lg:mx-0">
    <a href="<?php echo BASE_URL; ?>/venues" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors w-fit mb-2">
        <span class="material-symbols-outlined text-[20px]">arrow_back</span> Mekanlar
    </a>

    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)] mb-4">
        <div class="h-48 md:h-64 bg-surface-container relative">
            <?php if (!empty($venue['image'])): ?>
                <img src="<?php echo uploadUrl('posts', $venue['image']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-slate-600 bg-surface-container-high"><span class="material-symbols-outlined text-[64px]">store</span></div>
            <?php endif; ?>
        </div>
        <div class="p-6 md:p-8">
            <?php if ($venue['category']): ?>
                <span class="inline-block bg-primary-container/20 text-primary-container border border-primary-container/30 text-xs font-bold px-3 py-1 rounded-full mb-3 uppercase tracking-wider"><?php echo escape(VenueModel::categories()[$venue['category']] ?? $venue['category']); ?></span>
            <?php endif; ?>
            <h1 class="text-3xl font-bold text-on-surface mb-4"><?php echo escape($venue['name']); ?></h1>
            <?php if ($venue['description']): ?>
                <p class="text-slate-300 mb-6 leading-relaxed"><?php echo nl2brSafe($venue['description']); ?></p>
            <?php endif; ?>
            
            <div class="flex flex-col gap-3 text-sm text-slate-400 mb-6">
                <?php if ($venue['address']): ?>
                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary-container shrink-0">pin_drop</span> 
                        <span><?php echo escape($venue['address']); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($venue['website']): ?>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-primary-container">language</span> 
                        <a href="<?php echo escape($venue['website']); ?>" target="_blank" class="hover:text-primary-container transition-colors truncate"><?php echo escape($venue['website']); ?></a>
                    </div>
                <?php endif; ?>
                <?php if ($venue['facebrowser_url']): ?>
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-[20px] text-[#3b5998]">link</span> 
                        <a href="<?php echo escape($venue['facebrowser_url']); ?>" target="_blank" class="hover:text-[#3b5998] transition-colors truncate">Facebrowser</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="pt-6 border-t border-white/10 flex gap-8">
                <div class="flex flex-col">
                    <span class="text-2xl font-black text-primary-container"><?php echo $checkinCount; ?></span>
                    <span class="text-xs text-slate-400 uppercase tracking-widest font-semibold">Check-in</span>
                </div>
            </div>
        </div>
    </div>

    <h2 class="text-xl font-bold text-on-surface mb-2 mt-4 flex items-center gap-2"><span class="material-symbols-outlined text-primary-container">history</span> Son Check-in'ler</h2>

    <div class="flex flex-col gap-stack-md pb-container-padding">
        <?php if (empty($posts)): ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-8 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">pin_drop</span>
                <p>Bu mekanda henüz check-in yok.</p>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php include __DIR__ . '/partials/_tailwind_post_card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
