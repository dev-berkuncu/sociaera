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

// Premium erken erişim kontrolü
$isPremium = UserModel::isPremiumActive((new UserModel())->getById(Auth::id()));
$earlyAccessHours = $isPremium ? 24 : 0;

// ── Aktif kampanyalar (tüm mekanlardan) ──────────────────
$stmtActive = $db->prepare("
    SELECT vc.*, v.name as venue_name, v.id as venue_id, v.cover_image, v.image as venue_image, v.category,
           COUNT(cr.id) as redemption_count
    FROM venue_campaigns vc
    JOIN venues v ON vc.venue_id = v.id
    LEFT JOIN campaign_redemptions cr ON cr.campaign_id = vc.id
    WHERE vc.is_active = 1
      AND v.status = 'approved'
      AND (vc.starts_at IS NULL OR vc.starts_at <= DATE_ADD(NOW(), INTERVAL ? HOUR))
      AND (vc.ends_at IS NULL OR vc.ends_at >= NOW())
      AND (vc.max_redemptions IS NULL OR (SELECT COUNT(*) FROM campaign_redemptions WHERE campaign_id = vc.id) < vc.max_redemptions)
    GROUP BY vc.id
    ORDER BY vc.created_at DESC
");
$stmtActive->execute([$earlyAccessHours]);
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

<div style="min-width:0;" class="flex-1 flex flex-col gap-6 max-w-3xl w-full mx-auto lg:mx-0">

    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 mb-2">
        <div>
            <h1 class="text-3xl font-black tracking-tight flex items-center gap-3" style="color:var(--text-1);">
                <span class="material-symbols-outlined text-purple-400 text-[32px]">campaign</span>
                Kampanyalar
            </h1>
            <p class="text-sm mt-1" style="color:var(--text-3);">Mekanların sunduğu fırsatları keşfet</p>
        </div>
    </div>

    <!-- ── Aktif Kampanyalar ── -->
    <?php if (!empty($activeCampaigns)): ?>
    <div>
        <h2 class="text-base font-bold flex items-center gap-2 mb-3" style="color:var(--text-1);">
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
            <div class="rounded-xl overflow-hidden transition-colors" style="background:#fff; border:1.5px solid <?php echo $hasEarned ? 'rgba(16,185,129,0.3)' : 'var(--border)'; ?>; box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <div class="flex items-start gap-4 p-5">
                    <!-- Mekan logo -->
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $c['venue_id']; ?>" class="w-14 h-14 rounded-xl overflow-hidden flex items-center justify-center flex-shrink-0 transition-colors" style="background:var(--bg-section); border:1px solid var(--border);">
                        <?php if (!empty($c['cover_image'])): ?>
                            <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($c['cover_image']); ?>" class="w-full h-full object-contain p-1" width="56" height="56" loading="lazy">
                        <?php elseif (!empty($c['venue_image'])): ?>
                            <img src="<?php echo uploadUrl('posts', $c['venue_image']); ?>" class="w-full h-full object-contain p-1" width="56" height="56" loading="lazy">
                        <?php else: ?>
                            <span class="material-symbols-outlined text-[24px]" style="color:var(--text-3);">store</span>
                        <?php endif; ?>
                    </a>

                    <!-- Kampanya bilgisi -->
                    <div class="flex-grow min-w-0">
                        <div class="flex items-start gap-2 flex-wrap">
                            <h3 class="font-bold" style="color:var(--text-1);"><?php echo escape($c['title']); ?></h3>
                            <?php if ($hasEarned): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" style="background:rgba(22,163,74,0.08); color:#16a34a; border:1px solid rgba(22,163,74,0.25);">✓ Kazanıldı</span>
                            <?php endif; ?>
                            <?php if ($isPremium && $c['starts_at'] && strtotime($c['starts_at']) > time()): ?>
                                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" style="background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid rgba(59,130,246,0.2);">Erken Erişim 💎</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $c['venue_id']; ?>" class="text-xs font-semibold hover:underline" style="color:var(--color-primary);"><?php echo escape($c['venue_name']); ?></a>

                        <!-- Kural -->
                        <div class="text-sm mt-1.5" style="color:var(--text-2);">
                            <span class="font-bold text-purple-400"><?php echo CampaignModel::formatReward($c); ?></span>
                            <span style="color:var(--text-3);"> — </span>
                            <?php echo escape(CampaignModel::formatTrigger($c)); ?>
                        </div>

                        <?php if ($c['description']): ?>
                            <p class="text-xs mt-1" style="color:var(--text-3);"><?php echo escape($c['description']); ?></p>
                        <?php endif; ?>

                        <!-- İlerleme çubuğu -->
                        <?php if (!$hasEarned && $c['trigger_type'] !== 'first_checkin'): ?>
                        <div class="mt-2.5">
                            <div class="flex justify-between text-xs mb-1" style="color:var(--text-3);">
                                <span><?php echo $userCheckins; ?> / <?php echo $target; ?> check-in</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div class="h-1.5 rounded-full overflow-hidden" style="background:var(--bg-section);">
                                <div class="h-full rounded-full transition-all duration-700" style="width:<?php echo $progress; ?>%; background:var(--color-primary);"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Kazanılmış kod -->
                        <?php if ($hasEarned && $myRedemption): ?>
                        <div class="mt-2.5 flex items-center gap-2 rounded-lg px-3 py-2 flex-wrap" style="background:rgba(22,163,74,0.08); border:1px solid rgba(22,163,74,0.2);">
                            <span class="material-symbols-outlined text-[16px]" style="color:#16a34a;">confirmation_number</span>
                            <span class="text-xs" style="color:var(--text-2);">Kodun:</span>
                            <code class="font-mono font-bold tracking-widest" style="color:#16a34a;"><?php echo escape($myRedemption['code'] ?? '——'); ?></code>
                            <span class="text-xs" style="color:var(--text-3);">— kasaya göster</span>
                        </div>
                        <?php endif; ?>

                        <!-- Meta -->
                        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-xs" style="color:var(--text-3);">
                            <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">redeem</span> <?php echo (int)$c['redemption_count']; ?> kazanım</span>
                            <?php if ($c['max_redemptions']): ?>
                                <span>/ <?php echo $c['max_redemptions']; ?> max</span>
                            <?php endif; ?>
                            <?php if ($c['ends_at']): ?>
                                <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> <?php echo formatDate($c['ends_at']); ?>'e kadar</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="rounded-xl p-10 text-center" style="background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <span class="material-symbols-outlined text-[48px] mb-3 opacity-40 block" style="color:var(--text-3);">campaign</span>
        <p class="font-bold" style="color:var(--text-2);">Şu an aktif kampanya yok.</p>
        <p class="text-sm mt-1" style="color:var(--text-3);">Mekanlar yeni kampanyalar eklediğinde burada görünecek!</p>
    </div>
    <?php endif; ?>

    <!-- ── Sona Eren Kampanyalar ── -->
    <?php if (!empty($endedCampaigns)): ?>
    <div>
        <h2 class="text-base font-bold flex items-center gap-2 mb-3" style="color:var(--text-2);">
            <span class="material-symbols-outlined text-[18px]">history</span>
            Sona Eren Kampanyalar
        </h2>
        <div class="flex flex-col gap-2">
            <?php foreach ($endedCampaigns as $c):
                $hasEarned = in_array($c['id'], $earnedCampaignIds);
                $myRedemption = $earnedCodes[$c['id']] ?? null;
            ?>
            <div class="rounded-xl p-4 flex items-center gap-4 opacity-70" style="background:#fff; border:1px solid var(--border);">
                <!-- İkon -->
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:var(--bg-section); border:1px solid var(--border-light);">
                    <span class="material-symbols-outlined text-[20px]" style="color:var(--text-3);">
                        <?php echo $hasEarned ? 'check_circle' : ($c['reward_type'] === 'free_item' ? 'redeem' : 'percent'); ?>
                    </span>
                </div>

                <!-- Bilgi -->
                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-sm truncate" style="color:var(--text-2);"><?php echo escape($c['title']); ?></h3>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-bold" style="<?php echo $hasEarned ? 'background:rgba(22,163,74,0.08); color:#16a34a;' : 'background:var(--bg-section); color:var(--text-3);'; ?>">
                            <?php echo $hasEarned ? '✓ Kazanıldı' : 'Sona Erdi'; ?>
                        </span>
                    </div>
                    <div class="text-xs mt-0.5" style="color:var(--text-3);">
                        <?php echo escape($c['venue_name']); ?> — <?php echo CampaignModel::formatReward($c); ?>
                    </div>
                    <?php if ($hasEarned && $myRedemption): ?>
                    <div class="text-xs mt-0.5" style="color:#16a34a;">
                        Kodun: <code class="font-mono font-bold tracking-wider"><?php echo escape($myRedemption['code'] ?? '——'); ?></code>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($c['ends_at']): ?>
                <span class="text-[10px] flex-shrink-0" style="color:var(--text-3);"><?php echo formatDate($c['ends_at']); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
