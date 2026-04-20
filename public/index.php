<?php
/**
 * Sociaera — Landing Page (giriş yapmamış kullanıcılar için)
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

$pageTitle = 'Sosyal Keşif Platformu';
$pageDescription = 'Sociaera — Sosyal keşif ve check-in platformu. Mekanları keşfet, anlarını paylaş.';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
?>

<div style="min-height:100vh; display:flex; align-items:center; justify-content:center; padding:calc(var(--navbar-h) + 40px) 20px 40px;">
    <div style="text-align:center; max-width:600px;">
        <div style="width:80px; height:80px; border-radius:50%; background:var(--primary-gradient); display:flex; align-items:center; justify-content:center; margin:0 auto 24px; font-size:2rem; color:#fff;">
            <i class="bi bi-pin-map-fill"></i>
        </div>
        <h1 style="font-size:2.5rem; font-weight:800; margin-bottom:12px; line-height:1.2;">
            Keşfet. Paylaş.<br><span style="color:var(--primary);">Bağlan.</span>
        </h1>
        <p style="font-size:1.1rem; color:var(--text-muted); margin-bottom:32px; line-height:1.7;">
            <?php echo APP_NAME; ?>, sosyal keşif ve check-in platformudur. Favori mekanlarını keşfet, deneyimlerini paylaş ve topluluğunla bağlan.
        </p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="<?php echo BASE_URL; ?>/login" class="btn-primary-orange btn-lg">
                <i class="bi bi-box-arrow-in-right"></i> Giriş Yap
            </a>
        </div>

        <div style="display:flex; gap:40px; justify-content:center; margin-top:48px;">
            <div style="text-align:center;">
                <div style="font-size:1.8rem; font-weight:800; color:var(--primary);">🏠</div>
                <div style="font-weight:600; margin-top:6px;">Mekanlar</div>
                <div style="font-size:0.82rem; color:var(--text-muted);">Keşfet & paylaş</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.8rem; font-weight:800; color:var(--primary);">📍</div>
                <div style="font-weight:600; margin-top:6px;">Check-in</div>
                <div style="font-size:0.82rem; color:var(--text-muted);">Anını kaydet</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:1.8rem; font-weight:800; color:var(--primary);">🏆</div>
                <div style="font-weight:600; margin-top:6px;">Sıralama</div>
                <div style="font-size:0.82rem; color:var(--text-muted);">Haftalık yarış</div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
