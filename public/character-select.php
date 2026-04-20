<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/View.php';
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
            $charName = $char['name'] ?? ($char['firstname'] . ' ' . $char['lastname']);
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
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="auth-page">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-header">
            <div class="auth-icon"><i class="bi bi-people-fill"></i></div>
            <h1>Karakter Seçimi</h1>
            <p>Hangi karakter ile devam etmek istiyorsun?</p>
        </div>

        <?php if (empty($characters)): ?>
            <div class="empty-state">
                <i class="bi bi-person-x"></i>
                <p>Hiç karakter bulunamadı.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>/login" class="btn-secondary-soft btn-full" style="margin-top:16px;">Giriş Sayfasına Dön</a>
        <?php else: ?>
            <form method="POST">
                <?php echo csrfField(); ?>
                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:24px;">
                    <?php foreach ($characters as $char):
                        $name = $char['name'] ?? (($char['firstname'] ?? '') . ' ' . ($char['lastname'] ?? ''));
                        $cid = $char['id'] ?? 0;
                    ?>
                    <label class="card-box" style="display:flex; align-items:center; gap:16px; padding:16px; cursor:pointer; transition: border-color 0.2s;">
                        <input type="radio" name="character_id" value="<?php echo $cid; ?>" required style="accent-color: var(--primary); width:18px; height:18px;">
                        <div>
                            <div style="font-weight:700; font-size:1rem;"><?php echo escape($name); ?></div>
                            <?php if (!empty($char['faction'])): ?>
                                <div style="font-size:0.82rem; color:var(--text-muted);"><?php echo escape($char['faction']); ?></div>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn-primary-orange btn-full btn-lg">
                    <i class="bi bi-check-lg"></i> Bu Karakteri Seç
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
