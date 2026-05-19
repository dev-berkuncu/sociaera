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

<section class="flex-1 flex flex-col gap-6 max-w-2xl w-full mx-auto lg:mx-0">

    <!-- Hero -->
    <div class="relative bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl overflow-hidden">
        <!-- Gradient BG -->
        <div class="absolute inset-0 bg-gradient-to-br from-slate-800/50 via-[#1E293B] to-indigo-900/30 pointer-events-none"></div>
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-indigo-400 to-transparent"></div>

        <div class="relative z-10 p-8 md:p-10">
            <div class="flex items-start gap-4">
                <div class="w-16 h-16 rounded-2xl bg-indigo-500/15 border border-indigo-500/20 flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-indigo-400 text-[32px]">person_search</span>
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-on-surface tracking-tight">Gizli Müşteri Ol</h1>
                    <p class="text-slate-400 mt-1">İşletmeleri anonim olarak değerlendir, topluluğa katkı sağla</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-8">
                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                    <span class="material-symbols-outlined text-indigo-400 text-[24px] block mb-2">visibility_off</span>
                    <div class="text-sm font-semibold text-on-surface">Gizli Kimlik</div>
                    <div class="text-xs text-slate-500 mt-1">Yorumların "Gizli Müşteri" etiketiyle yayınlanır, kimliğin gizli kalır</div>
                </div>
                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                    <span class="material-symbols-outlined text-emerald-400 text-[24px] block mb-2">verified</span>
                    <div class="text-sm font-semibold text-on-surface">Admin Onayı</div>
                    <div class="text-xs text-slate-500 mt-1">Başvurular incelenir, seçilen kullanıcılar özel tag kazanır</div>
                </div>
                <div class="bg-white/5 rounded-xl p-4 border border-white/5">
                    <span class="material-symbols-outlined text-amber-400 text-[24px] block mb-2">rate_review</span>
                    <div class="text-sm font-semibold text-on-surface">Kalite Raporu</div>
                    <div class="text-xs text-slate-500 mt-1">Mekan kalitesini artırmak için dürüst değerlendirmeler yaparsın</div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // ── Durum Kartı ──
    if ($application):
        $statusMap = [
            'pending'  => ['icon' => 'hourglass_top',  'color' => 'amber',  'label' => 'İnceleniyor',  'text' => 'Başvurunuz admin tarafından inceleniyor. Sonucu bildirim olarak alacaksınız.'],
            'approved' => ['icon' => 'check_circle',   'color' => 'emerald','label' => 'Onaylandı',    'text' => 'Tebrikler! Artık mekan check-in\'lerinde "Gizli Müşteri" etiketiyle yorum yapabilirsiniz.'],
            'rejected' => ['icon' => 'cancel',         'color' => 'red',    'label' => 'Reddedildi',   'text' => 'Başvurunuz bu sefer onaylanmadı. Tekrar başvurabilirsiniz.'],
        ];
        $s = $statusMap[$application['status']] ?? $statusMap['pending'];
    ?>
    <div class="bg-[#1E293B]/80 border border-<?php echo $s['color']; ?>-500/20 rounded-xl p-6">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-<?php echo $s['color']; ?>-400 text-[24px]"><?php echo $s['icon']; ?></span>
            <span class="font-bold text-on-surface">Başvuru Durumu: <span class="text-<?php echo $s['color']; ?>-400"><?php echo $s['label']; ?></span></span>
        </div>
        <p class="text-sm text-slate-400"><?php echo $s['text']; ?></p>
        <?php if ($application['admin_note']): ?>
            <div class="mt-3 bg-white/5 rounded-lg px-4 py-3 text-sm text-slate-300 border border-white/5">
                <span class="text-slate-500 text-xs uppercase tracking-widest font-semibold block mb-1">Admin Notu</span>
                <?php echo escape($application['admin_note']); ?>
            </div>
        <?php endif; ?>
        <?php if ($application['status'] === 'approved'): ?>
            <div class="mt-4 bg-indigo-500/10 border border-indigo-500/20 rounded-xl p-4 flex items-center gap-3">
                <span class="material-symbols-outlined text-indigo-400">badge</span>
                <div>
                    <div class="text-sm font-bold text-indigo-300">Gizli Müşteri Rozeti Aktif</div>
                    <div class="text-xs text-slate-400 mt-0.5">Check-in paylaşırken "Gizli Müşteri olarak yayınla" seçeneğini kullanabilirsin</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($application['status'] === 'rejected'): // Reddedilmişse tekrar başvurabilir ?>
    <?php endif; ?>

    <?php if (in_array($application['status'], ['approved', 'pending'])): ?>
        <!-- Zaten aktif başvuru var, form gösterme -->
        <?php
        require_once __DIR__ . '/partials/app_footer.php';
        exit;
    ?>
    <?php endif; ?>

    <?php endif; // $application ?>

    <!-- ── Başvuru Formu ── -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-2">
            <span class="material-symbols-outlined text-indigo-400 text-[20px]">edit_note</span>
            <h2 class="text-base font-bold text-on-surface">
                <?php echo ($application && $application['status'] === 'rejected') ? 'Yeniden Başvur' : 'Başvuru Formu'; ?>
            </h2>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <?php echo csrfField(); ?>

            <div>
                <label class="block text-label-md text-slate-400 mb-1.5">
                    Neden Gizli Müşteri Olmak İstiyorsun?
                    <span class="text-red-400">*</span>
                    <span class="text-slate-600 ml-1">(en az 30 karakter)</span>
                </label>
                <textarea name="motivation" rows="5" required
                          placeholder="Mekan kalitesini iyileştirmek, topluluğa dürüst değerlendirmeler sunmak ve..."
                          class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-indigo-400/40 transition-colors resize-none"
                          id="motivationText"><?php echo escape($application['motivation'] ?? ''); ?></textarea>
                <div class="flex justify-between mt-1">
                    <p class="text-xs text-slate-500">Dürüst ve açıklayıcı bir metin yaz</p>
                    <span class="text-xs text-slate-500" id="charCount">0 / 30+</span>
                </div>
            </div>

            <!-- Kurallar -->
            <div class="bg-white/5 rounded-xl p-4 space-y-2 border border-white/5">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Kuralları Kabul Ediyorum</div>
                <?php $rules = [
                    'Mekanları tarafsız ve dürüst değerlendireceğim',
                    'Gizli müşteri kimliğimi mekan sahiplerine açıklamayacağım',
                    'Yanıltıcı veya kasıtlı olumsuz yorum yazmayacağım',
                    'Admin kararlarına uyacağım',
                ]; ?>
                <?php foreach ($rules as $i => $rule): ?>
                <label class="flex items-start gap-2 cursor-pointer">
                    <input type="checkbox" name="rule_<?php echo $i; ?>" required
                           class="mt-0.5 rounded border-white/20 bg-white/5 text-indigo-500">
                    <span class="text-xs text-slate-400"><?php echo $rule; ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-500 text-white py-3.5 rounded-xl text-label-md font-semibold transition-colors shadow-[0_0_20px_rgba(99,102,241,0.3)] flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[20px]">send</span>
                Başvuruyu Gönder
            </button>
        </form>
    </div>

</section>

<script>
const ta = document.getElementById('motivationText');
const cc = document.getElementById('charCount');
if (ta && cc) {
    const update = () => {
        const len = ta.value.length;
        cc.textContent = len + ' karakter';
        cc.className = 'text-xs ' + (len >= 30 ? 'text-emerald-400' : 'text-red-400');
    };
    ta.addEventListener('input', update);
    update();
}
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
