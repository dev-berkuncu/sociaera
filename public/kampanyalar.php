<?php
/**
 * Sociaera — Kampanyalar (Kullanıcı Tarafı)
 * Tüm mekanlardaki aktif ve bitmiş kampanyaları görüntüler
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Models/Campaign.php';

Auth::requireLogin();

$db = Database::getConnection();
$campaignModel = new CampaignModel();

// ── Aktif kampanyalar (tüm mekanlardan) ──────────────────
$stmtActive = $db->prepare("
    SELECT vc.*, v.name as venue_name, v.id as venue_id, v.cover_image, v.image as venue_image, v.category,
           COUNT(cr.id) as redemption_count
    FROM venue_campaigns vc
    JOIN venues v ON vc.venue_id = v.id
    LEFT JOIN campaign_redemptions cr ON cr.campaign_id = vc.id
    WHERE vc.is_active = 1
      AND v.status = 'approved'
      AND (vc.starts_at IS NULL OR vc.starts_at <= NOW())
      AND (vc.ends_at IS NULL OR vc.ends_at >= NOW())
      AND (vc.max_redemptions IS NULL OR (SELECT COUNT(*) FROM campaign_redemptions WHERE campaign_id = vc.id) < vc.max_redemptions)
    GROUP BY vc.id
    ORDER BY vc.created_at DESC
");
$stmtActive->execute();
$activeCampaigns = $stmtActive->fetchAll();

// ── Süresi dolmuş / pasif kampanyalar ───────────────────
$stmtEnded = $db->prepare("
    SELECT vc.*, v.name as venue_name, v.id as venue_id, v.cover_image, v.image as venue_image, v.category,
           COUNT(cr.id) as redemption_count
    FROM venue_campaigns vc
    JOIN venues v ON vc.venue_id = v.id
    LEFT JOIN campaign_redemptions cr ON cr.campaign_id = vc.id
    WHERE v.status = 'approved'
      AND (
          vc.is_active = 0
          OR (vc.ends_at IS NOT NULL AND vc.ends_at < NOW())
          OR (vc.max_redemptions IS NOT NULL AND (SELECT COUNT(*) FROM campaign_redemptions WHERE campaign_id = vc.id) >= vc.max_redemptions)
      )
    GROUP BY vc.id
    ORDER BY vc.ends_at DESC, vc.created_at DESC
    LIMIT 20
");
$stmtEnded->execute();
$endedCampaigns = $stmtEnded->fetchAll();

// Kullanıcının kazanımları
$userRedemptions = $campaignModel->getUserRedemptions(Auth::id());
$earnedCampaignIds = array_column($userRedemptions, 'campaign_id');
$earnedCodes = [];
foreach ($userRedemptions as $r) {
    $earnedCodes[$r['campaign_id']] = $r;
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Kampanyalar';
$activeNav = 'kampanyalar';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-6 max-w-3xl w-full mx-auto lg:mx-0">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-3xl font-black text-on-surface tracking-tight flex items-center gap-3">
                <span class="material-symbols-outlined text-purple-400 text-[32px]">campaign</span>
                Kampanyalar
            </h1>
            <p class="text-slate-400 text-sm mt-1">Mekanların sunduğu fırsatları keşfet</p>
        </div>
    </div>

    <!-- ── Aktif Kampanyalar ── -->
    <?php if (!empty($activeCampaigns)): ?>
    <div>
        <h2 class="text-base font-bold text-on-surface flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            Aktif Kampanyalar (<?php echo count($activeCampaigns); ?>)
        </h2>
        <div class="flex flex-col gap-3">
            <?php foreach ($activeCampaigns as $c):
                $hasEarned = in_array($c['id'], $earnedCampaignIds);
                $myRedemption = $earnedCodes[$c['id']] ?? null;
                $userCheckins = $campaignModel->getUserCheckinCount(Auth::id(), $c['venue_id']);
                $target = (int)$c['trigger_value'];
                $progress = 0;
                if ($c['trigger_type'] === 'first_checkin') {
                    $progress = $userCheckins >= 1 ? 100 : 0;
                } elseif ($target > 0) {
                    $progress = min(100, round(($userCheckins / $target) * 100));
                }
            ?>
            <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border <?php echo $hasEarned ? 'border-emerald-500/30' : 'border-white/10'; ?> rounded-xl overflow-hidden hover:border-white/20 transition-colors">
                <div class="flex items-start gap-4 p-5">
                    <!-- Mekan logo -->
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $c['venue_id']; ?>" class="w-14 h-14 rounded-xl overflow-hidden bg-surface-container flex items-center justify-center border border-white/10 flex-shrink-0 hover:border-primary-container/50 transition-colors">
                        <?php if (!empty($c['cover_image'])): ?>
                            <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($c['cover_image']); ?>" class="w-full h-full object-contain p-1" width="56" height="56" loading="lazy">
                        <?php elseif (!empty($c['venue_image'])): ?>
                            <img src="<?php echo uploadUrl('posts', $c['venue_image']); ?>" class="w-full h-full object-contain p-1" width="56" height="56" loading="lazy">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-[24px] text-slate-500">store</span>
                        <?php endif; ?>
                    </a>

                    <!-- Kampanya bilgisi -->
                    <div class="flex-grow min-w-0">
                        <div class="flex items-start gap-2 flex-wrap">
                            <h3 class="font-bold text-on-surface"><?php echo escape($c['title']); ?></h3>
                            <?php if ($hasEarned): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">✓ Kazanıldı</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $c['venue_id']; ?>" class="text-xs text-primary-container hover:underline"><?php echo escape($c['venue_name']); ?></a>

                        <!-- Kural -->
                        <div class="text-sm text-slate-300 mt-1.5">
                            <span class="font-semibold text-purple-400"><?php echo CampaignModel::formatReward($c); ?></span>
                            <span class="text-slate-500"> — </span>
                            <?php echo escape(CampaignModel::formatTrigger($c)); ?>
                        </div>

                        <?php if ($c['description']): ?>
                            <p class="text-xs text-slate-500 mt-1"><?php echo escape($c['description']); ?></p>
                        <?php endif; ?>

                        <!-- İlerleme çubuğu -->
                        <?php if (!$hasEarned && $c['trigger_type'] !== 'first_checkin'): ?>
                        <div class="mt-2.5">
                            <div class="flex justify-between text-xs text-slate-500 mb-1">
                                <span><?php echo $userCheckins; ?> / <?php echo $target; ?> check-in</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="h-1.5 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-purple-500 to-pink-500 rounded-full transition-all duration-700" style="width:<?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Kazanılmış kod -->
                        <?php if ($hasEarned && $myRedemption): ?>
                        <div class="mt-2.5 flex items-center gap-2 bg-emerald-500/10 border border-emerald-500/20 rounded-lg px-3 py-2 flex-wrap">
                            <span class="material-symbols-outlined text-emerald-400 text-[16px]">confirmation_number</span>
                            <span class="text-xs text-slate-400">Kodun:</span>
                            <code class="font-mono font-bold text-emerald-400 tracking-widest"><?php echo escape($myRedemption['code'] ?? '——'); ?></code>
                            <span class="text-xs text-slate-500">— kasaya göster</span>
                        </div>
                        <?php endif; ?>

                        <!-- Meta -->
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs text-slate-500">
                            <span><span class="material-symbols-outlined text-[12px] align-middle">redeem</span> <?php echo (int)$c['redemption_count']; ?> kazanım</span>
                            <?php if ($c['max_redemptions']): ?>
                                <span>/ <?php echo $c['max_redemptions']; ?> max</span>
                            <?php endif; ?>
                            <?php if ($c['ends_at']): ?>
                                <span><span class="material-symbols-outlined text-[12px] align-middle">schedule</span> <?php echo formatDate($c['ends_at']); ?>'e kadar</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-10 text-center text-slate-400">
        <span class="material-symbols-outlined text-[48px] mb-3 opacity-40 block">campaign</span>
        <p class="font-semibold">Şu an aktif kampanya yok.</p>
        <p class="text-sm mt-1">Mekanlar yeni kampanyalar eklediğinde burada görünecek!</p>
    </div>
    <?php endif; ?>

    <!-- ── Sona Eren Kampanyalar ── -->
    <?php if (!empty($endedCampaigns)): ?>
    <div>
        <h2 class="text-base font-bold text-on-surface flex items-center gap-2 mb-3">
            <span class="material-symbols-outlined text-slate-500 text-[18px]">history</span>
            Sona Eren Kampanyalar
        </h2>
        <div class="flex flex-col gap-2">
            <?php foreach ($endedCampaigns as $c):
                $hasEarned = in_array($c['id'], $earnedCampaignIds);
                $myRedemption = $earnedCodes[$c['id']] ?? null;
            ?>
            <div class="bg-[#1E293B]/50 border border-white/5 rounded-xl p-4 flex items-center gap-4 opacity-60">
                <!-- İkon -->
                <div class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-slate-500 text-[20px]">
                        <?php echo $hasEarned ? 'check_circle' : ($c['reward_type'] === 'free_item' ? 'redeem' : 'percent'); ?>
                    </span>
                </div>

                <!-- Bilgi -->
                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-semibold text-slate-300 text-sm truncate"><?php echo escape($c['title']); ?></h3>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?php echo $hasEarned ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'; ?>">
                            <?php echo $hasEarned ? '✓ Kazanıldı' : 'Sona Erdi'; ?>
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        <?php echo escape($c['venue_name']); ?> — <?php echo CampaignModel::formatReward($c); ?>
                    </div>
                    <?php if ($hasEarned && $myRedemption): ?>
                    <div class="text-xs text-emerald-400/70 mt-0.5">
                        Kodun: <code class="font-mono font-bold tracking-wider"><?php echo escape($myRedemption['code'] ?? '——'); ?></code>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($c['ends_at']): ?>
                <span class="text-[10px] text-slate-600 flex-shrink-0"><?php echo formatDate($c['ends_at']); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
