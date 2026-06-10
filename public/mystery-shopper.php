<?php
/**
 * Sociaera — Gizli Müşteri Başvuru Sayfası
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
require_once __DIR__ . '/../app/Models/MysteryShopperModel.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$mysteryModel = new MysteryShopperModel();
$application  = $mysteryModel->getByUser(Auth::id());

// POST — Başvur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $motivation = trim($_POST['motivation'] ?? '');
    if (mb_strlen($motivation) < 30) {
        Auth::setFlash('error', 'Lütfen en az 30 karakter motivasyon metni yaz.');
    } else {
        $result = $mysteryModel->apply(Auth::id(), $motivation);
        if ($result['ok']) {
            Auth::setFlash('success', 'Başvurunuz alındı! Admin onayı bekleniyor.');
        } else {
            Auth::setFlash('error', $result['error']);
        }
    }
    header('Location: ' . BASE_URL . '/mystery-shopper'); exit;
}

$hideSidebar    = false;
$pageTitle      = 'Gizli Müşteri Başvurusu — Sociaera';
$activeNav      = 'mystery';
require_once __DIR__ . '/partials/app_header.php';
?>

<style>
.ms-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-top:28px; }
.ms-feat { border-radius:12px; padding:16px; background:var(--bg-section); border:1.5px solid var(--border-light); }
.ms-feat-icon { font-size:24px; display:block; margin-bottom:8px; }
.ms-feat-title { font-size:13px; font-weight:700; color:var(--text-1); margin-bottom:4px; }
.ms-feat-desc { font-size:11px; color:var(--text-3); line-height:1.5; }
.ms-input {
    width:100%; border-radius:10px; padding:12px 16px; font-size:13px;
    outline:none; transition:border-color .2s; resize:vertical;
    background:var(--bg-section); border:1.5px solid var(--border); color:var(--text-1);
    font-family:inherit; line-height:1.6; box-sizing:border-box;
}
.ms-input:focus { border-color:#4f46e5; }
</style>

<div style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:640px; width:100%; padding-bottom:40px;">

    <!-- Hero -->
    <div style="position:relative; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.04); background:#fff; border:1.5px solid var(--border);">
        <div style="height:4px; background:linear-gradient(90deg, #4f46e5, #818cf8);"></div>

        <div style="position:relative; z-index:1; padding:28px 28px 24px;">
            <div style="display:flex; align-items:flex-start; gap:16px;">
                <div style="width:64px; height:64px; border-radius:16px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:rgba(79,70,229,0.08); border:1.5px solid rgba(79,70,229,0.15);">
                    <span class="material-symbols-outlined" style="font-size:32px; color:#4F46E5;">person_search</span>
                </div>
                <div>
                    <h1 style="font-size:1.6rem; font-weight:900; letter-spacing:-.02em; color:var(--text-1); margin:0 0 6px;">Gizli Müşteri Ol</h1>
                    <p style="color:var(--text-3); margin:0; font-size:14px;">İşletmeleri anonim olarak değerlendir, topluluğa katkı sağla</p>
                </div>
            </div>

            <div class="ms-grid">
                <div class="ms-feat">
                    <span class="material-symbols-outlined ms-feat-icon" style="color:#4F46E5;">visibility_off</span>
                    <div class="ms-feat-title">Gizli Kimlik</div>
                    <div class="ms-feat-desc">Yorumların "Gizli Müşteri" etiketiyle yayınlanır, kimliğin gizli kalır</div>
                </div>
                <div class="ms-feat">
                    <span class="material-symbols-outlined ms-feat-icon" style="color:#16a34a;">verified</span>
                    <div class="ms-feat-title">Admin Onayı</div>
                    <div class="ms-feat-desc">Başvurular incelenir, seçilen kullanıcılar özel tag kazanır</div>
                </div>
                <div class="ms-feat">
                    <span class="material-symbols-outlined ms-feat-icon" style="color:#f59e0b;">rate_review</span>
                    <div class="ms-feat-title">Kalite Raporu</div>
                    <div class="ms-feat-desc">Mekan kalitesini artırmak için dürüst değerlendirmeler yaparsın</div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // ── Durum Kartı ──
    if ($application):
        $statusMap = [
            'pending'  => ['icon' => 'hourglass_top',  'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.08)',  'border' => 'rgba(245,158,11,0.25)', 'label' => 'İnceleniyor',  'text' => 'Başvurunuz admin tarafından inceleniyor. Sonucu bildirim olarak alacaksınız.'],
            'approved' => ['icon' => 'check_circle',   'color' => '#16a34a', 'bg' => 'rgba(22,163,74,0.08)',   'border' => 'rgba(22,163,74,0.25)',  'label' => 'Onaylandı',    'text' => 'Tebrikler! Artık mekan check-in\'lerinde "Gizli Müşteri" etiketiyle yorum yapabilirsiniz.'],
            'rejected' => ['icon' => 'cancel',         'color' => '#ef4444', 'bg' => 'rgba(239,68,68,0.08)',   'border' => 'rgba(239,68,68,0.25)',  'label' => 'Reddedildi',   'text' => 'Başvurunuz bu sefer onaylanmadı. Tekrar başvurabilirsiniz.'],
        ];
        $s = $statusMap[$application['status']] ?? $statusMap['pending'];
    ?>
    <div style="border-radius:12px; padding:20px; background:#fff; border:1.5px solid <?php echo $s['border']; ?>; box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
            <span class="material-symbols-outlined" style="font-size:24px; color:<?php echo $s['color']; ?>;"><?php echo $s['icon']; ?></span>
            <span style="font-weight:700; font-size:13px; color:var(--text-1);">Başvuru Durumu: <span style="color:<?php echo $s['color']; ?>;"><?php echo $s['label']; ?></span></span>
        </div>
        <p style="font-size:13px; color:var(--text-2); margin:0;"><?php echo $s['text']; ?></p>
        <?php if ($application['admin_note']): ?>
            <div style="margin-top:12px; border-radius:8px; padding:12px 16px; font-size:13px; border:1px solid var(--border); background:var(--bg-section); color:var(--text-2);">
                <span style="font-size:10px; text-transform:uppercase; letter-spacing:.08em; font-weight:700; display:block; margin-bottom:4px; color:var(--text-3);">Admin Notu</span>
                <?php echo escape($application['admin_note']); ?>
            </div>
        <?php endif; ?>
        <?php if ($application['status'] === 'approved'): ?>
            <div style="margin-top:14px; border-radius:12px; padding:14px 16px; display:flex; align-items:center; gap:12px; background:rgba(79,70,229,0.08); border:1.5px solid rgba(79,70,229,0.2);">
                <span class="material-symbols-outlined" style="color:#4F46E5;">badge</span>
                <div>
                    <div style="font-size:13px; font-weight:700; color:#4F46E5;">Gizli Müşteri Rozeti Aktif</div>
                    <div style="font-size:11px; margin-top:2px; color:var(--text-3);">Check-in paylaşırken "Gizli Müşteri olarak yayınla" seçeneğini kullanabilirsin</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($application['status'] === 'rejected'): // Reddedilmişse tekrar başvurabilir ?>
    <?php endif; ?>

    <?php if (in_array($application['status'], ['approved', 'pending'])): ?>
        <!-- Zaten aktif başvuru var, form gösterme -->
        </div><!-- /grid cell -->
        <?php
        require_once __DIR__ . '/partials/app_footer.php';
        exit;
        ?>
    <?php endif; ?>

    <?php endif; // $application ?>

    <!-- ── Başvuru Formu ── -->
    <div style="border-radius:12px; overflow:hidden; background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="padding:14px 20px; display:flex; align-items:center; gap:8px; border-bottom:1px solid var(--border-light);">
            <span class="material-symbols-outlined" style="font-size:20px; color:#4F46E5;">edit_note</span>
            <h2 style="font-size:14px; font-weight:700; color:var(--text-1); margin:0;">
                <?php echo ($application && $application['status'] === 'rejected') ? 'Yeniden Başvur' : 'Başvuru Formu'; ?>
            </h2>
        </div>
        <form method="POST" style="padding:20px; display:flex; flex-direction:column; gap:18px;">
            <?php echo csrfField(); ?>

            <div>
                <label style="display:block; font-size:13px; font-weight:700; margin-bottom:6px; color:var(--text-2);">
                    Neden Gizli Müşteri Olmak İstiyorsun?
                    <span style="color:#dc2626;">*</span>
                    <span style="margin-left:4px; color:var(--text-3); font-weight:normal;">(en az 30 karakter)</span>
                </label>
                <textarea name="motivation" rows="5" required
                          placeholder="Mekan kalitesini iyileştirmek, topluluğa dürüst değerlendirmeler sunmak ve..."
                          class="ms-input"
                          id="motivationText"><?php echo escape($application['motivation'] ?? ''); ?></textarea>
                <div style="display:flex; justify-content:space-between; margin-top:4px;">
                    <p style="font-size:11px; color:var(--text-3); margin:0;">Dürüst ve açıklayıcı bir metin yaz</p>
                    <span style="font-size:11px; color:var(--text-3);" id="charCount">0 / 30+</span>
                </div>
            </div>

            <!-- Kurallar -->
            <div style="border-radius:12px; padding:16px; border:1px solid var(--border-light); background:var(--bg-section); display:flex; flex-direction:column; gap:10px;">
                <div style="font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--text-3);">Kuralları Kabul Ediyorum</div>
                <?php $rules = [
                    'Mekanları tarafsız ve dürüst değerlendireceğim',
                    'Gizli müşteri kimliğimi mekan sahiplerine açıklamayacağım',
                    'Yanıltıcı veya kasıtlı olumsuz yorum yazmayacağım',
                    'Admin kararlarına uyacağım',
                ]; ?>
                <?php foreach ($rules as $i => $rule): ?>
                <label style="display:flex; align-items:flex-start; gap:8px; cursor:pointer;">
                    <input type="checkbox" name="rule_<?php echo $i; ?>" required style="margin-top:2px; flex-shrink:0; accent-color:#4f46e5;">
                    <span style="font-size:12px; color:var(--text-2); line-height:1.5;"><?php echo $rule; ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit"
                    style="width:100%; color:#fff; padding:14px; border-radius:12px; font-weight:700; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:0 4px 16px rgba(79,70,229,0.25); transition:opacity .15s; background:#4F46E5; border:none; cursor:pointer; font-family:inherit;"
                    onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                <span class="material-symbols-outlined" style="font-size:20px;">send</span>
                Başvuruyu Gönder
            </button>
        </form>
    </div>

</div><!-- /grid cell -->

<script>
const ta = document.getElementById('motivationText');
const cc = document.getElementById('charCount');
if (ta && cc) {
    const update = () => {
        const len = ta.value.length;
        cc.textContent = len + ' karakter';
        cc.style.color = len >= 30 ? '#16a34a' : '#ef4444';
    };
    ta.addEventListener('input', update);
    update();
}
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
