<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Models/User.php';

$characters = $_SESSION['oauth_characters'] ?? [];
$userId = $_SESSION['oauth_user_id'] ?? null;

if (!$userId) {
    Auth::setFlash('error', 'Oturum bilgisi bulunamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        $userModel->updateCharacter($userId, $charId, $charName);
        $user = $userModel->getById($userId);
        Auth::login($user);
        Csrf::regenerate();
        unset($_SESSION['oauth_characters'], $_SESSION['oauth_user_id']);
        Logger::info('Character selected', ['user_id' => $userId, 'char' => $charName]);
        Auth::setFlash('success', $charName . ' olarak giriş yaptın! 🎭');
        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }
}

$pageTitle = 'Karakter Seçimi';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-background">
    <!-- Background Design -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-primary-container/20 rounded-full blur-[100px] opacity-50 mix-blend-screen"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-[#ff9e7d]/20 rounded-full blur-[80px] opacity-40 mix-blend-screen"></div>
    </div>
    
    <div class="w-full max-w-lg relative z-10">
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-8 md:p-10 shadow-[0_20px_40px_-15px_rgba(15,23,42,0.5)]">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto bg-primary-container text-white rounded-2xl flex items-center justify-center mb-4 shadow-[0_10px_20px_-5px_rgba(255,107,53,0.4)] transform -rotate-6">
                    <span class="material-symbols-outlined text-[32px]">group</span>
                </div>
                <h1 class="text-3xl font-black text-on-surface mb-2">Karakter Seçimi</h1>
                <p class="text-slate-400 text-sm">Hangi karakter ile devam etmek istiyorsun?</p>
            </div>

            <?php if (empty($characters)): ?>
                <div class="bg-surface-container border border-white/10 rounded-xl p-8 text-center text-slate-400 mb-6">
                    <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">person_off</span>
                    <p>Hiç karakter bulunamadı.</p>
                </div>
                <a href="<?php echo BASE_URL; ?>/login" class="block w-full bg-white/10 hover:bg-white/20 text-white text-center py-3 rounded-xl font-bold transition-colors border border-white/10">Giriş Sayfasına Dön</a>
            <?php else: ?>
                <form method="POST" class="flex flex-col gap-6">
                    <?php echo csrfField(); ?>
                    
                    <div class="flex flex-col gap-3 max-h-[300px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php foreach ($characters as $char):
                            $name = $char['name'] ?? trim(($char['firstname'] ?? '') . ' ' . ($char['lastname'] ?? ''));
                            $cid = $char['id'] ?? 0;
                        ?>
                        <label class="flex items-center gap-4 bg-background hover:bg-white/5 border border-white/10 hover:border-primary-container/50 rounded-xl p-4 cursor-pointer transition-colors group">
                            <div class="relative flex items-center justify-center">
                                <input type="radio" name="character_id" value="<?php echo $cid; ?>" required class="peer appearance-none w-5 h-5 border-2 border-slate-500 rounded-full checked:border-primary-container transition-colors cursor-pointer">
                                <div class="absolute w-2.5 h-2.5 bg-primary-container rounded-full opacity-0 peer-checked:opacity-100 transition-opacity pointer-events-none"></div>
                            </div>
                            
                            <div>
                                <div class="font-bold text-lg text-on-surface group-hover:text-primary-container transition-colors"><?php echo escape($name); ?></div>
                                <div class="text-sm text-slate-500">ID: <?php echo $cid; ?></div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" class="w-full bg-primary-container text-white py-4 rounded-xl font-bold shadow-[0_0_15px_rgba(255,107,53,0.3)] hover:bg-primary-container/90 transition-all flex items-center justify-center gap-2 active:scale-95">
                        <span class="material-symbols-outlined">check_circle</span> Bu Karakteri Seç
                    </button>
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

<?php require_once __DIR__ . '/partials/footer.php'; ?>
