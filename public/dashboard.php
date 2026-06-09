<?php
/**
 * Sociaera — Dashboard (Sade Keşif Paneli)
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

// Son 5 check-in
$recentCheckins = [];
try {
    $recentCheckins = $checkinModel->getUserCheckins(Auth::id(), 1, 5, Auth::id());
} catch (Exception $e) {}

// Trend mekanlar (sadece 3)
$trendVenues = [];
try {
    $trendVenues = $venueModel->getTrending(3);
} catch (Exception $e) {}

$userLevel  = floor(($stats['checkins'] ?? 0) / 15) + 1;
$categories = VenueModel::categories();

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';

require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-6 max-w-2xl w-full pb-8">

    <!-- ── ÖZET BAR ── -->
    <div class="flex items-center gap-3 flex-wrap">
        <div class="flex items-center gap-2 bg-surface-container/60 border border-white/5 rounded-full px-4 py-2">
            <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings:'FILL' 1;">location_on</span>
            <span class="text-sm font-bold text-on-surface"><?php echo (int)($stats['checkins'] ?? 0); ?></span>
            <span class="text-xs text-on-surface-variant">check-in</span>
        </div>
        <?php if ($streak > 0): ?>
        <div class="flex items-center gap-2 bg-surface-container/60 border border-white/5 rounded-full px-4 py-2">
            <span class="material-symbols-outlined text-[#ff9100] text-base streak-pulse" style="font-variation-settings:'FILL' 1;">local_fire_department</span>
            <span class="text-sm font-bold text-on-surface"><?php echo $streak; ?></span>
            <span class="text-xs text-on-surface-variant">günlük seri</span>
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-2 bg-surface-container/60 border border-white/5 rounded-full px-4 py-2">
            <span class="material-symbols-outlined text-emerald-400 text-base" style="font-variation-settings:'FILL' 1;">calendar_today</span>
            <span class="text-sm font-bold text-on-surface"><?php echo min(5, $weeklyCheckins); ?>/5</span>
            <span class="text-xs text-on-surface-variant">bu hafta</span>
        </div>
    </div>

    <!-- ── ANA CTA ── -->
    <a href="<?php echo BASE_URL; ?>/venues" id="main-checkin-cta"
       class="group relative overflow-hidden rounded-2xl border border-primary/20 bg-gradient-to-br from-[#ff9100]/10 via-surface-container to-surface-container p-6 flex items-center gap-5 hover:border-primary/50 hover:shadow-[0_0_40px_rgba(255,145,0,0.1)] transition-all active:scale-[0.99]">
        <div class="absolute inset-0 bg-gradient-to-r from-[#ff9100]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        <div class="w-14 h-14 rounded-2xl bg-primary-container flex items-center justify-center flex-shrink-0 shadow-[0_0_20px_rgba(255,145,0,0.3)] group-hover:scale-110 transition-transform duration-300 relative z-10">
            <span class="material-symbols-outlined text-white text-2xl" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
        </div>
        <div class="relative z-10">
            <div class="font-bold text-lg text-on-surface group-hover:text-primary transition-colors">Check-in Yap</div>
            <div class="text-sm text-on-surface-variant mt-0.5">Mekan seç, check-in yap, ödül kazan</div>
        </div>
        <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary ml-auto relative z-10 transition-colors group-hover:translate-x-1 transition-transform duration-200">chevron_right</span>
    </a>

    <!-- ── POPÜLER MEKANLAR ── -->
    <?php if (!empty($trendVenues)): ?>
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-on-surface-variant uppercase tracking-widest font-mono">Popüler Mekanlar</h2>
            <a href="<?php echo BASE_URL; ?>/venues" class="text-xs text-primary hover:underline font-semibold">Tümünü Gör →</a>
        </div>
        <div class="flex flex-col gap-2">
            <?php foreach ($trendVenues as $idx => $v): ?>
            <?php
                $vRating = 0;
                try {
                    $rd = $venueModel->getVenueRating($v['id']);
                    $vRating = round((float)($rd['average_rating'] ?? 0), 1);
                } catch (Exception $e) {}
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
               class="flex items-center gap-4 p-4 rounded-xl bg-surface-container/50 border border-white/5 hover:bg-surface-container hover:border-primary/20 transition-all group active:scale-[0.99]">

                <!-- Rank -->
                <div class="w-6 text-center font-black text-sm <?php echo $idx === 0 ? 'text-[#FFD700]' : ($idx === 1 ? 'text-slate-400' : 'text-[#CD7F32]'); ?>"><?php echo $idx + 1; ?></div>

                <!-- Görsel -->
                <div class="w-12 h-12 rounded-xl overflow-hidden bg-surface-container-high flex-shrink-0">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>" class="w-full h-full object-cover" loading="lazy">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>" class="w-full h-full object-cover" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center">
                            <span class="material-symbols-outlined text-on-surface-variant text-xl">storefront</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Bilgi -->
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-sm text-on-surface group-hover:text-primary transition-colors truncate"><?php echo escape($v['name']); ?></div>
                    <div class="text-xs text-on-surface-variant mt-0.5">
                        <?php echo escape($categories[$v['category'] ?? 'diger'] ?? 'Mekan'); ?>
                        <?php if ($vRating > 0): ?> · <span class="text-amber-400">★ <?php echo number_format($vRating, 1); ?></span><?php endif; ?>
                    </div>
                </div>

                <!-- Check-in sayısı -->
                <div class="text-right flex-shrink-0">
                    <div class="text-sm font-bold text-primary"><?php echo (int)($v['weekly_checkins'] ?? $v['checkin_count'] ?? 0); ?></div>
                    <div class="text-[10px] text-on-surface-variant">check-in</div>
                </div>

                <span class="material-symbols-outlined text-on-surface-variant text-lg group-hover:text-primary transition-colors">chevron_right</span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── SON CHECK-İN'LERİM ── -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-bold text-on-surface-variant uppercase tracking-widest font-mono">Son Check-in'lerim</h2>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-xs text-primary hover:underline font-semibold">Tümü →</a>
        </div>

        <?php if (empty($recentCheckins)): ?>
        <div class="p-6 rounded-xl bg-surface-container/50 border border-white/5 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-3xl mb-2 block opacity-40">history</span>
            <p class="text-sm text-on-surface-variant">Henüz check-in yapmadın.</p>
            <a href="<?php echo BASE_URL; ?>/venues" class="mt-3 inline-flex items-center gap-1.5 text-primary text-sm font-semibold hover:underline">
                <span class="material-symbols-outlined text-base">add_location_alt</span> İlk check-in'ini yap
            </a>
        </div>
        <?php else: ?>
        <div class="rounded-xl bg-surface-container/50 border border-white/5 overflow-hidden divide-y divide-white/5">
            <?php foreach ($recentCheckins as $ci): ?>
            <?php include __DIR__ . '/partials/_checkin_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
