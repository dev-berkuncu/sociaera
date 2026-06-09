<?php
/**
 * Sociaera — Mekanlar (Swarm-style discovery)
 */
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
$search     = trim($_GET['q'] ?? '');
$category   = trim($_GET['cat'] ?? '');

$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 12;
$offset = ($page - 1) * $limit;

$venues      = $venueModel->getApproved($search, $category, $limit, $offset);
$totalVenues = $venueModel->getApprovedCount($search, $category);
$totalPages  = ceil($totalVenues / $limit);
$categories  = VenueModel::categories();

$trendVenues    = [];
$miniLeaderboard = [];
try {
    $trendVenues     = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

// Kategori meta: ikon + renk
$categoryMeta = [
    'restoran'  => ['icon' => 'restaurant',     'color' => '#ff6b35', 'bg' => 'rgba(255,107,53,0.18)'],
    'kafe'      => ['icon' => 'local_cafe',      'color' => '#c47c4a', 'bg' => 'rgba(196,124,74,0.18)'],
    'bar'       => ['icon' => 'sports_bar',      'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.18)'],
    'otel'      => ['icon' => 'hotel',           'color' => '#6366f1', 'bg' => 'rgba(99,102,241,0.18)'],
    'alisveris' => ['icon' => 'shopping_bag',    'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.18)'],
    'eglence'   => ['icon' => 'theaters',        'color' => '#8b5cf6', 'bg' => 'rgba(139,92,246,0.18)'],
    'spor'      => ['icon' => 'fitness_center',  'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.18)'],
    'saglik'    => ['icon' => 'spa',             'color' => '#ec4899', 'bg' => 'rgba(236,72,153,0.18)'],
    'kultur'    => ['icon' => 'museum',          'color' => '#14b8a6', 'bg' => 'rgba(20,184,166,0.18)'],
    'diger'     => ['icon' => 'place',           'color' => '#64748b', 'bg' => 'rgba(100,116,139,0.18)'],
];

$pageTitle = 'Mekanlar';
$activeNav = 'venues';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-6 pb-8 min-w-0">

    <!-- ── BAŞLIK ── -->
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-black text-on-surface">Mekanlar</h1>
        <a href="<?php echo BASE_URL; ?>/add-venue"
           class="flex items-center gap-2 bg-primary-container text-white px-4 py-2 rounded-xl font-bold text-sm hover:brightness-110 transition-all active:scale-95 shadow-[0_0_15px_rgba(255,145,0,0.2)]">
            <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
            Mekan Ekle
        </a>
    </div>

    <!-- ── ARAMA ── -->
    <form method="GET" class="relative">
        <?php if ($category): ?>
        <input type="hidden" name="cat" value="<?php echo escape($category); ?>">
        <?php endif; ?>
        <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant text-lg pointer-events-none">search</span>
        <input type="text" name="q" value="<?php echo escape($search); ?>"
               placeholder="Mekan ara…"
               class="w-full bg-surface-container border border-white/8 rounded-2xl pl-12 pr-4 py-3.5 text-on-surface placeholder:text-on-surface-variant text-sm focus:outline-none focus:border-primary/40 transition-colors shadow-sm">
    </form>

    <!-- ── KATEGORİ PİLLERİ ── -->
    <div class="flex gap-2 overflow-x-auto pb-1" style="-ms-overflow-style:none;scrollbar-width:none;">
        <a href="?q=<?php echo urlencode($search); ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-full whitespace-nowrap font-semibold text-sm border transition-all flex-shrink-0
                  <?php echo !$category ? 'bg-primary-container text-white border-transparent shadow-[0_0_12px_rgba(255,145,0,0.3)]' : 'bg-surface-container border-white/8 text-on-surface-variant hover:border-white/20'; ?>">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;">apps</span>
            Tümü
        </a>
        <?php foreach ($categoryMeta as $key => $meta): ?>
        <?php if (!isset($categories[$key])) continue; ?>
        <a href="?cat=<?php echo urlencode($key); ?>&q=<?php echo urlencode($search); ?>"
           class="flex items-center gap-1.5 px-4 py-2 rounded-full whitespace-nowrap font-semibold text-sm border transition-all flex-shrink-0"
           style="<?php echo $category === $key
               ? "background:{$meta['bg']};border-color:{$meta['color']}40;color:{$meta['color']}"
               : 'background:rgba(255,255,255,0.04);border-color:rgba(255,255,255,0.08);color:#a0a0a0'; ?>">
            <span class="material-symbols-outlined text-sm" style="font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
            <?php echo escape($categories[$key]); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── SONUÇ SAYISI ── -->
    <p class="text-xs text-on-surface-variant font-mono">
        <?php echo $totalVenues; ?> mekan<?php if ($search || $category): ?> bulundu<?php endif; ?>
        <?php if ($search): ?> · "<span class="text-on-surface"><?php echo escape($search); ?></span>" araması<?php endif; ?>
    </p>

    <!-- ── MEKAN GRID ── -->
    <?php if (empty($venues)): ?>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <span class="material-symbols-outlined text-5xl text-on-surface-variant opacity-30 mb-3">location_off</span>
        <p class="text-on-surface-variant text-sm">Mekan bulunamadı.</p>
        <?php if ($search || $category): ?>
        <a href="<?php echo BASE_URL; ?>/venues" class="mt-3 text-primary text-sm font-semibold hover:underline">Filtreleri temizle</a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($venues as $v):
            $meta = $categoryMeta[$v['category'] ?? 'diger'] ?? $categoryMeta['diger'];
            $checkinCount = (int)($v['checkin_count'] ?? 0);
        ?>
        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
           class="group rounded-2xl overflow-hidden border border-white/5 bg-surface-container hover:border-white/15 hover:shadow-[0_8px_40px_-12px_rgba(0,0,0,0.6)] transition-all active:scale-[0.98] flex flex-col">

            <!-- Görsel -->
            <div class="relative overflow-hidden bg-surface-container-high" style="height:160px;">

                <?php if (!empty($v['cover_image'])): ?>
                    <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                <?php elseif (!empty($v['image'])): ?>
                    <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center" style="background:<?php echo $meta['bg']; ?>">
                        <span class="material-symbols-outlined text-5xl" style="color:<?php echo $meta['color']; ?>;font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Gradient overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent"></div>

                <!-- Kategori ikonu (Swarm signature) -->
                <div class="absolute bottom-3 left-3 w-9 h-9 rounded-xl flex items-center justify-center border border-white/20 shadow-lg"
                     style="background:<?php echo $meta['color']; ?>ee;backdrop-filter:blur(8px);">
                    <span class="material-symbols-outlined text-white text-[18px]" style="font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                </div>

                <!-- Check-in sayısı (sağ alt) -->
                <div class="absolute bottom-3 right-3 flex items-center gap-1 bg-black/60 backdrop-blur text-white text-[11px] font-bold px-2 py-1 rounded-lg border border-white/10">
                    <span class="material-symbols-outlined text-[11px] text-primary" style="font-variation-settings:'FILL' 1;">location_on</span>
                    <?php echo $checkinCount; ?>
                </div>

                <!-- Açık/Kapalı -->
                <?php if (isset($v['is_open'])): ?>
                <div class="absolute top-3 right-3 flex items-center gap-1 bg-black/60 backdrop-blur text-[10px] font-bold px-2 py-1 rounded-lg border border-white/10 <?php echo $v['is_open'] ? 'text-emerald-400' : 'text-red-400'; ?>">
                    <span class="w-1.5 h-1.5 rounded-full <?php echo $v['is_open'] ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                    <?php echo $v['is_open'] ? 'Açık' : 'Kapalı'; ?>
                </div>
                <?php endif; ?>

            </div>

            <!-- Bilgi -->
            <div class="p-4 flex-grow flex flex-col gap-1">
                <div class="font-bold text-sm text-on-surface group-hover:text-primary transition-colors truncate leading-snug">
                    <?php echo escape($v['name']); ?>
                </div>
                <div class="text-[11px] text-on-surface-variant" style="color:<?php echo $meta['color']; ?>;opacity:0.8;">
                    <?php echo escape($categories[$v['category'] ?? 'diger'] ?? 'Mekan'); ?>
                </div>
                <?php if (!empty($v['address'])): ?>
                <div class="text-[11px] text-on-surface-variant truncate mt-0.5 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[11px]">pin_drop</span>
                    <?php echo escape(truncate($v['address'], 45)); ?>
                </div>
                <?php endif; ?>
            </div>

        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── SAYFALAMA ── -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center items-center gap-2 pt-4">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&cat=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-surface-container border border-white/8 text-on-surface-variant hover:border-white/20 hover:text-on-surface transition-all">
            <span class="material-symbols-outlined text-lg">chevron_left</span>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?page=<?php echo $i; ?>&cat=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>"
           class="w-9 h-9 flex items-center justify-center rounded-xl font-bold text-sm transition-all <?php echo $i === $page ? 'bg-primary-container text-white shadow-[0_0_12px_rgba(255,145,0,0.3)]' : 'bg-surface-container border border-white/8 text-on-surface-variant hover:border-white/20'; ?>">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&cat=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>"
           class="w-9 h-9 flex items-center justify-center rounded-xl bg-surface-container border border-white/8 text-on-surface-variant hover:border-white/20 hover:text-on-surface transition-all">
            <span class="material-symbols-outlined text-lg">chevron_right</span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
