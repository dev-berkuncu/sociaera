<?php
/**
 * Sociaera — Kampanya Yönetim Sayfası
 * İşletme sahipleri mekanlarına kampanya oluşturur/düzenler
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
    Auth::setFlash('error', 'Bu mekanı yönetme yetkiniz yok.');
    header('Location: ' . BASE_URL . '/venues'); exit;
}

// ── POST İşlemleri ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create':
            if (empty(trim($_POST['title'] ?? ''))) {
                Auth::setFlash('error', 'Kampanya başlığı zorunludur.');
                break;
            }
            $campaignModel->create($venueId, $_POST);
            Auth::setFlash('success', 'Kampanya oluşturuldu!');
            break;

        case 'update':
            $cId = (int)($_POST['campaign_id'] ?? 0);
            if ($cId) {
                $campaignModel->update($cId, $venueId, $_POST);
                Auth::setFlash('success', 'Kampanya güncellendi.');
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
$pageTitle   = 'Kampanyalar — ' . $venue['name'];
$activeNav   = '';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-6 max-w-3xl w-full">

    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="<?php echo BASE_URL; ?>/venue-manage?id=<?php echo $venueId; ?>"
           class="w-10 h-10 bg-white/5 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors border border-white/10">
            <span class="material-symbols-outlined text-slate-400">arrow_back</span>
        </a>
        <div class="flex-grow">
            <h1 class="text-2xl font-black text-on-surface tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-container">campaign</span>
                Kampanyalar
            </h1>
            <p class="text-slate-400 text-sm"><?php echo escape($venue['name']); ?></p>
        </div>
    </div>

    <!-- ── Kampanya Oluştur / Düzenle Form ── -->
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-white/5 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary-container text-[20px]"><?php echo $editCamp ? 'edit' : 'add_circle'; ?></span>
            <h2 class="text-base font-bold text-on-surface"><?php echo $editCamp ? 'Kampanyayı Düzenle' : 'Yeni Kampanya Oluştur'; ?></h2>
        </div>
        <form method="POST" class="p-6 space-y-5">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="<?php echo $editCamp ? 'update' : 'create'; ?>">
            <?php if ($editCamp): ?>
                <input type="hidden" name="campaign_id" value="<?php echo $editCamp['id']; ?>">
            <?php endif; ?>

            <!-- Başlık -->
            <div>
                <label class="block text-label-md text-slate-400 mb-1.5">Kampanya Başlığı <span class="text-red-400">*</span></label>
                <input type="text" name="title" required
                       value="<?php echo escape($editCamp['title'] ?? ''); ?>"
                       placeholder="Örn: 10. Check-in İndirimi"
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>

            <!-- Açıklama -->
            <div>
                <label class="block text-label-md text-slate-400 mb-1.5">Açıklama</label>
                <textarea name="description" rows="2"
                          placeholder="Kampanya hakkında kısa bilgi..."
                          class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors resize-none"><?php echo escape($editCamp['description'] ?? ''); ?></textarea>
            </div>

            <!-- Tetikleyici -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Kampanya Ne Zaman Tetiklensin?</label>
                    <select name="trigger_type" id="triggerType"
                            class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                        <?php foreach ($triggerLabels as $k => $l): ?>
                            <option value="<?php echo $k; ?>" class="bg-background"
                                <?php echo ($editCamp['trigger_type'] ?? 'nth_checkin') === $k ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="triggerValueWrap">
                    <label class="block text-label-md text-slate-400 mb-1.5">Kaçıncı Check-in? <span class="text-slate-600">(first_checkin hariç)</span></label>
                    <input type="number" name="trigger_value" min="1"
                           value="<?php echo (int)($editCamp['trigger_value'] ?? 10); ?>"
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
            </div>

            <!-- Ödül -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Ödül Türü</label>
                    <select name="reward_type" id="rewardType"
                            class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                        <?php foreach ($rewardLabels as $k => $l): ?>
                            <option value="<?php echo $k; ?>" class="bg-background"
                                <?php echo ($editCamp['reward_type'] ?? 'discount_percent') === $k ? 'selected' : ''; ?>>
                                <?php echo $l; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="rewardValueWrap">
                    <label class="block text-label-md text-slate-400 mb-1.5">Ödül Değeri <span class="text-slate-600">(%50 için 50 yaz)</span></label>
                    <input type="number" name="reward_value" min="0" step="0.01"
                           value="<?php echo $editCamp['reward_value'] ?? ''; ?>"
                           placeholder="50"
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
            </div>

            <!-- Ödül Metni (özel ödüller için) -->
            <div>
                <label class="block text-label-md text-slate-400 mb-1.5">Ödül Açıklaması <span class="text-slate-600">(kullanıcıya gösterilir)</span></label>
                <input type="text" name="reward_text"
                       value="<?php echo escape($editCamp['reward_text'] ?? ''); ?>"
                       placeholder="Örn: Bedava tatlı, %50 indirim kuponu..."
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>

            <!-- Tarih & Limit -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Başlangıç <span class="text-slate-600">(boş = hemen)</span></label>
                    <input type="datetime-local" name="starts_at"
                           value="<?php echo $editCamp['starts_at'] ? date('Y-m-d\TH:i', strtotime($editCamp['starts_at'])) : ''; ?>"
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Bitiş <span class="text-slate-600">(boş = süresiz)</span></label>
                    <input type="datetime-local" name="ends_at"
                           value="<?php echo $editCamp['ends_at'] ? date('Y-m-d\TH:i', strtotime($editCamp['ends_at'])) : ''; ?>"
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Max Kazanım <span class="text-slate-600">(boş = sınırsız)</span></label>
                    <input type="number" name="max_redemptions" min="1"
                           value="<?php echo $editCamp['max_redemptions'] ?? ''; ?>"
                           placeholder="100"
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
            </div>

            <!-- Aktif toggle -->
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer"
                           <?php echo ($editCamp ? $editCamp['is_active'] : 1) ? 'checked' : ''; ?>>
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-white/10 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
                <span class="text-sm text-on-surface">Kampanya aktif</span>
            </div>

            <div class="flex gap-3">
                <button type="submit"
                        class="flex-grow bg-primary-container text-white py-3 rounded-xl text-label-md font-semibold hover:bg-primary-container/90 transition-colors shadow-[0_0_15px_rgba(255,107,53,0.2)] flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[20px]"><?php echo $editCamp ? 'save' : 'add_circle'; ?></span>
                    <?php echo $editCamp ? 'Güncelle' : 'Kampanya Oluştur'; ?>
                </button>
                <?php if ($editCamp): ?>
                <a href="?venue_id=<?php echo $venueId; ?>"
                   class="px-6 py-3 rounded-xl bg-white/5 text-slate-400 hover:bg-white/10 text-label-md font-semibold transition-colors border border-white/10">
                    İptal
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ── Mevcut Kampanyalar ── -->
    <?php if (empty($campaigns)): ?>
        <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-10 text-center text-slate-400">
            <span class="material-symbols-outlined text-[48px] mb-3 opacity-40 block">campaign</span>
            <p class="font-semibold">Henüz kampanya yok.</p>
            <p class="text-sm mt-1">İlk kampanyanı yukarıdan oluştur!</p>
        </div>
    <?php else: ?>
    <div class="space-y-3">
        <h2 class="text-base font-bold text-on-surface flex items-center gap-2">
            <span class="material-symbols-outlined text-slate-400 text-[18px]">list</span>
            Kampanyalar (<?php echo count($campaigns); ?>)
        </h2>
        <?php foreach ($campaigns as $c): ?>
        <div class="bg-[#1E293B]/80 border <?php echo $c['is_active'] ? 'border-white/10' : 'border-white/5 opacity-60'; ?> rounded-xl p-5 flex gap-4 flex-wrap md:flex-nowrap">
            <!-- İkon -->
            <div class="w-12 h-12 rounded-xl <?php echo $c['is_active'] ? 'bg-primary-container/15' : 'bg-white/5'; ?> flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined <?php echo $c['is_active'] ? 'text-primary-container' : 'text-slate-500'; ?> text-[24px]">
                    <?php echo $c['reward_type'] === 'free_item' ? 'redeem' : 'percent'; ?>
                </span>
            </div>

            <!-- Bilgi -->
            <div class="flex-grow min-w-0">
                <div class="flex items-start gap-2 flex-wrap">
                    <h3 class="font-bold text-on-surface"><?php echo escape($c['title']); ?></h3>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-bold <?php echo $c['is_active'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-500/10 text-slate-400'; ?>">
                        <?php echo $c['is_active'] ? 'Aktif' : 'Pasif'; ?>
                    </span>
                </div>

                <!-- Kural -->
                <div class="text-sm text-slate-300 mt-1">
                    <span class="font-semibold text-primary-container"><?php echo CampaignModel::formatReward($c); ?></span>
                    <span class="text-slate-500"> — </span>
                    <?php echo escape(CampaignModel::formatTrigger($c)); ?>
                </div>

                <?php if ($c['description']): ?>
                    <p class="text-xs text-slate-500 mt-1"><?php echo escape($c['description']); ?></p>
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

            <!-- Aksiyonlar -->
            <div class="flex gap-2 items-start flex-shrink-0">
                <a href="?venue_id=<?php echo $venueId; ?>&edit=<?php echo $c['id']; ?>"
                   class="w-9 h-9 rounded-lg bg-white/5 text-slate-400 hover:bg-white/10 flex items-center justify-center transition-colors" title="Düzenle">
                    <span class="material-symbols-outlined text-[18px]">edit</span>
                </a>
                <form method="POST" class="inline">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <button type="submit" class="w-9 h-9 rounded-lg <?php echo $c['is_active'] ? 'bg-amber-500/10 text-amber-400 hover:bg-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'; ?> flex items-center justify-center transition-colors"
                            title="<?php echo $c['is_active'] ? 'Durdur' : 'Aktif Et'; ?>">
                        <span class="material-symbols-outlined text-[18px]"><?php echo $c['is_active'] ? 'pause' : 'play_arrow'; ?></span>
                    </button>
                </form>
                <form method="POST" class="inline" onsubmit="return confirm('Kampanyayı silmek istediğine emin misin?')">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="campaign_id" value="<?php echo $c['id']; ?>">
                    <button type="submit" class="w-9 h-9 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="Sil">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</section>

<script>
// first_checkin seçilince trigger_value'yu gizle
document.getElementById('triggerType')?.addEventListener('change', function() {
    const wrap = document.getElementById('triggerValueWrap');
    if (wrap) wrap.style.opacity = this.value === 'first_checkin' ? '0.3' : '1';
});

// free_item & custom seçilince reward_value'yu gizle
document.getElementById('rewardType')?.addEventListener('change', function() {
    const wrap = document.getElementById('rewardValueWrap');
    if (wrap) wrap.style.opacity = ['free_item','custom'].includes(this.value) ? '0.3' : '1';
});
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
