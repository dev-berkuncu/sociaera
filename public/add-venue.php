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
                $coverImg = $_FILES['cover_image'] ?? null;
                $uploadedCover = null;
                if ($coverImg && $coverImg['tmp_name']) {
                    $uploadedCover = uploadImage($coverImg, 'venue');
                }

                $venueModel = new VenueModel();
                $isAdmin = Auth::isAdmin();
                $venueModel->create([
                    'name'           => $name,
                    'description'    => $desc,
                    'address'        => $addr,
                    'website'        => $website,
                    'category'       => $category,
                    'facebrowser_url'=> $fb,
                    'image'          => $uploadedCover,
                    'status'         => $isAdmin ? 'approved' : 'pending',
                    'created_by'     => Auth::id(),
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

<div style="min-width:0;">

<style>
/* ── YENİ TASARIM: ADD VENUE ── */
.av-wrapper {
    max-width: 680px;
    width: 100%;
    margin: 0 auto;
    padding-bottom: 60px;
    animation: avFadeIn 0.4s ease-out forwards;
}

@keyframes avFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Header Kısım */
.av-header {
    position: relative;
    background: linear-gradient(135deg, #0f2b46, #1a365d);
    border-radius: 20px;
    padding: 32px;
    color: #fff;
    margin-bottom: 24px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(15, 43, 70, 0.15);
}
.av-header::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url('<?php echo BASE_URL; ?>/assets/img/map-pattern.png') center/cover;
    opacity: 0.1;
    pointer-events: none;
}
.av-header-inner {
    position: relative;
    z-index: 2;
}
.av-back {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 16px;
    transition: color 0.2s;
}
.av-back:hover { color: #fff; }

.av-title {
    font-size: 2rem;
    font-weight: 900;
    margin: 0 0 8px 0;
    letter-spacing: -0.5px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.av-subtitle {
    font-size: 14px;
    color: rgba(255,255,255,0.8);
    margin: 0;
    line-height: 1.5;
}

/* Card ve Form */
.av-card {
    background: #fff;
    border: 1.5px solid var(--border-light);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.04);
    margin-bottom: 24px;
}
.av-section-title {
    font-size: 1.125rem;
    font-weight: 800;
    color: var(--text-1);
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.av-section-title .material-symbols-outlined {
    color: var(--color-primary);
    font-variation-settings: 'FILL' 1;
}

.av-row {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
@media(max-width:600px){ .av-row { flex-direction: column; gap: 20px; } }

.av-field { flex: 1; display: flex; flex-direction: column; gap: 8px; position: relative; }
.av-label { font-size: 13px; font-weight: 700; color: var(--text-2); }
.av-label span { color: var(--color-danger); }

.av-input-wrap { position: relative; }
.av-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-3);
    font-size: 20px;
    pointer-events: none;
    transition: color 0.2s;
}
.av-input {
    width: 100%;
    background: var(--bg-input);
    border: 1.5px solid transparent;
    border-radius: 12px;
    padding: 12px 14px 12px 42px; /* icon padding */
    color: var(--text-1);
    font-size: 14px;
    font-family: inherit;
    font-weight: 500;
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}
textarea.av-input { padding-left: 14px; min-height: 100px; resize: vertical; } /* no icon inside textarea usually */
select.av-input { cursor: pointer; padding-right: 36px; appearance: none; }
.av-select-arrow {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-3);
    pointer-events: none;
}

.av-input:focus {
    border-color: var(--color-primary);
    background: #fff;
    box-shadow: 0 4px 12px rgba(240,109,31,0.1);
}
.av-input:focus + .av-input-icon, .av-input:focus ~ .av-input-icon {
    color: var(--color-primary);
}

/* File Upload */
.av-file-upload {
    position: relative;
    border: 2px dashed var(--border);
    border-radius: 14px;
    padding: 32px 20px;
    text-align: center;
    background: var(--bg-section);
    transition: all 0.2s;
    cursor: pointer;
}
.av-file-upload:hover {
    border-color: var(--color-primary);
    background: var(--color-primary-bg);
}
.av-file-upload input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
.av-file-text {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: var(--text-2);
}

/* Submit Area */
.av-submit-area {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 16px;
    margin-top: 10px;
}
.av-btn {
    background: var(--color-primary);
    color: #fff;
    border: none;
    padding: 14px 32px;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 6px 20px rgba(240,109,31,0.3);
    transition: all 0.2s;
    font-family: inherit;
}
.av-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(240,109,31,0.4);
}

/* Success State */
.av-success-card {
    background: #fff;
    border: 1.5px solid rgba(16,185,129,0.3);
    border-radius: 20px;
    padding: 48px 32px;
    text-align: center;
    box-shadow: 0 12px 32px rgba(16,185,129,0.1);
    animation: avFadeIn 0.4s ease-out forwards;
}
</style>

<div class="av-wrapper">
    
    <div class="av-header">
        <div class="av-header-inner">
            <a href="<?php echo BASE_URL; ?>/venues" class="av-back">
                <span class="material-symbols-outlined" style="font-size:18px;">arrow_back</span>
                Mekanlara Dön
            </a>
            <h1 class="av-title">
                <span class="material-symbols-outlined" style="font-size:36px;">add_location_alt</span>
                Şehrin Mekanlarını Haritaya Taşı
            </h1>
            <p class="av-subtitle">Gittiğin, sevdiğin veya şehrin gizli kalmış köşelerindeki mekanları sisteme ekleyerek Swarm topluluğunun keşfetmesini sağla. Şehri birlikte haritalayalım.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="av-success-card">
            <span class="material-symbols-outlined" style="font-size:72px; color:#10b981; font-variation-settings:'FILL' 1; margin-bottom:20px;">task_alt</span>
            <h2 style="font-size:1.5rem; font-weight:900; color:var(--text-1); margin:0 0 12px;">Öneriniz Alındı!</h2>
            <p style="color:var(--text-2); font-size:15px; margin:0 0 32px; line-height:1.6;">Harika! Mekan öneriniz başarıyla sistemimize iletildi. Adminlerimiz en kısa sürede inceleyip onaylayacaktır. Katkın için teşekkürler!</p>
            
            <a href="<?php echo BASE_URL; ?>/venues" class="av-btn" style="background:#10b981; box-shadow:0 6px 20px rgba(16,185,129,0.3);">
                <span class="material-symbols-outlined" style="font-size:20px;">explore</span>
                Mekanları Keşfet
            </a>
        </div>
    <?php else: ?>
        
        <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.1); border:1.5px solid rgba(239,68,68,0.3); color:#EF4444; padding:16px 20px; border-radius:14px; margin-bottom:24px; display:flex; align-items:center; gap:12px; font-size:14px; font-weight:700;">
            <span class="material-symbols-outlined" style="font-size:24px;">error</span>
            <?php echo escape($error); ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo BASE_URL; ?>/add-venue" method="POST" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            
            <!-- Bölüm 1: Temel Bilgiler -->
            <div class="av-card">
                <h2 class="av-section-title">
                    <span class="material-symbols-outlined">info</span>
                    Temel Bilgiler
                </h2>
                
                <div class="av-row">
                    <div class="av-field">
                        <label class="av-label" for="name">Mekan Adı <span>*</span></label>
                        <div class="av-input-wrap">
                            <span class="material-symbols-outlined av-input-icon">storefront</span>
                            <input type="text" id="name" name="name" value="<?php echo escape($_POST['name'] ?? ''); ?>" required maxlength="100" class="av-input" placeholder="Örn: Bean Machine Coffee">
                        </div>
                    </div>
                    
                    <div class="av-field">
                        <label class="av-label" for="category">Kategori <span>*</span></label>
                        <div class="av-input-wrap">
                            <span class="material-symbols-outlined av-input-icon">category</span>
                            <select name="category" id="category" class="av-input" required>
                                <option value="">Bir kategori seçin</option>
                                <?php foreach ($categories as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($_POST['category'] ?? '') === $key ? 'selected' : ''; ?>><?php echo escape($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="material-symbols-outlined av-select-arrow">expand_more</span>
                        </div>
                    </div>
                </div>

                <div class="av-field" style="margin-bottom:20px;">
                    <label class="av-label" for="description">Açıklama (Opsiyonel)</label>
                    <textarea id="description" name="description" class="av-input" style="padding-left:14px;" placeholder="Mekan hakkında kısa bir bilgi..."><?php echo escape($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="av-field">
                    <label class="av-label">Mekan Fotoğrafı</label>
                    <div class="av-file-upload">
                        <input type="file" id="cover_image" name="cover_image" accept="image/*" onchange="document.getElementById('file-name').innerText = this.files[0] ? this.files[0].name : 'Fotoğraf yüklemek için tıklayın veya sürükleyin';">
                        <div class="av-file-text">
                            <span class="material-symbols-outlined" style="font-size:32px; color:var(--color-primary);">add_photo_alternate</span>
                            <span id="file-name" style="font-weight:600;">Fotoğraf yüklemek için tıklayın veya sürükleyin</span>
                            <span style="font-size:12px; opacity:0.7;">PNG, JPG veya WEBP (Maks 5MB)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bölüm 2: İletişim & Konum -->
            <div class="av-card">
                <h2 class="av-section-title">
                    <span class="material-symbols-outlined">pin_drop</span>
                    Konum & İletişim
                </h2>

                <div class="av-field" style="margin-bottom:20px;">
                    <label class="av-label" for="address">Adres</label>
                    <div class="av-input-wrap">
                        <span class="material-symbols-outlined av-input-icon">location_on</span>
                        <input type="text" id="address" name="address" value="<?php echo escape($_POST['address'] ?? ''); ?>" class="av-input" placeholder="Açık adres veya bölge">
                    </div>
                </div>

                <div class="av-row" style="margin-bottom:0;">
                    <div class="av-field">
                        <label class="av-label" for="facebrowser_url">Facebrowser URL</label>
                        <div class="av-input-wrap">
                            <span class="material-symbols-outlined av-input-icon">public</span>
                            <input type="url" id="facebrowser_url" name="facebrowser_url" value="<?php echo escape($_POST['facebrowser_url'] ?? ''); ?>" class="av-input" placeholder="https://face.gta.world/pages/...">
                        </div>
                    </div>
                    
                    <div class="av-field">
                        <label class="av-label" for="website">Website</label>
                        <div class="av-input-wrap">
                            <span class="material-symbols-outlined av-input-icon">language</span>
                            <input type="url" id="website" name="website" value="<?php echo escape($_POST['website'] ?? ''); ?>" class="av-input" placeholder="https://...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="av-submit-area">
                <a href="<?php echo BASE_URL; ?>/venues" style="color:var(--text-3); font-weight:700; text-decoration:none; margin-right:auto; padding:10px;">İptal</a>
                <button type="submit" class="av-btn">
                    <span class="material-symbols-outlined">send</span>
                    Mekanı Gönder
                </button>
            </div>

        </form>
    <?php endif; ?>
</div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
