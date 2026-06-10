<?php
/**
 * Sociaera â€” Kampanya YÃ¶netim SayfasÄ±
 * Ä°ÅŸletme sahipleri mekanlarÄ±na kampanya oluÅŸturur/dÃ¼zenler
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Campaign.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$venueModel    = new VenueModel();
$campaignModel = new CampaignModel();
$venueId       = (int)($_GET['venue_id'] ?? 0);

if (!$venueId) { header('Location: ' . BASE_URL . '/venues'); exit; }

$venue = $venueModel->getById($venueId);
if (!$venue || (int)$venue['created_by'] !== Auth::id()) {
    Auth::setFlash('error', 'Bu mekanÄ± yÃ¶netme yetkiniz yok.');
    header('Location: ' . BASE_URL . '/venues'); exit;
}

// â”€â”€ POST Ä°ÅŸlemleri â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            if (empty(trim($_POST['title'] ?? ''))) {
                Auth::setFlash('error', 'Kampanya baÅŸlÄ±ÄŸÄ± zorunludur.');
                break;
            }
            // GÃ¼venlik: $_POST yerine whitelist
            $postData = [
                'title'           => trim($_POST['title'] ?? ''),
                'description'     => trim($_POST['description'] ?? ''),
                'trigger_type'    => $_POST['trigger_type'] ?? 'nth_checkin',
                'trigger_value'   => (int)($_POST['trigger_value'] ?? 0),
                'reward_type'     => $_POST['reward_type'] ?? '',
                'reward_value'    => $_POST['reward_value'] ?? '',
                'reward_text'     => trim($_POST['reward_text'] ?? ''),
                'starts_at'       => $_POST['starts_at'] ?: null,
                'ends_at'         => $_POST['ends_at'] ?: null,
                'max_redemptions' => (int)($_POST['max_redemptions'] ?? 0) ?: null,
                'is_active'       => isset($_POST['is_active']) ? 1 : 0,
            ];
            $campaignModel->create($venueId, $postData);

            // Mekana check-in yapmÄ±ÅŸ kullanÄ±cÄ±lara yeni kampanya bildirimi gÃ¶nder
            try {
                $notifModel = new NotificationModel();
                $userIds = $campaignModel->getVenueCheckinUserIds($venueId, Auth::id());
                $rewardText = CampaignModel::formatReward($postData);
                foreach ($userIds as $uid) {
                    $notifModel->create(
                        (int)$uid,
                        Auth::id(),
                        'new_campaign',
                        'ğŸ¯ ' . $venue['name'] . ' yeni kampanya ekledi: "' . $postData['title'] . '" â€” ' . $rewardText
                    );
                }
            } catch (\Throwable $e) {
                error_log('Campaign notification error: ' . $e->getMessage());
            }

            Auth::setFlash('success', 'Kampanya oluÅŸturuldu!');
            break;

        case 'update':
            $cId = (int)($_POST['campaign_id'] ?? 0);
            if ($cId) {
                $updateData = [
                    'title'           => trim($_POST['title'] ?? ''),
                    'description'     => trim($_POST['description'] ?? ''),
                    'trigger_type'    => $_POST['trigger_type'] ?? 'nth_checkin',
                    'trigger_value'   => (int)($_POST['trigger_value'] ?? 0),
                    'reward_type'     => $_POST['reward_type'] ?? '',
                    'reward_value'    => $_POST['reward_value'] ?? '',
                    'reward_text'     => trim($_POST['reward_text'] ?? ''),
                    'starts_at'       => $_POST['starts_at'] ?: null,
                    'ends_at'         => $_POST['ends_at'] ?: null,
                    'max_redemptions' => (int)($_POST['max_redemptions'] ?? 0) ?: null,
                    'is_active'       => isset($_POST['is_active']) ? 1 : 0,
                ];
                $campaignModel->update($cId, $venueId, $updateData);
                Auth::setFlash('success', 'Kampanya gÃ¼ncellendi.');
            }
            break;

        case 'toggle':
            $cId = (int)($_POST['campaign_id'] ?? 0);
            $c   = $campaignModel->getById($cId);
            if ($c && (int)$c['venue_id'] === $venueId) {
                $db = Database::getConnection();
                $db->prepare("UPDATE venue_campaigns SET is_active = ? WHERE id = ?")
                   ->execute([$c['is_active'] ? 0 : 1, $cId]);
                Auth::setFlash('success', $c['is_active'] ? 'Kampanya durduruldu.' : 'Kampanya aktif edildi.');
            }
            break;

        case 'delete':
            $cId = (int)($_POST['campaign_id'] ?? 0);
            if ($cId) {
                $campaignModel->delete($cId, $venueId);
                Auth::setFlash('success', 'Kampanya silindi.');
            }
            break;
    }
    header('Location: ' . BASE_URL . '/campaigns?venue_id=' . $venueId); exit;
}

$campaigns = $campaignModel->getByVenue($venueId);
$editId    = (int)($_GET['edit'] ?? 0);
$editCamp  = $editId ? $campaignModel->getById($editId) : null;
if ($editCamp && (int)$editCamp['venue_id'] !== $venueId) $editCamp = null;

$triggerLabels = CampaignModel::triggerLabels();
$rewardLabels  = CampaignModel::rewardLabels();

$hideSidebar = true;
$pageTitle   = 'Kampanyalar â€” ' . $venue['name'];
$activeNav   = '';
require_once __DIR__ . '/partials/app_header.php';
?>

<style>
.cmp-input {
    width:100%; border-radius:10px; padding:10px 14px; font-size:13px;
    outline:none; transition:border-color .2s;
    background:var(--bg-section); border:1.5px solid var(--border); color:var(--text-1);
    font-family:inherit; box-sizing:border-box; appearance:none;
}
.cmp-input:focus { border-color:var(--color-primary); }
.cmp-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.cmp-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
@media(max-width:560px) { .cmp-grid2,.cmp-grid3 { grid-template-columns:1fr; } }
</style>

<section style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:768px; width:100%; padding-bottom:40px;">

    <!-- Header -->
    <div style="display:flex; align-items:center; gap:14px;">
        <a href="<?php echo BASE_URL; ?>/venue-manage?id=<?php echo $venueId; ?>"
           style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:var(--bg-section); border:1px solid var(--border); transition:background .15s;"
           onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--bg-section)'">
            <span class="material-symbols-outlined" style="color:var(--text-3);">arrow_back</span>
        </a>
        <div style="flex:1;">
            <h1 style="font-size:1.5rem; font-weight:900; color:var(--text-1); letter-spacing:-.02em; display:flex; align-items:center; gap:8px; margin:0 0 4px;">
                <span class="material-symbols-outlined" style="color:var(--color-primary);">campaign</span>
                Kampanyalar
            </h1>
            <p style="color:var(--text-3); font-size:13px; margin:0;"><?php echo escape($venue['name']); ?></p>
        </div>
    </div>

    <!-- â”€â”€ Kampanya OluÅŸtur / DÃ¼zenle Form â”€â”€ -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:20px;"><?php echo $editCamp ? 'edit' : 'add_circle'; ?></span>
            <h2 style="font-size:14px; font-weight:700; color:var(--text-1); margin:0;"><?php echo $editCamp ? 'KampanyayÄ± DÃ¼zenle' : 'Yeni Kampanya OluÅŸtur'; ?></h2>
        </div>
        <form method="POST" style="padding:20px; display:flex; flex-direction:column; gap:16px;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="<?php echo $editCamp ? 'update' : 'create'; ?>">
            <?php if ($editCamp): ?>
                <input type="hidden" name="campaign_id" value="<?php echo $editCamp['id']; ?>">
            <?php endif; ?>

            <!-- BaÅŸlÄ±k -->
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Kampanya BaÅŸlÄ±ÄŸÄ± <span style="color:#dc2626;">*</span></label>
                <input type="text" name="title" required class="cmp-input"
                       value="<?php echo escape($editCamp['title'] ?? ''); ?>"
                       placeholder="Ã–rn: 10. Check-in Ä°ndirimi">
            </div>

            <!-- AÃ§Ä±klama -->
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">AÃ§Ä±klama</label>
                <textarea name="description" rows="2" class="cmp-input" style="resize:none;"
                          placeholder="Kampanya hakkÄ±nda kÄ±sa bilgi..."><?php echo escape($editCamp['description'] ?? ''); ?></textarea>
            </div>

            <!-- Tetikleyici -->
            <div class="cmp-grid2">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Kampanya Ne Zaman Tetiklensin?</label>
                    <select name="trigger_type" id="triggerType" class="cmp-input">
                        <?php foreach ($triggerLabels as $k => $l): ?>
                            <option value="<?php echo $k; ?>"
                                <?php echo ($editCamp['trigger_type'] ?? 'nth_checkin') === $k ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="triggerValueWrap">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">KaÃ§Ä±ncÄ± Check-in? <span style="color:var(--text-3); font-weight:400;">(first_checkin hariÃ§)</span></label>
                    <input type="number" name="trigger_value" min="1" class="cmp-input"
                           value="<?php echo (int)($editCamp['trigger_value'] ?? 10); ?>">
                </div>
            </div>

            <!-- Ã–dÃ¼l -->
            <div class="cmp-grid2">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Ã–dÃ¼l TÃ¼rÃ¼</label>
                    <select name="reward_type" id="rewardType" class="cmp-input">
                        <?php foreach ($rewardLabels as $k => $l): ?>
                            <option value="<?php echo $k; ?>"
                                <?php echo ($editCamp['reward_type'] ?? 'discount_percent') === $k ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="rewardValueWrap">
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Ã–dÃ¼l DeÄŸeri <span style="color:var(--text-3); font-weight:400;">(%50 iÃ§in 50 yaz)</span></label>
                    <input type="number" name="reward_value" min="0" step="0.01" class="cmp-input"
                           value="<?php echo $editCamp['reward_value'] ?? ''; ?>"
                           placeholder="50">
                </div>
            </div>

            <!-- Ã–dÃ¼l Metni -->
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Ã–dÃ¼l AÃ§Ä±klamasÄ± <span style="color:var(--text-3); font-weight:400;">(kullanÄ±cÄ±ya gÃ¶sterilir)</span></label>
                <input type="text" name="reward_text" class="cmp-input"
                       value="<?php echo escape($editCamp['reward_text'] ?? ''); ?>"
                       placeholder="Ã–rn: Bedava tatlÄ±, %50 indirim kuponu...">
            </div>

            <!-- Tarih & Limit -->
            <div class="cmp-grid3">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">BaÅŸlangÄ±Ã§ <span style="color:var(--text-3); font-weight:400;">(boÅŸ = hemen)</span></label>
                    <input type="datetime-local" name="starts_at" class="cmp-input"
                           value="<?php echo !empty($editCamp['starts_at']) ? date('Y-m-d\TH:i', strtotime($editCamp['starts_at'])) : ''; ?>">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">BitiÅŸ <span style="color:var(--text-3); font-weight:400;">(boÅŸ = sÃ¼resiz)</span></label>
                    <input type="datetime-local" name="ends_at" class="cmp-input"
                           value="<?php echo !empty($editCamp['ends_at']) ? date('Y-m-d\TH:i', strtotime($editCamp['ends_at'])) : ''; ?>">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Max KazanÄ±m <span style="color:var(--text-3); font-weight:400;">(boÅŸ = sÄ±nÄ±rsÄ±z)</span></label>
                    <input type="number" name="max_redemptions" min="1" class="cmp-input"
                           value="<?php echo $editCamp['max_redemptions'] ?? ''; ?>"
                           placeholder="100">
                </div>
            </div>

            <!-- Aktif toggle -->
            <div style="display:flex; align-items:center; gap:10px;">
                <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                    <input type="checkbox" name="is_active" value="1" style="position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0;" id="isActiveChk"
                           <?php echo ($editCamp ? $editCamp['is_active'] : 1) ? 'checked' : ''; ?>>
                    <span id="toggleKnob" style="display:inline-block; width:44px; height:24px; border-radius:999px; background:#e2e8f0; border:1px solid var(--border); position:relative; transition:background .2s;">
                        <span style="position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s;" id="toggleDot"></span>
                    </span>
                </label>
                <span style="font-size:13px; color:var(--text-1);">Kampanya aktif</span>
            </div>

            <div style="display:flex; gap:10px; align-items:center;">
                <button type="submit"
                        style="flex:1; background:var(--color-primary); color:#fff; padding:12px 20px; border-radius:12px; font-weight:700; font-size:14px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; font-family:inherit; box-shadow:0 4px 14px rgba(240,109,31,0.2); transition:opacity .15s;"
                        onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <span class="material-symbols-outlined" style="font-size:20px;"><?php echo $editCamp ? 'save' : 'add_circle'; ?></span>
                    <?php echo $editCamp ? 'GÃ¼ncelle' : 'Kampanya OluÅŸtur'; ?>
                </button>
                <?php if ($editCamp): ?>
                <a href="?venue_id=<?php echo $venueId; ?>"
                   style="padding:12px 20px; border-radius:12px; background:var(--bg-section); color:var(--text-2); font-size:13px; font-weight:600; border:1px solid var(--border); text-decoration:none; white-space:nowrap; transition:background .15s;"
                   onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--bg-section)'">
                    Ä°ptal
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- â”€â”€ Mevcut Kampanyalar â”€â”€ -->
    <?php if (empty($campaigns)): ?>
        <div style="background:#fff; border:1px solid var(--border); border-radius:14px; padding:40px; text-align:center; color:var(--text-3); box-shadow:0 1px 3px rgba(0,0,0,.08);">
            <span class="material-symbols-outlined" style="font-size:48px; margin-bottom:10px; opacity:.4; display:block;">campaign</span>
            <p style="font-weight:700; font-size:14px; margin:0 0 4px; color:var(--text-2);">HenÃ¼z kampanya yok.</p>
            <p style="font-size:12px; margin:0;">Ä°lk kampanyanÄ± yukarÄ±dan oluÅŸtur!</p>
        </div>
    <?php else: ?>
    <div style="display:flex; flex-direction:column; gap:10px;">
        <h2 style="font-size:14px; font-weight:700; color:var(--text-1); display:flex; align-items:center; gap:6px; margin:0;">
            <span class="material-symbols-outlined" style="color:var(--text-3); font-size:18px;">list</span>
            Kampanyalar (<?php echo count($campaigns); ?>)
        </h2>
        <?php foreach ($campaigns as $c): ?>
        <div style="background:#fff; border:1px solid var(--border); border-radius:14px; padding:16px 18px; display:flex; gap:14px; flex-wrap:wrap; align-items:flex-start; box-shadow:0 1px 3px rgba(0,0,0,.06); <?php echo !$c['is_active'] ? 'opacity:.6;' : ''; ?>">
            <!-- Ä°kon -->
            <div style="width:48px; height:48px; border-radius:12px; background:<?php echo $c['is_active'] ? 'rgba(240,109,31,0.1)' : 'var(--bg-section)'; ?>; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <span class="material-symbols-outlined" style="color:<?php echo $c['is_active'] ? 'var(--color-primary)' : 'var(--text-3)'; ?>; font-size:24px;">
                    <?php echo $c['reward_type'] === 'free_item' ? 'redeem' : 'percent'; ?>
                </span>
            </div>

            <!-- Bilgi -->
            <div style="flex:1; min-width:0;">
                <div style="display:flex; align-items:flex-start; gap:8px; flex-wrap:wrap; margin-bottom:4px;">
                    <h3 style="font-weight:700; color:var(--text-1); font-size:14px; margin:0;"><?php echo escape($c['title']); ?></h3>
                    <span style="font-size:10px; padding:2px 8px; border-radius:999px; font-weight:700; background:<?php echo $c['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(148,163,184,0.1)'; ?>; color:<?php echo $c['is_active'] ? '#10b981' : '#94a3b8'; ?>; border:1px solid <?php echo $c['is_active'] ? 'rgba(16,185,129,0.3)' : 'rgba(148,163,184,0.3)'; ?>;">
                        <?php echo $c['is_active'] ? 'Aktif' : 'Pasif'; ?>
                    </span>
                </div>

                <!-- Kural -->
                <div style="font-size:13px; color:var(--text-2); margin-bottom:4px;">
                    <span style="font-weight:600; color:var(--color-primary);"><?php echo CampaignModel::formatReward($c); ?></span>
                    <span style="color:var(--text-3);"> â€” </span>
                    <?php echo escape(CampaignModel::formatTrigger($c)); ?>
                </div>

                <?php if ($c['description']): ?>
                    <p style="font-size:12px; color:var(--text-3); margin:0 0 4px;"><?php echo escape($c['description']); ?></p>
                <?php endif; ?>

                <!-- Meta -->
                <div style="display:flex; flex-wrap:wrap; column-gap:16px; row-gap:4px; font-size:11px; color:var(--text-3); margin-top:4px;">
                    <span><span class="material-symbols-outlined" style="font-size:12px; vertical-align:middle;">redeem</span> <?php echo (int)$c['redemption_count']; ?> kazanÄ±m</span>
                    <?php if ($c['max_redemptions']): ?>
                        <span>/ <?php echo $c['max_redemptions']; ?> max</span>
                    <?php endif; ?>
                    <?php if ($c['ends_at']): ?>
                        <span><span class="material-symbols-outlined" style="font-size:12px; vertical-align:middle;">schedule</span> <?php echo formatDate($c['ends_at']); ?>'e kadar</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Aksiyonlar -->
            <div style="display:flex; gap:8px; align-items:flex-start; flex-shrink:0;">
                <a href="?venue_id=<?php echo $venueId; ?>&edit=<?php echo $c['id']; ?>"
                   style="width:36px; height:36px; border-radius:8px; background:var(--bg-section); color:var(--text-3); display:flex; align-items:center; justify-content:center; border:1px solid var(--border); transition:background .15s; text-decoration:none;"
                   title="DÃ¼zenle"
                   onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--bg-section)'">
                    <span class="material-symbols-outlined" style="font-size:18px;">edit</span>
                </a>
                <form method="POST" style="display:inline;">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <button type="submit"
                            style="width:36px; height:36px; border-radius:8px; background:<?php echo $c['is_active'] ? 'rgba(245,158,11,0.1)' : 'rgba(16,185,129,0.1)'; ?>; color:<?php echo $c['is_active'] ? '#f59e0b' : '#10b981'; ?>; display:flex; align-items:center; justify-content:center; border:1px solid <?php echo $c['is_active'] ? 'rgba(245,158,11,0.3)' : 'rgba(16,185,129,0.3)'; ?>; cursor:pointer; transition:background .15s;"
                            title="<?php echo $c['is_active'] ? 'Durdur' : 'Aktif Et'; ?>">
                        <span class="material-symbols-outlined" style="font-size:18px;"><?php echo $c['is_active'] ? 'pause' : 'play_arrow'; ?></span>
                    </button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('KampanyayÄ± silmek istediÄŸine emin misin?')">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <button type="submit"
                            style="width:36px; height:36px; border-radius:8px; background:rgba(239,68,68,0.1); color:#ef4444; display:flex; align-items:center; justify-content:center; border:1px solid rgba(239,68,68,0.3); cursor:pointer; transition:background .15s;"
                            title="Sil">
                        <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</section>

<script>
// first_checkin seÃ§ilince trigger_value'yu gizle
document.getElementById('triggerType')?.addEventListener('change', function() {
    const wrap = document.getElementById('triggerValueWrap');
    if (wrap) wrap.style.opacity = this.value === 'first_checkin' ? '0.3' : '1';
});

// free_item & custom seÃ§ilince reward_value'yu gizle
document.getElementById('rewardType')?.addEventListener('change', function() {
    const wrap = document.getElementById('rewardValueWrap');
    if (wrap) wrap.style.opacity = ['free_item','custom'].includes(this.value) ? '0.3' : '1';
});

// Toggle checkbox styling
(function() {
    const chk = document.getElementById('isActiveChk');
    const knob = document.getElementById('toggleKnob');
    const dot = document.getElementById('toggleDot');
    if (!chk) return;
    function update() {
        knob.style.background = chk.checked ? 'var(--color-primary)' : '#e2e8f0';
        dot.style.transform = chk.checked ? 'translateX(20px)' : 'translateX(0)';
    }
    chk.addEventListener('change', update);
    update();
    knob.addEventListener('click', () => { chk.checked = !chk.checked; update(); });
})();
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
