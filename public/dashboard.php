<?php
/**
 * Sociaera — Dashboard (Keşfet Paneli)
 * Feed-free, check-in ve mekan keşfi odaklı tasarım
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

// Streak & haftalık check-in
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

// Kullanıcının son 5 check-in'i
$recentCheckins = [];
try {
    $recentCheckins = $checkinModel->getUserCheckins(Auth::id(), 1, 5, Auth::id());
} catch (Exception $e) {}

// Mekan keşif verileri
$trendVenues    = [];
$featuredVenues = [];
try {
    $trendVenues    = $venueModel->getTrending(4);
    $featuredVenues = $venueModel->getApproved('', '', 6, 0);
    // Trend'de olmayanları "yeni" olarak göster
    $trendIds = array_column($trendVenues, 'id');
    $newVenues = array_filter($featuredVenues, fn($v) => !in_array($v['id'], $trendIds));
    $newVenues = array_slice(array_values($newVenues), 0, 4);
} catch (Exception $e) {
    $newVenues = [];
}

$userLevel = floor(($stats['checkins'] ?? 0) / 15) + 1;
$levelLabel = $userLevel >= 20 ? 'Efsane' : ($userLevel >= 10 ? 'Uzman' : ($userLevel >= 3 ? 'Kaşif' : 'Yeni'));

$categories = VenueModel::categories();

$pageTitle = 'Keşfet';
$activeNav = 'dashboard';

require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-8 max-w-3xl w-full pb-8">

    <!-- ── HERO: Kullanıcı İstatistik Kartları ── -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

        <!-- Toplam Check-in -->
        <div class="swarm-glass-card rounded-xl p-4 flex flex-col gap-1 border border-outline-variant/20 relative overflow-hidden group hover:border-primary/30 transition-colors">
            <div class="absolute top-0 right-0 w-16 h-16 bg-primary/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-primary/10 transition-colors"></div>
            <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings:'FILL' 1;">location_on</span>
            <div class="text-2xl font-black text-on-surface mt-1"><?php echo (int)($stats['checkins'] ?? 0); ?></div>
            <div class="text-[10px] text-on-surface-variant uppercase tracking-widest font-mono">Toplam Check-in</div>
        </div>

        <!-- Günlük Seri -->
        <div class="swarm-glass-card rounded-xl p-4 flex flex-col gap-1 border border-outline-variant/20 relative overflow-hidden group hover:border-[#ff9100]/30 transition-colors">
            <div class="absolute top-0 right-0 w-16 h-16 bg-[#ff9100]/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-[#ff9100]/10 transition-colors"></div>
            <span class="material-symbols-outlined text-[#ff9100] text-xl streak-pulse" style="font-variation-settings:'FILL' 1;">local_fire_department</span>
            <div class="text-2xl font-black text-on-surface mt-1"><?php echo $streak; ?></div>
            <div class="text-[10px] text-on-surface-variant uppercase tracking-widest font-mono">Günlük Seri</div>
        </div>

        <!-- Bu Hafta -->
        <div class="swarm-glass-card rounded-xl p-4 flex flex-col gap-1 border border-outline-variant/20 relative overflow-hidden group hover:border-emerald-500/30 transition-colors">
            <div class="absolute top-0 right-0 w-16 h-16 bg-emerald-500/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-emerald-500/10 transition-colors"></div>
            <span class="material-symbols-outlined text-emerald-400 text-xl" style="font-variation-settings:'FILL' 1;">calendar_month</span>
            <div class="text-2xl font-black text-on-surface mt-1"><?php echo min(5, $weeklyCheckins); ?><span class="text-sm text-on-surface-variant font-normal">/5</span></div>
            <div class="text-[10px] text-on-surface-variant uppercase tracking-widest font-mono">Bu Hafta</div>
            <!-- Mini Progress -->
            <div class="h-1 bg-surface-container-highest rounded-full mt-1 overflow-hidden">
                <div class="h-full bg-emerald-500 rounded-full transition-all" style="width:<?php echo min(100, ($weeklyCheckins/5)*100); ?>%"></div>
            </div>
        </div>

        <!-- Seviye -->
        <div class="swarm-glass-card rounded-xl p-4 flex flex-col gap-1 border border-outline-variant/20 relative overflow-hidden group hover:border-tertiary/30 transition-colors">
            <div class="absolute top-0 right-0 w-16 h-16 bg-tertiary/5 rounded-full -translate-y-1/2 translate-x-1/2 group-hover:bg-tertiary/10 transition-colors"></div>
            <span class="material-symbols-outlined text-tertiary text-xl" style="font-variation-settings:'FILL' 1;">military_tech</span>
            <div class="text-2xl font-black text-on-surface mt-1"><?php echo $userLevel; ?></div>
            <div class="text-[10px] text-on-surface-variant uppercase tracking-widest font-mono"><?php echo $levelLabel; ?></div>
        </div>

    </div>

    <!-- ── HIZLI AKSİYON BUTONLARI ── -->
    <div class="grid grid-cols-3 gap-3">
        <a href="<?php echo BASE_URL; ?>/venues" id="btn-explore-venues"
           class="swarm-glass-card rounded-xl p-4 flex flex-col items-center gap-2 border border-outline-variant/20 hover:border-primary/40 transition-all group active:scale-95 text-center">
            <div class="w-10 h-10 rounded-full bg-primary/15 flex items-center justify-center group-hover:bg-primary/25 transition-colors">
                <span class="material-symbols-outlined text-primary text-xl" style="font-variation-settings:'FILL' 1;">explore</span>
            </div>
            <div class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors">Mekan Keşfet</div>
        </a>
        <a href="<?php echo BASE_URL; ?>/kampanyalar" id="btn-campaigns"
           class="swarm-glass-card rounded-xl p-4 flex flex-col items-center gap-2 border border-outline-variant/20 hover:border-secondary/40 transition-all group active:scale-95 text-center">
            <div class="w-10 h-10 rounded-full bg-secondary/10 flex items-center justify-center group-hover:bg-secondary/20 transition-colors">
                <span class="material-symbols-outlined text-secondary text-xl" style="font-variation-settings:'FILL' 1;">redeem</span>
            </div>
            <div class="text-xs font-bold text-on-surface group-hover:text-secondary transition-colors">Kampanyalar</div>
        </a>
        <a href="<?php echo BASE_URL; ?>/leaderboard" id="btn-leaderboard"
           class="swarm-glass-card rounded-xl p-4 flex flex-col items-center gap-2 border border-outline-variant/20 hover:border-[#FFD700]/40 transition-all group active:scale-95 text-center">
            <div class="w-10 h-10 rounded-full bg-[#FFD700]/10 flex items-center justify-center group-hover:bg-[#FFD700]/20 transition-colors">
                <span class="material-symbols-outlined text-[#FFD700] text-xl" style="font-variation-settings:'FILL' 1;">emoji_events</span>
            </div>
            <div class="text-xs font-bold text-on-surface group-hover:text-[#FFD700] transition-colors">Sıralama</div>
        </a>
    </div>

    <!-- ── TREND MEKANLAR ── -->
    <?php if (!empty($trendVenues)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-secondary text-lg" style="font-variation-settings:'FILL' 1;">trending_up</span>
                Trend Mekanlar
            </h2>
            <a href="<?php echo BASE_URL; ?>/venues" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold">Tümünü Gör →</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($trendVenues as $idx => $v): ?>
            <?php
                $vRating = 0;
                try {
                    $rd = $venueModel->getVenueRating($v['id']);
                    $vRating = round((float)($rd['average_rating'] ?? 0), 1);
                } catch (Exception $e) {}
            ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
               class="swarm-glass-card rounded-xl overflow-hidden flex items-stretch gap-0 border border-outline-variant/20 hover:border-primary/40 hover:shadow-[0_8px_30px_-10px_rgba(255,145,0,0.15)] transition-all group active:scale-[0.99]">

                <!-- Görsel -->
                <div class="w-24 sm:w-28 flex-shrink-0 bg-surface-container relative overflow-hidden">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-surface-container-high">
                            <span class="material-symbols-outlined text-on-surface-variant text-3xl">storefront</span>
                        </div>
                    <?php endif; ?>
                    <!-- Sıra badge -->
                    <div class="absolute top-2 left-2 w-6 h-6 rounded-full bg-black/70 backdrop-blur flex items-center justify-center text-[10px] font-black text-white border border-white/10">
                        <?php echo $idx + 1; ?>
                    </div>
                </div>

                <!-- Bilgi -->
                <div class="flex-grow p-3 flex flex-col justify-between min-w-0">
                    <div>
                        <div class="font-bold text-sm text-on-surface group-hover:text-primary transition-colors truncate"><?php echo escape($v['name']); ?></div>
                        <div class="text-[10px] text-on-surface-variant mt-0.5 truncate">
                            <?php echo escape($categories[$v['category'] ?? 'diger'] ?? 'Mekan'); ?>
                            <?php if (!empty($v['address'])): ?> · <?php echo escape(truncate($v['address'], 30)); ?><?php endif; ?>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="inline-flex items-center gap-1 bg-primary/10 border border-primary/20 text-primary text-[10px] font-bold px-2 py-0.5 rounded-full">
                            <span class="material-symbols-outlined text-[10px]" style="font-variation-settings:'FILL' 1;">location_on</span>
                            <?php echo (int)($v['weekly_checkins'] ?? $v['checkin_count'] ?? 0); ?> bu hafta
                        </span>
                        <?php if ($vRating > 0): ?>
                        <span class="inline-flex items-center gap-0.5 text-amber-400 text-[10px] font-bold">
                            <span class="material-symbols-outlined text-[10px]" style="font-variation-settings:'FILL' 1;">star</span>
                            <?php echo number_format($vRating, 1); ?>
                        </span>
                        <?php endif; ?>
                        <?php if (isset($v['is_open'])): ?>
                        <span class="text-[10px] font-semibold <?php echo $v['is_open'] ? 'text-emerald-400' : 'text-red-400'; ?>">
                            <?php echo $v['is_open'] ? '● Açık' : '● Kapalı'; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Chevron -->
                <div class="flex items-center pr-3 text-on-surface-variant group-hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-lg">chevron_right</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── YENİ / DİĞER MEKANLAR ── -->
    <?php if (!empty($newVenues)): ?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-tertiary text-lg" style="font-variation-settings:'FILL' 1;">storefront</span>
                Keşfedilecek Mekanlar
            </h2>
            <a href="<?php echo BASE_URL; ?>/venues" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold">Tümünü Gör →</a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <?php foreach ($newVenues as $v): ?>
            <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $v['id']; ?>"
               class="swarm-glass-card rounded-xl overflow-hidden border border-outline-variant/20 hover:border-tertiary/40 transition-all group active:scale-[0.98] flex flex-col">

                <div class="h-28 bg-surface-container relative overflow-hidden flex-shrink-0">
                    <?php if (!empty($v['cover_image'])): ?>
                        <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($v['cover_image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php elseif (!empty($v['image'])): ?>
                        <img src="<?php echo uploadUrl('posts', $v['image']); ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-surface-container-high">
                            <span class="material-symbols-outlined text-on-surface-variant text-3xl">storefront</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($v['category']): ?>
                    <span class="absolute top-2 right-2 bg-black/60 backdrop-blur text-[9px] font-bold px-1.5 py-0.5 rounded-md border border-white/10 text-white">
                        <?php echo escape($categories[$v['category']] ?? $v['category']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="p-3 flex-grow">
                    <div class="font-bold text-xs text-on-surface group-hover:text-tertiary transition-colors truncate"><?php echo escape($v['name']); ?></div>
                    <div class="text-[9px] text-on-surface-variant mt-1 flex items-center gap-1">
                        <span class="material-symbols-outlined text-[10px]">location_on</span>
                        <?php echo (int)($v['checkin_count'] ?? 0); ?> check-in
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
                <span class="material-symbols-outlined text-primary text-lg" style="font-variation-settings:'FILL' 1;">history</span>
                Son Check-in'lerim
            </h2>
            <a href="<?php echo BASE_URL; ?>/profile" class="text-xs text-on-surface-variant hover:text-primary transition-colors font-semibold">Tüm Geçmiş →</a>
        </div>

        <?php if (empty($recentCheckins)): ?>
        <div class="swarm-glass-card rounded-xl p-8 text-center border border-outline-variant/20">
            <span class="material-symbols-outlined text-on-surface-variant text-4xl mb-3 block opacity-40">location_off</span>
            <p class="text-sm text-on-surface-variant mb-4">Henüz check-in yapmadın.</p>
            <a href="<?php echo BASE_URL; ?>/venues" class="inline-flex items-center gap-2 bg-primary-container text-white px-5 py-2.5 rounded-lg font-bold text-sm hover:brightness-110 transition-all active:scale-95 shadow-[0_0_15px_rgba(255,145,0,0.3)]">
                <span class="material-symbols-outlined text-base" style="font-variation-settings:'FILL' 1;">add_location_alt</span>
                İlk Check-in'ini Yap
            </a>
        </div>
        <?php else: ?>
        <div class="swarm-glass-card rounded-xl border border-outline-variant/20 overflow-hidden divide-y divide-white/5">
            <?php foreach ($recentCheckins as $ci): ?>
            <?php include __DIR__ . '/partials/_checkin_row.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
