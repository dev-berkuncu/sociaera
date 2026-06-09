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

<div style="min-width:0;">
<div style="display:flex;flex-direction:column;gap:20px;padding-bottom:32px;">

    <!-- ── BAŞLIK ── -->
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <h1 style="font-size:1.375rem;font-weight:800;color:var(--text-1);margin:0;letter-spacing:-0.3px;">Mekanlar</h1>
        <a href="<?php echo BASE_URL; ?>/add-venue" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;white-space:nowrap;">
            <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' 1;">add_location_alt</span>
            Mekan Ekle
        </a>
    </div>

    <!-- ── ARAMA ── -->
    <div class="swarm-search-wrap">
        <form method="GET" style="position:relative;">
            <?php if ($category): ?>
            <input type="hidden" name="cat" value="<?php echo escape($category); ?>">
            <?php endif; ?>
            <span class="material-symbols-outlined" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:18px;pointer-events:none;">search</span>
            <input type="text" name="q" value="<?php echo escape($search); ?>"
                   placeholder="Mekan ara…"
                   style="width:100%;box-sizing:border-box;background:#fff;border:1.5px solid #E8E6E1;border-radius:14px;padding:12px 16px 12px 44px;color:var(--text-1);font-size:0.875rem;outline:none;transition:border-color 0.2s;font-family:inherit;"
                   onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='#E8E6E1'">
        </form>
    </div>

    <!-- ── KATEGORİ PİLLERİ ── -->
    <div style="display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;-ms-overflow-style:none;scrollbar-width:none;">
        <a href="?q=<?php echo urlencode($search); ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:999px;white-space:nowrap;font-weight:600;font-size:0.8125rem;text-decoration:none;flex-shrink:0;transition:all 0.18s;
                  <?php echo !$category
                      ? 'background:var(--color-primary);color:#fff;border:1.5px solid var(--color-primary);box-shadow:0 2px 10px rgba(240,109,31,0.25);'
                      : 'background:#fff;border:1.5px solid #E8E6E1;color:var(--text-3);'; ?>">
            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;">apps</span>
            Tümü
        </a>
        <?php foreach ($categoryMeta as $key => $meta): ?>
        <?php if (!isset($categories[$key])) continue; ?>
        <a href="?cat=<?php echo urlencode($key); ?>&q=<?php echo urlencode($search); ?>"
           style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:999px;white-space:nowrap;font-weight:600;font-size:0.8125rem;text-decoration:none;flex-shrink:0;transition:all 0.18s;
                  <?php echo $category === $key
                      ? "background:{$meta['color']};color:#fff;border:1.5px solid {$meta['color']};box-shadow:0 2px 10px {$meta['color']}40;"
                      : 'background:#fff;border:1.5px solid #E8E6E1;color:var(--text-3);'; ?>">
            <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
            <?php echo escape($categories[$key]); ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── SONUÇ SAYISI ── -->
    <p style="font-size:0.75rem;color:var(--text-3);margin:0;font-weight:500;">
        <strong style="color:var(--text-1);"><?php echo $totalVenues; ?></strong> mekan<?php if ($search || $category): ?> bulundu<?php endif; ?>
        <?php if ($search): ?>&nbsp;· "<span style="color:var(--text-1);"><?php echo escape($search); ?></span>" araması<?php endif; ?>
    </p>

    <!-- ── MEKAN GRID ── -->
    <?php if (empty($venues)): ?>

    <div class="empty-state">
        <span class="empty-state-icon material-symbols-outlined">location_off</span>
        <p class="empty-state-title">Mekan bulunamadı</p>
        <p style="font-size:0.875rem;color:var(--text-3);margin:4px 0 0;">
            <?php if ($search || $category): ?>
            <a href="<?php echo BASE_URL; ?>/venues" style="color:var(--color-primary);font-weight:600;text-decoration:none;">Filtreleri temizle</a>
            <?php else: ?>
            Henüz mekan eklenmemiş.
            <?php endif; ?>
        </p>
    </div>

    <?php else: ?>

    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
        <style>
            @media(min-width:900px){.venues-grid{grid-template-columns:repeat(3,1fr)!important;}}
        </style>
        <?php foreach ($venues as $v):
            $meta = $categoryMeta[$v['category'] ?? 'diger'] ?? $categoryMeta['diger'];
            $checkinCount = (int)($v['checkin_count'] ?? 0);
        ?>

        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
           class="venue-card"
           style="text-decoration:none;display:flex;flex-direction:column;overflow:hidden;transition:box-shadow 0.2s,transform 0.15s;"
           onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 28px rgba(0,0,0,0.10)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">

            <!-- Görsel / Image Area -->
            <div class="venue-card-img" style="position:relative;height:150px;overflow:hidden;background:<?php echo $meta['bg']; ?>;">

                <?php if (!empty($v['cover_image'])): ?>
                    <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.4s;" loading="lazy"
                         onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
                <?php elseif (!empty($v['image'])): ?>
                    <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                         style="width:100%;height:100%;object-fit:cover;transition:transform 0.4s;" loading="lazy"
                         onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
                <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                        <span class="material-symbols-outlined" style="font-size:48px;color:<?php echo $meta['color']; ?>;opacity:0.5;font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                    </div>
                <?php endif; ?>

                <!-- Gradient overlay -->
                <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,0.42) 0%,transparent 55%);pointer-events:none;"></div>

                <!-- Kategori badge (bottom-left) -->
                <div style="position:absolute;bottom:10px;left:10px;width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:<?php echo $meta['color']; ?>;box-shadow:0 2px 8px rgba(0,0,0,0.18);">
                    <span class="material-symbols-outlined" style="font-size:16px;color:#fff;font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                </div>

                <!-- Check-in count (bottom-right) -->
                <div style="position:absolute;bottom:10px;right:10px;display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.92);border-radius:8px;padding:3px 8px;font-size:11px;font-weight:700;color:var(--text-1);">
                    <span class="material-symbols-outlined" style="font-size:11px;color:var(--color-primary);font-variation-settings:'FILL' 1;">location_on</span>
                    <?php echo $checkinCount; ?>
                </div>

                <!-- Açık/Kapalı (top-right) -->
                <?php if (isset($v['is_open'])): ?>
                <div style="position:absolute;top:10px;right:10px;display:flex;align-items:center;gap:4px;background:rgba(255,255,255,0.92);border-radius:8px;padding:3px 8px;font-size:10px;font-weight:700;color:<?php echo $v['is_open'] ? '#16a34a' : '#dc2626'; ?>;">
                    <span style="width:6px;height:6px;border-radius:50%;background:<?php echo $v['is_open'] ? '#16a34a' : '#dc2626'; ?>;display:inline-block;"></span>
                    <?php echo $v['is_open'] ? 'Açık' : 'Kapalı'; ?>
                </div>
                <?php endif; ?>

            </div>

            <!-- Bilgi / Info -->
            <div class="venue-card-info" style="padding:12px 14px;display:flex;flex-direction:column;gap:3px;flex:1;">
                <div style="font-weight:700;font-size:0.875rem;color:var(--text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.3;">
                    <?php echo escape($v['name']); ?>
                </div>
                <div style="font-size:0.6875rem;font-weight:600;color:<?php echo $meta['color']; ?>;">
                    <?php echo escape($categories[$v['category'] ?? 'diger'] ?? 'Mekan'); ?>
                </div>
                <?php if (!empty($v['address'])): ?>
                <div style="font-size:0.6875rem;color:var(--text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:flex;align-items:center;gap:3px;margin-top:2px;">
                    <span class="material-symbols-outlined" style="font-size:11px;">pin_drop</span>
                    <?php echo escape(truncate($v['address'], 45)); ?>
                </div>
                <?php endif; ?>
            </div>

        </a>
        <?php endforeach; ?>
    </div>

    <script>
    (function(){
        var g = document.querySelector('[style*="grid-template-columns:repeat(2"]');
        if(g) g.classList.add('venues-grid');
        function resize(){if(g)g.style.gridTemplateColumns=window.innerWidth>=900?'repeat(3,1fr)':'repeat(2,1fr)';}
        resize(); window.addEventListener('resize',resize);
    })();
    </script>

    <!-- ── SAYFALAMA ── -->
    <?php if ($totalPages > 1): ?>
    <div class="swarm-pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&cat=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>"
           class="swarm-page-btn" style="text-decoration:none;">
            <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">chevron_left</span>
        </a>
        <?php endif; ?>
        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
        <a href="?page=<?php echo $i; ?>&cat=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>"
           class="swarm-page-btn <?php echo $i === $page ? 'active' : ''; ?>"
           style="text-decoration:none;"><?php echo $i; ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?page=<?php echo $page + 1; ?>&cat=<?php echo urlencode($category); ?>&q=<?php echo urlencode($search); ?>"
           class="swarm-page-btn" style="text-decoration:none;">
            <span class="material-symbols-outlined" style="font-size:18px;line-height:1;">chevron_right</span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>
</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
