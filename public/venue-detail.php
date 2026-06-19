<?php
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
require_once __DIR__ . '/../app/Models/Campaign.php';

Auth::requireLogin();

$venueId = (int)($_GET['id'] ?? 0);
if (!$venueId) Response::notFound('Mekan bulunamadı.');

$venueModel = new VenueModel();
$venue = $venueModel->getById($venueId);
if (!$venue || $venue['status'] !== 'approved') Response::notFound('Mekan bulunamadı.');

$checkinModel = new CheckinModel();
$checkinCount = $venueModel->getCheckinCount($venueId);
$posts = $checkinModel->getVenueCheckins($venueId, 1, 30, Auth::id());

// Rating verileri
$ratingData = $venueModel->getVenueRating($venueId);
$averageRating = round((float) $ratingData['average_rating'], 1);
$ratingCount = (int) $ratingData['rating_count'];
$userRating = Auth::check() ? $venueModel->getUserRating($venueId, Auth::id()) : 0;

// Kampanya verileri
$campaignModel   = new CampaignModel();
$activeCampaigns = $campaignModel->getActiveCampaigns($venueId);
$userCheckinHere = Auth::check() ? $campaignModel->getUserCheckinCount(Auth::id(), $venueId) : 0;

// Favori durumu (Premium)
$isFavorited = false;
$favoriteCount = 0;
$currentUserData = Auth::check() ? (new UserModel())->getById(Auth::id()) : null;
$isPremiumUser = UserModel::isPremiumActive($currentUserData);
try {
    $favoriteCount = $venueModel->getFavoriteCount($venueId);
    if (Auth::check()) {
        $isFavorited = $venueModel->isFavorited(Auth::id(), $venueId);
    }
} catch (\Throwable $e) {}

// Favori toggle POST handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_favorite') {
    Csrf::requireValid();
    if (!$isPremiumUser) {
        Auth::setFlash('error', 'Bu özellik Premium üyelere özeldir. 💎');
    } else {
        if ($isFavorited) {
            $venueModel->removeFavorite(Auth::id(), $venueId);
            Auth::setFlash('success', 'Mekan favorilerden kaldırıldı.');
        } else {
            $venueModel->addFavorite(Auth::id(), $venueId);
            Auth::setFlash('success', 'Mekan favorilere eklendi! ⭐');
        }
    }
    header('Location: ' . BASE_URL . '/venue-detail?id=' . $venueId);
    exit;
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = $venue['name'];
$activeNav = 'venues';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; padding-bottom:40px; max-width:768px; width:100%;">
    <a href="<?php echo BASE_URL; ?>/venues"
       style="display:inline-flex; align-items:center; gap:6px; color:var(--text-3); text-decoration:none; font-size:13px; font-weight:600; margin-bottom:8px; transition:color .15s;"
       onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-3)'">
        <span class="material-symbols-outlined" style="font-size:20px;">arrow_back</span> Mekanlar
    </a>

    <div style="background:#fff; border:1.5px solid var(--border-light); border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.06); margin-bottom:20px; position:relative;">
        <!-- Banner Image -->
        <div style="height:200px; width:100%; position:relative; background:var(--bg-section);">
            <div style="position:absolute; inset:0; z-index:1; background:linear-gradient(to top,rgba(255,255,255,0.85) 0%,transparent 60%);"></div>
            <?php if (!empty($venue['cover_image'])): ?>
                <div style="position:absolute; inset:0; background-image:url('<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>'); background-size:cover; background-position:center; filter:blur(20px); opacity:0.4; transform:scale(1.1);"></div>
                <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>" style="width:100%; height:100%; object-fit:contain; padding:16px; box-sizing:border-box; position:relative; z-index:2; display:block;" width="800" height="200">
            <?php elseif (!empty($venue['image'])): ?>
                <div style="position:absolute; inset:0; background-image:url('<?php echo uploadUrl('venues', $venue['image']); ?>'); background-size:cover; background-position:center; filter:blur(20px); opacity:0.4; transform:scale(1.1);"></div>
                <img src="<?php echo uploadUrl('venues', $venue['image']); ?>" style="width:100%; height:100%; object-fit:contain; padding:16px; box-sizing:border-box; position:relative; z-index:2; display:block;" width="800" height="200">
            <?php else: ?>
                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; position:relative; z-index:2; color:var(--text-3);"><span class="material-symbols-outlined" style="font-size:64px;">store</span></div>
            <?php endif; ?>

            <!-- Category Badge -->
            <?php if ($venue['category']): ?>
            <div style="position:absolute; top:12px; left:12px; z-index:10;">
                <span style="font-size:11px; font-weight:800; padding:4px 12px; border-radius:999px; text-transform:uppercase; letter-spacing:.06em; background:rgba(26,26,26,0.75); color:#fff; border:1px solid rgba(255,255,255,0.2);">
                    <?php echo escape(VenueModel::categories()[$venue['category']] ?? $venue['category']); ?>
                </span>
            </div>
            <?php endif; ?>

            <!-- Open/Close Badge -->
            <?php if (isset($venue['is_open'])): ?>
            <div style="position:absolute; top:12px; right:12px; z-index:10; display:flex; align-items:center; gap:8px; font-size:12px; font-weight:700; padding:4px 12px; border-radius:999px; background:rgba(26,26,26,0.75); border:1px solid rgba(255,255,255,0.2); color:<?php echo $venue['is_open'] ? '#10b981' : '#ef4444'; ?>;">
                <span style="width:8px; height:8px; border-radius:50%; background:<?php echo $venue['is_open'] ? '#10b981' : '#ef4444'; ?>; box-shadow:0 0 8px <?php echo $venue['is_open'] ? 'rgba(16,185,129,0.8)' : 'rgba(239,68,68,0.8)'; ?>; flex-shrink:0;"></span>
                <?php echo $venue['is_open'] ? 'Şu An Açık' : 'Şu An Kapalı'; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Venue Content -->
        <div style="padding:24px; position:relative; z-index:5;">
            <div style="display:flex; flex-wrap:wrap; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:20px;">
                <div>
                    <h1 style="font-size:2rem; font-weight:900; letter-spacing:-.02em; color:var(--text-1); margin:0;"><?php echo escape($venue['name']); ?></h1>
                </div>
                <!-- CTA buttons -->
                <div style="display:flex; align-items:center; gap:10px; flex-shrink:0;">
                    <button onclick="openVenueCheckinModal()" id="btn-venue-checkin"
                            style="display:flex; align-items:center; justify-content:center; gap:8px; background:var(--color-primary); color:#fff; padding:11px 22px; border-radius:12px; font-weight:700; font-size:14px; box-shadow:0 4px 16px rgba(240,109,31,0.3); transition:opacity .15s; border:none; cursor:pointer; font-family:inherit; white-space:nowrap;"
                            onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        <span class="material-symbols-outlined" style="font-size:20px; font-variation-settings:'FILL' 1;">add_location_alt</span>
                        Burada Check-in Yap
                    </button>
                    <!-- Favori Butonu (Premium) -->
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                        <input type="hidden" name="action" value="toggle_favorite">
                        <button type="submit"
                                style="display:flex; align-items:center; justify-content:center; gap:6px; padding:11px 14px; border-radius:12px; font-weight:700; font-size:14px; transition:all .15s; flex-shrink:0; font-family:inherit; <?php echo $isFavorited ? 'background:rgba(245,158,11,0.15);color:#f59e0b;border:1.5px solid rgba(245,158,11,0.35);' : 'background:var(--bg-section);color:var(--text-3);border:1.5px solid var(--border);' . (!$isPremiumUser ? 'cursor:not-allowed;' : ''); ?>"
                                <?php echo !$isPremiumUser ? 'title="Premium özellik 💎"' : ''; ?>>
                            <span class="material-symbols-outlined" style="font-size:20px; font-variation-settings:'FILL' <?php echo $isFavorited ? '1' : '0'; ?>;" >star</span>
                            <span style="font-size:13px;"><?php echo $favoriteCount; ?></span>
                        </button>
                    </form>
                    <!-- Rapor Butonu -->
                    <button onclick="App.openReportModal('venue', <?php echo $venue['id']; ?>)"
                            style="display:flex; align-items:center; justify-content:center; gap:6px; padding:11px 14px; border-radius:12px; font-weight:700; font-size:14px; transition:all .15s; flex-shrink:0; font-family:inherit; background:var(--bg-section);color:var(--text-3);border:1.5px solid var(--border); cursor:pointer;"
                            title="Mekanı Şikayet Et"
                            onmouseover="this.style.background='rgba(245,158,11,0.08)';this.style.color='var(--color-primary)';this.style.borderColor='var(--color-primary)';" onmouseout="this.style.background='var(--bg-section)';this.style.color='var(--text-3)';this.style.borderColor='var(--border)';">
                        <span class="material-symbols-outlined" style="font-size:20px;">flag</span>
                    </button>
                </div>
            </div>

            <?php if ($venue['description']): ?>
                <p style="margin:0 0 24px; line-height:1.7; font-size:1rem; color:var(--text-2); max-width:640px;"><?php echo nl2brSafe($venue['description']); ?></p>
            <?php endif; ?>

            <!-- Information Grid -->
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px; margin-bottom:8px;">
                <div style="display:flex; flex-direction:column; gap:14px; border-radius:12px; padding:18px; background:var(--bg-section); border:1px solid var(--border);">
                    <?php if ($venue['address']): ?>
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <span class="material-symbols-outlined" style="font-size:22px; flex-shrink:0; margin-top:2px; color:var(--color-primary);">map</span>
                            <span style="color:var(--text-2); line-height:1.5;"><?php echo escape($venue['address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($venue['hours'])): ?>
                        <div style="display:flex; align-items:flex-start; gap:12px;">
                            <span class="material-symbols-outlined" style="font-size:22px; flex-shrink:0; color:var(--color-primary);">schedule</span>
                            <span style="color:var(--text-2);"><?php echo escape($venue['hours']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($venue['phone'])): ?>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="material-symbols-outlined" style="font-size:22px; flex-shrink:0; color:var(--color-primary);">call</span>
                            <span style="color:var(--text-2);"><?php echo escape($venue['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="display:flex; flex-direction:column; gap:14px; border-radius:12px; padding:18px; background:var(--bg-section); border:1px solid var(--border);">
                    <?php if ($venue['website']): ?>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="material-symbols-outlined" style="font-size:22px; color:#3b82f6;">language</span>
                            <a href="<?php echo safeHref($venue['website']); ?>" target="_blank" rel="noopener noreferrer"
                               style="color:var(--text-2); text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Resmi Web Sitesi</a>
                        </div>
                    <?php endif; ?>
                    <?php if ($venue['facebrowser_url']): ?>
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span class="material-symbols-outlined" style="font-size:22px; color:#3b5998;">link</span>
                            <a href="<?php echo safeHref($venue['facebrowser_url']); ?>" target="_blank" rel="noopener noreferrer"
                               style="color:var(--text-2); text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Facebrowser Sayfası</a>
                        </div>
                    <?php endif; ?>

                    <!-- Stats -->
                    <div style="display:flex; align-items:center; gap:12px; margin-top:auto; padding-top:8px;">
                        <span class="material-symbols-outlined" style="font-size:22px; color:#10b981;">verified_user</span>
                        <span style="color:var(--text-2);"><strong style="color:var(--text-1); font-size:1.1rem;"><?php echo $checkinCount; ?></strong> Toplam Check-in</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Section -->
    <div style="border-radius:16px; padding:24px; margin-bottom:20px; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);" id="venueRatingSection">
        <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:24px;">
            <!-- Left: Average Rating Display -->
            <div style="display:flex; align-items:center; gap:20px;">
                <div style="display:flex; flex-direction:column; align-items:center; justify-content:center; width:80px; height:80px; border-radius:16px; background:linear-gradient(135deg,rgba(245,158,11,0.2),rgba(249,115,22,0.1)); border:1px solid rgba(245,158,11,0.2);">
                    <span style="font-size:1.875rem; font-weight:900; color:#f59e0b; line-height:1;" id="ratingAvgDisplay"><?php echo $averageRating > 0 ? number_format($averageRating, 1) : '—'; ?></span>
                    <span style="font-size:10px; color:rgba(245,158,11,0.7); font-weight:600; text-transform:uppercase; letter-spacing:.05em; margin-top:2px;">/ 5</span>
                </div>
                <div style="display:flex; flex-direction:column; gap:4px;">
                    <h3 style="font-size:1.1rem; font-weight:700; display:flex; align-items:center; gap:8px; color:var(--text-1); margin:0;">
                        <span class="material-symbols-outlined" style="color:#f59e0b; font-variation-settings:'FILL' 1;">star</span>
                        Mekan Puanı
                    </h3>
                    <p style="font-size:13px; color:var(--text-3); margin:0;">
                        <span id="ratingCountDisplay"><?php echo $ratingCount; ?></span> kişi puan verdi
                    </p>
                    <!-- Average Stars (read-only) -->
                    <div style="display:flex; align-items:center; gap:2px; margin-top:4px;" id="averageStarsDisplay">
                        <?php for ($i = 1; $i <= 5; $i++):
                            if ($averageRating >= $i) $starColor = '#f59e0b';
                            elseif ($averageRating >= $i - 0.5) $starColor = 'rgba(245,158,11,0.5)';
                            else $starColor = '#94a3b8';
                        ?>
                        <span class="material-symbols-outlined" style="font-size:20px; color:<?php echo $starColor; ?>; font-variation-settings:'FILL' 1;">star</span>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Right: User Rating Interactive Stars -->
            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                <?php if (Auth::check()): ?>
                    <span style="font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-3);">
                        <?php echo $userRating > 0 ? 'Puanını güncelle' : 'Puan ver'; ?>
                    </span>
                    <div style="display:flex; align-items:center; gap:4px;" id="userStarRating" data-venue-id="<?php echo $venueId; ?>" data-current="<?php echo $userRating; ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button"
                            class="star-btn"
                            style="background:none; border:none; cursor:pointer; padding:4px; border-radius:8px; transition:background .15s;"
                            data-star="<?php echo $i; ?>"
                            aria-label="<?php echo $i; ?> yıldız"
                            onmouseover="this.style.background='rgba(245,158,11,0.1)'"
                            onmouseout="this.style.background='none'">
                            <span class="material-symbols-outlined" style="font-size:32px; transition:all .2s; color:<?php echo $i <= $userRating ? '#f59e0b' : '#94a3b8'; ?>; font-variation-settings:'FILL' 1;">star</span>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <?php if ($userRating > 0): ?>
                        <span style="font-size:11px; color:rgba(245,158,11,0.7);" id="userRatingLabel">Senin puanın: <strong><?php echo $userRating; ?>/5</strong></span>
                    <?php else: ?>
                        <span style="font-size:11px; color:var(--text-3);" id="userRatingLabel">Henüz puan vermedin</span>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="display:flex; flex-direction:column; align-items:center; gap:8px; border-radius:12px; padding:16px 24px; background:var(--bg-section); border:1px solid var(--border);">
                        <span class="material-symbols-outlined" style="font-size:24px; color:var(--text-3);">lock</span>
                        <p style="font-size:13px; text-align:center; color:var(--text-2); margin:0;">Puan vermek için <a href="<?php echo BASE_URL; ?>/login" style="color:var(--color-primary); font-weight:600; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">giriş yapın</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h2 style="font-size:1.25rem; font-weight:800; margin:0 0 8px; margin-top:16px; display:flex; align-items:center; gap:8px; color:var(--text-1);"><span class="material-symbols-outlined" style="color:var(--color-primary);">history</span> Son Check-in'ler</h2>

    <!-- ── Aktif Kampanyalar ── -->
    <?php if (!empty($activeCampaigns)): ?>
    <div style="border-radius:12px; overflow:hidden; margin-bottom:8px; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="padding:14px 20px; display:flex; align-items:center; gap:8px; border-bottom:1px solid var(--border);">
            <span class="material-symbols-outlined" style="color:#a855f7; font-size:20px;">campaign</span>
            <h2 style="font-size:14px; font-weight:700; color:var(--text-1); margin:0;">Aktif Kampanyalar</h2>
        </div>
        <div>
            <?php foreach ($activeCampaigns as $c):
                $hasEarned = Auth::check() && $campaignModel->hasEarned($c['id'], Auth::id());
                $myCode    = null;
                if ($hasEarned) {
                    $db = Database::getConnection();
                    $stmt = $db->prepare("SELECT code FROM campaign_redemptions WHERE campaign_id = ? AND user_id = ?");
                    $stmt->execute([$c['id'], Auth::id()]);
                    $myCode = $stmt->fetchColumn() ?: null;
                }
                $target   = (int)$c['trigger_value'];
                $progress = 0;
                if ($c['trigger_type'] === 'first_checkin') {
                    $progress = $userCheckinHere >= 1 ? 100 : 0;
                } elseif ($target > 0) {
                    $progress = min(100, round(($userCheckinHere / $target) * 100));
                }
            ?>
            <div style="padding:16px 20px; display:flex; align-items:flex-start; gap:14px; border-bottom:1px solid var(--border-light);">
                <div style="width:40px; height:40px; border-radius:10px; background:<?php echo $hasEarned ? 'rgba(16,185,129,0.12)' : 'rgba(168,85,247,0.12)'; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                    <span class="material-symbols-outlined" style="color:<?php echo $hasEarned ? '#10b981' : '#a855f7'; ?>; font-size:20px;">
                        <?php echo $hasEarned ? 'check_circle' : ($c['reward_type'] === 'free_item' ? 'redeem' : 'percent'); ?>
                    </span>
                </div>
                <div style="flex:1; min-width:0;">
                    <div style="font-weight:600; color:var(--text-1); font-size:14px;"><?php echo escape($c['title']); ?></div>
                    <div style="font-size:13px; margin-top:2px; color:var(--text-3);">
                        <?php echo escape(CampaignModel::formatTrigger($c)); ?> →
                        <span style="color:#a855f7; font-weight:600;"><?php echo escape(CampaignModel::formatReward($c)); ?></span>
                    </div>
                    <?php if ($c['description']): ?>
                        <p style="font-size:12px; margin-top:4px; color:var(--text-3);"><?php echo escape($c['description']); ?></p>
                    <?php endif; ?>

                    <?php if (!$hasEarned && $c['trigger_type'] !== 'first_checkin'): ?>
                    <div style="margin-top:8px;">
                        <div style="display:flex; justify-content:space-between; font-size:11px; margin-bottom:4px; color:var(--text-3);">
                            <span><?php echo $userCheckinHere; ?> / <?php echo $target; ?> check-in</span>
                            <span><?php echo $progress; ?>%</span>
                        </div>
                        <div style="height:6px; border-radius:999px; overflow:hidden; background:var(--bg-section);">
                            <div style="height:100%; background:rgba(168,85,247,0.7); border-radius:999px; transition:width .7s; width:<?php echo $progress; ?>%;"></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasEarned): ?>
                    <div style="margin-top:8px; display:flex; align-items:center; gap:8px; flex-wrap:wrap; background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.2); border-radius:8px; padding:8px 12px;">
                        <span class="material-symbols-outlined" style="color:#10b981; font-size:16px;">confirmation_number</span>
                        <span style="font-size:12px; color:var(--text-3);">Kodun:</span>
                        <code style="font-family:monospace; font-weight:700; color:#10b981; letter-spacing:.1em;"><?php echo escape($myCode ?? '——'); ?></code>
                        <span style="font-size:12px; color:var(--text-3);">— kasaya göster</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Check-in Listesi -->
    <div style="border-radius:12px; overflow:hidden; margin-bottom:16px; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <?php if (empty($posts)): ?>
            <div style="padding:32px; text-align:center; color:var(--text-3);">
                <span class="material-symbols-outlined" style="font-size:40px; display:block; margin-bottom:8px; opacity:.4;">pin_drop</span>
                <p style="font-size:13px; margin:0 0 16px;">Bu mekanda henüz check-in yok. İlk check-in'i sen yap!</p>
                <button onclick="openVenueCheckinModal()"
                        style="display:inline-flex; align-items:center; gap:8px; background:var(--color-primary); color:#fff; padding:10px 20px; border-radius:10px; font-weight:700; font-size:13px; border:none; cursor:pointer; font-family:inherit;">
                    <span class="material-symbols-outlined" style="font-size:16px; font-variation-settings:'FILL' 1;">add_location_alt</span>
                    Check-in Yap
                </button>
            </div>
        <?php else: ?>
            <div style="border-top:none;">
                <?php foreach ($posts as $ci): ?>
                    <?php include __DIR__ . '/partials/_checkin_row.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Inline Check-in Modal ── -->
<div id="venueCheckinModal" style="position:fixed; inset:0; z-index:9999; display:none;">
    <div style="position:absolute; inset:0;" onclick="closeVenueCheckinModal()"></div>
    <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; padding:16px;">
        <div style="border-radius:16px; width:100%; max-width:480px; box-shadow:0 20px 48px rgba(0,0,0,0.18); position:relative; padding:24px; background:#fff; border:1px solid var(--border);">

            <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:16px; margin-bottom:16px; border-bottom:1px solid var(--border);">
                <div>
                    <h3 style="font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px; color:var(--text-1); margin:0;">
                        <span class="material-symbols-outlined" style="color:var(--color-primary); font-variation-settings:'FILL' 1;">add_location_alt</span>
                        Check-in Yap
                    </h3>
                    <p style="font-size:12px; color:var(--text-3); margin:4px 0 0;"><?php echo escape($venue['name']); ?></p>
                </div>
                <button onclick="closeVenueCheckinModal()" style="background:none; border:none; cursor:pointer; color:var(--text-3); padding:4px; transition:color .14s;" onmouseover="this.style.color='var(--text-1)'" onmouseout="this.style.color='var(--text-3)'">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="venueCheckinForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="venue_id" value="<?php echo $venue['id']; ?>">

                <?php $ciAvatarUrl = safeAvatarUrl($currentUserData['avatar'] ?? null, $currentUserData['username'] ?? 'U'); ?>
                <div style="display:flex; gap:12px; margin-bottom:16px;">
                    <img src="<?php echo $ciAvatarUrl; ?>" alt="" style="width:40px; height:40px; border-radius:50%; object-fit:cover; flex-shrink:0; border:1px solid var(--border);" width="40" height="40">
                    <textarea id="venueCheckinNote" name="note"
                        style="flex:1; border-radius:12px; padding:10px 14px; font-size:13px; outline:none; transition:border-color .2s; resize:none; background:var(--bg-section); border:1px solid var(--border); color:var(--text-1); font-family:inherit; line-height:1.5;"
                        placeholder="Bu mekanda ne yaptın? (opsiyonel)" rows="3" maxlength="500"></textarea>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--text-3); transition:color .15s;" title="Fotoğraf ekle"
                           onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-3)'">
                        <span class="material-symbols-outlined" style="font-size:22px;">add_a_photo</span>
                        <span style="font-size:12px;" id="venueCheckinImageLabel">Fotoğraf</span>
                        <input type="file" name="image" id="venueCheckinImage" accept="image/*" style="display:none;">
                    </label>
                    <button type="submit" id="venueCheckinBtn"
                        style="background:var(--color-primary); color:#fff; padding:10px 22px; border-radius:12px; font-weight:700; font-size:13px; box-shadow:0 4px 16px rgba(240,109,31,0.25); transition:opacity .15s; display:flex; align-items:center; gap:8px; border:none; cursor:pointer; font-family:inherit;"
                        onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                        <span class="material-symbols-outlined" style="font-size:16px; font-variation-settings:'FILL' 1;">pin_drop</span>
                        Check-in Yap
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>

<!-- Venue Rating Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const starContainer = document.getElementById('userStarRating');
    if (!starContainer) return;

    const venueId = starContainer.dataset.venueId;
    let currentRating = parseInt(starContainer.dataset.current) || 0;
    const starBtns = starContainer.querySelectorAll('.star-btn');

    // Hover preview
    starBtns.forEach(btn => {
        const starVal = parseInt(btn.dataset.star);

        btn.addEventListener('mouseenter', () => {
            starBtns.forEach(b => {
                const v = parseInt(b.dataset.star);
                const icon = b.querySelector('.material-symbols-outlined');
                icon.style.color = v <= starVal ? '#f59e0b' : '#94a3b8';
                icon.style.transform = v <= starVal ? 'scale(1.1)' : 'scale(1)';
            });
        });

        btn.addEventListener('click', async () => {
            // Disable all buttons during request
            starBtns.forEach(b => b.disabled = true);

            const formData = new FormData();
            formData.append('csrf_token', App.csrfToken);
            formData.append('venue_id', venueId);
            formData.append('rating', starVal);

            const res = await App.post(App.baseUrl + '/api/venue-rating', formData);

            if (res.ok) {
                currentRating = starVal;
                starContainer.dataset.current = starVal;

                // Update star visuals
                updateStarDisplay(starVal);

                // Update average display
                const avgEl = document.getElementById('ratingAvgDisplay');
                const countEl = document.getElementById('ratingCountDisplay');
                const labelEl = document.getElementById('userRatingLabel');

                if (avgEl) avgEl.textContent = res.data.average_rating.toFixed(1);
                if (countEl) countEl.textContent = res.data.rating_count;
                if (labelEl) {
                    labelEl.textContent = 'Senin puanın: ' + starVal + '/5';
                    labelEl.style.fontSize = '11px';
                    labelEl.style.color = 'rgba(245,158,11,0.7)';
                }

                // Update average stars
                updateAverageStars(res.data.average_rating);

                App.flash('Puanınız kaydedildi! ⭐', 'success');
            } else {
                App.flash(res.error || 'Puan verilemedi.', 'error');
            }

            starBtns.forEach(b => b.disabled = false);
        });
    });

    // Reset stars on mouse leave to current rating
    starContainer.addEventListener('mouseleave', () => {
        updateStarDisplay(currentRating);
    });

    function updateStarDisplay(rating) {
        starBtns.forEach(b => {
            const v = parseInt(b.dataset.star);
            const icon = b.querySelector('.material-symbols-outlined');
            icon.style.color = v <= rating ? '#f59e0b' : '#94a3b8';
            icon.style.transform = v <= rating ? 'scale(1.1)' : 'scale(1)';
        });
    }

    function updateAverageStars(avg) {
        const avgStars = document.getElementById('averageStarsDisplay');
        if (!avgStars) return;
        const icons = avgStars.querySelectorAll('.material-symbols-outlined');
        icons.forEach((icon, idx) => {
            const starNum = idx + 1;
            if (avg >= starNum) {
                icon.style.color = '#f59e0b';
                icon.style.opacity = '1';
            } else if (avg >= starNum - 0.5) {
                icon.style.color = '#f59e0b';
                icon.style.opacity = '0.5';
            } else {
                icon.style.color = '#94a3b8';
                icon.style.opacity = '1';
            }
            icon.style.fontVariationSettings = "'FILL' 1";
        });
    }
});
</script>

<!-- Venue Check-in Modal Script -->
<script>
function openVenueCheckinModal() {
    const modal = document.getElementById('venueCheckinModal');
    if (modal) {
        modal.style.display = 'block';
        modal.style.background = 'rgba(0,0,0,0.6)';
        document.body.style.overflow = 'hidden';
        setTimeout(() => {
            const ta = document.getElementById('venueCheckinNote');
            if (ta) ta.focus();
        }, 150);
    }
}

function closeVenueCheckinModal() {
    const modal = document.getElementById('venueCheckinModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('venueCheckinForm');
    const btn  = document.getElementById('venueCheckinBtn');
    if (!form || !btn) return;

    // File input selection feedback
    const fileInput = document.getElementById('venueCheckinImage');
    const fileLabel = document.getElementById('venueCheckinImageLabel');
    if (fileInput && fileLabel) {
        fileInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                fileLabel.textContent = this.files[0].name.substring(0, 18) + (this.files[0].name.length > 18 ? '...' : '');
                fileLabel.style.color = 'var(--color-primary)';
                fileLabel.style.fontWeight = 'bold';
            } else {
                fileLabel.textContent = 'Fotoğraf';
                fileLabel.style.color = 'var(--text-3)';
                fileLabel.style.fontWeight = 'normal';
            }
        });
    }

    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        btn.disabled = true;
        btn.innerHTML = '<span class="material-symbols-outlined animate-spin text-base">progress_activity</span> Kaydediliyor...';

        const formData = new FormData(form);
        const res = await App.post(App.baseUrl + '/api/create-post', formData);

        if (res.ok) {
            closeVenueCheckinModal();
            if (res.data && res.data.earned_campaigns && res.data.earned_campaigns.length > 0) {
                showVenueCampaignModal(res.data.earned_campaigns);
            } else {
                App.flash(res.message || 'Check-in başarılı! 📍', 'success');
                setTimeout(() => location.reload(), 800);
            }
        } else {
            App.flash(res.error || 'Hata oluştu.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined text-base" style="font-variation-settings:\'FILL\' 1;">pin_drop</span> Check-in Yap';
        }
    });

    // ESC tuşu ile kapat
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeVenueCheckinModal();
    });
});

function showVenueCampaignModal(campaigns) {
    document.getElementById('campaignRewardOverlay')?.remove();
    const overlay = document.createElement('div');
    overlay.id = 'campaignRewardOverlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(4px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:1rem;';

    const cardsHtml = campaigns.map(c => `
        <div style="background:var(--color-primary-bg);border:1px solid rgba(240,109,31,0.3);border-radius:16px;padding:1.5rem;text-align:center;">
            <div style="font-size:1rem;font-weight:700;color:var(--text-1);margin-bottom:0.25rem;">${escapeHtml(c.title)}</div>
            ${c.reward_text ? `<div style="font-size:0.8rem;color:var(--color-primary);margin-bottom:1rem;">${escapeHtml(c.reward_text)}</div>` : '<div style="margin-bottom:1rem;"></div>'}
            <div style="font-size:0.65rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--text-3);margin-bottom:0.5rem;">Ödül Kodun</div>
            <div onclick="navigator.clipboard.writeText('${escapeHtml(c.code)}'); this.querySelector('.copy-hint').textContent='Kopyalandı!'; this.style.borderColor='#16a34a';"
                 style="font-family:monospace;font-size:1.4rem;font-weight:800;letter-spacing:0.2em;color:var(--color-primary);background:#fff;border:2px dashed rgba(240,109,31,0.4);border-radius:12px;padding:0.75rem 1rem;cursor:pointer;">
                ${escapeHtml(c.code)}
                <div class="copy-hint" style="font-size:0.6rem;color:var(--text-3);margin-top:0.25rem;font-family:system-ui;letter-spacing:normal;">tıkla → kopyala</div>
            </div>
        </div>`).join('');

    overlay.innerHTML = `
        <div style="background:#fff;border:1.5px solid var(--border);border-radius:24px;max-width:400px;width:100%;padding:2rem;position:relative;box-shadow:0 20px 48px rgba(0,0,0,0.12);">
            <div style="text-align:center;font-size:2.5rem;margin-bottom:0.5rem;">🎉</div>
            <h2 style="text-align:center;font-size:1.3rem;font-weight:800;color:var(--text-1);margin:0 0 0.25rem;">Kampanya Kazandın!</h2>
            <p style="text-align:center;font-size:0.8rem;color:var(--text-3);margin:0 0 1.5rem;">Check-in'in seni ödüllendirdi</p>
            <div style="display:flex;flex-direction:column;gap:1rem;margin-bottom:1.5rem;">${cardsHtml}</div>
            <button onclick="document.getElementById('campaignRewardOverlay').remove(); location.reload();"
                    class="btn btn-primary btn-block" style="width:100%;padding:0.85rem;border:none;border-radius:14px;color:white;font-size:0.9rem;font-weight:700;cursor:pointer;justify-content:center;">
                Harika! 🎁
            </button>
        </div>`;

    document.body.appendChild(overlay);
    overlay.addEventListener('click', e => { if (e.target === overlay) { overlay.remove(); location.reload(); } });
}
</script>
