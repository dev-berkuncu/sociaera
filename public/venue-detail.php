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

    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl overflow-hidden shadow-[0_20px_40px_-15px_rgba(15,23,42,0.5)] mb-6 relative">
        <!-- Banner Image -->
        <div class="h-64 md:h-80 w-full bg-surface-container relative">
            <div class="absolute inset-0 bg-gradient-to-t from-[#1E293B]/90 via-[#1E293B]/40 to-transparent z-10"></div>
            <?php if (!empty($venue['cover_image'])): ?>
                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>" class="w-full h-full object-cover">
            <?php elseif (!empty($venue['image'])): ?>
                <img src="<?php echo uploadUrl('posts', $venue['image']); ?>" class="w-full h-full object-cover">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center text-slate-600 bg-surface-container-high"><span class="material-symbols-outlined text-[64px]">store</span></div>
            <?php endif; ?>
            
            <!-- Category Badge overlaying banner -->
            <?php if ($venue['category']): ?>
            <div class="absolute top-4 left-4 z-20">
                <span class="bg-black/60 backdrop-blur text-white text-xs font-bold px-3 py-1.5 rounded-full border border-white/20 uppercase tracking-wider shadow-lg">
                    <?php echo escape(VenueModel::categories()[$venue['category']] ?? $venue['category']); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <!-- Open/Close Badge -->
            <?php if (isset($venue['is_open'])): ?>
            <div class="absolute top-4 right-4 z-20 flex items-center gap-2 bg-black/60 backdrop-blur text-[12px] font-bold px-3 py-1.5 rounded-full border border-white/20 shadow-lg <?php echo $venue['is_open'] ? 'text-emerald-400' : 'text-red-400'; ?>">
                <span class="w-2 h-2 rounded-full animate-pulse <?php echo $venue['is_open'] ? 'bg-emerald-400 shadow-[0_0_8px_rgba(16,185,129,0.8)]' : 'bg-red-400 shadow-[0_0_8px_rgba(239,68,68,0.8)]'; ?>"></span>
                <?php echo $venue['is_open'] ? 'Şu An Açık' : 'Şu An Kapalı'; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Venue Content -->
        <div class="p-6 md:p-8 relative z-20 -mt-20">
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-4xl md:text-5xl font-black text-white drop-shadow-md tracking-tight"><?php echo escape($venue['name']); ?></h1>
                </div>
                <!-- Call to action button -->
                <a href="<?php echo BASE_URL; ?>/?venue_id=<?php echo $venue['id']; ?>" class="flex items-center justify-center gap-2 bg-primary-container text-white px-6 py-3 rounded-xl font-bold hover:bg-primary-container/90 transition-all shadow-[0_0_20px_rgba(255,107,53,0.3)] active:scale-95 group shrink-0">
                    <span class="material-symbols-outlined text-[20px] group-hover:scale-110 transition-transform">pin_drop</span>
                    Burada Check-in Yap
                </a>
            </div>

            <?php if ($venue['description']): ?>
                <p class="text-slate-300 mb-8 leading-relaxed font-body-md text-lg max-w-2xl"><?php echo nl2brSafe($venue['description']); ?></p>
            <?php endif; ?>
            
            <!-- Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                <div class="flex flex-col gap-4 bg-white/5 border border-white/10 rounded-xl p-5">
                    <?php if ($venue['address']): ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-container shrink-0 mt-0.5">map</span> 
                            <span class="text-slate-300"><?php echo escape($venue['address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($venue['hours'])): ?>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-container shrink-0">schedule</span> 
                            <span class="text-slate-300"><?php echo escape($venue['hours']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($venue['phone'])): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-primary-container shrink-0">call</span> 
                            <span class="text-slate-300"><?php echo escape($venue['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col gap-4 bg-white/5 border border-white/10 rounded-xl p-5">
                    <?php if ($venue['website']): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-[#3b82f6]">language</span> 
                            <a href="<?php echo escape($venue['website']); ?>" target="_blank" class="text-slate-300 hover:text-[#3b82f6] transition-colors truncate">Resmi Web Sitesi</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($venue['facebrowser_url']): ?>
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[24px] text-[#3b5998]">link</span> 
                            <a href="<?php echo escape($venue['facebrowser_url']); ?>" target="_blank" class="text-slate-300 hover:text-[#3b5998] transition-colors truncate">Facebrowser Sayfası</a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Stats in the grid -->
                    <div class="flex items-center gap-3 mt-auto pt-2">
                        <span class="material-symbols-outlined text-[24px] text-emerald-400">verified_user</span> 
                        <span class="text-slate-300"><strong class="text-white text-lg"><?php echo $checkinCount; ?></strong> Toplam Check-in</span>
                    </div>
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
