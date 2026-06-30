<?php
/**
 * Sociaera — Landing Page (giriş yapmamış kullanıcılar için)
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }
?>
<!DOCTYPE html>
<html class="dark" lang="tr"><head>
<meta charset="utf-8"/> 
<?php $heroV = file_exists(__DIR__ . '/assets/images/hero-bg.jpg') ? filemtime(__DIR__ . '/assets/images/hero-bg.jpg') : time(); ?>
<link rel="preload" href="<?php echo BASE_URL; ?>/assets/images/hero-bg.jpg?v=<?php echo $heroV; ?>" as="image" type="image/jpeg"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo APP_NAME; ?> - Nexus | Dijital Ajans</title>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/tailwind-front.css?v=<?php echo filemtime(__DIR__ . '/assets/css/tailwind-front.css'); ?>"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=block" rel="stylesheet"/>
<style>
        .glass-card {
            background-color: rgba(19, 19, 20, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-glow {
            box-shadow: 0 0 15px rgba(255, 145, 0, 0.2);
        }
        .text-gradient {
            background: linear-gradient(to right, #e5e2e3, #aeb9d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        /* Font yüklenene kadar simge adlarının taşarak tasarımı bozmasını engeller */
        .material-symbols-outlined {
            display: inline-block;
            width: 1em;
            height: 1em;
            overflow: hidden;
            white-space: nowrap;
            word-wrap: normal;
        }
    </style>
</head>
<body class="text-on-background antialiased selection:bg-primary-container selection:text-white relative bg-[#131314] min-h-screen">
<!-- Full Page Background -->
<div class="fixed inset-0 z-[-2] bg-no-repeat" style="top: 64px; background-image: url('<?php echo BASE_URL; ?>/assets/images/hero-bg.jpg?v=<?php echo $heroV; ?>'); background-size: 80%; background-position: center top; filter: blur(2px);"></div>
<div class="fixed inset-0 z-[-1]" style="top: 64px; background: linear-gradient(135deg, rgba(10,8,6,0.82) 0%, rgba(40,18,4,0.75) 50%, rgba(10,8,6,0.85) 100%);"></div>
<!-- TopNavBar -->
<nav class="bg-surface-container font-['Manrope'] text-sm tracking-wide font-medium w-full top-0 sticky border-b border-outline-variant/30 shadow-[0_30px_30px_rgba(19,19,20,0.15)] z-50">
<div class="flex justify-center items-center w-full px-8 py-4 max-w-7xl mx-auto">
<div class="flex items-center gap-2 text-2xl font-bold tracking-tighter text-[#ff9100]">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" class="h-8 w-auto opacity-90" width="32" height="32">
                <?php echo APP_NAME; ?>
            </div>
</div>
</nav>
<main class="w-full flex flex-col items-center">
<!-- Hero Section -->
<section class="w-full max-w-7xl mx-auto px-container-padding py-24 md:py-32 flex flex-col md:flex-row items-center gap-stack-lg relative">
<div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-container/10 rounded-full blur-[120px] -z-10 pointer-events-none"></div>
<div class="flex-1 flex flex-col gap-stack-md z-10">
<div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/5 border border-white/10 w-fit">
<span class="w-2 h-2 rounded-full bg-primary-container animate-pulse"></span>
<span class="font-label-sm text-label-sm text-secondary">Nexus | Dijital Ajans</span>
</div>
<h1 class="font-display-lg text-display-lg text-gradient leading-tight">
                    Keşfet. Paylaş. Bağlan.
                </h1>
<p class="font-body-lg text-body-lg text-secondary max-w-xl">
                    <?php echo APP_NAME; ?>, sosyal keşif ve check-in platformudur. Favori mekanlarını keşfet, deneyimlerini paylaş ve topluluğunla bağlan.
                </p>
<div class="flex items-center gap-4 mt-4">
<a href="<?php echo BASE_URL; ?>/login" class="bg-primary-container text-white px-8 py-4 rounded-xl btn-glow hover:bg-opacity-90 transition-all font-label-md text-label-md inline-flex items-center justify-center gap-2 w-fit">
                        Giriş Yap
                        <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 0;">arrow_forward</span>
</a>
</div>
</div>
<div class="flex-1 w-full relative h-[300px] md:h-[500px] hidden md:block">
    <!-- Floating Widget 1: Check-in -->
    <div class="absolute top-[15%] left-[5%] glass-card p-4 rounded-2xl flex items-center gap-4 animate-float shadow-[0_20px_40px_rgba(0,0,0,0.3)] border border-white/20 backdrop-blur-md">
        <div class="w-12 h-12 rounded-xl bg-primary-container/20 flex items-center justify-center text-primary-container shadow-inner border border-primary-container/30">
            <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">location_on</span>
        </div>
        <div>
            <p class="font-bold text-sm text-on-surface">Solid Comics</p>
            <p class="text-xs text-slate-300">Az önce check-in yapıldı</p>
        </div>
    </div>

    <!-- Floating Widget 2: Badge -->
    <div class="absolute top-[65%] left-[20%] glass-card p-4 rounded-2xl flex items-center gap-4 animate-float-slow shadow-[0_20px_40px_rgba(0,0,0,0.3)] border border-white/20 backdrop-blur-md">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 shadow-inner border border-emerald-500/30">
            <span class="material-symbols-outlined text-[20px]" style="font-variation-settings: 'FILL' 1;">military_tech</span>
        </div>
        <div>
            <p class="font-bold text-sm text-on-surface">Gece Kuşu</p>
            <p class="text-xs text-slate-300">Yeni rozet kazanıldı!</p>
        </div>
    </div>

    <!-- Floating Widget 3: Trending Venue -->
    <div class="absolute top-[35%] right-[5%] lg:right-[15%] glass-card p-4 rounded-2xl flex items-center gap-4 animate-float-fast shadow-[0_20px_40px_rgba(0,0,0,0.3)] border border-white/20 backdrop-blur-md">
        <div class="w-12 h-12 rounded-xl bg-[#7bd0ff]/20 flex items-center justify-center text-[#7bd0ff] shadow-inner border border-[#7bd0ff]/30">
            <span class="material-symbols-outlined text-[24px]" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
        </div>
        <div>
            <p class="font-bold text-sm text-on-surface">Urban Performance Hall & Bar</p>
            <p class="text-xs text-slate-300">Şu an çok popüler</p>
        </div>
    </div>
</div>
</section>
<!-- Features Bento Grid -->
<section class="w-full max-w-7xl mx-auto px-container-padding py-stack-lg flex flex-col gap-stack-lg">
<div class="text-center max-w-2xl mx-auto flex flex-col gap-base">
<h2 class="font-headline-lg text-headline-lg text-on-surface">Platform Özellikleri</h2>
<p class="font-body-md text-body-md text-secondary">Premium bir ağ deneyimi için tasarlandı.</p>
</div>
<div class="grid grid-cols-1 md:grid-cols-3 gap-gutter">
<!-- Feature 1 -->
<div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 flex flex-col gap-6 group hover:bg-white/10 hover:border-white/20 hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(0,0,0,0.4)] transition-all duration-500 shadow-[0_8px_32px_rgba(0,0,0,0.3)] relative overflow-hidden">
<div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
<div class="w-14 h-14 rounded-2xl bg-primary-container/10 flex items-center justify-center text-primary-container border border-primary-container/20 group-hover:bg-primary-container/20 group-hover:border-primary-container/40 transition-all duration-300 shadow-inner relative z-10">
<span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0;">map</span>
</div>
<div class="relative z-10">
<h3 class="font-headline-md text-headline-md text-white mb-2">Mekanlar</h3>
<p class="font-body-md text-body-md text-slate-300">Keşfet &amp; paylaş. Şehrin en seçkin noktalarını bulun ve ağınızla deneyimlerinizi paylaşın.</p>
</div>
</div>
<!-- Feature 2 -->
<div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 flex flex-col gap-6 group hover:bg-white/10 hover:border-white/20 hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(0,0,0,0.4)] transition-all duration-500 shadow-[0_8px_32px_rgba(0,0,0,0.3)] relative overflow-hidden">
<div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
<div class="w-14 h-14 rounded-2xl bg-[#7bd0ff]/10 flex items-center justify-center text-[#7bd0ff] border border-[#7bd0ff]/20 group-hover:bg-[#7bd0ff]/20 group-hover:border-[#7bd0ff]/40 transition-all duration-300 shadow-inner relative z-10">
<span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0;">how_to_reg</span>
</div>
<div class="relative z-10">
<h3 class="font-headline-md text-headline-md text-white mb-2">Check-in</h3>
<p class="font-body-md text-body-md text-slate-300">Anını kaydet. Bulunduğunuz konumu doğrulayın ve profesyonel çevrenizle etkileşime geçin.</p>
</div>
</div>
<!-- Feature 3 -->
<div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl p-8 flex flex-col gap-6 group hover:bg-white/10 hover:border-white/20 hover:-translate-y-2 hover:shadow-[0_20px_40px_rgba(0,0,0,0.4)] transition-all duration-500 shadow-[0_8px_32px_rgba(0,0,0,0.3)] relative overflow-hidden">
<div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
<div class="w-14 h-14 rounded-2xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20 group-hover:bg-emerald-500/20 group-hover:border-emerald-500/40 transition-all duration-300 shadow-inner relative z-10">
<span class="material-symbols-outlined text-3xl" style="font-variation-settings: 'FILL' 0;">emoji_events</span>
</div>
<div class="relative z-10">
<h3 class="font-headline-md text-headline-md text-white mb-2">Sıralama</h3>
<p class="font-body-md text-body-md text-slate-300">Haftalık yarış. Aktif olarak ödüller kazanın ve liderlik tablosunda yerinizi alın.</p>
</div>
</div>
</div>
</section>
</main>
<!-- Footer -->
<footer class="bg-surface-container-low w-full py-12 border-t border-outline-variant/30 mt-24">
<div class="flex flex-col md:flex-row justify-between items-center w-full px-8 max-w-7xl mx-auto gap-6 font-['Manrope'] text-xs uppercase tracking-widest text-slate-500">
<div class="flex items-center gap-2 text-lg font-bold text-slate-200">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" class="h-6 w-auto opacity-70" width="24" height="24">
                <?php echo APP_NAME; ?>
            </div>
<div class="flex flex-wrap items-center justify-center gap-6">
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#ff9100] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">Kullanım Koşulları</a>
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#ff9100] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">Gizlilik Politikası</a>
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#ff9100] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">Çerez Ayarları</a>
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#ff9100] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">İletişim</a>
</div>
<div class="text-center md:text-right">
                © <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Nexus | Dijital Ajans.
            </div>
</div>
</footer>
</body></html>
