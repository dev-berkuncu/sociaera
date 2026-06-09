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
        <?php foreach ($posts as $post):
            $pMeta   = $categoryMeta[$post['venue_category'] ?? 'diger'] ?? $categoryMeta['diger'];
            $pAvatar = safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'U');
            $pTimeAgo = timeAgo($post['created_at']);
            $isOwn   = (int)($post['user_id'] ?? 0) === Auth::id();
        ?>
        <div class="group rounded-2xl border border-white/5 bg-surface-container/50 hover:bg-surface-container hover:border-white/10 transition-all overflow-hidden">

            <!-- Üst: Kim, nereye -->
            <div class="flex items-center gap-3 px-4 pt-4 pb-3">
                <!-- Avatar + kategori pin -->
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo urlencode($post['tag'] ?: $post['username']); ?>"
                   class="relative flex-shrink-0">
                    <img src="<?php echo $pAvatar; ?>" alt=""
                         class="w-10 h-10 rounded-full object-cover border-2 border-white/10 group-hover:border-white/20 transition-colors" width="40" height="40">
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-[#131314] flex items-center justify-center"
                         style="background:<?php echo $pMeta['color']; ?>;">
                        <span class="material-symbols-outlined text-white" style="font-size:9px;font-variation-settings:'FILL' 1;"><?php echo $pMeta['icon']; ?></span>
                    </div>
                </a>

                <!-- Metin: "X, Y'de check-in yaptı" -->
                <div class="flex-grow min-w-0">
                    <div class="text-sm leading-snug">
                        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo urlencode($post['tag'] ?: $post['username']); ?>"
                           class="font-bold text-on-surface hover:text-primary transition-colors">
                            <?php echo escape($post['username']); ?>
                        </a>
                        <span class="text-on-surface-variant"> check-in yaptı: </span>
                        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$post['venue_id']; ?>"
                           class="font-bold hover:underline" style="color:<?php echo $pMeta['color']; ?>">
                            <?php echo escape($post['venue_name']); ?>
                        </a>
                    </div>
                    <div class="text-[11px] text-on-surface-variant mt-0.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[11px]">schedule</span>
                        <?php echo $pTimeAgo; ?>
                        <?php if (!empty($post['venue_address'])): ?>
                        <span class="opacity-40">·</span>
                        <span class="material-symbols-outlined text-[11px]">pin_drop</span>
                        <span class="truncate max-w-[200px]"><?php echo escape(truncate($post['venue_address'], 40)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Check-in badge -->
                <div class="flex items-center gap-1 text-[10px] font-bold px-2.5 py-1 rounded-full flex-shrink-0 border"
                     style="color:<?php echo $pMeta['color']; ?>;background:<?php echo $pMeta['color']; ?>15;border-color:<?php echo $pMeta['color']; ?>30;">
                    <span class="material-symbols-outlined" style="font-size:10px;font-variation-settings:'FILL' 1;">verified</span>
                    Check-in
                </div>
            </div>

            <!-- Check-in notu -->
            <?php if (!empty($post['note'])): ?>
            <div class="px-4 pb-3">
                <p class="text-sm text-on-surface/80 italic bg-surface-container-highest/50 border border-white/5 rounded-xl px-4 py-3 leading-relaxed">
                    "<?php echo escape($post['note']); ?>"
                </p>
            </div>
            <?php endif; ?>

            <!-- Check-in görseli -->
            <?php if (!empty($post['image'])): ?>
            <div class="px-4 pb-3">
                <img src="<?php echo uploadUrl('posts', $post['image']); ?>"
                     class="w-full max-h-80 object-cover rounded-xl border border-white/5" loading="lazy">
            </div>
            <?php endif; ?>

            <!-- Alt: Venue butonu + beğeni sayısı -->
            <div class="flex items-center justify-between px-4 pb-4 pt-1 border-t border-white/5">
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$post['venue_id']; ?>"
                   class="flex items-center gap-1.5 text-xs text-on-surface-variant hover:text-on-surface transition-colors group/vl">
                    <span class="material-symbols-outlined text-sm group-hover/vl:text-primary transition-colors" style="font-variation-settings:'FILL' 1;">storefront</span>
                    <span class="font-semibold group-hover/vl:text-primary transition-colors"><?php echo escape($post['venue_name']); ?></span>
                    <span class="material-symbols-outlined text-xs opacity-60">chevron_right</span>
                </a>

                <div class="flex items-center gap-3 text-xs text-on-surface-variant">
                    <?php if (($post['like_count'] ?? 0) > 0): ?>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm text-red-400" style="font-variation-settings:'FILL' 1;">favorite</span>
                        <?php echo (int)$post['like_count']; ?>
                    </span>
                    <?php endif; ?>
                    <?php if (($post['comment_count'] ?? 0) > 0): ?>
                    <span class="flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">chat_bubble</span>
                        <?php echo (int)$post['comment_count']; ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php endforeach; ?>
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
