<?php
/**
 * Sociaera — Giriş Sayfası
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/RateLimit.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Models/User.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $rateLimit = new RateLimit();
    $ip = RateLimit::getClientIp();

    if (!$rateLimit->attempt("login_{$ip}", 8, 600)) {
        $error = 'Çok fazla giriş denemesi. Lütfen biraz bekleyin.';
    } else {
        $usernameOrEmail = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            $error = 'Tüm alanları doldurun.';
        } else {
            $userModel = new UserModel();
            $result = $userModel->login($usernameOrEmail, $password);

            if ($result['ok']) {
                Auth::login($result['user']);
                Csrf::regenerate();
                $rateLimit->reset("login_{$ip}");
                Logger::info('User logged in', ['user_id' => $result['user']['id']]);
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}

$pageTitle = 'Giriş Yap';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon"><i class="bi bi-pin-map-fill"></i></div>
            <h1><?php echo APP_NAME; ?>'ya Hoş Geldin</h1>
            <p>Hesabına giriş yap ve keşfe başla</p>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="position:static; transform:none; margin-bottom:20px; width:100%;">
                <div class="flash-content"><i class="bi bi-exclamation-circle-fill"></i><span><?php echo escape($error); ?></span></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="username">Kullanıcı Adı veya E-posta</label>
                <input type="text" id="username" name="username" class="form-control-styled" placeholder="Kullanıcı adı veya e-posta" value="<?php echo escape($_POST['username'] ?? ''); ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control-styled" placeholder="Şifreniz" required>
            </div>
            <button type="submit" class="btn-primary-orange btn-full btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Giriş Yap
            </button>
        </form>

        <div class="auth-divider"><span>veya</span></div>

        <a href="<?php echo BASE_URL; ?>/oauth-login" class="btn-oauth-gta">
            <i class="bi bi-controller"></i>
            GTA World ile Giriş Yap
        </a>

        <div class="auth-footer">
            Hesabın yok mu? <a href="<?php echo BASE_URL; ?>/register">Kayıt Ol</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
