<?php
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Services/ImageUploader.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';

Auth::requireAccess('venues');
$venueModel = new VenueModel();
$id = (int)($_GET['id'] ?? 0);
$venue = $venueModel->getById($id);

if (!$venue) {
    Auth::setFlash('error', 'Mekan bulunamadı.');
    header('Location: ' . BASE_URL . '/admin/venues');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    
    $data = [
        'name'            => trim($_POST['name'] ?? ''),
        'description'     => trim($_POST['description'] ?? ''),
        'address'         => trim($_POST['address'] ?? ''),
        'category'        => trim($_POST['category'] ?? ''),
        'website'         => trim($_POST['website'] ?? ''),
        'facebrowser_url' => trim($_POST['facebrowser_url'] ?? ''),
        'phone'           => trim($_POST['phone'] ?? ''),
        'status'          => $_POST['status'] ?? 'pending',
    ];

    if (!empty($_FILES['image']['name'])) {
        $uploader = new ImageUploader();
        $result = $uploader->upload($_FILES['image'], 'venues', [
            'maxSize'      => 5 * 1024 * 1024,
            'maxWidth'     => 1200,
            'maxHeight'    => 1200,
            'outputFormat' => 'webp',
        ]);
        if ($result['success']) {
            $data['image'] = $result['filename'];
            if ($venue['image']) {
                $uploader->delete('venues', $venue['image']);
            }
        } else {
            Auth::setFlash('error', 'Görsel yüklenemedi: ' . $result['error']);
        }
    }

    $venueModel->update($id, $data);
    Logger::adminAudit('update', 'venue', $id);
    Auth::setFlash('success', 'Mekan başarıyla güncellendi.');
    header('Location: ' . BASE_URL . '/admin/venue-detail?id=' . $id);
    exit;
}

$categories = VenueModel::categories();
$pageTitle = 'Mekan Düzenle: ' . escape($venue['name']);
$adminPage = 'venues';
require_once __DIR__ . '/_header.php';
?>

<div class="mb-6">
    <a href="<?php echo BASE_URL; ?>/admin/venues" class="text-slate-400 hover:text-white flex items-center gap-1 mb-4 text-sm w-fit transition-colors">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Mekanlara Dön
    </a>
    <h1 class="text-xl font-black text-on-surface flex items-center gap-2">
        <span class="material-symbols-outlined text-primary-container">edit_location</span> Mekan Düzenle
    </h1>
</div>

<div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl shadow-[0_10px_20px_-10px_rgba(15,23,42,0.3)] p-6">
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
        
        <!-- Görsel -->
        <div>
            <label class="block text-slate-300 font-semibold mb-2 text-sm">Mekan Kapak Fotoğrafı</label>
            <div class="flex items-center gap-6">
                <?php if ($venue['image']): ?>
                    <img src="<?php echo uploadUrl('venues', $venue['image']); ?>" alt="Mekan Görseli" class="w-32 h-32 rounded-xl object-cover border border-white/10 shadow-lg">
                <?php else: ?>
                    <div class="w-32 h-32 rounded-xl border border-white/10 bg-white/5 flex items-center justify-center text-slate-500 shadow-lg">
                        <span class="material-symbols-outlined text-4xl">storefront</span>
                    </div>
                <?php endif; ?>
                <div>
                    <input type="file" name="image" accept="image/*" class="block w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary-container file:text-on-primary-container hover:file:bg-primary/80 transition-all cursor-pointer">
                    <p class="text-xs text-slate-500 mt-2">Maksimum 5MB. PNG, JPG veya WEBP.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sol Sütun -->
            <div class="space-y-4">
                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Mekan Adı</label>
                    <input type="text" name="name" value="<?php echo escape($venue['name']); ?>" required class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                </div>
                
                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Kategori</label>
                    <select name="category" class="w-full bg-[#1E293B] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all">
                        <option value="">Seçiniz</option>
                        <?php foreach ($categories as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $venue['category'] === $val ? 'selected' : ''; ?>><?php echo escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Telefon</label>
                    <input type="text" name="phone" value="<?php echo escape($venue['phone'] ?? ''); ?>" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all">
                </div>
                
                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Durum</label>
                    <select name="status" class="w-full bg-[#1E293B] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all">
                        <option value="pending" <?php echo $venue['status'] === 'pending' ? 'selected' : ''; ?>>Bekliyor</option>
                        <option value="approved" <?php echo $venue['status'] === 'approved' ? 'selected' : ''; ?>>Onaylı</option>
                        <option value="rejected" <?php echo $venue['status'] === 'rejected' ? 'selected' : ''; ?>>Reddedildi</option>
                    </select>
                </div>
            </div>

            <!-- Sağ Sütun -->
            <div class="space-y-4">
                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Açıklama</label>
                    <textarea name="description" rows="3" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all resize-none"><?php echo escape($venue['description'] ?? ''); ?></textarea>
                </div>
                
                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Adres</label>
                    <textarea name="address" rows="2" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all resize-none"><?php echo escape($venue['address'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Web Sitesi</label>
                    <input type="url" name="website" value="<?php echo escape($venue['website'] ?? ''); ?>" placeholder="https://" class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all">
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1 text-sm">Facebrowser URL</label>
                    <input type="url" name="facebrowser_url" value="<?php echo escape($venue['facebrowser_url'] ?? ''); ?>" placeholder="https://facebrowser.com/..." class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-primary transition-all">
                </div>
            </div>
        </div>

        <div class="pt-6 border-t border-white/10 flex justify-end">
            <button type="submit" class="bg-primary hover:bg-primary/90 text-on-primary font-bold py-2.5 px-8 rounded-xl transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-lg flex items-center gap-2">
                <span class="material-symbols-outlined text-[20px]">save</span>
                Değişiklikleri Kaydet
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
