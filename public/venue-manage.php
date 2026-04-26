<?php
/**
 * Sociaera — İşletme Paneli (Venue Management)
 * Mekan sahibi kendi mekanını yönetir
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
    Auth::setFlash('error', 'Bu mekanı yönetme yetkiniz yok.');
    header('Location: ' . BASE_URL . '/venues'); exit;
}

// POST: Güncelleme
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

        $venueModel->update($venueId, $updateData);
        Auth::setFlash('success', 'İşletme bilgileri güncellendi.');
        header('Location: ' . BASE_URL . '/venue-manage?id=' . $venueId); exit;

    } elseif ($action === 'toggle_open') {
        $newStatus = ($venue['is_open'] ?? 1) ? 0 : 1;
        $venueModel->update($venueId, ['is_open' => $newStatus]);
        Auth::setFlash('success', $newStatus ? 'İşletme açık olarak işaretlendi.' : 'İşletme kapalı olarak işaretlendi.');
        header('Location: ' . BASE_URL . '/venue-manage?id=' . $venueId); exit;
    }
}

// Verileri yeniden çek
$venue = $venueModel->getById($venueId);
$checkinCount = $venueModel->getCheckinCount($venueId);
$categories = VenueModel::categories();

// Bu sayfada right sidebar gösterme
$trendVenues = [];
$miniLeaderboard = [];
$hideSidebar = true;

$pageTitle = $venue['name'] . ' — İşletme Paneli';
$activeNav = 'venue_manage_' . $venueId;

require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-6 max-w-3xl w-full">

    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="<?php echo BASE_URL; ?>/venues" class="w-10 h-10 bg-white/5 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors border border-white/10">
            <span class="material-symbols-outlined text-slate-400">arrow_back</span>
        </a>
        <div class="flex-grow">
            <h1 class="text-2xl font-black text-on-surface tracking-tight"><?php echo escape($venue['name']); ?></h1>
            <p class="text-slate-400 text-sm">İşletme Paneli</p>
        </div>
        <!-- Açık/Kapalı Toggle -->
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="toggle_open">
            <?php $isOpen = $venue['is_open'] ?? 1; ?>
            <button type="submit" class="flex items-center gap-2 px-4 py-2 rounded-lg text-label-md font-semibold transition-all border <?php echo $isOpen ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20 hover:bg-red-500/20'; ?>">
                <span class="w-2.5 h-2.5 rounded-full <?php echo $isOpen ? 'bg-emerald-400 shadow-[0_0_6px_rgba(52,211,153,0.5)]' : 'bg-red-400'; ?>"></span>
                <?php echo $isOpen ? 'Açık' : 'Kapalı'; ?>
            </button>
        </form>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-on-surface"><?php echo $checkinCount; ?></div>
            <div class="text-label-sm text-slate-400 mt-1">Check-in</div>
        </div>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-4 text-center">
            <div class="text-2xl font-black text-on-surface"><?php echo escape($categories[$venue['category']] ?? '-'); ?></div>
            <div class="text-label-sm text-slate-400 mt-1">Kategori</div>
        </div>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-4 text-center">
            <?php
            $statusBadge = match($venue['status']) {
                'approved' => ['text' => 'Onaylı', 'class' => 'text-emerald-400'],
                'pending' => ['text' => 'Bekliyor', 'class' => 'text-amber-400'],
                default => ['text' => ucfirst($venue['status']), 'class' => 'text-slate-400']
            };
            ?>
            <div class="text-2xl font-black <?php echo $statusBadge['class']; ?>"><?php echo $statusBadge['text']; ?></div>
            <div class="text-label-sm text-slate-400 mt-1">Durum</div>
        </div>
    </div>

    <!-- Edit Form -->
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="update">

        <!-- Kapak Görseli -->
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden">
            <div class="h-48 bg-surface-container relative group">
                <?php if (!empty($venue['cover_image'])): ?>
                    <img src="<?php echo BASE_URL . '/uploads/venues/' . escape($venue['cover_image']); ?>" class="w-full h-full object-cover">
                <?php elseif (!empty($venue['image'])): ?>
                    <img src="<?php echo uploadUrl('posts', $venue['image']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-slate-600 bg-surface-container-high">
                        <span class="material-symbols-outlined text-[64px]">add_photo_alternate</span>
                    </div>
                <?php endif; ?>
                <label class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer">
                    <span class="text-white text-sm font-semibold flex items-center gap-2">
                        <span class="material-symbols-outlined">camera_alt</span> Kapak Değiştir
                    </span>
                    <input type="file" name="cover_image" accept="image/*" class="hidden">
                </label>
            </div>
            <div class="p-6 space-y-5">
                <!-- İşletme Adı -->
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">İşletme Adı</label>
                    <input type="text" name="name" value="<?php echo escape($venue['name']); ?>" required
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>

                <!-- Açıklama -->
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Açıklama</label>
                    <textarea name="description" rows="3"
                              class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors resize-none"><?php echo escape($venue['description'] ?? ''); ?></textarea>
                </div>

                <!-- Kategori -->
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Kategori</label>
                    <select name="category" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" class="bg-background" <?php echo $venue['category'] === $key ? 'selected' : ''; ?>><?php echo escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Konum & İletişim -->
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 space-y-5">
            <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-container text-[20px]">pin_drop</span> Konum & İletişim
            </h2>

            <div>
                <label class="block text-label-md text-slate-400 mb-1.5">Adres</label>
                <input type="text" name="address" value="<?php echo escape($venue['address'] ?? ''); ?>" placeholder="İşletme adresi..."
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Telefon</label>
                    <input type="text" name="phone" value="<?php echo escape($venue['phone'] ?? ''); ?>" placeholder="555-XXX-XXXX"
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
                <div>
                    <label class="block text-label-md text-slate-400 mb-1.5">Web Sitesi</label>
                    <input type="url" name="website" value="<?php echo escape($venue['website'] ?? ''); ?>" placeholder="https://..."
                           class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                </div>
            </div>
        </div>

        <!-- Çalışma Saatleri -->
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 space-y-5">
            <h2 class="text-lg font-bold text-on-surface flex items-center gap-2">
                <span class="material-symbols-outlined text-primary-container text-[20px]">schedule</span> Çalışma Saatleri
            </h2>

            <div>
                <label class="block text-label-md text-slate-400 mb-1.5">Saatler</label>
                <input type="text" name="hours" value="<?php echo escape($venue['hours'] ?? ''); ?>" placeholder="Örn: Hafta içi 09:00-22:00, Hafta sonu 10:00-00:00"
                       class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-3 text-sm focus:outline-none focus:border-primary-container/40 transition-colors">
                <p class="text-xs text-slate-500 mt-1">Çalışma saatlerinizi yazın</p>
            </div>

            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_open" value="1" <?php echo ($venue['is_open'] ?? 1) ? 'checked' : ''; ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-white/10 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-white/10 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                </label>
                <span class="text-sm text-on-surface">İşletme şu an açık</span>
            </div>
        </div>

        <!-- Kaydet -->
        <button type="submit" class="w-full bg-primary-container text-white py-3.5 rounded-xl text-label-md font-semibold hover:bg-primary-container/90 transition-colors shadow-[0_0_15px_rgba(255,107,53,0.3)] flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[20px]">save</span> Değişiklikleri Kaydet
        </button>
    </form>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
