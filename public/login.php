<?php
/**
 * Sociaera — Giriş Sayfası (Sadece GTA World OAuth)
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

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
            <p>GTA World hesabınla giriş yap ve keşfe başla</p>
        </div>

        <a href="<?php echo BASE_URL; ?>/oauth-login" class="btn-oauth-gta">
            <i class="bi bi-controller"></i>
            GTA World ile Giriş Yap
        </a>

        <p style="text-align:center; margin-top:20px; font-size:0.85rem; color:var(--text-muted);">
            GTA World UCP hesabınız ile güvenli bir şekilde giriş yaparsınız.<br>
            Şifreniz bizimle paylaşılmaz.
        </p>

        <div class="auth-footer">
            Hesabın yok mu? <a href="<?php echo BASE_URL; ?>/register">Kayıt Ol</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
