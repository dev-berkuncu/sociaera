<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Core/View.php';

Auth::requireLogin();

$pageTitle = 'Premium';
$activeNav = 'premium';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <main class="main-feed" style="max-width:640px; margin:0 auto;">
        <div class="page-header">
            <h1><i class="bi bi-gem" style="color:var(--primary)"></i> Premium</h1>
        </div>

        <div class="premium-card">
            <div class="premium-icon">💎</div>
            <h2 style="font-size:1.5rem; font-weight:800; margin-bottom:8px;"><?php echo APP_NAME; ?> Premium</h2>
            <p style="color:var(--text-muted); margin-bottom:0;">Deneyimini bir üst seviyeye taşı</p>

            <ul class="premium-features">
                <li><i class="bi bi-check-circle-fill"></i> Reklamsız deneyim</li>
                <li><i class="bi bi-check-circle-fill"></i> Profil rozeti <span class="post-badge" style="margin-left:auto;">Premium</span></li>
                <li><i class="bi bi-check-circle-fill"></i> Daha yüksek yükleme limiti</li>
                <li><i class="bi bi-check-circle-fill"></i> Öncelikli destek</li>
                <li><i class="bi bi-check-circle-fill"></i> Özel profil temaları</li>
                <li><i class="bi bi-check-circle-fill"></i> Sıralama tablosunda öne çıkma</li>
            </ul>

            <button class="btn-primary-orange btn-full btn-lg" disabled style="opacity:0.7;">
                <i class="bi bi-credit-card"></i> Yakında...
            </button>
            <p style="margin-top:12px; font-size:0.82rem; color:var(--text-muted);">Ödeme sistemi yapım aşamasındadır.</p>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
