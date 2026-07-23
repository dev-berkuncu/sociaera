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

<style>
/* Custom Styles for Business Mockup Design */
.business-container {
    background-color: #0b0e14;
    color: #ffffff;
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
    position: relative;
    overflow: hidden;
}

/* Ambient Radial Glows */
.glow-top-left {
    position: absolute;
    top: -100px;
    left: -100px;
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, rgba(240, 109, 31, 0.18) 0%, rgba(240, 109, 31, 0) 70%);
    pointer-events: none;
    z-index: 0;
}

.glow-phone {
    position: absolute;
    top: 50%;
    right: 10%;
    transform: translateY(-50%);
    width: 450px;
    height: 450px;
    background: radial-gradient(circle, rgba(240, 109, 31, 0.22) 0%, rgba(240, 109, 31, 0) 70%);
    pointer-events: none;
    z-index: 0;
}

/* Glowing Orange Buttons */
.btn-orange-glow {
    background-color: #F06D1F;
    color: #ffffff;
    font-weight: 700;
    border-radius: 9999px;
    box-shadow: 0 0 25px rgba(240, 109, 31, 0.55), 0 4px 12px rgba(240, 109, 31, 0.3);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.btn-orange-glow:hover {
    background-color: #ff7a29;
    box-shadow: 0 0 35px rgba(240, 109, 31, 0.8), 0 6px 20px rgba(240, 109, 31, 0.5);
    transform: translateY(-2px);
}

/* 3D Phone Mockup Styling */
.phone-perspective {
    perspective: 1200px;
}
.phone-frame {
    transform: rotateY(-14deg) rotateX(8deg) rotateZ(3deg);
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.6s ease;
    box-shadow: -20px 20px 50px rgba(0, 0, 0, 0.7), 0 0 30px rgba(240, 109, 31, 0.2);
}
.phone-frame:hover {
    transform: rotateY(-4deg) rotateX(2deg) rotateZ(1deg) scale(1.02);
    box-shadow: -10px 15px 40px rgba(0, 0, 0, 0.6), 0 0 45px rgba(240, 109, 31, 0.4);
}

/* Glassmorphism Form Card */
.glass-form-card {
    background: rgba(18, 24, 38, 0.75);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(240, 109, 31, 0.4);
    box-shadow: 0 0 40px rgba(240, 109, 31, 0.15), inset 0 1px 1px rgba(255, 255, 255, 0.1);
    position: relative;
    overflow: hidden;
}

.glass-form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(240, 109, 31, 0.8), transparent);
}

/* Form Input Styling */
.form-input-dark {
    background: rgba(10, 14, 23, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.12);
    color: #ffffff;
    border-radius: 12px;
    padding: 12px 16px;
    transition: all 0.2s ease;
}
.form-input-dark:focus {
    outline: none;
    border-color: #F06D1F;
    box-shadow: 0 0 15px rgba(240, 109, 31, 0.3);
}
</style>

<div class="business-container min-h-screen pb-16 px-4 md:px-8">
    
    <!-- Ambient Lighting Effects -->
    <div class="glow-top-left"></div>
    <div class="glow-phone"></div>

    <div class="max-w-6xl mx-auto relative z-10 space-y-12 pt-6">

        <!-- Top Header Navigation Bar -->
        <header class="flex items-center justify-between py-4 border-b border-white/10">
            <div class="flex items-center gap-2">
                <span class="text-2xl font-black text-[#F06D1F] tracking-tight drop-shadow-[0_0_10px_rgba(240,109,31,0.5)]">Sociaera</span>
                <span class="text-2xl font-light text-white tracking-wide">Business</span>
            </div>
            <a href="#register-form" class="btn-orange-glow px-6 py-2.5 text-sm tracking-wide">
                İşletmeni Kaydet
            </a>
        </header>

        <!-- Hero Section -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center pt-4 lg:pt-8">
            
            <!-- Hero Text Left (7 Cols) -->
            <div class="lg:col-span-7 space-y-8 text-center lg:text-left">
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.15] tracking-tight">
                    İşletmenizi Ekleyin,<br>
                    <span class="text-white">Müşterilerinize</span><br>
                    <span class="text-white">Ulaşın!</span>
                </h1>

                <div class="pt-2 flex justify-center lg:justify-start">
                    <a href="#register-form" class="btn-orange-glow px-8 py-4 text-base sm:text-lg inline-flex items-center gap-3">
                        Hemen İşletmeni Kaydet
                    </a>
                </div>

                <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-xl mx-auto lg:mx-0">
                    Mekanınızı Sociaera haritasına kaydedin. Müşterileriniz mekanınızda check-in yapsın, fotoğraflar paylaşsın ve özel kampanyalarınızla müdavim kitlenizi büyütün.
                </p>
            </div>

            <!-- Phone 3D Visual Right (5 Cols) -->
            <div class="lg:col-span-5 flex justify-center lg:justify-end phone-perspective py-6">
                
                <!-- Realistic Smartphone Frame -->
                <div class="phone-frame w-[280px] sm:w-[310px] bg-[#121620] rounded-[44px] p-3 border-4 border-[#2a303f] relative overflow-hidden">
                    
                    <!-- Phone Dynamic Island / Speaker Notch -->
                    <div class="w-28 h-4 bg-black rounded-full mx-auto mb-2 relative z-20 flex items-center justify-center">
                        <div class="w-3 h-3 rounded-full bg-[#1a1a1a] mr-2"></div>
                    </div>

                    <!-- Inner App Display Screen -->
                    <div class="bg-[#0f131c] rounded-[34px] overflow-hidden text-white border border-white/10 text-left relative min-h-[520px] flex flex-col justify-between p-3">
                        
                        <!-- App Header -->
                        <div class="flex items-center justify-between pb-3 border-b border-white/10">
                            <span class="font-black text-sm text-[#F06D1F]">Sociaera</span>
                            <div class="flex items-center gap-2">
                                <span class="material-symbols-outlined text-xs text-slate-400">search</span>
                                <span class="material-symbols-outlined text-xs text-[#F06D1F]">notifications</span>
                            </div>
                        </div>

                        <!-- Simulated Map View with Pins -->
                        <div class="relative my-4 flex-1 rounded-2xl bg-[#161c28] border border-white/5 overflow-hidden p-2 min-h-[280px]">
                            <!-- Grid / Roads illustration -->
                            <div class="absolute inset-0 opacity-20 bg-[radial-gradient(#38bdf8_1px,transparent_1px)] [background-size:16px_16px]"></div>
                            
                            <!-- Glowing Map Pins -->
                            <div class="absolute top-6 left-10 text-[#F06D1F] drop-shadow-[0_0_8px_rgba(240,109,31,0.8)] animate-pulse">
                                <span class="material-symbols-outlined text-2xl">location_on</span>
                            </div>
                            <div class="absolute top-16 right-12 text-[#F06D1F] drop-shadow-[0_0_8px_rgba(240,109,31,0.8)]">
                                <span class="material-symbols-outlined text-xl">location_on</span>
                            </div>
                            <div class="absolute bottom-16 left-16 text-[#F06D1F] drop-shadow-[0_0_8px_rgba(240,109,31,0.8)]">
                                <span class="material-symbols-outlined text-xl">location_on</span>
                            </div>

                            <!-- Central Pulsing Radar / Business Marker -->
                            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-[#F06D1F]/20 animate-ping absolute"></div>
                                <div class="w-8 h-8 rounded-full bg-[#F06D1F] border-2 border-white flex items-center justify-center text-white shadow-[0_0_15px_rgba(240,109,31,0.9)] relative z-10">
                                    <span class="material-symbols-outlined text-sm">visibility</span>
                                </div>
                            </div>
                            
                            <!-- Simulated Active Venue Card -->
                            <div class="absolute bottom-2 left-2 right-2 bg-[#1e2638]/90 backdrop-blur-md rounded-xl p-2 border border-white/10 text-xs">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-white">Sociaera Venue</span>
                                    <span class="text-amber-400 font-semibold">★ 5.0</span>
                                </div>
                                <div class="text-[10px] text-slate-400">68 Check-in • Del Perro Blvd</div>
                            </div>
                        </div>

                        <!-- App Bottom Navigation Bar -->
                        <div class="flex items-center justify-around pt-2 border-t border-white/10 text-slate-400">
                            <span class="material-symbols-outlined text-base text-[#F06D1F]">home</span>
                            <span class="material-symbols-outlined text-base">map</span>
                            <div class="w-7 h-7 rounded-full bg-[#F06D1F] text-white flex items-center justify-center shadow-md">
                                <span class="material-symbols-outlined text-sm">add</span>
                            </div>
                            <span class="material-symbols-outlined text-base">bookmark</span>
                            <span class="material-symbols-outlined text-base">person</span>
                        </div>

                    </div>

                </div>

            </div>

        </div>

        <!-- Features Slider / Carousel Bar -->
        <div class="pt-6">
            <div class="bg-[#121722]/80 backdrop-blur-md border border-white/10 rounded-full py-4 px-6 flex items-center justify-between gap-4 max-w-4xl mx-auto shadow-lg">
                <button type="button" class="text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                
                <div class="flex flex-wrap items-center justify-around w-full gap-4 text-center">
                    
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#F06D1F]/20 text-[#F06D1F] flex items-center justify-center border border-[#F06D1F]/30 shadow-[0_0_10px_rgba(240,109,31,0.3)]">
                            <span class="material-symbols-outlined text-lg">location_on</span>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-white">Şehir Haritasında Yerinizi Alın</span>
                    </div>

                    <div class="hidden sm:block w-px h-6 bg-white/10"></div>

                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#F06D1F]/20 text-[#F06D1F] flex items-center justify-center border border-[#F06D1F]/30 shadow-[0_0_10px_rgba(240,109,31,0.3)]">
                            <span class="material-symbols-outlined text-lg">person</span>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-white">Müşteri Check-in'leri & Paylaşımlar</span>
                    </div>

                    <div class="hidden md:block w-px h-6 bg-white/10"></div>

                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-[#F06D1F]/20 text-[#F06D1F] flex items-center justify-center border border-[#F06D1F]/30 shadow-[0_0_10px_rgba(240,109,31,0.3)]">
                            <span class="material-symbols-outlined text-lg">local_offer</span>
                        </div>
                        <span class="text-xs sm:text-sm font-semibold text-white">Özel Kampanyalar Tanımlayın</span>
                    </div>

                </div>

                <button type="button" class="text-slate-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>

        <!-- Form Section -->
        <div id="register-form" class="pt-8">
            <div class="glass-form-card rounded-3xl p-6 sm:p-10">
                
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-8 tracking-tight">
                    İşletme Kayıt Formu
                </h2>

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
                                <a href="<?php echo BASE_URL; ?>/venue?id=<?php echo $createdVenueId; ?>" class="bg-[#F06D1F] hover:bg-[#F06D1F]/90 text-white font-bold px-6 py-2.5 rounded-xl transition-all shadow-md">
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
                        <div class="bg-red-500/10 border border-red-500/30 text-red-400 rounded-xl p-4 flex items-center gap-3 text-sm mb-6">
                            <span class="material-symbols-outlined text-lg">error</span>
                            <div><?php echo escape($error); ?></div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <?php echo csrfInput(); ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Left Column -->
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">
                                        İşletme Adı <span class="text-[#F06D1F]">*</span>
                                    </label>
                                    <input type="text" name="name" required placeholder="İşletme Kayıt Formu"
                                           class="w-full form-input-dark placeholder-slate-600">
                                </div>

                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">
                                        Kategori <span class="text-[#F06D1F]">*</span>
                                    </label>
                                    <select name="category" required class="w-full form-input-dark">
                                        <option value="">Bir kategori seçiniz...</option>
                                        <?php foreach ($categories as $key => $label): ?>
                                            <option value="<?php echo escape($key); ?>"><?php echo escape($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">İletişim / Telefon</label>
                                    <input type="text" name="phone" placeholder="Örn: 555-0199"
                                           class="w-full form-input-dark placeholder-slate-600">
                                </div>

                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">Adres / Konum</label>
                                    <textarea name="address" rows="3" placeholder="Örn: Del Perro Boulevard No:42, Rockford Hills"
                                              class="w-full form-input-dark placeholder-slate-600 resize-none"></textarea>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-5">
                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">Mekan Görseli / Kapak Fotoğrafı</label>
                                    <div class="border border-white/10 rounded-xl p-3 bg-black/40">
                                        <input type="file" name="cover_image" accept="image/*" class="block w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-[#F06D1F] file:text-white hover:file:bg-[#F06D1F]/80 transition-all cursor-pointer">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">Açıklama</label>
                                    <textarea name="description" rows="3" placeholder="Mekanınızın konsepti veya sunduğunuz imkanlar..."
                                              class="w-full form-input-dark placeholder-slate-600 resize-none"></textarea>
                                </div>

                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">Web Sitesi</label>
                                    <input type="url" name="website" placeholder="https://..."
                                           class="w-full form-input-dark placeholder-slate-600">
                                </div>

                                <div>
                                    <label class="block text-slate-300 font-semibold mb-2 text-xs uppercase tracking-wider">Facebrowser URL</label>
                                    <input type="url" name="facebrowser_url" placeholder="https://facebrowser.com/..."
                                           class="w-full form-input-dark placeholder-slate-600">
                                </div>
                            </div>

                        </div>

                        <div class="pt-6 border-t border-white/10 flex justify-end">
                            <button type="submit" class="btn-orange-glow px-10 py-3.5 text-base inline-flex items-center gap-2">
                                <span class="material-symbols-outlined">send</span>
                                İşletmeyi Kaydet & Yayınla
                            </button>
                        </div>

                    </form>

                <?php endif; ?>

            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
