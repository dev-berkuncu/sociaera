<?php
/**
 * Sociaera — Aktivite Akışı
 * Tüm kullanıcıların son check-in'lerini gösterir
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

$checkinModel = new CheckinModel();
$venueModel   = new VenueModel();

// Sekme: herkese açık / sadece takip edilenler
$allowedTabs = ['global', 'following'];
$tab  = in_array($_GET['tab'] ?? 'global', $allowedTabs, true) ? ($_GET['tab'] ?? 'global') : 'global';
$page = max(1, (int)($_GET['page'] ?? 1));

if ($tab === 'following') {
    $posts = $checkinModel->getFollowingFeed(Auth::id(), $page, 20);
} else {
    $posts = $checkinModel->getGlobalFeed($page, 20, Auth::id());
}

$trendVenues     = [];
$miniLeaderboard = [];
try {
    $trendVenues     = $venueModel->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

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

$pageTitle = 'Aktivite';
$activeNav = 'activity';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-5 pb-8 min-w-0">

    <!-- ── BAŞLIK + SEKMELER ── -->
    <div>
        <h1 class="text-xl font-black text-on-surface mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings:'FILL' 1;">explore</span>
            Aktivite
        </h1>

        <!-- Tab seçici -->
        <div class="flex gap-1 bg-surface-container/60 border border-white/8 rounded-xl p-1 w-fit">
            <a href="?tab=global"
               class="px-5 py-2 rounded-lg text-sm font-bold transition-all <?php echo $tab === 'global' ? 'bg-primary-container text-white shadow-[0_0_12px_rgba(255,145,0,0.3)]' : 'text-on-surface-variant hover:text-on-surface'; ?>">
                🌍 Herkes
            </a>
            <a href="?tab=following"
               class="px-5 py-2 rounded-lg text-sm font-bold transition-all <?php echo $tab === 'following' ? 'bg-primary-container text-white shadow-[0_0_12px_rgba(255,145,0,0.3)]' : 'text-on-surface-variant hover:text-on-surface'; ?>">
                👥 Takip Ettiklerim
            </a>
        </div>
    </div>

    <!-- ── CHECK-İN AKIŞI ── -->
    <?php if (empty($posts)): ?>
    <div class="rounded-2xl border border-dashed border-white/10 p-12 text-center">
        <span class="material-symbols-outlined text-on-surface-variant text-5xl mb-3 block opacity-25">
            <?php echo $tab === 'following' ? 'group' : 'public'; ?>
        </span>
        <p class="text-sm text-on-surface-variant">
            <?php echo $tab === 'following' ? 'Takip ettiğin kişiler henüz check-in yapmadı.' : 'Henüz check-in yok.'; ?>
        </p>
        <?php if ($tab === 'following'): ?>
        <a href="<?php echo BASE_URL; ?>/leaderboard"
           class="mt-4 inline-flex items-center gap-2 text-primary text-sm font-semibold hover:underline">
            <span class="material-symbols-outlined text-base">group_add</span>
            Aktif kullanıcıları keşfet
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <div class="flex flex-col gap-3">
        <?php foreach ($posts as $post) {
            include __DIR__ . '/partials/_tailwind_post_card.php';
        } ?>
    </div>

    <!-- Daha fazla yükle -->
    <?php if (count($posts) >= 20): ?>
    <div class="text-center">
        <a href="?tab=<?php echo $tab; ?>&page=<?php echo $page + 1; ?>"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-surface-container border border-white/8 text-sm font-semibold text-on-surface-variant hover:border-white/15 hover:text-on-surface transition-all">
            <span class="material-symbols-outlined text-base">expand_more</span>
            Daha Fazla Göster
        </a>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
