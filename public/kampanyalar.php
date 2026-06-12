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

// ── Aktif kampanyalar ─────────────────────────────────────
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

// ── Süresi dolmuş / pasif kampanyalar ────────────────────
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

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:768px; width:100%; padding-bottom:40px;">

    <!-- Başlık -->
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
        <div>
            <h1 style="font-size:1.8rem; font-weight:900; letter-spacing:-.02em; display:flex; align-items:center; gap:10px; color:var(--text-1); margin:0 0 4px;">
                <span class="material-symbols-outlined" style="color:#a855f7; font-size:32px;">campaign</span>
                Kampanyalar
            </h1>
            <p style="font-size:13px; margin:0; color:var(--text-3);">Mekanların sunduğu fırsatları keşfet</p>
        </div>
    </div>

    <!-- ── Aktif Kampanyalar ── -->
    <?php if (!empty($activeCampaigns)): ?>
    <div>
        <h2 style="font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; margin:0 0 10px; color:var(--text-1);">
            <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:#10b981;"></span>
            Aktif Kampanyalar (<?php echo count($activeCampaigns); ?>)
        </h2>
        <div style="display:flex; flex-direction:column; gap:10px;">
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
            <div style="background:#fff; border:1.5px solid <?php echo $hasEarned ? 'rgba(16,185,129,0.3)' : 'var(--border)'; ?>; border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);">
                <div style="display:flex; align-items:flex-start; gap:14px; padding:16px;">
                    <!-- Mekan logo -->
                    <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $c['venue_id']; ?>"
                       style="width:56px; height:56px; border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:var(--bg-section); border:1px solid var(--border); text-decoration:none;">
                        <?php if (!empty($c['cover_image'])): ?>
                            <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($c['cover_image']); ?>" style="width:100%; height:100%; object-fit:contain; padding:4px; box-sizing:border-box;" width="56" height="56" loading="lazy">
                        <?php elseif (!empty($c['venue_image'])): ?>
                            <img src="<?php echo uploadUrl('posts', $c['venue_image']); ?>" style="width:100%; height:100%; object-fit:contain; padding:4px; box-sizing:border-box;" width="56" height="56" loading="lazy">
                        <?php else: ?>
                            <span class="material-symbols-outlined" style="font-size:24px; color:var(--text-3);">store</span>
                        <?php endif; ?>
                    </a>

                    <!-- Kampanya bilgisi -->
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:flex-start; gap:8px; flex-wrap:wrap; margin-bottom:2px;">
                            <h3 style="font-size:14px; font-weight:700; color:var(--text-1); margin:0;"><?php echo escape($c['title']); ?></h3>
                            <?php if ($hasEarned): ?>
                                <span style="font-size:10px; padding:2px 8px; border-radius:20px; font-weight:700; background:rgba(22,163,74,0.08); color:#16a34a; border:1px solid rgba(22,163,74,0.25);">✓ Kazanıldı</span>
                            <?php endif; ?>
                            <?php if ($isPremium && $c['starts_at'] && strtotime($c['starts_at']) > time()): ?>
                                <span style="font-size:10px; padding:2px 8px; border-radius:20px; font-weight:700; background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid rgba(59,130,246,0.2);">Erken Erişim 💎</span>
                            <?php endif; ?>
                        </div>

                        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo $c['venue_id']; ?>" style="font-size:12px; font-weight:600; color:var(--color-primary); text-decoration:none;"><?php echo escape($c['venue_name']); ?></a>

                        <!-- Kural -->
                        <div style="font-size:13px; margin-top:6px; color:var(--text-2);">
                            <span style="font-weight:700; color:#a855f7;"><?php echo CampaignModel::formatReward($c); ?></span>
                            <span style="color:var(--text-3);"> — </span>
                            <?php echo escape(CampaignModel::formatTrigger($c)); ?>
                        </div>

                        <?php if ($c['description']): ?>
                            <p style="font-size:12px; margin:4px 0 0; color:var(--text-3);"><?php echo escape($c['description']); ?></p>
                        <?php endif; ?>

                        <!-- İlerleme çubuğu -->
                        <?php if (!$hasEarned && $c['trigger_type'] !== 'first_checkin'): ?>
                        <div style="margin-top:10px;">
                            <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:4px; color:var(--text-3);">
                                <span><?php echo $userCheckins; ?> / <?php echo $target; ?> check-in</span>
                                <span><?php echo $progress; ?>%</span>
                            </div>
                            <div style="height:6px; border-radius:999px; overflow:hidden; background:var(--bg-section);">
                                <div style="height:100%; border-radius:999px; transition:width .7s; width:<?php echo $progress; ?>%; background:var(--color-primary);"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Kazanılmış kod -->
                        <?php if ($hasEarned && $myRedemption): ?>
                        <div style="margin-top:10px; display:flex; align-items:center; gap:8px; border-radius:8px; padding:8px 12px; flex-wrap:wrap; background:rgba(22,163,74,0.08); border:1px solid rgba(22,163,74,0.2);">
                            <span class="material-symbols-outlined" style="font-size:16px; color:#16a34a;">confirmation_number</span>
                            <span style="font-size:12px; color:var(--text-2);">Kodun:</span>
                            <code style="font-family:monospace; font-weight:700; letter-spacing:.1em; color:#16a34a;"><?php echo escape($myRedemption['code'] ?? '——'); ?></code>
                            <span style="font-size:12px; color:var(--text-3);">— kasaya göster</span>
                        </div>
                        <?php endif; ?>

                        <!-- Meta -->
                        <div style="display:flex; flex-wrap:wrap; column-gap:16px; row-gap:4px; margin-top:8px; font-size:11px; color:var(--text-3);">
                            <span style="display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:14px;">redeem</span> <?php echo (int)$c['redemption_count']; ?> kazanım</span>
                            <?php if ($c['max_redemptions']): ?>
                                <span>/ <?php echo $c['max_redemptions']; ?> max</span>
                            <?php endif; ?>
                            <?php if ($c['ends_at']): ?>
                                <span style="display:flex; align-items:center; gap:4px;"><span class="material-symbols-outlined" style="font-size:14px;">schedule</span> <?php echo formatDate($c['ends_at']); ?>'e kadar</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div style="background:#fff; border:1px solid var(--border); border-radius:14px; box-shadow:0 1px 3px rgba(0,0,0,.08); padding:48px 16px; text-align:center;">
        <span class="material-symbols-outlined" style="font-size:48px; color:var(--text-3); opacity:.4; display:block; margin-bottom:12px;">campaign</span>
        <p style="font-weight:700; color:var(--text-2); margin:0 0 4px;">Şu an aktif kampanya yok.</p>
        <p style="font-size:13px; color:var(--text-3); margin:0;">Mekanlar yeni kampanyalar eklediğinde burada görünecek!</p>
    </div>
    <?php endif; ?>

    <!-- ── Sona Eren Kampanyalar ── -->
    <?php if (!empty($endedCampaigns)): ?>
    <div>
        <h2 style="font-size:14px; font-weight:700; display:flex; align-items:center; gap:8px; margin:0 0 10px; color:var(--text-2);">
            <span class="material-symbols-outlined" style="font-size:18px;">history</span>
            Sona Eren Kampanyalar
        </h2>
        <div style="display:flex; flex-direction:column; gap:8px;">
            <?php foreach ($endedCampaigns as $c):
                $hasEarned = in_array($c['id'], $earnedCampaignIds);
                $myRedemption = $earnedCodes[$c['id']] ?? null;
            ?>
            <div style="background:#fff; border:1px solid var(--border); border-radius:14px; padding:14px 16px; display:flex; align-items:center; gap:14px; opacity:.7;">
                <!-- İkon -->
                <div style="width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:var(--bg-section); border:1px solid var(--border);">
                    <span class="material-symbols-outlined" style="font-size:20px; color:var(--text-3);">
                        <?php echo $hasEarned ? 'check_circle' : ($c['reward_type'] === 'free_item' ? 'redeem' : 'percent'); ?>
                    </span>
                </div>

                <!-- Bilgi -->
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <h3 style="font-size:13px; font-weight:700; color:var(--text-2); margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:200px;"><?php echo escape($c['title']); ?></h3>
                        <span style="font-size:10px; padding:2px 8px; border-radius:20px; font-weight:700; <?php echo $hasEarned ? 'background:rgba(22,163,74,0.08); color:#16a34a;' : 'background:var(--bg-section); color:var(--text-3);'; ?>">
                            <?php echo $hasEarned ? '✓ Kazanıldı' : 'Sona Erdi'; ?>
                        </span>
                    </div>
                    <div style="font-size:12px; margin-top:2px; color:var(--text-3);">
                        <?php echo escape($c['venue_name']); ?> — <?php echo CampaignModel::formatReward($c); ?>
                    </div>
                    <?php if ($hasEarned && $myRedemption): ?>
                    <div style="font-size:12px; margin-top:2px; color:#16a34a;">
                        Kodun: <code style="font-family:monospace; font-weight:700; letter-spacing:.08em;"><?php echo escape($myRedemption['code'] ?? '——'); ?></code>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($c['ends_at']): ?>
                <span style="font-size:10px; flex-shrink:0; color:var(--text-3);"><?php echo formatDate($c['ends_at']); ?></span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
