<?php
/**
 * Sociaera â€” Ä°ÅŸletme Paneli (Venue Management)
 * Mekan sahibi kendi mekanÄ±nÄ± yÃ¶netir
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$venueModel = new VenueModel();
$venueId = (int)($_GET['id'] ?? 0);

if (!$venueId) {
    header('Location: ' . BASE_URL . '/venues'); exit;
}

$venue = $venueModel->getById($venueId);

// Mekan sahibi mi kontrol et
if (!$venue || (int)$venue['created_by'] !== Auth::id()) {
    Auth::setFlash('error', 'Bu mekanÄ± yÃ¶netme yetkiniz yok.');
    header('Location: ' . BASE_URL . '/venues'); exit;
}

// POST: GÃ¼ncelleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? 'update';

    if ($action === 'update') {
        $updateData = [
            'name'        => trim($_POST['name'] ?? $venue['name']),
            'description' => trim($_POST['description'] ?? ''),
            'address'     => trim($_POST['address'] ?? ''),
            'phone'       => trim($_POST['phone'] ?? ''),
            'hours'       => trim($_POST['hours'] ?? ''),
            'category'    => $_POST['category'] ?? $venue['category'],
            'website'     => trim($_POST['website'] ?? ''),
            'is_open'     => isset($_POST['is_open']) ? 1 : 0,
        ];

        // Kapak görseli yükleme
        if (!empty($_FILES['cover_image']['name'])) {
            $uploader = new ImageUploader();
            $result = $uploader->upload($_FILES['cover_image'], 'venues', ['maxWidth' => 1200, 'quality' => 85]);
            if ($result['success']) {
                $updateData['cover_image'] = $result['filename'];
            }
        }

        // Logo (Profil Fotoğrafı) yükleme
        if (!empty($_FILES['image']['name'])) {
            if (!isset($uploader)) $uploader = new ImageUploader();
            $result = $uploader->upload($_FILES['image'], 'venues', ['maxWidth' => 600, 'quality' => 85]);
            if ($result['success']) {
                $updateData['image'] = $result['filename'];
            }
        }

        $venueModel->update($venueId, $updateData);
        Auth::setFlash('success', 'Ä°ÅŸletme bilgileri gÃ¼ncellendi.');
        header('Location: ' . BASE_URL . '/venue-manage?id=' . $venueId); exit;

    } elseif ($action === 'toggle_open') {
        // DÃ¼zeltme: varsayÄ±lan 0 (kapalÄ±) â€” NULL durumu aÃ§Ä±k kabul edilirse ilk toggle kapanÄ±yordu
        $newStatus = ($venue['is_open'] ?? 0) ? 0 : 1;
        $venueModel->update($venueId, ['is_open' => $newStatus]);
        Auth::setFlash('success', $newStatus ? 'Ä°ÅŸletme aÃ§Ä±k olarak iÅŸaretlendi.' : 'Ä°ÅŸletme kapalÄ± olarak iÅŸaretlendi.');
        header('Location: ' . BASE_URL . '/venue-manage?id=' . $venueId); exit;
    }
}

// Verileri yeniden Ã§ek
$venue = $venueModel->getById($venueId);
$checkinCount = $venueModel->getCheckinCount($venueId);
$categories = VenueModel::categories();

// Bu sayfada right sidebar gÃ¶sterme
$trendVenues = [];
$miniLeaderboard = [];
$hideSidebar = true;

$pageTitle = $venue['name'] . ' â€” Ä°ÅŸletme Paneli';
$activeNav = 'venue_manage_' . $venueId;

require_once __DIR__ . '/partials/app_header.php';
?>

<style>
.vm-input {
    width:100%; border-radius:10px; padding:10px 14px; font-size:13px;
    outline:none; transition:border-color .2s;
    background:var(--bg-section); border:1.5px solid var(--border); color:var(--text-1);
    font-family:inherit; box-sizing:border-box; appearance:none;
}
.vm-input:focus { border-color:var(--color-primary); }
.vm-card {
    background:#fff; border:1px solid var(--border); border-radius:14px;
    overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);
}
</style>

<section style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:768px; width:100%; padding-bottom:40px;">

    <!-- Header -->
    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
        <a href="<?php echo BASE_URL; ?>/venues"
           style="width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; background:var(--bg-section); border:1px solid var(--border); flex-shrink:0;"
           onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--bg-section)'">
            <span class="material-symbols-outlined" style="color:var(--text-3);">arrow_back</span>
        </a>
        <div style="flex:1; min-width:0;">
            <h1 style="font-size:1.4rem; font-weight:900; color:var(--text-1); letter-spacing:-.02em; margin:0 0 2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo escape($venue['name']); ?></h1>
            <p style="color:var(--text-3); font-size:12px; margin:0;">Ä°ÅŸletme Paneli</p>
        </div>
        <!-- Kampanyalar + AÃ§Ä±k/KapalÄ± Toggle -->
        <div style="display:flex; gap:8px; flex-shrink:0;">
            <a href="<?php echo BASE_URL; ?>/campaigns?venue_id=<?php echo $venueId; ?>"
               style="display:flex; align-items:center; gap:6px; padding:8px 14px; border-radius:10px; font-weight:700; font-size:13px; border:1px solid rgba(168,85,247,0.3); background:rgba(168,85,247,0.08); color:#a855f7; text-decoration:none; white-space:nowrap; transition:background .15s;"
               onmouseover="this.style.background='rgba(168,85,247,0.15)'" onmouseout="this.style.background='rgba(168,85,247,0.08)'">
                <span class="material-symbols-outlined" style="font-size:18px;">campaign</span>
                Kampanyalar
            </a>
            <form method="POST" style="display:inline;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="toggle_open">
                <?php $isOpen = $venue['is_open'] ?? 1; ?>
                <button type="submit"
                        style="display:flex; align-items:center; gap:8px; padding:8px 14px; border-radius:10px; font-weight:700; font-size:13px; border:1px solid <?php echo $isOpen ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'; ?>; background:<?php echo $isOpen ? 'rgba(16,185,129,0.08)' : 'rgba(239,68,68,0.08)'; ?>; color:<?php echo $isOpen ? '#10b981' : '#ef4444'; ?>; cursor:pointer; font-family:inherit; transition:background .15s; white-space:nowrap;">
                    <span style="width:10px; height:10px; border-radius:50%; background:<?php echo $isOpen ? '#10b981' : '#ef4444'; ?>; <?php echo $isOpen ? 'box-shadow:0 0 6px rgba(16,185,129,0.5);' : ''; ?>"></span>
                    <?php echo $isOpen ? 'AÃ§Ä±k' : 'KapalÄ±'; ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Stats -->
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px;">
        <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <div style="font-size:1.6rem; font-weight:900; color:var(--text-1);"><?php echo $checkinCount; ?></div>
            <div style="font-size:11px; color:var(--text-3); margin-top:4px;">Check-in</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <div style="font-size:1rem; font-weight:900; color:var(--text-1); word-break:break-word;"><?php echo escape($categories[$venue['category']] ?? '-'); ?></div>
            <div style="font-size:11px; color:var(--text-3); margin-top:4px;">Kategori</div>
        </div>
        <div style="background:#fff; border:1px solid var(--border); border-radius:12px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <?php
            $statusColor = match($venue['status']) {
                'approved' => '#10b981',
                'pending' => '#f59e0b',
                default => 'var(--text-3)'
            };
            $statusText = match($venue['status']) {
                'approved' => 'OnaylÄ±',
                'pending' => 'Bekliyor',
                default => ucfirst($venue['status'])
            };
            ?>
            <div style="font-size:1rem; font-weight:900; color:<?php echo $statusColor; ?>;"><?php echo $statusText; ?></div>
            <div style="font-size:11px; color:var(--text-3); margin-top:4px;">Durum</div>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="update">

        <!-- Kapak GÃ¶rseli -->
        <div class="vm-card">
            <!-- Image cover -->
            <div style="height:192px; position:relative; overflow:hidden; background:var(--bg-section);" id="coverWrap">
                <?php if (!empty($venue['cover_image'])): ?>
                    <img src="<?php echo uploadUrl('venues', $venue['cover_image']); ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/assets/images/default-cover.png';">
                <?php elseif (!empty($venue['image'])): ?>
                    <img src="<?php echo uploadUrl('venues', $venue['image']); ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='<?php echo BASE_URL; ?>/assets/images/default-cover.png';">
                <?php else: ?>
                    <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--text-3);">
                        <span class="material-symbols-outlined" style="font-size:64px;">add_photo_alternate</span>
                    </div>
                <?php endif; ?>
                <label id="coverLabel" style="position:absolute; inset:0; background:rgba(0,0,0,0.4); opacity:0; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:opacity .2s;">
                    <span style="color:#fff; font-size:13px; font-weight:700; display:flex; align-items:center; gap:6px;">
                        <span class="material-symbols-outlined">camera_alt</span> Kapak DeÄŸiÅŸtir
                    </span>
                    <input type="file" name="cover_image" accept="image/*" style="display:none;" id="coverInput">
                </label>
            </div>
            
            <div style="padding:20px 20px 0; display:flex; align-items:center; gap:16px;">
                <div style="width:72px; height:72px; border-radius:16px; overflow:hidden; border:2px solid var(--border); background:var(--bg-section); position:relative;" id="logoWrap">
                    <?php if (!empty($venue['image'])): ?>
                        <img src="<?php echo uploadUrl('venues', $venue['image']); ?>" style="width:100%; height:100%; object-fit:cover;" onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($venue['name']); ?>&background=random';">
                    <?php else: ?>
                        <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:var(--text-3);">
                            <span class="material-symbols-outlined" style="font-size:32px;">storefront</span>
                        </div>
                    <?php endif; ?>
                    <label id="logoLabel" style="position:absolute; inset:0; background:rgba(0,0,0,0.4); opacity:0; display:flex; align-items:center; justify-content:center; cursor:pointer; transition:opacity .2s;">
                        <span class="material-symbols-outlined" style="color:#fff;">camera_alt</span>
                        <input type="file" name="image" accept="image/*" style="display:none;" id="logoInput">
                    </label>
                </div>
                <div style="font-size:13px; color:var(--text-2);">
                    <strong style="color:var(--text-1); display:block; margin-bottom:2px;">Mekan Logosu (PP)</strong>
                    Kare formatta bir logo yükleyin.
                </div>
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; gap:14px;">
                <!-- Ä°ÅŸletme AdÄ± -->
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Ä°ÅŸletme AdÄ±</label>
                    <input type="text" name="name" value="<?php echo escape($venue['name']); ?>" required class="vm-input">
                </div>
                <!-- AÃ§Ä±klama -->
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">AÃ§Ä±klama</label>
                    <textarea name="description" rows="3" class="vm-input" style="resize:none;"><?php echo escape($venue['description'] ?? ''); ?></textarea>
                </div>
                <!-- Kategori -->
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Kategori</label>
                    <select name="category" class="vm-input">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $venue['category'] === $key ? 'selected' : ''; ?>><?php echo escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Konum & Ä°letiÅŸim -->
        <div class="vm-card" style="padding:20px; display:flex; flex-direction:column; gap:14px;">
            <h2 style="font-size:15px; font-weight:700; color:var(--text-1); display:flex; align-items:center; gap:8px; margin:0;">
                <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:20px;">pin_drop</span> Konum &amp; Ä°letiÅŸim
            </h2>
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Adres</label>
                <input type="text" name="address" value="<?php echo escape($venue['address'] ?? ''); ?>" placeholder="Ä°ÅŸletme adresi..." class="vm-input">
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Telefon</label>
                    <input type="text" name="phone" value="<?php echo escape($venue['phone'] ?? ''); ?>" placeholder="555-XXX-XXXX" class="vm-input">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Web Sitesi</label>
                    <input type="url" name="website" value="<?php echo escape($venue['website'] ?? ''); ?>" placeholder="https://..." class="vm-input">
                </div>
            </div>
        </div>

        <!-- Ã‡alÄ±ÅŸma Saatleri -->
        <div class="vm-card" style="padding:20px; display:flex; flex-direction:column; gap:14px;">
            <h2 style="font-size:15px; font-weight:700; color:var(--text-1); display:flex; align-items:center; gap:8px; margin:0;">
                <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:20px;">schedule</span> Ã‡alÄ±ÅŸma Saatleri
            </h2>
            <div>
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Saatler</label>
                <input type="text" name="hours" value="<?php echo escape($venue['hours'] ?? ''); ?>"
                       placeholder="Ã–rn: Hafta iÃ§i 09:00-22:00, Hafta sonu 10:00-00:00" class="vm-input">
                <p style="font-size:11px; color:var(--text-3); margin:4px 0 0;">Ã‡alÄ±ÅŸma saatlerinizi yazÄ±n</p>
            </div>

            <!-- Toggle: Ä°ÅŸletme aÃ§Ä±k mÄ±? -->
            <div style="display:flex; align-items:center; gap:10px;">
                <label style="position:relative; display:inline-flex; align-items:center; cursor:pointer;">
                    <input type="checkbox" name="is_open" value="1" <?php echo ($venue['is_open'] ?? 1) ? 'checked' : ''; ?> id="isOpenChk"
                           style="position:absolute; width:1px; height:1px; overflow:hidden; clip:rect(0,0,0,0);">
                    <span id="openKnob" style="display:inline-block; width:44px; height:24px; border-radius:999px; background:#e2e8f0; border:1px solid var(--border); position:relative; transition:background .2s;">
                        <span id="openDot" style="position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s;"></span>
                    </span>
                </label>
                <span style="font-size:13px; color:var(--text-1);">Ä°ÅŸletme ÅŸu an aÃ§Ä±k</span>
            </div>
        </div>

        <!-- Kaydet -->
        <button type="submit"
                style="width:100%; background:var(--color-primary); color:#fff; padding:14px; border-radius:12px; font-weight:700; font-size:14px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; font-family:inherit; box-shadow:0 4px 14px rgba(240,109,31,0.2); transition:opacity .15s;"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
            <span class="material-symbols-outlined" style="font-size:20px;">save</span> DeÄŸiÅŸiklikleri Kaydet
        </button>
    </form>

</section>

<script>
// Cover image hover
(function() {
    const wrap = document.getElementById('coverWrap');
    const label = document.getElementById('coverLabel');
    if (wrap && label) {
        wrap.addEventListener('mouseover', () => label.style.opacity = '1');
        wrap.addEventListener('mouseout', () => label.style.opacity = '0');
    }
    
    const logoWrap = document.getElementById('logoWrap');
    const logoLabel = document.getElementById('logoLabel');
    if (logoWrap && logoLabel) {
        logoWrap.addEventListener('mouseover', () => logoLabel.style.opacity = '1');
        logoWrap.addEventListener('mouseout', () => logoLabel.style.opacity = '0');
    }
})();

// Toggle open checkbox
(function() {
    const chk = document.getElementById('isOpenChk');
    const knob = document.getElementById('openKnob');
    const dot = document.getElementById('openDot');
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
