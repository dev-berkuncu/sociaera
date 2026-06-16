<?php
/**
 * Sociaera — Sponsorlu Reklamlar Sayfası
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Wallet.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Services/ImageUploader.php';

Auth::requireLogin();

$userId = Auth::id();
$walletModel = new WalletModel();
$adModel = new AdModel();
$userModel = new UserModel();

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$adPrice = 10000.00;
$userBalance = $walletModel->getBalance($userId);

// POST İstekleri (Reklam Oluşturma veya Silme)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');

        // Validasyonlar
        if (empty($title)) {
            Auth::setFlash('error', 'Reklam başlığı/marka adı boş bırakılamaz.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        if (strlen($title) < 3 || strlen($title) > 100) {
            Auth::setFlash('error', 'Reklam başlığı en az 3, en fazla 100 karakter olmalıdır.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        if (!empty($linkUrl) && !filter_var($linkUrl, FILTER_VALIDATE_URL)) {
            Auth::setFlash('error', 'Geçersiz yönlendirme adresi/URL formatı.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            Auth::setFlash('error', 'Lütfen reklam görseli yükleyin.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        // Bakiye kontrolü
        if ($userBalance < $adPrice) {
            Auth::setFlash('error', 'Cüzdanınızda yeterli bakiye bulunmuyor. Lütfen bakiye yükleyin.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        // Dosya yükleme
        $uploader = new ImageUploader();
        $uploadResult = $uploader->upload($_FILES['image'], 'ads', [
            'outputFormat' => 'webp',
            'maxSize' => 5 * 1024 * 1024,
            'maxWidth' => 1200,
            'maxHeight' => 800,
            'quality' => 85
        ]);

        if (!$uploadResult['success']) {
            Auth::setFlash('error', 'Görsel yüklenemedi: ' . ($uploadResult['error'] ?? 'Bilinmeyen hata'));
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        $imagePath = $uploadResult['path'];

        // Ödeme işlemi
        $payDescription = "Feed Sponsorlu Reklamı Satın Alındı: " . $title;
        if (!$walletModel->pay($userId, $adPrice, $payDescription)) {
            // Yüklenen resmi temizle
            $uploader->delete('ads', $uploadResult['filename']);
            Auth::setFlash('error', 'Ödeme işlemi gerçekleştirilemedi. Cüzdan bakiyenizi kontrol edin.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        // Reklamı veritabanında oluştur
        try {
            $adModel->createSponsored($title, $imagePath, empty($linkUrl) ? null : $linkUrl, $userId);
            Auth::setFlash('success', 'Reklamınız başarıyla oluşturuldu ve yayına alındı! 🎉');
        } catch (Exception $e) {
            Auth::setFlash('error', 'Reklam oluşturulurken bir veritabanı hatası oluştu.');
        }

        header('Location: ' . BASE_URL . '/sponsors.php');
        exit;
    } elseif ($action === 'delete') {
        $adId = (int)($_POST['ad_id'] ?? 0);
        $ad = $adModel->getById($adId);

        if (!$ad || (int)$ad['user_id'] !== $userId) {
            Auth::setFlash('error', 'Bu reklamı silme yetkiniz bulunmuyor.');
            header('Location: ' . BASE_URL . '/sponsors.php');
            exit;
        }

        try {
            // Görseli sunucudan sil
            if (!empty($ad['image_url'])) {
                $filename = basename($ad['image_url']);
                $uploader = new ImageUploader();
                $uploader->delete('ads', $filename);
            }
            
            $adModel->delete($adId);
            Auth::setFlash('success', 'Reklamınız başarıyla silindi.');
        } catch (Exception $e) {
            Auth::setFlash('error', 'Reklam silinirken bir hata oluştu.');
        }

        header('Location: ' . BASE_URL . '/sponsors.php');
        exit;
    }
}

// Kullanıcının kendi reklamlarını getir
$userAds = $adModel->getByUserId($userId);

$pageTitle = 'Sponsorlu Reklamlar';
$activeNav = 'sponsors';
require_once __DIR__ . '/partials/app_header.php';
?>

<section style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:768px; width:100%; padding-bottom:40px;">

    <!-- Flash Mesajları -->
    <?php require_once __DIR__ . '/partials/flash.php'; ?>

    <!-- Page Header -->
    <div style="display:flex; align-items:center; gap:16px; margin-bottom:4px;">
        <div style="width:48px; height:48px; background:linear-gradient(135deg, var(--color-primary), #ff9e7d); border-radius:14px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 8px 20px -5px rgba(240,109,31,0.35); transform:rotate(-3deg);">
            <span class="material-symbols-outlined" style="font-size:28px; color:#fff;">campaign</span>
        </div>
        <div>
            <h1 style="font-size:1.8rem; font-weight:900; color:var(--text-1); letter-spacing:-.02em; margin:0 0 4px;">Sponsorlu Reklamlar</h1>
            <p style="color:var(--text-3); font-size:13px; margin:0;">Feed akışında markanızın reklamını yapın ve binlerce oyuncuya ulaşın.</p>
        </div>
    </div>

    <!-- Info & Wallet Card Row -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:16px;">
        <!-- Wallet Card -->
        <div style="background:#fff; border:1.5px solid var(--border); border-radius:16px; padding:20px; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div>
                <div style="font-size:11px; font-weight:700; color:var(--text-3); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Mevcut Bakiyeniz</div>
                <div style="font-size:2rem; font-weight:900; color:var(--text-1); line-height:1;">
                    $<?php echo number_format($userBalance, 0, ',', '.'); ?>
                </div>
            </div>
            <div style="margin-top:16px;">
                <?php if ($userBalance < $adPrice): ?>
                    <div style="display:flex; align-items:center; gap:6px; background:#FEF2F2; border:1px solid #FCA5A5; border-radius:10px; padding:8px 12px; font-size:12px; color:var(--color-danger); margin-bottom:12px; font-weight:600;">
                        <span class="material-symbols-outlined" style="font-size:16px;">warning</span>
                        Bakiye yetersiz ($<?php echo number_format($adPrice, 0, ',', '.'); ?> gerekli)
                    </div>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/wallet.php" style="display:inline-flex; align-items:center; gap:8px; background:var(--color-primary); color:#fff; padding:10px 18px; border-radius:10px; font-weight:700; font-size:13px; text-decoration:none; transition:opacity .15s; width:100%; justify-content:center; box-shadow:0 4px 12px rgba(240,109,31,0.2);"
                   onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                    <span class="material-symbols-outlined" style="font-size:18px;">account_balance_wallet</span>
                    Bakiye Yükle / Cüzdanım
                </a>
            </div>
        </div>
        
        <!-- Pricing Info Card -->
        <div style="background:linear-gradient(135deg, #FFF3EB, #fff); border:1.5px solid rgba(240,109,31,0.2); border-radius:16px; padding:20px; display:flex; flex-direction:column; justify-content:space-between; box-shadow:0 4px 20px rgba(240,109,31,0.05);">
            <div>
                <div style="font-size:11px; font-weight:700; color:var(--color-primary); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Reklam Tarifesi</div>
                <div style="font-size:2rem; font-weight:900; color:var(--text-1); line-height:1; display:flex; align-items:baseline; gap:4px;">
                    $<?php echo number_format($adPrice, 0, ',', '.'); ?>
                    <span style="font-size:12px; font-weight:600; color:var(--text-3);">/ 1 Haftalık</span>
                </div>
            </div>
            <div style="margin-top:16px; font-size:12px; color:var(--text-2); line-height:1.5; display:flex; flex-direction:column; gap:6px;">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:16px; color:var(--color-success);">check_circle</span>
                    Akış içerisinde (feed) gösterim
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:16px; color:var(--color-success);">check_circle</span>
                    1 hafta boyunca gösterilir
                </div>
            </div>
        </div>
    </div>

    <!-- Ad Creation Form -->
    <div style="background:#fff; border:1.5px solid var(--border); border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.04);">
        <h2 style="font-size:1.2rem; font-weight:800; color:var(--text-1); margin:0 0 16px; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--color-primary);">add_photo_alternate</span>
            Yeni Reklam Kampanyası Başlat
        </h2>
        
        <form action="<?php echo BASE_URL; ?>/sponsors.php" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:16px;">
            <input type="hidden" name="csrf_token" value="<?php echo escape(Csrf::token()); ?>">
            <input type="hidden" name="action" value="create">
            
            <div>
                <label for="title" style="display:block; font-size:13px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Reklam Başlığı / Marka Adı <span style="color:var(--color-danger);">*</span></label>
                <input type="text" name="title" id="title" required maxlength="100" placeholder="Örn: Pillbox Grand Casino — VIP Geceniz Sizi Bekliyor"
                       style="width:100%; border:1.5px solid var(--border); border-radius:10px; padding:10px 14px; font-size:13px; font-family:var(--font); outline:none; background:var(--bg-section); color:var(--text-1); transition:border-color .15s;"
                       onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            
            <div>
                <label for="link_url" style="display:block; font-size:13px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Yönlendirme Adresi (URL) <span style="font-size:11px; font-weight:500; color:var(--text-3);">(Opsiyonel)</span></label>
                <input type="url" name="link_url" id="link_url" placeholder="Örn: https://face.gta.world/pages/pillbox-casino"
                       style="width:100%; border:1.5px solid var(--border); border-radius:10px; padding:10px 14px; font-size:13px; font-family:var(--font); outline:none; background:var(--bg-section); color:var(--text-1); transition:border-color .15s;"
                       onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            
            <div>
                <label style="display:block; font-size:13px; font-weight:700; color:var(--text-2); margin-bottom:6px;">Reklam Görseli (Banner) <span style="color:var(--color-danger);">*</span></label>
                <div style="border:1.5px dashed var(--border); border-radius:10px; padding:20px; text-align:center; background:var(--bg-section); cursor:pointer; position:relative; transition:border-color .15s;"
                     onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='var(--border)'"
                     onclick="document.getElementById('image').click();">
                    <span class="material-symbols-outlined" style="font-size:36px; color:var(--text-3); margin-bottom:8px; display:block;">upload_file</span>
                    <span style="font-size:13px; font-weight:600; color:var(--text-2); display:block; margin-bottom:4px;">Görsel yüklemek için tıklayın</span>
                    <span style="font-size:11px; color:var(--text-3); display:block;">Önerilen boyut: 600x300. Maksimum: 5MB (JPEG, PNG, WebP)</span>
                    <input type="file" name="image" id="image" required accept="image/jpeg,image/png,image/webp" style="display:none;"
                           onchange="document.getElementById('fileNameSpan').innerText = this.files[0] ? this.files[0].name : '';">
                </div>
                <span id="fileNameSpan" style="font-size:12px; font-weight:700; color:var(--color-primary); margin-top:8px; display:block; text-align:center;"></span>
            </div>
            
            <button type="submit" <?php echo ($userBalance < $adPrice) ? 'disabled' : ''; ?>
                    style="width:100%; border:none; background:<?php echo ($userBalance < $adPrice) ? 'var(--text-3)' : 'var(--color-primary)'; ?>; color:#fff; padding:12px 24px; border-radius:12px; font-weight:700; font-size:14px; cursor:<?php echo ($userBalance < $adPrice) ? 'not-allowed' : 'pointer'; ?>; transition:opacity .15s; display:flex; align-items:center; justify-content:center; gap:8px; box-shadow:<?php echo ($userBalance < $adPrice) ? 'none' : '0 4px 16px rgba(240,109,31,0.25)'; ?>;"
                    <?php if ($userBalance >= $adPrice): ?>onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'"<?php endif; ?>>
                <span class="material-symbols-outlined" style="font-size:20px;">send</span>
                Ödeme Yap ve Reklamı Yayınla ($<?php echo number_format($adPrice, 0, ',', '.'); ?>)
            </button>
        </form>
    </div>

    <!-- Active/Previous Ads List -->
    <div style="background:#fff; border:1.5px solid var(--border); border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,.04);">
        <h2 style="font-size:1.2rem; font-weight:800; color:var(--text-1); margin:0 0 16px; display:flex; align-items:center; gap:8px;">
            <span class="material-symbols-outlined" style="color:var(--color-primary);">list_alt</span>
            Aktif Reklamlarım
        </h2>
        
        <?php if (empty($userAds)): ?>
            <div style="text-align:center; padding:32px 16px; background:var(--bg-section); border-radius:12px; border:1px solid var(--border);">
                <span class="material-symbols-outlined" style="font-size:40px; color:var(--text-3); margin-bottom:8px; display:block;">campaign</span>
                <p style="margin:0; font-size:13px; color:var(--text-2); font-weight:600;">Henüz oluşturduğunuz bir sponsorlu reklam bulunmuyor.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column; gap:12px;">
                <?php foreach ($userAds as $ad): ?>
                    <div style="display:flex; align-items:center; gap:16px; background:var(--bg-section); border:1px solid var(--border); border-radius:12px; padding:12px; position:relative; overflow:hidden;">
                        <!-- Image Thumbnail -->
                        <div style="width:64px; height:64px; border-radius:8px; overflow:hidden; flex-shrink:0; background:#fff; border:1px solid var(--border);">
                            <img src="<?php echo BASE_URL . '/' . escape($ad['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
                        </div>
                        
                        <!-- Details -->
                        <div style="flex:1; min-width:0;">
                           <div style="font-size:13px; font-weight:700; color:var(--text-1); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-bottom:4px;">
                               <?php echo escape($ad['title']); ?>
                           </div>
                           <div style="font-size:11px; color:var(--text-3); display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                               <?php if (!empty($ad['link_url'])): ?>
                                   <a href="<?php echo escape($ad['link_url']); ?>" target="_blank" rel="noopener" style="color:var(--color-primary); text-decoration:none; font-weight:600; display:flex; align-items:center; gap:2px;">
                                       <span class="material-symbols-outlined" style="font-size:14px;">link</span>
                                       URL Adresi
                                   </a>
                               <?php endif; ?>
                               <span style="display:flex; align-items:center; gap:2px;">
                                   <span class="material-symbols-outlined" style="font-size:14px;">calendar_month</span>
                                   <?php echo date('d.m.Y H:i', strtotime($ad['created_at'])); ?>
                               </span>
                           </div>
                        </div>
                        
                        <!-- Actions & Status -->
                        <div style="display:flex; align-items:center; gap:12px;">
                            <?php 
                                $isExpired = !empty($ad['expires_at']) && strtotime($ad['expires_at']) < time();
                            ?>
                            <?php if ($isExpired): ?>
                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:var(--color-danger); background:rgba(239,68,68,0.08); padding:4px 8px; border-radius:6px; border:1px solid rgba(239,68,68,0.2);">
                                    Süresi Doldu
                                </span>
                            <?php else: ?>
                                <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; color:var(--color-success); background:rgba(22,163,74,0.08); padding:4px 8px; border-radius:6px; border:1px solid rgba(22,163,74,0.2);">
                                    Yayında
                                </span>
                            <?php endif; ?>
                            
                            <form action="<?php echo BASE_URL; ?>/sponsors.php" method="POST" onsubmit="return confirm('Bu reklamı silmek istediğinize emin misiniz?');" style="margin:0;">
                                <input type="hidden" name="csrf_token" value="<?php echo escape(Csrf::token()); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="ad_id" value="<?php echo (int)$ad['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:var(--color-danger); cursor:pointer; padding:6px; display:flex; align-items:center; justify-content:center; border-radius:6px; transition:background .15s;"
                                        onmouseover="this.style.background='#FEF2F2'" onmouseout="this.style.background='none'">
                                    <span class="material-symbols-outlined" style="font-size:18px;">delete</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
