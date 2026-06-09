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

<div style="min-width:0;" class="flex-1 flex flex-col gap-6 max-w-2xl w-full mx-auto lg:mx-0">

    <!-- Hero -->
    <div class="relative rounded-2xl overflow-hidden shadow-[0_4px_16px_rgba(0,0,0,0.04)]" style="background:#fff; border:1.5px solid var(--border);">
        <!-- Top accent border -->
        <div style="height:4px; background:linear-gradient(90deg, #4f46e5, #818cf8);"></div>

        <div class="relative z-10 p-8 md:p-10">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center flex-shrink-0" style="background:rgba(79,70,229,0.08); border:1.5px solid rgba(79,70,229,0.15);">
                    <span class="material-symbols-outlined text-[32px]" style="color:#4F46E5;">person_search</span>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight" style="color:var(--text-1);">Gizli Müşteri Ol</h1>
                    <p class="mt-1" style="color:var(--text-3);">İşletmeleri anonim olarak değerlendir, topluluğa katkı sağla</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
                <div class="rounded-xl p-4" style="background:var(--bg-section); border:1.5px solid var(--border-light);">
                    <span class="material-symbols-outlined text-[24px] block mb-2" style="color:#4F46E5;">visibility_off</span>
                    <div class="text-sm font-bold" style="color:var(--text-1);">Gizli Kimlik</div>
                    <div class="text-xs mt-1" style="color:var(--text-3);">Yorumların "Gizli Müşteri" etiketiyle yayınlanır, kimliğin gizli kalır</div>
                </div>
                <div class="rounded-xl p-4" style="background:var(--bg-section); border:1.5px solid var(--border-light);">
                    <span class="material-symbols-outlined text-[24px] block mb-2" style="color:#16a34a;">verified</span>
                    <div class="text-sm font-bold" style="color:var(--text-1);">Admin Onayı</div>
                    <div class="text-xs mt-1" style="color:var(--text-3);">Başvurular incelenir, seçilen kullanıcılar özel tag kazanır</div>
                </div>
                <div class="rounded-xl p-4" style="background:var(--bg-section); border:1.5px solid var(--border-light);">
                    <span class="material-symbols-outlined text-[24px] block mb-2" style="color:#f59e0b;">rate_review</span>
                    <div class="text-sm font-bold" style="color:var(--text-1);">Kalite Raporu</div>
                    <div class="text-xs mt-1" style="color:var(--text-3);">Mekan kalitesini artırmak için dürüst değerlendirmeler yaparsın</div>
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
    <div class="rounded-xl p-6" style="background:#fff; border:1.5px solid <?php echo $s['border']; ?>; box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-[24px]" style="color:<?php echo $s['color']; ?>;"><?php echo $s['icon']; ?></span>
            <span class="font-bold text-sm" style="color:var(--text-1);">Başvuru Durumu: <span style="color:<?php echo $s['color']; ?>;"><?php echo $s['label']; ?></span></span>
        </div>
        <p class="text-sm" style="color:var(--text-2);"><?php echo $s['text']; ?></p>
        <?php if ($application['admin_note']): ?>
            <div class="mt-3 rounded-lg px-4 py-3 text-sm border" style="background:var(--bg-section); border-color:var(--border); color:var(--text-2);">
                <span class="text-xs uppercase tracking-widest font-semibold block mb-1" style="color:var(--text-3);">Admin Notu</span>
                <?php echo escape($application['admin_note']); ?>
            </div>
        <?php endif; ?>
        <?php if ($application['status'] === 'approved'): ?>
            <div class="mt-4 rounded-xl p-4 flex items-center gap-3" style="background:rgba(79,70,229,0.08); border:1.5px solid rgba(79,70,229,0.2);">
                <span class="material-symbols-outlined" style="color:#4F46E5;">badge</span>
                <div>
                    <div class="text-sm font-bold" style="color:#4F46E5;">Gizli Müşteri Rozeti Aktif</div>
                    <div class="text-xs mt-0.5" style="color:var(--text-3);">Check-in paylaşırken "Gizli Müşteri olarak yayınla" seçeneğini kullanabilirsin</div>
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
    <div class="rounded-xl overflow-hidden" style="background:#fff; border:1px solid var(--border); box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div class="px-6 py-4 flex items-center gap-2" style="border-bottom:1px solid var(--border-light);">
            <span class="material-symbols-outlined text-[20px]" style="color:#4F46E5;">edit_note</span>
            <h2 class="text-base font-bold" style="color:var(--text-1);">
                <?php echo ($application && $application['status'] === 'rejected') ? 'Yeniden Başvur' : 'Başvuru Formu'; ?>
            </h2>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <?php echo csrfField(); ?>

            <div>
                <label class="block text-sm font-bold mb-1.5" style="color:var(--text-2);">
                    Neden Gizli Müşteri Olmak İstiyorsun?
                    <span class="text-red-500">*</span>
                    <span class="ml-1" style="color:var(--text-3); font-weight:normal;">(en az 30 karakter)</span>
                </label>
                <textarea name="motivation" rows="5" required
                          placeholder="Mekan kalitesini iyileştirmek, topluluğa dürüst değerlendirmeler sunmak ve..."
                          class="w-full rounded-lg px-4 py-3 text-sm focus:outline-none transition-colors resize-none shadow-inner"
                          style="background:var(--bg-section); border:1px solid var(--border); color:var(--text-1);"
                          id="motivationText"><?php echo escape($application['motivation'] ?? ''); ?></textarea>
                <div class="flex justify-between mt-1">
                    <p class="text-xs" style="color:var(--text-3);">Dürüst ve açıklayıcı bir metin yaz</p>
                    <span class="text-xs" style="color:var(--text-3);" id="charCount">0 / 30+</span>
                </div>
            </div>

            <!-- Kurallar -->
            <div class="rounded-xl p-4 space-y-2 border" style="background:var(--bg-section); border-color:var(--border-light);">
                <div class="text-xs font-bold uppercase tracking-widest mb-2" style="color:var(--text-3);">Kuralları Kabul Ediyorum</div>
                <?php $rules = [
                    'Mekanları tarafsız ve dürüst değerlendireceğim',
                    'Gizli müşteri kimliğimi mekan sahiplerine açıklamayacağım',
                    'Yanıltıcı veya kasıtlı olumsuz yorum yazmayacağım',
                    'Admin kararlarına uyacağım',
                ]; ?>
                <?php foreach ($rules as $i => $rule): ?>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="rule_<?php echo $i; ?>" required
                           class="mt-0.5 rounded" style="background:#fff; border-color:var(--border); color:#4F46E5;">
                    <span class="text-xs" style="color:var(--text-2);"><?php echo $rule; ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit"
                    class="w-full text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-2 hover:brightness-110 active:scale-95 transition-all shadow-[0_4px_16px_rgba(79,70,229,0.25)]"
                    style="background:#4F46E5; border:none; cursor:pointer;">
                <span class="material-symbols-outlined text-[20px]">send</span>
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
        cc.className = 'text-xs ' + (len >= 30 ? 'text-emerald-500' : 'text-red-500');
    };
    ta.addEventListener('input', update);
    update();
}
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
