<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Core/View.php';

$pageTitle = 'Sponsorlar';
$activeNav = 'sponsors';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <main class="main-feed" style="max-width:640px; margin:0 auto;">
        <div class="page-header">
            <h1><i class="bi bi-megaphone" style="color:var(--primary)"></i> Sponsorlar</h1>
            <p><?php echo APP_NAME; ?> <!--'-->u destekleyen sponsorlarımız</p>
        </div>

        <div class="card-box empty-state">
            <i class="bi bi-stars" style="color:var(--primary); opacity:1;"></i>
            <h3 style="font-weight:700; margin:12px 0 8px;">Sponsor Olmak İster Misin?</h3>
            <p>Markanı binlerce GTA World TR oyuncusuna tanıt. Reklam alanlarımız hakkında bilgi almak için bizimle iletişime geç.</p>
            <a href="mailto:info@sociaera.online" class="btn-primary-orange" style="margin-top:16px;">
                <i class="bi bi-envelope"></i> İletişime Geç
            </a>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
