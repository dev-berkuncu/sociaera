<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Tüm alanları doldurun.';
    } elseif ($password !== $confirm) {
        $error = 'Şifreler eşleşmiyor.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = 'Kullanıcı adı 3-50 karakter, harf/rakam/alt çizgi olmalıdır.';
    } else {
        $userModel = new UserModel();
        $result = $userModel->register($username, $email, $password);

        if ($result['ok']) {
            $user = $userModel->getById($result['user_id']);
            Auth::login($user);
            Auth::setFlash('success', 'Hoş geldin! Hesabın başarıyla oluşturuldu. 🎉');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = 'Kayıt Ol';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon"><i class="bi bi-person-plus-fill"></i></div>
            <h1><?php echo APP_NAME; ?>'ya Katıl</h1>
            <p>Hemen kayıt ol ve topluluğa katıl</p>
        </div>

        <?php if ($error): ?>
            <div class="flash-message flash-error" style="position:static; transform:none; margin-bottom:20px; width:100%;">
                <div class="flash-content"><i class="bi bi-exclamation-circle-fill"></i><span><?php echo escape($error); ?></span></div>
            </div>
        <?php endif; ?>

        <a href="<?php echo BASE_URL; ?>/oauth-login" class="btn-oauth-gta" style="margin-bottom:16px;">
            <i class="bi bi-controller"></i> GTA World ile Kayıt Ol
        </a>

        <div class="auth-divider"><span>veya e-posta ile</span></div>

        <form method="POST" action="">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="username">Kullanıcı Adı</label>
                <input type="text" id="username" name="username" class="form-control-styled" placeholder="Kullanıcı adı" value="<?php echo escape($_POST['username'] ?? ''); ?>" required pattern="[a-zA-Z0-9_]{3,50}">
                <div class="form-hint">3-50 karakter, harf, rakam ve alt çizgi</div>
            </div>
            <div class="form-group">
                <label for="email">E-posta</label>
                <input type="email" id="email" name="email" class="form-control-styled" placeholder="E-posta adresiniz" value="<?php echo escape($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Şifre</label>
                <input type="password" id="password" name="password" class="form-control-styled" placeholder="En az 6 karakter" required minlength="6">
            </div>
            <div class="form-group">
                <label for="password_confirm">Şifre Tekrar</label>
                <input type="password" id="password_confirm" name="password_confirm" class="form-control-styled" placeholder="Şifrenizi tekrar girin" required>
            </div>
            <button type="submit" class="btn-primary-orange btn-full btn-lg">
                <i class="bi bi-person-plus"></i> Kayıt Ol
            </button>
        </form>

        <div class="auth-footer">
            Zaten hesabın var mı? <a href="<?php echo BASE_URL; ?>/login">Giriş Yap</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
