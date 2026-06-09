<?php
/**
 * Sociaera — Dashboard (Swarm-style)
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
if (!$currentUser) { Auth::logout(); header('Location: ' . BASE_URL . '/login'); exit; }

$stats = $userModel->getStats(Auth::id());

// Streak & haftalık
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

// Arkadaş aktivitesi (takip ettiklerinin son check-in'leri)
$friendActivity = [];
try {
    $db   = Database::getConnection();
    $stmt = $db->prepare("
        SELECT c.id, c.note, c.created_at,
               u.username, u.avatar, u.tag,
               v.name as venue_name, v.id as venue_id, v.category as venue_category
        FROM checkins c
        JOIN users u ON c.user_id = u.id
        JOIN venues v ON c.venue_id = v.id
        JOIN user_follows f ON f.following_id = c.user_id AND f.follower_id = ?
        WHERE c.is_deleted = 0
        ORDER BY c.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([Auth::id()]);
    $friendActivity = $stmt->fetchAll();
} catch (Exception $e) {}

// Kullanıcının son check-in'leri
$recentCheckins = [];
try {
    $recentCheckins = $checkinModel->getUserCheckins(Auth::id(), 1, 4, Auth::id());
} catch (Exception $e) {}

// Trend mekanlar
$trendVenues = [];
try { $trendVenues = $venueModel->getTrending(6); } catch (Exception $e) {}

$userLevel  = floor(($stats['checkins'] ?? 0) / 15) + 1;
$categories = VenueModel::categories();

// Kategori meta
$categoryMeta = [
    'restoran'  => ['icon' => 'restaurant',     'color' => '#ff6b35'],
    'kafe'      => ['icon' => 'local_cafe',      'color' => '#c47c4a'],
    'bar'       => ['icon' => 'sports_bar',      'color' => '#f59e0b'],
    'otel'      => ['icon' => 'hotel',           'color' => '#6366f1'],
    'alisveris' => ['icon' => 'shopping_bag',    'color' => '#3b82f6'],
    'eglence'   => ['icon' => 'theaters',        'color' => '#8b5cf6'],
    'spor'      => ['icon' => 'fitness_center',  'color' => '#ef4444'],
    'saglik'    => ['icon' => 'spa',             'color' => '#ec4899'],
    'kultur'    => ['icon' => 'museum',          'color' => '#14b8a6'],
    'diger'     => ['icon' => 'place',           'color' => '#64748b'],
];

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-8 pb-8 min-w-0">

    <!-- ── HERO: Check-in CTA + İstatistikler ── -->
    <div class="relative overflow-hidden rounded-2xl border border-white/8 p-6 md:p-8"
         style="background:linear-gradient(135deg,rgba(255,145,0,0.08) 0%,rgba(30,30,31,0.9) 60%);">
        <div class="absolute top-0 right-0 w-80 h-80 rounded-full blur-3xl pointer-events-none"
             style="background:rgba(255,145,0,0.06);transform:translate(30%,-30%);"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <p class="text-[11px] text-on-surface-variant font-mono uppercase tracking-widest mb-1">Hoş geldin 👋</p>
                <h1 class="text-2xl md:text-3xl font-black text-on-surface tracking-tight mb-5">
                    <?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>
                </h1>
                <div class="flex flex-wrap gap-2.5">
                    <div class="stat-pill">
                        <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings:'FILL' 1;">location_on</span>
                        <span class="font-bold"><?php echo (int)($stats['checkins'] ?? 0); ?></span>
                        <span class="text-on-surface-variant">Check-in</span>
                    </div>
                    <?php if ($streak > 0): ?>
                    <div class="stat-pill" style="border-color:rgba(255,145,0,0.25);">
                        <span class="material-symbols-outlined text-[#ff9100] text-sm streak-pulse" style="font-variation-settings:'FILL' 1;">local_fire_department</span>
                        <span class="font-bold"><?php echo $streak; ?> Gün</span>
                        <span style="color:#ff9100;opacity:0.7;">Seri</span>
                    </div>
                    <?php endif; ?>
                    <div class="stat-pill">
                        <span class="material-symbols-outlined text-emerald-400 text-sm" style="font-variation-settings:'FILL' 1;">calendar_today</span>
                        <span class="font-bold"><?php echo min(5, $weeklyCheckins); ?>/5</span>
                        <span class="text-on-surface-variant">Bu Hafta</span>
                    </div>
                    <div class="stat-pill">
                        <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings:'FILL' 1;">military_tech</span>
                        <span class="font-bold">Seviye <?php echo $userLevel; ?></span>
                    </div>
                </div>
            </div>

            <a href="<?php echo BASE_URL; ?>/venues" id="hero-checkin-btn"
               class="group flex items-center gap-3 text-white font-bold px-7 py-4 rounded-2xl transition-all active:scale-95 shrink-0 self-start sm:self-auto"
               style="background:linear-gradient(135deg,#ff9100,#ff6b00);box-shadow:0 0 30px rgba(255,145,0,0.3);">
                <span class="material-symbols-outlined text-xl group-hover:scale-110 transition-transform" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                Check-in Yap
                <span class="material-symbols-outlined text-base opacity-80 group-hover:translate-x-1 transition-transform">arrow_forward</span>
            </a>
        </div>
    </div>

    <!-- ── ARKADAŞ AKTİVİTESİ ── -->
    <?php if (!empty($friendActivity)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-base" style="font-variation-settings:'FILL' 1;">group</span>
                Arkadaşlar Ne Yapıyor?
            </h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($friendActivity as $fa):
                $faMeta = $categoryMeta[$fa['venue_category'] ?? 'diger'] ?? $categoryMeta['diger'];
                $faAvatar = safeAvatarUrl($fa['avatar'] ?? null, $fa['username'] ?? 'U');
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$fa['venue_id']; ?>"
               class="group flex items-center gap-3 p-3.5 rounded-xl border border-white/5 bg-surface-container/50 hover:bg-surface-container hover:border-white/10 transition-all">
                <!-- Avatar + check-in pin -->
                <div class="relative flex-shrink-0">
                    <img src="<?php echo $faAvatar; ?>" alt=""
                         class="w-10 h-10 rounded-full object-cover border-2 border-white/10 group-hover:border-primary/30 transition-colors" width="40" height="40">
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-[#131314] flex items-center justify-center"
                         style="background:<?php echo $faMeta['color']; ?>;">
                        <span class="material-symbols-outlined text-white" style="font-size:10px;font-variation-settings:'FILL' 1;"><?php echo $faMeta['icon']; ?></span>
                    </div>
                </div>
                <!-- İçerik -->
                <div class="flex-grow min-w-0">
                    <div class="text-sm font-semibold text-on-surface truncate group-hover:text-primary transition-colors">
                        <span class="text-on-surface-variant font-normal">@<?php echo escape($fa['tag'] ?: $fa['username']); ?></span>
                        → <?php echo escape($fa['venue_name']); ?>
                    </div>
                    <div class="text-[11px] text-on-surface-variant mt-0.5">
                        <?php echo timeAgo($fa['created_at']); ?>
                        <?php if (!empty($fa['note'])): ?>
                        · <span class="italic"><?php echo escape(mb_strimwidth($fa['note'], 0, 30, '…')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── POPÜLER MEKANLAR ── -->
    <?php if (!empty($trendVenues)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-[#ff9100] text-base" style="font-variation-settings:'FILL' 1;">trending_up</span>
                Bu Hafta Popüler
            </h2>
            <a href="<?php echo BASE_URL; ?>/venues" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold flex items-center gap-0.5">
                Tümü <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>

        <!-- Bento: 1 büyük + küçükler -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <?php foreach ($trendVenues as $idx => $v):
                $meta = $categoryMeta[$v['category'] ?? 'diger'] ?? $categoryMeta['diger'];
                $isHero = ($idx === 0);
                $checkinCount = (int)($v['weekly_checkins'] ?? 0);
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
               class="group rounded-2xl overflow-hidden border border-white/5 bg-surface-container hover:border-white/15 hover:shadow-[0_8px_30px_-8px_rgba(0,0,0,0.5)] transition-all active:scale-[0.98] flex flex-col <?php echo $isHero ? 'md:col-span-2 md:row-span-2' : ''; ?>">

                <div class="relative overflow-hidden bg-surface-container-high <?php echo $isHero ? 'h-44 md:h-56' : 'h-32'; ?>">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center" style="background:<?php echo $meta['color']; ?>18;">
                            <span class="material-symbols-outlined text-5xl" style="color:<?php echo $meta['color']; ?>;font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,0.7) 0%,transparent 60%);"></div>

                    <!-- Kategori ikonu -->
                    <div class="absolute bottom-3 left-3 w-9 h-9 rounded-xl flex items-center justify-center border border-white/20"
                         style="background:<?php echo $meta['color']; ?>ee;backdrop-filter:blur(8px);">
                        <span class="material-symbols-outlined text-white text-[17px]" style="font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                    </div>

                    <!-- Check-in count -->
                    <div class="absolute bottom-3 right-3 flex items-center gap-1 text-white text-[11px] font-bold px-2 py-1 rounded-lg border border-white/15" style="background:rgba(0,0,0,0.55);backdrop-filter:blur(6px);">
                        <span class="material-symbols-outlined text-primary text-[11px]" style="font-variation-settings:'FILL' 1;">location_on</span>
                        <?php echo $checkinCount; ?> bu hafta
                    </div>

                    <!-- Sıra badge -->
                    <?php if ($idx < 3): ?>
                    <div class="absolute top-3 left-3 w-6 h-6 rounded-full flex items-center justify-center font-black text-[11px] border"
                         style="<?php echo $idx === 0 ? 'background:#FFD700;border-color:#FFD700;color:#000;box-shadow:0 0 10px rgba(255,215,0,0.5)' : ($idx === 1 ? 'background:#C0C0C0;border-color:#C0C0C0;color:#000' : 'background:#CD7F32;border-color:#CD7F32;color:#fff'); ?>">
                        <?php echo $idx + 1; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($v['is_open'])): ?>
                    <div class="absolute top-3 right-3 flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-lg border border-white/10 <?php echo $v['is_open'] ? 'text-emerald-400' : 'text-red-400'; ?>" style="background:rgba(0,0,0,0.6);backdrop-filter:blur(6px);">
                        <span class="w-1.5 h-1.5 rounded-full <?php echo $v['is_open'] ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                        <?php echo $v['is_open'] ? 'Açık' : 'Kapalı'; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="p-3.5 flex-grow">
                    <div class="font-bold <?php echo $isHero ? 'text-base' : 'text-sm'; ?> text-on-surface group-hover:text-primary transition-colors truncate">
                        <?php echo escape($v['name']); ?>
                    </div>
                    <div class="text-[11px] font-semibold mt-0.5" style="color:<?php echo $meta['color']; ?>;opacity:0.8;">
                        <?php echo escape($categories[$v['category'] ?? 'diger'] ?? 'Mekan'); ?>
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
            <h2 class="text-sm font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings:'FILL' 1;">history</span>
                Son Check-in'lerim
            </h2>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold flex items-center gap-0.5">
                Tümü <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>

        <?php if (empty($recentCheckins)): ?>
        <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center">
            <span class="material-symbols-outlined text-on-surface-variant text-4xl mb-3 block opacity-25">pin_drop</span>
            <p class="text-sm text-on-surface-variant mb-4">Henüz check-in yapmadın.</p>
            <a href="<?php echo BASE_URL; ?>/venues"
               class="inline-flex items-center gap-2 text-white font-bold px-5 py-2.5 rounded-xl text-sm active:scale-95 transition-all"
               style="background:linear-gradient(135deg,#ff9100,#ff6b00);box-shadow:0 0 15px rgba(255,145,0,0.25);">
                <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                İlk Check-in'ini Yap
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($recentCheckins as $ci):
                $ciMeta   = $categoryMeta[$ci['venue_category'] ?? 'diger'] ?? $categoryMeta['diger'];
                $ciAvatar = safeAvatarUrl($ci['avatar'] ?? null, $ci['username'] ?? 'U');
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)($ci['venue_id'] ?? 0); ?>"
               class="group flex items-center gap-3 p-4 rounded-xl border border-white/5 bg-surface-container/50 hover:bg-surface-container hover:border-white/10 transition-all">
                <div class="relative flex-shrink-0">
                    <img src="<?php echo $ciAvatar; ?>" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-white/10 group-hover:border-primary/30 transition-colors" width="40" height="40">
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-[#131314] flex items-center justify-center"
                         style="background:<?php echo $ciMeta['color']; ?>;">
                        <span class="material-symbols-outlined text-white" style="font-size:9px;font-variation-settings:'FILL' 1;"><?php echo $ciMeta['icon']; ?></span>
                    </div>
                </div>
                <div class="flex-grow min-w-0">
                    <div class="font-semibold text-sm text-on-surface group-hover:text-primary transition-colors truncate">
                        <?php echo escape($ci['venue_name'] ?? 'Mekan'); ?>
                    </div>
                    <div class="text-[11px] text-on-surface-variant mt-0.5 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[11px]">schedule</span>
                        <?php echo timeAgo($ci['created_at']); ?>
                        <?php if (!empty($ci['note'])): ?>
                        · <span class="italic truncate"><?php echo escape(mb_strimwidth($ci['note'], 0, 28, '…')); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="material-symbols-outlined text-on-surface-variant group-hover:text-primary transition-colors flex-shrink-0">chevron_right</span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</section>

<style>
.stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(0,0,0,0.35);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 13px;
    color: #e5e2e3;
}
</style>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
