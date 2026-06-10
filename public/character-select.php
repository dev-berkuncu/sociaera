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
$gtaUsername  = $_SESSION['oauth_gta_username'] ?? ($currentUserData ? $currentUserData['gta_username'] : '');

// Eğer giriş yapmışsa ve OAuth akışında değilsek, DB'den diğer karakterleri çek
$isSwitching = ($isLoggedIn && !isset($_SESSION['oauth_gta_user_id']));
if ($isSwitching && $gtaUserId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username as name, gta_character_id FROM users WHERE gta_user_id = ? AND id != ? AND is_active = 1");
        $stmt->execute([$gtaUserId, Auth::id()]);
        $dbChars = $stmt->fetchAll();
        $characters = [];
        foreach ($dbChars as $dc) {
            $characters[] = ['id' => $dc['id'], 'name' => $dc['name']];
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
    $charId   = (int) ($_POST['character_id'] ?? 0);
    $charName = '';

    foreach ($characters as $char) {
        if ((int)($char['id'] ?? 0) === $charId) {
            $charName = $char['name'] ?? trim(($char['firstname'] ?? '') . ' ' . ($char['lastname'] ?? ''));
            break;
        }
    }

    if ($charId && $charName) {
        $userModel = new UserModel();
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

$pageTitle   = $isSwitching ? 'Karakter Değiştir' : 'Karakter Seçimi';
$hideSidebar = true;
require_once __DIR__ . '/partials/app_header.php';
?>

<!-- Page header -->
<div style="text-align:center;margin-bottom:28px;padding-top:16px;">
    <div style="display:inline-flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#F06D1F,#FFA633);margin-bottom:14px;box-shadow:0 8px 20px rgba(240,109,31,.25);">
        <span class="material-symbols-outlined" style="font-size:28px;color:#fff;font-variation-settings:'FILL' 1;">manage_accounts</span>
    </div>
    <h1 style="font-size:1.5rem;font-weight:800;color:var(--text-1);margin:0 0 6px;letter-spacing:-.4px;">
        <?php echo $isSwitching ? 'Karakter Değiştir' : 'Karakter Seçimi'; ?>
    </h1>
    <p style="font-size:13px;color:var(--text-3);margin:0;">
        <?php echo $isSwitching
            ? 'Aynı UCP hesabına bağlı diğer karakterlere hızlıca geçiş yapın.'
            : 'Hangi karakter ile devam etmek istiyorsunuz?'; ?>
    </p>
</div>

<!-- Card -->
<div style="background:#fff;border:1.5px solid var(--border);border-radius:20px;padding:28px 24px;box-shadow:0 4px 24px rgba(0,0,0,.07);">

    <?php if (empty($characters)): ?>
    <!-- Karakter bulunamadı -->
    <div style="text-align:center;padding:32px 16px;">
        <span class="material-symbols-outlined" style="font-size:52px;color:var(--text-3);opacity:.45;display:block;margin-bottom:12px;">person_off</span>
        <div style="font-size:15px;font-weight:700;color:var(--text-2);margin-bottom:8px;">
            <?php echo $isSwitching ? 'Başka karakter bulunamadı' : 'Hiç karakter bulunamadı'; ?>
        </div>
        <p style="font-size:13px;color:var(--text-3);margin:0 0 20px;">
            <?php echo $isSwitching
                ? 'Aynı UCP hesabına bağlı geçiş yapabileceğiniz aktif başka karakter yok.'
                : 'UCP hesabınıza bağlı kayıtlı karakter bulunamadı.'; ?>
        </p>
        <?php if ($isSwitching): ?>
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-ghost">← Ana Sayfaya Dön</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/login" class="btn btn-ghost">← Giriş Sayfasına Dön</a>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Karakter seçim formu -->
    <form method="POST" action="<?php echo $isSwitching ? BASE_URL . '/switch-character' : ''; ?>"
          style="display:flex;flex-direction:column;gap:24px;">
        <?php echo csrfField(); ?>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;">
            <?php foreach ($characters as $char):
                $name      = $char['name'];
                $cid       = (int)$char['id'];
                $radioName = $isSwitching ? 'target_user_id' : 'character_id';
            ?>
            <label class="char-opt" style="cursor:pointer;display:block;">
                <input type="radio" name="<?php echo $radioName; ?>" value="<?php echo $cid; ?>" required
                       style="display:none;">
                <div class="char-card"
                     style="background:var(--bg-section);border:2px solid var(--border);border-radius:16px;padding:24px 16px;display:flex;flex-direction:column;align-items:center;gap:8px;transition:all .15s;text-align:center;">
                    <div style="width:60px;height:60px;border-radius:50%;background:#FFF3EB;border:2px solid #F06D1F22;display:flex;align-items:center;justify-content:center;margin-bottom:4px;">
                        <span class="material-symbols-outlined" style="font-size:30px;color:var(--color-primary);font-variation-settings:'FILL' 1;">account_circle</span>
                    </div>
                    <div style="font-size:15px;font-weight:800;color:var(--text-1);line-height:1.2;"><?php echo escape($name); ?></div>
                    <div style="font-size:11px;font-weight:600;color:var(--text-3);">ID: <?php echo $cid; ?></div>
                    <div class="char-check" style="display:none;margin-top:4px;">
                        <span style="background:var(--color-primary);color:#fff;border-radius:99px;padding:2px 10px;font-size:11px;font-weight:700;">✓ Seçildi</span>
                    </div>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <div style="display:flex;justify-content:center;gap:12px;flex-wrap:wrap;">
            <?php if ($isSwitching): ?>
            <a href="<?php echo BASE_URL; ?>/dashboard" class="btn btn-ghost">İptal</a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary btn-lg" style="gap:10px;padding:12px 32px;">
                <span class="material-symbols-outlined" style="font-size:20px;font-variation-settings:'FILL' 1;">sync_alt</span>
                Seçili Karakterle Devam Et
            </button>
        </div>
    </form>
    <?php endif; ?>

</div>

<style>
.char-opt input[type="radio"]:checked ~ .char-card {
    border-color: var(--color-primary) !important;
    background: #FFF3EB !important;
    box-shadow: 0 0 0 4px rgba(240,109,31,.12);
}
.char-card:hover { border-color: rgba(240,109,31,.5) !important; background: #FFFAF7 !important; }
.char-opt input[type="radio"]:checked ~ .char-card .char-check { display:block; }
</style>
<script>
// Immediate visual feedback on selection
document.querySelectorAll('.char-opt input[type="radio"]').forEach(function(r) {
    r.addEventListener('change', function() {
        document.querySelectorAll('.char-card').forEach(function(c) {
            c.style.borderColor = '';
            c.style.background = '';
            c.style.boxShadow = '';
        });
        document.querySelectorAll('.char-check').forEach(function(c) { c.style.display = 'none'; });
        var card = this.parentElement.querySelector('.char-card');
        if (card) {
            card.style.borderColor = 'var(--color-primary)';
            card.style.background = '#FFF3EB';
            card.style.boxShadow = '0 0 0 4px rgba(240,109,31,.12)';
            var chk = card.querySelector('.char-check');
            if (chk) chk.style.display = 'block';
        }
    });
});
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
