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
    <div class="relative overflow-hidden rounded-2xl border border-white/5 glass-panel p-6 md:p-8">
        <!-- Accent Glows -->
        <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full blur-[100px] pointer-events-none bg-primary/10"></div>
        <div class="absolute -bottom-20 -left-20 w-80 h-80 rounded-full blur-[100px] pointer-events-none bg-secondary/5"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div>
                <p class="text-[10px] text-slate-500 font-mono uppercase tracking-widest mb-1.5">Kullanıcı HUD // Giriş Başarılı</p>
                <h1 class="text-2xl md:text-3xl font-black text-on-surface tracking-tight mb-5 font-mono">
                    <?php echo escape($currentUser['gta_character_name'] ?: $currentUser['username']); ?>
                </h1>
                <div class="flex flex-wrap gap-2.5">
                    <div class="stat-pill">
                        <span class="material-symbols-outlined text-primary text-sm" style="font-variation-settings:'FILL' 1;">location_on</span>
                        <span class="font-bold"><?php echo (int)($stats['checkins'] ?? 0); ?></span>
                        <span class="text-slate-400">Check-in</span>
                    </div>
                    <?php if ($streak > 0): ?>
                    <div class="stat-pill" style="border-color:rgba(255,106,0,0.25);">
                        <span class="material-symbols-outlined text-primary text-sm streak-pulse" style="font-variation-settings:'FILL' 1;">local_fire_department</span>
                        <span class="font-bold"><?php echo $streak; ?> Gün</span>
                        <span style="color:#ff6a00;opacity:0.9;">Seri</span>
                    </div>
                    <?php endif; ?>
                    <div class="stat-pill">
                        <span class="material-symbols-outlined text-secondary text-sm" style="font-variation-settings:'FILL' 1;">calendar_today</span>
                        <span class="font-bold"><?php echo min(5, $weeklyCheckins); ?>/5</span>
                        <span class="text-slate-400">Haftalık</span>
                    </div>
                    <div class="stat-pill">
                        <span class="material-symbols-outlined text-tertiary text-sm" style="font-variation-settings:'FILL' 1;">military_tech</span>
                        <span class="font-bold">SEVİYE <?php echo $userLevel; ?></span>
                    </div>
                </div>
            </div>

            <a href="<?php echo BASE_URL; ?>/venues" id="hero-checkin-btn"
               class="group flex items-center gap-3 text-white font-bold px-7 py-4 rounded-xl transition-all active:scale-95 shrink-0 self-start sm:self-auto btn-primary shadow-[0_0_30px_rgba(255,106,0,0.2)]">
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
            <h2 class="text-xs font-bold font-mono text-slate-400 uppercase tracking-wider flex items-center gap-2">
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
               class="group flex items-center gap-3 p-3.5 rounded-xl border border-white/5 glass-panel hover:border-secondary/40 transition-all duration-300 hover:-translate-y-0.5 shadow-lg">
                <!-- Avatar + check-in pin -->
                <div class="relative flex-shrink-0">
                    <img src="<?php echo $faAvatar; ?>" alt=""
                          class="w-10 h-10 rounded-full object-cover border-2 border-white/10 group-hover:border-secondary/50 transition-colors" width="40" height="40">
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-[#060a13] flex items-center justify-center shadow-[0_0_10px_rgba(0,0,0,0.5)]"
                          style="background:<?php echo $faMeta['color']; ?>;">
                        <span class="material-symbols-outlined text-white" style="font-size:10px;font-variation-settings:'FILL' 1;"><?php echo $faMeta['icon']; ?></span>
                    </div>
                </div>
                <!-- İçerik -->
                <div class="flex-grow min-w-0">
                    <div class="text-sm font-semibold text-on-surface truncate">
                        <span class="text-slate-400 font-mono font-normal">@<?php echo escape($fa['tag'] ?: $fa['username']); ?></span>
                        <span class="text-slate-500">→</span>
                        <span class="text-secondary font-bold group-hover:text-primary transition-colors"><?php echo escape($fa['venue_name']); ?></span>
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono mt-0.5 flex items-center gap-1.5">
                        <span><?php echo timeAgo($fa['created_at']); ?></span>
                        <?php if (!empty($fa['note'])): ?>
                        <span>·</span>
                        <span class="italic text-slate-400 font-sans">"<?php echo escape(mb_strimwidth($fa['note'], 0, 30, '…')); ?>"</span>
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
            <h2 class="text-xs font-bold font-mono text-slate-400 uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings:'FILL' 1;">trending_up</span>
                Bu Hafta Popüler
            </h2>
            <a href="<?php echo BASE_URL; ?>/venues" class="text-xs text-secondary hover:text-primary transition-colors font-mono uppercase tracking-wider flex items-center gap-0.5">
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
               class="group rounded-2xl overflow-hidden border border-white/5 glass-panel hover:border-primary/40 transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_8px_30px_-8px_rgba(0,240,255,0.15)] flex flex-col <?php echo $isHero ? 'md:col-span-2 md:row-span-2' : ''; ?>">

                <div class="relative overflow-hidden bg-slate-950/40 <?php echo $isHero ? 'h-44 md:h-56' : 'h-32'; ?>">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-slate-950/20">
                            <span class="material-symbols-outlined text-5xl opacity-30" style="color:<?php echo $meta['color']; ?>;font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(6,10,19,0.9) 0%,transparent 60%);"></div>

                    <!-- Kategori ikonu -->
                    <div class="absolute bottom-3 left-3 w-9 h-9 rounded-xl flex items-center justify-center border border-white/10"
                         style="background:<?php echo $meta['color']; ?>dd;backdrop-filter:blur(8px);">
                        <span class="material-symbols-outlined text-white text-[17px]" style="font-variation-settings:'FILL' 1;"><?php echo $meta['icon']; ?></span>
                    </div>

                    <!-- Check-in count -->
                    <div class="absolute bottom-3 right-3 flex items-center gap-1 text-white text-[10px] font-bold px-2 py-1 rounded-lg border border-white/10 font-mono" style="background:rgba(6,10,19,0.75);backdrop-filter:blur(6px);">
                        <span class="material-symbols-outlined text-secondary text-[11px] animate-pulse" style="font-variation-settings:'FILL' 1;">location_on</span>
                        <?php echo $checkinCount; ?> check-in
                    </div>

                    <!-- Sıra badge -->
                    <?php if ($idx < 3): ?>
                    <div class="absolute top-3 left-3 w-6 h-6 rounded-full flex items-center justify-center font-mono font-black text-[11px] border"
                         style="<?php echo $idx === 0 ? 'background:#ff6a00;border-color:#ff6a00;color:#fff;box-shadow:0 0 10px rgba(255,106,0,0.6)' : ($idx === 1 ? 'background:#00f0ff;border-color:#00f0ff;color:#000;box-shadow:0 0 10px rgba(0,240,255,0.6)' : 'background:#a855f7;border-color:#a855f7;color:#fff;box-shadow:0 0 10px rgba(168,85,247,0.6)'); ?>">
                        <?php echo $idx + 1; ?>
                    </div>
                    <?php endif; ?>

                    <?php if (isset($v['is_open'])): ?>
                    <div class="absolute top-3 right-3 flex items-center gap-1 text-[10px] font-mono font-bold px-2 py-1 rounded-lg border border-white/10 <?php echo $v['is_open'] ? 'text-emerald-400' : 'text-rose-400'; ?>" style="background:rgba(6,10,19,0.8);backdrop-filter:blur(6px);">
                        <span class="w-1.5 h-1.5 rounded-full <?php echo $v['is_open'] ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'; ?>"></span>
                        <?php echo $v['is_open'] ? 'AÇIK' : 'KAPALI'; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="p-3.5 flex-grow">
                    <div class="font-bold <?php echo $isHero ? 'text-base' : 'text-sm'; ?> text-on-surface group-hover:text-primary transition-colors truncate">
                        <?php echo escape($v['name']); ?>
                    </div>
                    <div class="text-[10px] font-semibold font-mono mt-0.5 uppercase tracking-wider" style="color:<?php echo $meta['color']; ?>;opacity:0.9;">
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
            <h2 class="text-xs font-bold font-mono text-slate-400 uppercase tracking-wider flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-base" style="font-variation-settings:'FILL' 1;">history</span>
                Son Check-in'lerim
            </h2>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-xs text-secondary hover:text-primary transition-colors font-mono uppercase tracking-wider flex items-center gap-0.5">
                Tümü <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>

        <?php if (empty($recentCheckins)): ?>
        <div class="rounded-2xl border border-dashed border-white/10 p-8 text-center glass-panel">
            <span class="material-symbols-outlined text-primary text-4xl mb-3 block opacity-40 animate-bounce">pin_drop</span>
            <p class="text-sm text-slate-400 mb-4 font-mono text-xs">Henüz check-in yapmadın.</p>
            <a href="<?php echo BASE_URL; ?>/venues"
               class="inline-flex items-center gap-2 text-white font-bold px-5 py-2.5 rounded-xl text-sm active:scale-95 transition-all btn-primary shadow-[0_0_15px_rgba(255,106,0,0.25)]">
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
               class="group flex items-center gap-3 p-4 rounded-xl border border-white/5 glass-panel hover:border-primary/40 transition-all duration-300 hover:-translate-y-0.5 shadow-lg">
                <div class="relative flex-shrink-0">
                    <img src="<?php echo $ciAvatar; ?>" alt="" class="w-10 h-10 rounded-full object-cover border-2 border-white/10 group-hover:border-primary/30 transition-colors" width="40" height="40">
                    <div class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-2 border-[#060a13] flex items-center justify-center shadow-[0_0_10px_rgba(0,0,0,0.5)]"
                         style="background:<?php echo $ciMeta['color']; ?>;">
                        <span class="material-symbols-outlined text-white" style="font-size:9px;font-variation-settings:'FILL' 1;"><?php echo $ciMeta['icon']; ?></span>
                    </div>
                </div>
                <div class="flex-grow min-w-0">
                    <div class="font-bold text-sm text-on-surface group-hover:text-primary transition-colors truncate">
                        <?php echo escape($ci['venue_name'] ?? 'Mekan'); ?>
                    </div>
                    <div class="text-[10px] text-slate-500 font-mono mt-0.5 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[11px] text-slate-500">schedule</span>
                        <span><?php echo timeAgo($ci['created_at']); ?></span>
                        <?php if (!empty($ci['note'])): ?>
                        <span>·</span>
                        <span class="italic text-slate-400 font-sans">"<?php echo escape(mb_strimwidth($ci['note'], 0, 28, '…')); ?>"</span>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="material-symbols-outlined text-slate-500 group-hover:text-primary transition-colors flex-shrink-0">chevron_right</span>
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
    background: rgba(11, 16, 26, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 11px;
    font-family: 'JetBrains Mono', 'Share Tech Mono', monospace;
    color: #e5e2e3;
    backdrop-filter: blur(8px);
}
</style>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
