<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Models/User.php';

$isLoggedIn = Auth::check();
$currentUserData = null;
if ($isLoggedIn) {
    $currentUserData = (new UserModel())->getById(Auth::id());
}

$characters   = $_SESSION['oauth_characters'] ?? [];
$gtaUserId    = $_SESSION['oauth_gta_user_id'] ?? ($currentUserData ? $currentUserData['gta_user_id'] : null);
$gtaUsername   = $_SESSION['oauth_gta_username'] ?? ($currentUserData ? $currentUserData['gta_username'] : '');

// Eğer giriş yapmışsa ve OAuth akışında değilsek, diğer karakterlerini UCP id'sine göre veritabanından çek
$isSwitching = ($isLoggedIn && !isset($_SESSION['oauth_gta_user_id']));
if ($isSwitching && $gtaUserId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username as name, gta_character_id FROM users WHERE gta_user_id = ? AND id != ? AND is_active = 1");
        $stmt->execute([$gtaUserId, Auth::id()]);
        $dbChars = $stmt->fetchAll();
        $characters = [];
        foreach ($dbChars as $dc) {
            $characters[] = [
                'id' => $dc['id'],
                'name' => $dc['name']
            ];
        }
    } catch (Exception $e) {}
}

if (!$gtaUserId) {
    Auth::setFlash('error', 'Oturum bilgisi bulunamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isSwitching) {
    Csrf::requireValid();
    $charId = (int) ($_POST['character_id'] ?? 0);
    $charName = '';

    foreach ($characters as $char) {
        if ((int)($char['id'] ?? 0) === $charId) {
            $charName = $char['name'] ?? trim(($char['firstname'] ?? '') . ' ' . ($char['lastname'] ?? ''));
            break;
        }
    }

    if ($charId && $charName) {
        $userModel = new UserModel();

        // Karakter bazlı kullanıcı bul veya oluştur
        $result = $userModel->findOrCreateByCharacter($gtaUserId, $gtaUsername, $charId, $charName);

        if (!$result['ok']) {
            Auth::setFlash('error', 'Hesap oluşturulamadı. Lütfen tekrar deneyin.');
            header('Location: ' . BASE_URL . '/character-select');
            exit;
        }

        $user = $result['user'];

        Auth::login($user);
        Csrf::regenerate();
        unset($_SESSION['oauth_characters'], $_SESSION['oauth_gta_user_id'], $_SESSION['oauth_gta_username']);

        if ($result['is_new']) {
            Logger::info('New character account created', ['user_id' => $user['id'], 'char' => $charName, 'char_id' => $charId]);
            Auth::setFlash('success', 'Hoş geldin ' . $charName . '! 🎭 Yeni hesabın oluşturuldu.');
        } else {
            Logger::info('Character login', ['user_id' => $user['id'], 'char' => $charName]);
            Auth::setFlash('success', $charName . ' olarak giriş yaptın! 🎭');
        }

        $redirectUrl = !empty($user['bank_account']) ? '/dashboard' : '/settings';
        header('Location: ' . BASE_URL . $redirectUrl);
        exit;
    } elseif ($charId && !$charName) {
        Auth::setFlash('error', 'Seçilen karakter için isim bilgisi alınamadı. Lütfen tekrar deneyin.');
        header('Location: ' . BASE_URL . '/character-select');
        exit;
    }
}

$pageTitle = 'Karakter Seçimi';
$hideSidebar = true;
require_once __DIR__ . '/partials/app_header.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div style="min-width:0;">
<div class="flex-grow flex items-center justify-center w-full relative overflow-hidden" style="min-height:70vh;">
    <!-- Background Design -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-primary-container/20 rounded-full blur-[100px] opacity-50 mix-blend-screen"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-[#ff9e7d]/20 rounded-full blur-[80px] opacity-40 mix-blend-screen"></div>
    </div>
    
    <div class="w-full max-w-3xl relative z-10">
        <div class="bg-[#2a2a2b]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-8 md:p-12 shadow-[0_20px_40px_-15px_rgba(19,19,20,0.5)]" style="background:#fff; border-color:var(--border); box-shadow:0 8px 30px rgba(0,0,0,0.06);">
            <div class="text-center mb-10">
                <h1 class="text-3xl font-black mb-2" style="color:var(--text-1);"><?php echo $isSwitching ? 'Karakter Değiştir' : 'Karakter Seçimi'; ?></h1>
                <p class="text-sm" style="color:var(--text-3);"><?php echo $isSwitching ? 'Diğer karakterlerinizden birine hızlıca geçiş yapın.' : 'Hangi karakter ile devam etmek istiyorsun?'; ?></p>
            </div>

            <?php if (empty($characters)): ?>
                <div class="border rounded-xl p-8 text-center mb-6" style="background:var(--bg-section); border-color:var(--border);">
                    <span class="material-symbols-outlined text-[48px] mb-2 opacity-50" style="color:var(--text-3);">person_off</span>
                    <p style="color:var(--text-2); font-weight:600;"><?php echo $isSwitching ? 'Aynı UCP hesabına bağlı geçiş yapabileceğiniz başka aktif karakter bulunamadı.' : 'Hiç karakter bulunamadı.'; ?></p>
                </div>
                <?php if ($isSwitching): ?>
                    <a href="<?php echo BASE_URL; ?>/dashboard" class="block w-full text-center py-3 rounded-xl font-bold btn btn-ghost" style="text-decoration:none;">Ana Sayfaya Dön</a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login" class="block w-full text-center py-3 rounded-xl font-bold btn btn-ghost" style="text-decoration:none;">Giriş Sayfasına Dön</a>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" action="<?php echo $isSwitching ? BASE_URL . '/switch-character' : ''; ?>" class="flex flex-col gap-10">
                    <?php echo csrfField(); ?>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <?php foreach ($characters as $char):
                            $name = $char['name'];
                            $cid = $char['id'];
                        ?>
                        <label class="relative cursor-pointer group">
                            <input type="radio" name="<?php echo $isSwitching ? 'target_user_id' : 'character_id'; ?>" value="<?php echo $cid; ?>" required class="peer sr-only">
                            <div class="bg-background border rounded-xl p-8 flex flex-col items-center justify-center gap-1 hover:bg-white/5 hover:border-primary-container/30 peer-checked:border-primary-container peer-checked:bg-primary-container/5 transition-all" style="background:var(--bg-section); border-color:var(--border);">
                                <div class="w-16 h-16 rounded-full bg-primary-container/20 text-primary-container flex items-center justify-center mb-3 group-hover:scale-110 transition-transform peer-checked:bg-primary-container peer-checked:text-white shadow-[0_0_15px_rgba(255,145,0,0.1)]" style="background:var(--color-primary-bg); color:var(--color-primary);">
                                    <span class="material-symbols-outlined text-[32px] font-light">account_circle</span>
                                </div>
                                
                                <div class="font-bold text-xl text-center mb-1" style="color:var(--text-1);"><?php echo escape($name); ?></div>
                                <div class="text-sm text-center font-light tracking-wide" style="color:var(--text-3);">ID: <?php echo (int)$cid; ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="flex justify-center">
                        <button type="submit" class="btn btn-primary btn-lg flex items-center justify-center gap-3">
                            <span class="material-symbols-outlined text-[20px]">sync_alt</span> Seçili Karakterle Devam Et
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.2); }
</style>
</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
