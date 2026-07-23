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
        $createdVenueId = $venueModel->create([
            'name'            => $name,
            'description'     => $description,
            'address'         => $address,
            'website'         => $website,
            'category'        => $category,
            'facebrowser_url' => $facebrowser,
            'image'           => $uploadedCover,
            'status'          => 'pending',
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
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="<?php echo csrfToken(); ?>"/>
<title>İşletmenizi Ekleyin — Sociaera Business</title>
<meta name="description" content="Mekanınızı Sociaera haritasına kaydedin. Müşterileriniz check-in yapsın, fotoğraf paylaşsın ve kampanyalarınızla kitlenizi büyütün."/>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="anonymous" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>

<style>
/* ── RESET & BASE ── */
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
html, body {
    background: #0b0e14 !important;
    color: #ffffff !important;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
    scroll-behavior: smooth;
}
a { text-decoration: none; color: inherit; }
input, select, textarea, button { font-family: inherit; }

/* ── AMBIENT GLOWS ── */
.glow-top-left {
    position: absolute; top: -120px; left: -120px;
    width: 600px; height: 600px;
    background: radial-gradient(circle, rgba(240,109,31,0.15) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
}
.glow-right {
    position: absolute; top: 30%; right: -100px;
    width: 500px; height: 500px;
    background: radial-gradient(circle, rgba(240,109,31,0.18) 0%, transparent 70%);
    pointer-events: none; z-index: 0;
}

/* ── BUTTONS ── */
.btn-orange {
    display: inline-flex; align-items: center; gap: 8px;
    background: #F06D1F; color: #fff; font-weight: 700;
    border: none; border-radius: 9999px; cursor: pointer;
    box-shadow: 0 0 20px rgba(240,109,31,0.5), 0 4px 12px rgba(240,109,31,0.25);
    transition: all 0.25s ease;
}
.btn-orange:hover {
    background: #ff7a29;
    box-shadow: 0 0 30px rgba(240,109,31,0.7), 0 6px 18px rgba(240,109,31,0.4);
    transform: translateY(-2px);
}

/* ── 3D PHONE ── */
.phone-perspective { perspective: 1200px; }
.phone-frame {
    transform: rotateY(-14deg) rotateX(6deg) rotateZ(2deg);
    transition: transform 0.6s cubic-bezier(0.16,1,0.3,1), box-shadow 0.6s ease;
    box-shadow: -20px 20px 60px rgba(0,0,0,0.6), 0 0 25px rgba(240,109,31,0.15);
}
.phone-frame:hover {
    transform: rotateY(-5deg) rotateX(2deg) rotateZ(0.5deg) scale(1.03);
    box-shadow: -12px 15px 40px rgba(0,0,0,0.5), 0 0 40px rgba(240,109,31,0.35);
}

/* ── GLASS FORM ── */
.glass-form {
    background: rgba(16,22,34,0.8);
    backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
    border: 1.5px solid rgba(240,109,31,0.35);
    box-shadow: 0 0 50px rgba(240,109,31,0.1), inset 0 1px 0 rgba(255,255,255,0.06);
    position: relative; overflow: hidden;
}
.glass-form::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
    background: linear-gradient(90deg, transparent, rgba(240,109,31,0.7), transparent);
}

/* ── FORM INPUTS ── */
.form-input {
    width: 100%; background: rgba(8,12,20,0.8);
    border: 1px solid rgba(255,255,255,0.1); color: #fff;
    border-radius: 12px; padding: 12px 16px; font-size: 14px;
    transition: all 0.2s ease; outline: none;
}
.form-input:focus {
    border-color: #F06D1F;
    box-shadow: 0 0 12px rgba(240,109,31,0.25);
}
.form-input::placeholder { color: rgba(255,255,255,0.25); }
select.form-input { appearance: none; cursor: pointer; }
select.form-input option { background: #0f1520; color: #fff; }

/* ── FEATURE PILL BAR ── */
.pill-bar {
    background: rgba(14,19,30,0.85);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.08);
}

/* ── RESPONSIVE ── */
@media (max-width: 768px) {
    .hero-grid { flex-direction: column !important; text-align: center; }
    .hero-text h1 { font-size: 2.2rem; }
    .hero-actions { justify-content: center; }
    .phone-perspective { display: none; }
    .pill-features { flex-direction: column; gap: 12px !important; }
    .pill-divider { display: none; }
}

/* ── ANIMATIONS ── */
@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 12px rgba(240,109,31,0.5); }
    50% { box-shadow: 0 0 24px rgba(240,109,31,0.9); }
}
@keyframes float-pin {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-4px); }
}
.pin-float { animation: float-pin 2s ease-in-out infinite; }
.pin-float-delay { animation: float-pin 2.5s ease-in-out 0.4s infinite; }
.pin-float-delay2 { animation: float-pin 2.2s ease-in-out 0.8s infinite; }
</style>
</head>

<body>

<div style="position:relative; overflow:hidden; min-height:100vh; padding-bottom:60px;">

    <!-- Ambient Glows -->
    <div class="glow-top-left"></div>
    <div class="glow-right"></div>

    <div style="max-width:1140px; margin:0 auto; position:relative; z-index:10; padding:0 20px;">

        <!-- ── TOP NAV ── -->
        <header style="display:flex; align-items:center; justify-content:space-between; padding:20px 0; border-bottom:1px solid rgba(255,255,255,0.08);">
            <div style="display:flex; align-items:center; gap:8px;">
                <span style="font-size:22px; font-weight:800; color:#F06D1F; letter-spacing:-0.5px; text-shadow:0 0 12px rgba(240,109,31,0.4);">Sociaera</span>
                <span style="font-size:22px; font-weight:300; color:rgba(255,255,255,0.85); letter-spacing:1px;">Business</span>
            </div>
            <a href="#register-form" class="btn-orange" style="padding:10px 24px; font-size:14px;">
                İşletmeni Kaydet
            </a>
        </header>

        <!-- ── HERO ── -->
        <div class="hero-grid" style="display:flex; align-items:center; gap:40px; padding:50px 0 30px;">

            <!-- Left: Text -->
            <div class="hero-text" style="flex:1; min-width:0;">
                <h1 style="font-size:3.2rem; font-weight:800; line-height:1.12; letter-spacing:-1px; margin-bottom:28px;">
                    İşletmenizi Ekleyin,<br>
                    Müşterilerinize<br>
                    Ulaşın!
                </h1>

                <div class="hero-actions" style="display:flex; gap:16px; margin-bottom:24px;">
                    <a href="#register-form" class="btn-orange" style="padding:16px 32px; font-size:16px;">
                        Hemen İşletmeni Kaydet
                    </a>
                </div>

                <p style="color:rgba(255,255,255,0.45); font-size:15px; line-height:1.7; max-width:520px;">
                    Mekanınızı Sociaera haritasına kaydedin. Müşterileriniz mekanınızda check-in yapsın, fotoğraflar paylaşsın ve özel kampanyalarınızla kitlenizi büyütün.
                </p>
            </div>

            <!-- Right: 3D Phone -->
            <div class="phone-perspective" style="flex-shrink:0;">
                <div class="phone-frame" style="width:290px; background:#1a1f2e; border-radius:44px; padding:10px; border:3px solid #2d3345;">

                    <!-- Notch -->
                    <div style="width:100px; height:14px; background:#000; border-radius:99px; margin:0 auto 6px;"></div>

                    <!-- Screen -->
                    <div style="background:#0d1117; border-radius:34px; overflow:hidden; padding:12px; min-height:500px; display:flex; flex-direction:column;">

                        <!-- App Top Bar -->
                        <div style="display:flex; align-items:center; justify-content:space-between; padding-bottom:10px; border-bottom:1px solid rgba(255,255,255,0.08);">
                            <span style="font-weight:800; font-size:13px; color:#F06D1F;">Sociaera</span>
                            <div style="display:flex; gap:6px;">
                                <span class="material-symbols-outlined" style="font-size:14px; color:rgba(255,255,255,0.4);">search</span>
                                <span class="material-symbols-outlined" style="font-size:14px; color:#F06D1F;">notifications</span>
                            </div>
                        </div>

                        <!-- Map Area -->
                        <div style="flex:1; position:relative; margin:12px 0; border-radius:18px; background:#141a26; border:1px solid rgba(255,255,255,0.04); overflow:hidden; min-height:300px;">
                            <!-- Grid Pattern -->
                            <div style="position:absolute; inset:0; opacity:0.12; background-image:radial-gradient(rgba(56,189,248,0.8) 1px, transparent 1px); background-size:18px 18px;"></div>

                            <!-- Pins -->
                            <div class="pin-float" style="position:absolute; top:30px; left:28px; color:#F06D1F; filter:drop-shadow(0 0 6px rgba(240,109,31,0.8));">
                                <span class="material-symbols-outlined" style="font-size:22px; font-variation-settings:'FILL' 1;">location_on</span>
                            </div>
                            <div class="pin-float-delay" style="position:absolute; top:70px; right:30px; color:#F06D1F; filter:drop-shadow(0 0 6px rgba(240,109,31,0.8));">
                                <span class="material-symbols-outlined" style="font-size:18px; font-variation-settings:'FILL' 1;">location_on</span>
                            </div>
                            <div class="pin-float-delay2" style="position:absolute; bottom:80px; left:45px; color:#F06D1F; filter:drop-shadow(0 0 6px rgba(240,109,31,0.8));">
                                <span class="material-symbols-outlined" style="font-size:20px; font-variation-settings:'FILL' 1;">location_on</span>
                            </div>

                            <!-- Central Pulse -->
                            <div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); display:flex; align-items:center; justify-content:center;">
                                <div style="position:absolute; width:50px; height:50px; border-radius:50%; background:rgba(240,109,31,0.15); animation:pulse-glow 2s ease-in-out infinite;"></div>
                                <div style="width:32px; height:32px; border-radius:50%; background:#F06D1F; border:2px solid #fff; display:flex; align-items:center; justify-content:center; box-shadow:0 0 15px rgba(240,109,31,0.8); position:relative; z-index:2;">
                                    <span class="material-symbols-outlined" style="font-size:14px; color:#fff;">visibility</span>
                                </div>
                            </div>

                            <!-- Bottom Venue Card -->
                            <div style="position:absolute; bottom:8px; left:8px; right:8px; background:rgba(22,30,48,0.92); backdrop-filter:blur(8px); border-radius:12px; padding:8px 10px; border:1px solid rgba(255,255,255,0.08);">
                                <div style="display:flex; align-items:center; justify-content:space-between;">
                                    <span style="font-weight:700; font-size:11px; color:#fff;">Sociaera Venue</span>
                                    <span style="color:#fbbf24; font-weight:700; font-size:10px;">★ 5.0</span>
                                </div>
                                <div style="font-size:9px; color:rgba(255,255,255,0.4); margin-top:2px;">68 Check-in • Del Perro Blvd</div>
                            </div>
                        </div>

                        <!-- Bottom Nav -->
                        <div style="display:flex; align-items:center; justify-content:space-around; padding-top:8px; border-top:1px solid rgba(255,255,255,0.06);">
                            <span class="material-symbols-outlined" style="font-size:18px; color:#F06D1F; font-variation-settings:'FILL' 1;">home</span>
                            <span class="material-symbols-outlined" style="font-size:18px; color:rgba(255,255,255,0.3);">map</span>
                            <div style="width:28px; height:28px; border-radius:50%; background:#F06D1F; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(240,109,31,0.5);">
                                <span class="material-symbols-outlined" style="font-size:14px; color:#fff;">add</span>
                            </div>
                            <span class="material-symbols-outlined" style="font-size:18px; color:rgba(255,255,255,0.3);">bookmark</span>
                            <span class="material-symbols-outlined" style="font-size:18px; color:rgba(255,255,255,0.3);">person</span>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        <!-- ── FEATURE PILL BAR ── -->
        <div class="pill-bar" style="border-radius:9999px; padding:16px 28px; display:flex; align-items:center; justify-content:center; gap:8px; max-width:860px; margin:0 auto 50px;">

            <div class="pill-features" style="display:flex; align-items:center; justify-content:center; gap:24px; flex:1;">

                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:rgba(240,109,31,0.15); border:1px solid rgba(240,109,31,0.3); display:flex; align-items:center; justify-content:center; box-shadow:0 0 10px rgba(240,109,31,0.25);">
                        <span class="material-symbols-outlined" style="font-size:18px; color:#F06D1F; font-variation-settings:'FILL' 1;">location_on</span>
                    </div>
                    <span style="font-size:13px; font-weight:700; color:#fff;">Şehir Haritasında<br>Yerinizi Alın</span>
                </div>

                <div class="pill-divider" style="width:1px; height:28px; background:rgba(255,255,255,0.08);"></div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:rgba(240,109,31,0.15); border:1px solid rgba(240,109,31,0.3); display:flex; align-items:center; justify-content:center; box-shadow:0 0 10px rgba(240,109,31,0.25);">
                        <span class="material-symbols-outlined" style="font-size:18px; color:#F06D1F; font-variation-settings:'FILL' 1;">person</span>
                    </div>
                    <span style="font-size:13px; font-weight:700; color:#fff;">Müşteri Check-in'leri<br>& Paylaşımlar</span>
                </div>

                <div class="pill-divider" style="width:1px; height:28px; background:rgba(255,255,255,0.08);"></div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:50%; background:rgba(240,109,31,0.15); border:1px solid rgba(240,109,31,0.3); display:flex; align-items:center; justify-content:center; box-shadow:0 0 10px rgba(240,109,31,0.25);">
                        <span class="material-symbols-outlined" style="font-size:18px; color:#F06D1F; font-variation-settings:'FILL' 1;">local_offer</span>
                    </div>
                    <span style="font-size:13px; font-weight:700; color:#fff;">Özel Kampanyalar<br>Tanımlayın</span>
                </div>

            </div>

        </div>

        <!-- ── REGISTRATION FORM ── -->
        <div id="register-form" style="display:flex; flex-direction:column; gap:24px;">

                <h2 style="font-size:24px; font-weight:800; letter-spacing:-0.5px;">
                    İşletme Kayıt Formu
                </h2>

                <?php if ($success): ?>
                    <div class="glass-form" style="border-radius:20px; padding:32px; text-align:center;">
                        <div style="width:56px; height:56px; background:rgba(16,185,129,0.15); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
                            <span class="material-symbols-outlined" style="font-size:28px; color:#10b981; font-variation-settings:'FILL' 1;">check_circle</span>
                        </div>
                        <h3 style="font-size:20px; font-weight:700; color:#6ee7b7; margin-bottom:8px;">Başvurunuz Alındı! 🎉</h3>
                        <p style="color:rgba(255,255,255,0.5); font-size:14px; max-width:460px; margin:0 auto 20px;">
                            Mekanınız incelenmek üzere admin paneline gönderildi. Onaylandığında kullanıcılar mekanınızda check-in yapabilecek!
                        </p>
                        <div style="display:flex; justify-content:center; gap:12px;">
                            <a href="<?php echo BASE_URL; ?>/business" class="btn-orange" style="padding:12px 24px; font-size:14px; border-radius:12px;">
                                Yeni Mekan Ekle
                            </a>
                            <a href="<?php echo BASE_URL; ?>/" style="display:inline-flex; align-items:center; gap:6px; padding:12px 24px; background:rgba(255,255,255,0.08); border-radius:12px; font-weight:600; font-size:14px; color:#fff;">
                                Ana Sayfaya Dön
                            </a>
                        </div>
                    </div>
                <?php else: ?>

                    <?php if ($error): ?>
                        <div style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.3); border-radius:12px; padding:14px 18px; display:flex; align-items:center; gap:10px; font-size:14px; color:#f87171;">
                            <span class="material-symbols-outlined" style="font-size:18px;">error</span>
                            <?php echo escape($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:24px;">
                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

                        <!-- ── Temel Bilgiler ── -->
                        <div class="glass-form" style="border-radius:20px; padding:28px;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:22px;">
                                <div style="width:28px; height:28px; border-radius:50%; background:#F06D1F; display:flex; align-items:center; justify-content:center;">
                                    <span class="material-symbols-outlined" style="font-size:16px; color:#fff;">info</span>
                                </div>
                                <span style="font-size:16px; font-weight:800;">Temel Bilgiler</span>
                            </div>

                            <!-- Mekan Adı + Kategori yan yana -->
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                <div>
                                    <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Mekan Adı <span style="color:#F06D1F;">*</span></label>
                                    <div style="position:relative;">
                                        <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:rgba(255,255,255,0.25);">storefront</span>
                                        <input type="text" name="name" required placeholder="Örn: Bean Machine Coffee" class="form-input" style="padding-left:40px;">
                                    </div>
                                </div>
                                <div>
                                    <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Kategori <span style="color:#F06D1F;">*</span></label>
                                    <div style="position:relative;">
                                        <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:rgba(255,255,255,0.25);">category</span>
                                        <select name="category" required class="form-input" style="padding-left:40px;">
                                            <option value="">Bir kategori seçin</option>
                                            <?php foreach ($categories as $key => $label): ?>
                                                <option value="<?php echo escape($key); ?>"><?php echo escape($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Açıklama -->
                            <div style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Açıklama (Opsiyonel)</label>
                                <textarea name="description" rows="3" placeholder="Mekan hakkında kısa bir bilgi..." class="form-input" style="resize:vertical;"></textarea>
                            </div>

                            <!-- Mekan Fotoğrafı -->
                            <div>
                                <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Mekan Fotoğrafı</label>
                                <label for="cover_image_input" style="display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; padding:28px 20px; border:2px dashed rgba(255,255,255,0.12); border-radius:16px; background:rgba(255,255,255,0.02); cursor:pointer; transition:all 0.2s;" onmouseover="this.style.borderColor='rgba(240,109,31,0.5)'; this.style.background='rgba(240,109,31,0.03)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.12)'; this.style.background='rgba(255,255,255,0.02)'">
                                    <span class="material-symbols-outlined" style="font-size:36px; color:#F06D1F; opacity:0.7;">add_photo_alternate</span>
                                    <span style="font-size:13px; color:rgba(255,255,255,0.5);">Fotoğraf yüklemek için tıklayın veya sürükleyin</span>
                                    <span style="font-size:11px; color:rgba(255,255,255,0.25);">PNG, JPG veya WEBP (Maks 5MB)</span>
                                    <input type="file" name="cover_image" id="cover_image_input" accept="image/*" style="display:none;">
                                </label>
                                <div id="file-name-display" style="font-size:12px; color:#F06D1F; margin-top:6px; display:none;"></div>
                            </div>
                        </div>

                        <!-- ── Konum & İletişim ── -->
                        <div class="glass-form" style="border-radius:20px; padding:28px;">
                            <div style="display:flex; align-items:center; gap:10px; margin-bottom:22px;">
                                <div style="width:28px; height:28px; border-radius:50%; background:#F06D1F; display:flex; align-items:center; justify-content:center;">
                                    <span class="material-symbols-outlined" style="font-size:16px; color:#fff; font-variation-settings:'FILL' 1;">location_on</span>
                                </div>
                                <span style="font-size:16px; font-weight:800;">Konum & İletişim</span>
                            </div>

                            <!-- Adres -->
                            <div style="margin-bottom:16px;">
                                <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Adres</label>
                                <div style="position:relative;">
                                    <span class="material-symbols-outlined" style="position:absolute; left:12px; top:14px; font-size:18px; color:rgba(255,255,255,0.25);">location_on</span>
                                    <input type="text" name="address" placeholder="Açık adres veya bölge" class="form-input" style="padding-left:40px;">
                                </div>
                            </div>

                            <!-- Facebrowser URL + Website yan yana -->
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                                <div>
                                    <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Facebrowser URL</label>
                                    <div style="position:relative;">
                                        <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:rgba(255,255,255,0.25);">language</span>
                                        <input type="url" name="facebrowser_url" placeholder="https://face.gta.world/pages/..." class="form-input" style="padding-left:40px;">
                                    </div>
                                </div>
                                <div>
                                    <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Website</label>
                                    <div style="position:relative;">
                                        <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:rgba(255,255,255,0.25);">public</span>
                                        <input type="url" name="website" placeholder="https://..." class="form-input" style="padding-left:40px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Telefon -->
                            <div>
                                <label style="display:block; font-size:12px; font-weight:600; color:rgba(255,255,255,0.6); margin-bottom:6px;">Telefon (Opsiyonel)</label>
                                <div style="position:relative;">
                                    <span class="material-symbols-outlined" style="position:absolute; left:12px; top:50%; transform:translateY(-50%); font-size:18px; color:rgba(255,255,255,0.25);">call</span>
                                    <input type="text" name="phone" placeholder="Örn: 555-0199" class="form-input" style="padding-left:40px;">
                                </div>
                            </div>
                        </div>

                        <!-- ── Footer Buttons ── -->
                        <div style="display:flex; align-items:center; justify-content:space-between; padding-top:8px;">
                            <a href="<?php echo BASE_URL; ?>/" style="font-size:14px; font-weight:600; color:rgba(255,255,255,0.4); transition:color 0.2s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'">İptal</a>
                            <button type="submit" class="btn-orange" style="padding:14px 32px; font-size:15px; border-radius:14px;">
                                <span class="material-symbols-outlined" style="font-size:20px;">send</span>
                                Mekanı Gönder
                            </button>
                        </div>

                    </form>

                <?php endif; ?>

            </div>
        </div>

        <!-- ── FOOTER ── -->
        <div style="text-align:center; padding:40px 0 20px; color:rgba(255,255,255,0.2); font-size:12px;">
            <a href="<?php echo BASE_URL; ?>" style="color:#F06D1F; font-weight:700;">Sociaera</a> &copy; <?php echo date('Y'); ?> — Tüm Hakları Saklıdır.
        </div>

    </div>

</div>

<script>
// Show selected file name
document.getElementById('cover_image_input').addEventListener('change', function() {
    var display = document.getElementById('file-name-display');
    if (this.files && this.files[0]) {
        display.textContent = '📎 ' + this.files[0].name;
        display.style.display = 'block';
    } else {
        display.style.display = 'none';
    }
});
</script>

</body>
</html>
