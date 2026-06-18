<?php
/**
 * Sociaera — Dashboard (Swarm Edition)
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
$streak = 0; $weeklyCheckins = 0;
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

// Arkadaş aktivitesi
$friendActivity = [];
try {
    $db   = Database::getConnection();
    $stmt = $db->prepare("
        SELECT c.id, c.note, c.image, c.created_at,
               u.username, u.avatar, u.tag,
               v.name as venue_name, v.id as venue_id, v.category as venue_category
        FROM checkins c
        JOIN users u ON c.user_id = u.id
        JOIN venues v ON c.venue_id = v.id
        JOIN user_follows f ON f.following_id = c.user_id AND f.follower_id = ?
        WHERE c.is_deleted = 0
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([Auth::id()]);
    $friendActivity = $stmt->fetchAll();
} catch (Exception $e) {}

// Trend mekanlar
$trendVenues = [];
try { $trendVenues = $venueModel->getTrending(6); } catch (Exception $e) {}

$userLevel = floor(($stats['checkins'] ?? 0) / 15) + 1;
$categories = VenueModel::categories();

$catMeta = [
    'restoran'  => ['icon' => 'restaurant',    'color' => '#F06D1F'],
    'kafe'      => ['icon' => 'local_cafe',    'color' => '#92400E'],
    'bar'       => ['icon' => 'sports_bar',    'color' => '#7C3AED'],
    'otel'      => ['icon' => 'hotel',         'color' => '#4F46E5'],
    'alisveris' => ['icon' => 'shopping_bag',  'color' => '#2563EB'],
    'eglence'   => ['icon' => 'theaters',      'color' => '#D97706'],
    'spor'      => ['icon' => 'fitness_center','color' => '#DC2626'],
    'saglik'    => ['icon' => 'spa',           'color' => '#DB2777'],
    'kultur'    => ['icon' => 'museum',        'color' => '#0D9488'],
    'diger'     => ['icon' => 'place',         'color' => '#6B7280'],
];

$pageTitle = 'Ana Sayfa';
$activeNav = 'dashboard';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0; display:flex; flex-direction:column; gap:20px;">

<!-- ── HERO: Karşılama + Streak ────────────────────────────── -->
<div class="swarm-card" style="background:linear-gradient(135deg,#fff8f4 0%,#fff 100%);border-top:3px solid var(--color-primary); margin-bottom:0;">
    <div class="swarm-card-body" style="display:flex;flex-direction:column;gap:16px;">

        <div style="display:flex;align-items:center;gap:12px;">
            <img src="<?php echo safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username']); ?>"
                 style="width:52px;height:52px;border-radius:50%;object-fit:cover;border:3px solid var(--color-primary);flex-shrink:0;" width="52" height="52">
            <div>
                <div style="font-size:13px;color:var(--text-3);font-weight:600;">Hoş geldin,</div>
                <h1 style="font-size:20px;font-weight:800;color:var(--text-1);line-height:1.2;">
                     <?php echo escape($currentUser['username']); ?>
                </h1>
            </div>
        </div>

        <!-- Gamification satırı -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php if ($streak > 0): ?>
            <div class="streak-widget" style="flex:1;min-width:120px;">
                <span class="material-symbols-outlined" style="font-size:20px;font-variation-settings:'FILL' 1;">local_fire_department</span>
                <div>
                    <div style="font-size:18px;font-weight:800;line-height:1;"><?php echo $streak; ?></div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;opacity:0.8;">Günlük Seri</div>
                </div>
            </div>
            <?php endif; ?>
            <div style="flex:1;min-width:120px;background:var(--bg-section);border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:var(--color-primary);font-variation-settings:'FILL' 1;font-size:20px;">location_on</span>
                <div>
                    <div style="font-size:18px;font-weight:800;line-height:1;color:var(--text-1);"><?php echo (int)($stats['checkins'] ?? 0); ?></div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);">Toplam Check-in</div>
                </div>
            </div>
            <div style="flex:1;min-width:120px;background:var(--bg-section);border-radius:12px;padding:10px 14px;display:flex;align-items:center;gap:8px;">
                <span class="material-symbols-outlined" style="color:#7C3AED;font-variation-settings:'FILL' 1;font-size:20px;">military_tech</span>
                <div>
                    <div style="font-size:18px;font-weight:800;line-height:1;color:var(--text-1);">Sv. <?php echo $userLevel; ?></div>
                    <div style="font-size:10px;text-transform:uppercase;letter-spacing:0.5px;color:var(--text-3);">Seviye</div>
                </div>
            </div>
        </div>

        <!-- Haftalık ilerleme -->
        <div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                <span style="font-size:12px;font-weight:700;color:var(--text-2);">Bu Hafta Hedefi</span>
                <span style="font-size:13px;font-weight:800;color:var(--color-primary);"><?php echo min(5,$weeklyCheckins); ?> / 5 check-in</span>
            </div>
            <div class="progress-bar-track">
                <div class="progress-bar-fill" style="width:<?php echo min(100, ($weeklyCheckins/5)*100); ?>%;"></div>
            </div>
        </div>

        <!-- CTA Butonu -->
        <a href="<?php echo BASE_URL; ?>/venues" class="btn btn-primary btn-block btn-lg" id="hero-checkin-btn" style="justify-content:center;">
            <span class="material-symbols-outlined" style="font-size:20px;font-variation-settings:'FILL' 1;">add_location_alt</span>
            Check-in Yap
            <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
        </a>
    </div>
</div>

<!-- ── SPONSORLARIMIZ (CAROUSEL) ────────────────────────────── -->
<?php if (!empty($carouselAds)): ?>
<div style="margin-bottom:20px;">
    <div class="swarm-section-label" style="margin-bottom:8px;">
        <span class="material-symbols-outlined" style="font-size:14px;color:var(--color-primary);font-variation-settings:'FILL' 1;">star</span>
        Sponsorlarımız
    </div>
    <div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:10px;scrollbar-width:none;-ms-overflow-style:none;">
        <?php foreach ($carouselAds as $ad): ?>
            <a href="<?php echo !empty($ad['link_url']) ? escape($ad['link_url']) : '#'; ?>" target="_blank" rel="noopener noreferrer" style="flex-shrink:0;width:120px;height:80px;border-radius:12px;overflow:hidden;border:1.5px solid var(--border-light);background:#fff;display:flex;align-items:center;justify-content:center;transition:transform 0.2s;text-decoration:none;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                <?php if ($ad['media_type'] === 'youtube'): ?>
                    <img src="https://img.youtube.com/vi/<?php 
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $ad['image_url'], $matches);
                        echo $matches[1] ?? '';
                    ?>/mqdefault.jpg" style="width:100%;height:100%;object-fit:cover;" alt="Sponsor">
                <?php elseif ($ad['media_type'] === 'video'): ?>
                    <video src="<?php echo BASE_URL . '/' . ltrim($ad['image_url'], '/'); ?>" style="width:100%;height:100%;object-fit:cover;" autoplay loop muted playsinline></video>
                <?php else: ?>
                    <img src="<?php echo BASE_URL . '/' . ltrim($ad['image_url'], '/'); ?>" alt="<?php echo escape($ad['title']); ?>" style="width:100%;height:100%;object-fit:cover;">
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ── ARKADAŞ AKTİVİTESİ ──────────────────────────────────── -->
<?php if (!empty($friendActivity)): ?>
<div>
    <div class="swarm-section-label">
        <span class="material-symbols-outlined" style="font-size:14px;color:var(--color-primary);font-variation-settings:'FILL' 1;">group</span>
        Arkadaşlar Ne Yapıyor?
        <a href="<?php echo BASE_URL; ?>/activity?tab=following" style="margin-left:auto;font-size:11px;font-weight:700;color:var(--color-primary);text-decoration:none;">Tümünü Gör →</a>
    </div>

    <div style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($friendActivity as $fa):
            $faMeta   = $catMeta[$fa['venue_category'] ?? 'diger'] ?? $catMeta['diger'];
            $faAvatar = safeAvatarUrl($fa['avatar'] ?? null, $fa['username'] ?? 'U');
        ?>
        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$fa['venue_id']; ?>"
           class="swarm-card"
           style="display:flex;align-items:center;gap:12px;padding:12px 14px;text-decoration:none;color:inherit;transition:box-shadow .2s;">
            <!-- Avatar + kategori dot -->
            <div class="checkin-card-avatar" style="width:40px;height:40px;flex-shrink:0;">
                <img src="<?php echo $faAvatar; ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                <div class="checkin-card-cat-dot" style="background:<?php echo $faMeta['color']; ?>;">
                    <span class="material-symbols-outlined" style="font-size:7px;color:#fff;font-variation-settings:'FILL' 1;"><?php echo $faMeta['icon']; ?></span>
                </div>
            </div>
            <!-- İçerik -->
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:var(--text-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <strong style="color:var(--text-1);"><?php echo escape($fa['username']); ?></strong>
                    <span> → </span>
                    <strong style="color:<?php echo $faMeta['color']; ?>;"><?php echo escape($fa['venue_name']); ?></strong>
                </div>
                <div style="font-size:11px;color:var(--text-3);margin-top:2px;"><?php echo timeAgo($fa['created_at']); ?></div>
            </div>
            <span class="material-symbols-outlined" style="color:var(--text-3);font-size:18px;flex-shrink:0;">chevron_right</span>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="swarm-card">
    <div class="swarm-card-body" style="text-align:center;padding:32px 16px;">
        <div style="font-size:40px;margin-bottom:8px;">👥</div>
        <div style="font-weight:700;color:var(--text-2);margin-bottom:6px;">Takip ettiğin kimse yok</div>
        <div style="font-size:13px;color:var(--text-3);margin-bottom:16px;">Arkadaşlarını bul ve check-in'lerini takip et!</div>
        <a href="<?php echo BASE_URL; ?>/members" class="btn btn-ghost btn-sm">Kullanıcıları Keşfet</a>
    </div>
</div>
<?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
