<?php
/**
 * Sociaera — Dashboard (Web Keşif Paneli)
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$userModel    = new UserModel();
$checkinModel = new CheckinModel();
$venueModel   = new VenueModel();

$currentUser = $userModel->getById(Auth::id());
if (!$currentUser) {
    Auth::logout();
    header('Location: ' . BASE_URL . '/login');
    exit;
}

$stats = $userModel->getStats(Auth::id());

$streak         = 0;
$weeklyCheckins = 0;
try {
    $db   = Database::getConnection();
    $stmt = $db->prepare("SELECT DISTINCT DATE(created_at) as d FROM checkins WHERE user_id = ? AND is_deleted = 0 ORDER BY d DESC LIMIT 60");
    $stmt->execute([Auth::id()]);
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $today = new DateTime();
    foreach ($dates as $i => $d) {
        $expected = (clone $today)->modify("-{$i} days")->format('Y-m-d');
        if ($d === $expected) { $streak++; } else { break; }
    }
    $weeklyCheckins = $checkinModel->getWeeklyCheckinCount(Auth::id());
} catch (Exception $e) {}

$recentCheckins = [];
try {
    $recentCheckins = $checkinModel->getUserCheckins(Auth::id(), 1, 4, Auth::id());
} catch (Exception $e) {}

$trendVenues = [];
try {
    $trendVenues = $venueModel->getTrending(6);
} catch (Exception $e) {}

$userLevel  = floor(($stats['checkins'] ?? 0) / 15) + 1;
$categories = VenueModel::categories();

$pageTitle = 'Keşfet';
$activeNav = 'dashboard';

require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-8 pb-8 min-w-0">

    <!-- ── HERO WELCOME BAR ── -->
    <div class="relative overflow-hidden rounded-2xl border border-white/8 bg-gradient-to-br from-surface-container via-[#1a1a1b] to-[#131314] p-6 md:p-8">
        <!-- Dekoratif arka plan -->
        <div class="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-tertiary/5 rounded-full blur-2xl translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <!-- Sol: Selamlama + İstatistikler -->
            <div>
                <p class="text-on-surface-variant text-sm mb-1 font-mono uppercase tracking-widest">Hoş geldin 👋</p>
                <h1 class="text-2xl md:text-3xl font-black text-on-surface tracking-tight mb-4">
                    <?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>
                </h1>

                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-2 bg-black/30 border border-white/8 rounded-full px-3 py-1.5">
                        <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings:'FILL' 1;">location_on</span>
                        <span class="text-sm font-bold text-on-surface"><?php echo (int)($stats['checkins'] ?? 0); ?></span>
                        <span class="text-xs text-on-surface-variant">Check-in</span>
                    </div>
                    <?php if ($streak > 0): ?>
                    <div class="flex items-center gap-2 bg-black/30 border border-[#ff9100]/20 rounded-full px-3 py-1.5">
                        <span class="material-symbols-outlined text-[#ff9100] text-sm streak-pulse" style="font-variation-settings:'FILL' 1;">local_fire_department</span>
                        <span class="text-sm font-bold text-on-surface"><?php echo $streak; ?> Gün</span>
                        <span class="text-xs text-[#ff9100]/70">Seri</span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 bg-black/30 border border-white/8 rounded-full px-3 py-1.5">
                        <span class="material-symbols-outlined text-emerald-400 text-sm" style="font-variation-settings:'FILL' 1;">calendar_today</span>
                        <span class="text-sm font-bold text-on-surface"><?php echo min(5, $weeklyCheckins); ?>/5</span>
                        <span class="text-xs text-on-surface-variant">Bu Hafta</span>
                    </div>
                    <div class="flex items-center gap-2 bg-black/30 border border-white/8 rounded-full px-3 py-1.5">
                        <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings:'FILL' 1;">military_tech</span>
                        <span class="text-sm font-bold text-on-surface">Seviye <?php echo $userLevel; ?></span>
                    </div>
                </div>
            </div>

            <!-- Sağ: CTA Butonu -->
            <a href="<?php echo BASE_URL; ?>/venues" id="hero-checkin-btn"
               class="group flex items-center gap-3 bg-primary-container text-white font-bold px-6 py-4 rounded-xl hover:brightness-110 transition-all shadow-[0_0_30px_rgba(255,145,0,0.25)] active:scale-95 shrink-0 self-start md:self-auto">
                <span class="material-symbols-outlined text-xl group-hover:scale-110 transition-transform" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                <span>Check-in Yap</span>
                <span class="material-symbols-outlined text-base opacity-70 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </a>
        </div>
    </div>

    <!-- ── POPÜLER MEKANLAR GRID ── -->
    <?php if (!empty($trendVenues)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-[18px]" style="font-variation-settings:'FILL' 1;">trending_up</span>
                Popüler Mekanlar
            </h2>
            <a href="<?php echo BASE_URL; ?>/venues" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold flex items-center gap-1">
                Tüm Mekanlar <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <!-- Bento Grid: büyük + küçükler -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <?php foreach ($trendVenues as $idx => $v): ?>
            <?php
                $vRating = 0;
                try {
                    $rd = $venueModel->getVenueRating($v['id']);
                    $vRating = round((float)($rd['average_rating'] ?? 0), 1);
                } catch (Exception $e) {}
                $isOpen  = $v['is_open'] ?? null;
                $catName = $categories[$v['category'] ?? 'diger'] ?? 'Mekan';
                $checkinCount = (int)($v['weekly_checkins'] ?? $v['checkin_count'] ?? 0);
                // İlk kart daha büyük
                $isHero = ($idx === 0);
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
               class="group rounded-xl overflow-hidden border border-white/5 bg-surface-container hover:border-primary/30 hover:shadow-[0_8px_30px_-8px_rgba(255,145,0,0.12)] transition-all active:scale-[0.99] flex flex-col <?php echo $isHero ? 'md:col-span-2 md:row-span-2' : ''; ?>">

                <!-- Görsel -->
                <div class="relative overflow-hidden bg-surface-container-high <?php echo $isHero ? 'h-48 md:h-64' : 'h-36'; ?>">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-surface-container-high group-hover:scale-105 transition-transform duration-500">
                            <span class="material-symbols-outlined text-on-surface-variant text-4xl">storefront</span>
                        </div>
                    <?php endif; ?>

                    <!-- Overlay gradient -->
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex gap-1.5">
                        <span class="bg-black/60 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-md border border-white/10">
                            <?php echo escape($catName); ?>
                        </span>
                    </div>
                    <?php if ($isOpen !== null): ?>
                    <div class="absolute top-3 right-3">
                        <span class="flex items-center gap-1 bg-black/60 backdrop-blur text-[10px] font-bold px-2 py-1 rounded-md border border-white/10 <?php echo $isOpen ? 'text-emerald-400' : 'text-red-400'; ?>">
                            <span class="w-1.5 h-1.5 rounded-full <?php echo $isOpen ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                            <?php echo $isOpen ? 'Açık' : 'Kapalı'; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <?php if ($idx < 3): ?>
                    <div class="absolute bottom-3 left-3 w-7 h-7 rounded-full flex items-center justify-center font-black text-xs border
                        <?php echo $idx === 0 ? 'bg-[#FFD700] border-[#FFD700] text-black shadow-[0_0_10px_rgba(255,215,0,0.6)]' : ($idx === 1 ? 'bg-[#C0C0C0] border-[#C0C0C0] text-black' : 'bg-[#CD7F32] border-[#CD7F32] text-white'); ?>">
                        <?php echo $idx + 1; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Bilgi -->
                <div class="p-4 flex-grow flex flex-col gap-1">
                    <div class="font-bold <?php echo $isHero ? 'text-lg' : 'text-sm'; ?> text-on-surface group-hover:text-primary transition-colors truncate">
                        <?php echo escape($v['name']); ?>
                    </div>
                    <div class="flex items-center gap-3 mt-auto pt-2">
                        <span class="flex items-center gap-1 text-primary text-xs font-semibold">
                            <span class="material-symbols-outlined text-[12px]" style="font-variation-settings:'FILL' 1;">location_on</span>
                            <?php echo $checkinCount; ?> check-in
                        </span>
                        <?php if ($vRating > 0): ?>
                        <span class="flex items-center gap-1 text-amber-400 text-xs font-semibold">
                            <span class="material-symbols-outlined text-[12px]" style="font-variation-settings:'FILL' 1;">star</span>
                            <?php echo number_format($vRating, 1); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── SON CHECK-İN'LERİM ── -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-[18px]" style="font-variation-settings:'FILL' 1;">history</span>
                Son Check-in'lerim
            </h2>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold flex items-center gap-1">
                Tüm Geçmiş <span class="material-symbols-outlined text-sm">arrow_forward</span>
            </a>
        </div>

        <?php if (empty($recentCheckins)): ?>
        <div class="rounded-xl border border-dashed border-white/10 p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-4xl mb-3 block opacity-30">pin_drop</span>
            <p class="text-sm text-on-surface-variant mb-4">Henüz hiç check-in yapmadın.</p>
            <a href="<?php echo BASE_URL; ?>/venues"
               class="inline-flex items-center gap-2 bg-primary-container text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:brightness-110 transition-all active:scale-95 shadow-[0_0_15px_rgba(255,145,0,0.25)]">
                <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                İlk Check-in'ini Yap
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($recentCheckins as $ci): ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)($ci['venue_id'] ?? 0); ?>"
               class="flex items-center gap-3 p-4 rounded-xl bg-surface-container/50 border border-white/5 hover:bg-surface-container hover:border-primary/20 transition-all group">
                <?php
                    $ciAvatar = safeAvatarUrl($ci['avatar'] ?? null, $ci['username'] ?? 'U');
                ?>
                <img src="<?php echo $ciAvatar; ?>" alt="" class="w-10 h-10 rounded-full object-cover border border-white/10 flex-shrink-0" width="40" height="40">
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-sm text-on-surface group-hover:text-primary transition-colors truncate">
                        <?php echo escape($ci['venue_name'] ?? 'Mekan'); ?>
                    </div>
                    <div class="text-xs text-on-surface-variant mt-0.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[11px]" style="font-variation-settings:'FILL' 1;">schedule</span>
                        <?php echo timeAgo($ci['created_at']); ?>
                        <?php if (!empty($ci['note'])): ?>
                        <span class="text-on-surface-variant/50">·</span>
                        <span class="truncate italic"><?php echo escape(mb_strimwidth($ci['note'], 0, 30, '…')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors text-lg flex-shrink-0">chevron_right</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
