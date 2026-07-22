<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';

$pageTitle = "İşletmenizi Ekleyin & Kaydedin — Sociaera Business";
$activeNav = "business";

$error = '';
$success = false;
$createdVenueId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        Csrf::requireValid();

        $name           = trim($_POST['name'] ?? '');
        $category       = trim($_POST['category'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $website        = trim($_POST['website'] ?? '');
        $facebrowser    = trim($_POST['facebrowser_url'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');

        if (empty($name)) {
            throw new Exception('Lütfen mekan adını belirtiniz.');
        }

        if (empty($category)) {
            throw new Exception('Lütfen bir mekan kategorisi seçiniz.');
        }

        // Fotoğraf Yükleme
        $coverImg = $_FILES['cover_image'] ?? null;
        $uploadedCover = null;
        if ($coverImg && !empty($coverImg['tmp_name'])) {
            $uploader = new ImageUploader();
            $result = $uploader->upload($coverImg, 'venues', [
                'maxWidth'     => 1200,
                'maxHeight'    => 1200,
                'quality'      => 85,
                'outputFormat' => 'webp'
            ]);

            if ($result['success']) {
                $uploadedCover = $result['filename'];
            } else {
                throw new Exception('Fotoğraf yüklenemedi: ' . $result['error']);
            }
        }

        $venueModel = new VenueModel();
        
        // İşletme kaydı doğrudan onaylı (approved) olarak sisteme işlenir
        $createdVenueId = $venueModel->create([
            'name'            => $name,
            'description'     => $description,
            'address'         => $address,
            'website'         => $website,
            'category'        => $category,
            'facebrowser_url' => $facebrowser,
            'image'           => $uploadedCover,
            'status'          => 'approved',
            'is_active'       => 1,
            'created_by'      => Auth::check() ? Auth::id() : null,
        ]);

        if ($phone && $createdVenueId) {
            $venueModel->update($createdVenueId, ['phone' => $phone]);
        }

        $success = true;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$categories = VenueModel::categories();

require_once __DIR__ . '/partials/app_header.php';
?>

<div class="max-w-5xl mx-auto px-4 py-8 space-y-12">

    <!-- Hero Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-900/40 via-purple-900/20 to-slate-900/80 border border-white/10 p-8 md:p-12 shadow-2xl text-center md:text-left flex flex-col md:flex-row items-center justify-between gap-8">
        <div class="space-y-4 max-w-2xl z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/20 border border-primary/30 text-primary text-xs font-bold uppercase tracking-wider">
                <span class="material-symbols-outlined text-sm">storefront</span>
                Sociaera Business
            </div>
            <h1 class="text-3xl md:text-5xl font-black text-white leading-tight">
                İşletmenizi Ekleyin, <br><span class="bg-gradient-to-r from-primary via-purple-400 to-pink-400 bg-clip-text text-transparent">Müşterilerinize Ulaşın!</span>
            </h1>
            <p class="text-slate-300 text-base md:text-lg leading-relaxed">
                Mekanınızı Sociaera haritasına kaydedin. Müşterileriniz mekanınızda check-in yapsın, fotoğraflar paylaşsın ve özel kampanyalarınızla müdavim kitlenizi büyütün.
            </p>
            <div class="pt-2 flex flex-wrap gap-4 justify-center md:justify-start">
                <a href="#register-form" class="bg-primary hover:bg-primary/90 text-on-primary font-bold px-6 py-3 rounded-xl shadow-lg hover:shadow-primary/30 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined">add_location_alt</span>
                    Hemen İşletmeni Kaydet
                </a>
            </div>
        </div>
        <div class="relative z-10 w-48 h-48 md:w-64 md:h-64 flex-shrink-0 flex items-center justify-center bg-white/5 rounded-3xl border border-white/10 backdrop-blur-xl shadow-inner">
            <div class="text-center space-y-3 p-4">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-tr from-primary to-purple-500 flex items-center justify-center shadow-lg text-white">
                    <span class="material-symbols-outlined text-3xl">location_on</span>
                </div>
                <div class="text-white font-bold text-lg">Mekan Haritası</div>
                <div class="text-xs text-slate-400">Anında onaylı listeleme & kolay check-in</div>
            </div>
        </div>
    </div>

    <!-- Advantages Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-[#1E293B]/60 border border-white/10 rounded-2xl p-6 space-y-3 hover:border-primary/50 transition-all shadow-md">
            <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">map</span>
            </div>
            <h3 class="text-lg font-bold text-white">Şehir Haritasında Yerinizi Alın</h3>
            <p class="text-slate-400 text-sm leading-relaxed">
                Tüm oyuncular ve kullanıcılar mekanınızı canlı haritada ve arama listelerinde anında görüntüleyebilir.
            </p>
        </div>

        <div class="bg-[#1E293B]/60 border border-white/10 rounded-2xl p-6 space-y-3 hover:border-primary/50 transition-all shadow-md">
            <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">photo_camera</span>
            </div>
            <h3 class="text-lg font-bold text-white">Müşteri Check-in'leri & Paylaşımlar</h3>
            <p class="text-slate-400 text-sm leading-relaxed">
                Müşterileriniz mekanınızdan gönderiler ve fotoğraflar paylaşarak mekanınızın organik reklamını yapar.
            </p>
        </div>

        <div class="bg-[#1E293B]/60 border border-white/10 rounded-2xl p-6 space-y-3 hover:border-primary/50 transition-all shadow-md">
            <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">campaign</span>
            </div>
            <h3 class="text-lg font-bold text-white">Özel Kampanyalar Tanımlayın</h3>
            <p class="text-slate-400 text-sm leading-relaxed">
                Sadık müşterilerinize 3. check-in indirimi veya özel fırsatlar sunarak mekanınıza çekin.
            </p>
        </div>
    </div>

    <!-- Registration Form Section -->
    <div id="register-form" class="bg-[#1E293B]/80 backdrop-blur-xl border border-white/10 rounded-3xl p-6 md:p-10 shadow-2xl space-y-8">
        
        <div class="border-b border-white/10 pb-6 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-2xl font-black text-white flex items-center gap-3">
                    <span class="material-symbols-outlined text-primary text-3xl">domain_add</span>
                    İşletme Kayıt Formu
                </h2>
                <p class="text-slate-400 text-sm mt-1">Aşağıdaki bilgileri doldurarak mekanınızı anında Sociaera sistemine ekleyin.</p>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="bg-emerald-500/10 border border-emerald-500/30 rounded-2xl p-6 text-center space-y-4">
                <div class="w-16 h-16 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-3xl">check_circle</span>
                </div>
                <h3 class="text-xl font-bold text-emerald-300">Tebrikler! İşletmeniz Başarıyla Kaydedildi 🎉</h3>
                <p class="text-slate-300 text-sm max-w-lg mx-auto">
                    Mekanınız sisteme eklendi ve onaylandı. Artık kullanıcılar mekanınızda check-in yapabilir!
                </p>
                <div class="pt-2 flex justify-center gap-4">
                    <?php if ($createdVenueId): ?>
                        <a href="<?php echo BASE_URL; ?>/venue?id=<?php echo $createdVenueId; ?>" class="bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold px-6 py-2.5 rounded-xl transition-all shadow-md">
                            Mekanı Görüntüle
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/business" class="bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-2.5 rounded-xl transition-all">
                        Yeni Mekan Ekle
                    </a>
                </div>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl p-4 flex items-center gap-3 text-sm">
                    <span class="material-symbols-outlined text-lg">error</span>
                    <div><?php echo escape($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <?php echo csrfInput(); ?>

                <!-- Form Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Left Column -->
                    <div class="space-y-5">
                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">
                                Mekan / İşletme Adı <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="name" required placeholder="Örn: Bahama Mamas, Bean Machine..."
                                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">
                                Kategori <span class="text-red-400">*</span>
                            </label>
                            <select name="category" required class="w-full bg-[#1E293B] border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
                                <option value="">Bir kategori seçiniz...</option>
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo escape($key); ?>"><?php echo escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">İletişim / Telefon</label>
                            <input type="text" name="phone" placeholder="Örn: 555-0199"
                                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">Adres / Konum</label>
                            <textarea name="address" rows="3" placeholder="Örn: Del Perro Boulevard No:42, Rockford Hills"
                                      class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-all resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-5">
                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">Mekan Görseli / Kapak Fotoğrafı</label>
                            <div class="border-2 border-dashed border-white/15 rounded-2xl p-4 text-center hover:border-primary/50 transition-all bg-white/[0.02]">
                                <input type="file" name="cover_image" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-primary file:text-on-primary hover:file:bg-primary/80 transition-all cursor-pointer">
                                <p class="text-xs text-slate-500 mt-2">Önerilen: 1200x800 WEBP, PNG veya JPG (Max 5MB)</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">Açıklama</label>
                            <textarea name="description" rows="3" placeholder="Mekanınızın konsepti, müzik tarzı veya sunduğunuz imkanlar..."
                                      class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-all resize-none"></textarea>
                        </div>

                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">Web Sitesi</label>
                            <input type="url" name="website" placeholder="https://..."
                                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-all">
                        </div>

                        <div>
                            <label class="block text-slate-200 font-semibold mb-2 text-sm">Facebrowser URL</label>
                            <input type="url" name="facebrowser_url" placeholder="https://facebrowser.com/page..."
                                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-all">
                        </div>
                    </div>

                </div>

                <div class="pt-4 border-t border-white/10 flex justify-end">
                    <button type="submit" class="bg-primary hover:bg-primary/90 text-on-primary font-bold py-3.5 px-10 rounded-2xl transition-all transform hover:scale-[1.02] active:scale-[0.98] shadow-xl flex items-center gap-2">
                        <span class="material-symbols-outlined">send</span>
                        İşletmeyi Kaydet & Yayınla
                    </button>
                </div>

            </form>

        <?php endif; ?>

    </div>

</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
