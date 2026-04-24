<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';

Auth::requireLogin();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $addr = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $fb = trim($_POST['facebrowser_url'] ?? '');

    if (empty($name)) {
        $error = 'Mekan adı gereklidir.';
    } elseif (strlen($name) > 100) {
        $error = 'Mekan adı en fazla 100 karakter olabilir.';
    } else {
        try {
            $venueModel = new VenueModel();
            $isAdmin = Auth::isAdmin();
            $venueModel->create([
                'name' => $name,
                'description' => $desc,
                'address' => $addr,
                'website' => $website,
                'category' => $category,
                'facebrowser_url' => $fb,
                'status' => $isAdmin ? 'approved' : 'pending',
                'created_by' => Auth::id(),
            ]);
            $success = true;
            if ($isAdmin) {
                Auth::setFlash('success', 'Mekan başarıyla eklendi! ✅');
            } else {
                Auth::setFlash('success', 'Mekan önerisi gönderildi! Admin onayından sonra yayınlanacak.');
            }
        } catch (\Throwable $e) {
            $error = 'Mekan eklenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

$categories = VenueModel::categories();

$pageTitle = 'Mekan Ekle';
$activeNav = 'venues';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-2xl w-full mx-auto">
    <div class="mb-4">
        <a href="<?php echo BASE_URL; ?>/venues" class="flex items-center gap-2 text-slate-400 hover:text-white transition-colors w-fit mb-4">
            <span class="material-symbols-outlined text-[20px]">arrow_back</span> Mekanlar
        </a>
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[32px]">add_circle</span> Yeni Mekan Öner</h1>
        <p class="text-slate-400 mt-2">Mekan öneriniz admin onayından sonra yayınlanacaktır.</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-8 text-center text-slate-400 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
            <span class="material-symbols-outlined text-[64px] mb-4 text-[#10b981]">check_circle</span>
            <p class="text-xl text-on-surface mb-6">Mekan öneriniz başarıyla gönderildi!</p>
            <a href="<?php echo BASE_URL; ?>/venues" class="inline-block bg-primary-container text-white px-6 py-3 rounded-lg font-label-md text-label-md shadow-[0_0_10px_rgba(255,107,53,0.2)] hover:bg-primary-container/90 transition-all">Mekanlara Dön</a>
        </div>
    <?php else: ?>
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
            <?php if ($error): ?>
                <div class="bg-error/10 border border-error/50 text-error px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                    <span class="material-symbols-outlined">error</span>
                    <span><?php echo escape($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="flex flex-col gap-5">
                <?php echo csrfField(); ?>
                
                <div class="flex flex-col gap-2">
                    <label for="name" class="font-label-md text-label-md text-slate-300">Mekan Adı <span class="text-error">*</span></label>
                    <input type="text" id="name" name="name" value="<?php echo escape($_POST['name'] ?? ''); ?>" required maxlength="100" class="bg-background border border-white/10 rounded-lg px-4 py-3 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="category" class="font-label-md text-label-md text-slate-300">Kategori</label>
                    <select name="category" id="category" class="bg-background border border-white/10 rounded-lg px-4 py-3 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm appearance-none cursor-pointer">
                        <option value="" class="bg-background text-slate-400">Seçiniz</option>
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo ($_POST['category'] ?? '') === $key ? 'selected' : ''; ?> class="bg-background text-on-surface"><?php echo escape($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="description" class="font-label-md text-label-md text-slate-300">Açıklama</label>
                    <textarea name="description" id="description" rows="3" class="bg-background border border-white/10 rounded-lg px-4 py-3 text-on-surface focus:border-primary-container focus:outline-none transition-colors shadow-sm resize-y min-h-[100px]"><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="address" class="font-label-md text-label-md text-slate-300">Adres</label>
                    <input type="text" id="address" name="address" value="<?php echo escape($_POST['address'] ?? ''); ?>" placeholder="Los Santos, Vinewood Blvd." class="bg-background border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder:text-slate-600 focus:border-primary-container focus:outline-none transition-colors shadow-sm">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="facebrowser_url" class="font-label-md text-label-md text-slate-300">Facebrowser URL</label>
                    <input type="url" id="facebrowser_url" name="facebrowser_url" value="<?php echo escape($_POST['facebrowser_url'] ?? ''); ?>" placeholder="https://face.gta.world/pages/..." class="bg-background border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder:text-slate-600 focus:border-primary-container focus:outline-none transition-colors shadow-sm">
                </div>
                
                <div class="flex flex-col gap-2">
                    <label for="website" class="font-label-md text-label-md text-slate-300">Website</label>
                    <input type="url" id="website" name="website" value="<?php echo escape($_POST['website'] ?? ''); ?>" placeholder="https://..." class="bg-background border border-white/10 rounded-lg px-4 py-3 text-on-surface placeholder:text-slate-600 focus:border-primary-container focus:outline-none transition-colors shadow-sm">
                </div>
                
                <button type="submit" class="mt-4 bg-primary-container text-white px-6 py-3 rounded-lg font-label-md text-label-md shadow-[0_0_15px_rgba(255,107,53,0.3)] hover:bg-primary-container/90 transition-all flex items-center justify-center gap-2 active:scale-95">
                    <span class="material-symbols-outlined">send</span> Mekan Öner
                </button>
            </form>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
