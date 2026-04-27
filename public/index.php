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
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title><?php echo APP_NAME; ?> - Nexus | Dijital Ajans</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&amp;family=Manrope:wght@500;600;700&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "on-error": "#690005",
                        "surface-bright": "#31394d",
                        "on-secondary": "#263143",
                        "on-tertiary-container": "#00364b",
                        "on-secondary-container": "#aeb9d0",
                        "tertiary": "#7bd0ff",
                        "on-secondary-fixed-variant": "#3c475a",
                        "error": "#ffb4ab",
                        "surface-container-highest": "#2d3449",
                        "on-primary-fixed": "#390c00",
                        "outline": "#a98a80",
                        "on-surface-variant": "#e1bfb5",
                        "on-primary-container": "#5f1900",
                        "tertiary-fixed": "#c4e7ff",
                        "surface-container-low": "#131b2e",
                        "on-error-container": "#ffdad6",
                        "surface-container-high": "#222a3d",
                        "on-surface": "#dae2fd",
                        "primary": "#ffb59d",
                        "tertiary-fixed-dim": "#7bd0ff",
                        "inverse-surface": "#dae2fd",
                        "inverse-on-surface": "#283044",
                        "inverse-primary": "#ab3500",
                        "error-container": "#93000a",
                        "secondary": "#bcc7de",
                        "background": "#0b1326",
                        "surface-dim": "#0b1326",
                        "surface-container": "#171f33",
                        "secondary-fixed-dim": "#bcc7de",
                        "on-tertiary-fixed-variant": "#004c69",
                        "on-tertiary": "#00354a",
                        "on-primary": "#5d1900",
                        "primary-fixed": "#ffdbd0",
                        "tertiary-container": "#00a5de",
                        "surface-container-lowest": "#060e20",
                        "primary-container": "#ff6b35",
                        "outline-variant": "#594139",
                        "surface-tint": "#ffb59d",
                        "primary-fixed-dim": "#ffb59d",
                        "on-secondary-fixed": "#111c2d",
                        "on-tertiary-fixed": "#001e2c",
                        "surface": "#0b1326",
                        "secondary-container": "#3e495d",
                        "secondary-fixed": "#d8e3fb",
                        "on-background": "#dae2fd",
                        "on-primary-fixed-variant": "#832600",
                        "surface-variant": "#2d3449"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "stack-md": "24px",
                        "stack-lg": "48px",
                        "base": "8px",
                        "gutter": "24px",
                        "container-padding": "32px",
                        "stack-sm": "12px"
                    },
                    "fontFamily": {
                        "body-md": ["Inter"],
                        "label-sm": ["Inter"],
                        "display-lg": ["Manrope"],
                        "headline-md": ["Manrope"],
                        "body-lg": ["Inter"],
                        "label-md": ["Inter"],
                        "headline-lg": ["Manrope"]
                    },
                    "fontSize": {
                        "body-md": ["16px", { "lineHeight": "1.6", "fontWeight": "400" }],
                        "label-sm": ["12px", { "lineHeight": "1.2", "letterSpacing": "0.05em", "fontWeight": "600" }],
                        "display-lg": ["48px", { "lineHeight": "1.2", "letterSpacing": "-0.02em", "fontWeight": "700" }],
                        "headline-md": ["24px", { "lineHeight": "1.4", "fontWeight": "600" }],
                        "body-lg": ["18px", { "lineHeight": "1.6", "fontWeight": "400" }],
                        "label-md": ["14px", { "lineHeight": "1.2", "letterSpacing": "0.01em", "fontWeight": "500" }],
                        "headline-lg": ["32px", { "lineHeight": "1.3", "letterSpacing": "-0.01em", "fontWeight": "600" }]
                    },
                    "animation": {
                        "float-fast": "float 4s ease-in-out infinite",
                        "float": "float 5s ease-in-out infinite",
                        "float-slow": "float 6s ease-in-out infinite"
                    },
                    "keyframes": {
                        "float": {
                            "0%, 100%": { transform: "translateY(0)" },
                            "50%": { transform: "translateY(-15px)" }
                        }
                    }
                }
            }
        }
    </script>
<style>
        .glass-card {
            background-color: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-glow {
            box-shadow: 0 0 15px rgba(255, 107, 53, 0.2);
        }
        .text-gradient {
            background: linear-gradient(to right, #dae2fd, #aeb9d0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="text-on-background antialiased selection:bg-primary-container selection:text-white relative bg-transparent min-h-screen">
<!-- Full Page Background -->
<div class="fixed inset-0 z-[-2] bg-cover bg-center bg-no-repeat" style="background-image: url('<?php echo BASE_URL; ?>/assets/images/hero-bg.jpg');"></div>
<div class="fixed inset-0 z-[-1] bg-background/85 backdrop-blur-sm"></div>
<!-- TopNavBar -->
<nav class="bg-[#0F172A]/80 backdrop-blur-xl font-['Manrope'] text-sm tracking-wide font-medium docked full-width top-0 sticky border-b border-white/10 shadow-[0_30px_30px_rgba(15,23,42,0.15)] z-50">
<div class="flex justify-center items-center w-full px-8 py-4 max-w-7xl mx-auto">
<div class="flex items-center gap-2 text-2xl font-bold tracking-tighter text-[#FF6B35]">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" class="h-8 w-auto opacity-90">
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
<footer class="bg-[#0F172A] full-width py-12 border-t border-slate-800 flat no-shadows mt-24">
<div class="flex flex-col md:flex-row justify-between items-center w-full px-8 max-w-7xl mx-auto gap-6 font-['Manrope'] text-xs uppercase tracking-widest text-slate-500">
<div class="flex items-center gap-2 text-lg font-bold text-slate-200">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Logo" class="h-6 w-auto opacity-70">
                <?php echo APP_NAME; ?>
            </div>
<div class="flex flex-wrap items-center justify-center gap-6">
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#FF6B35] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">Kullanım Koşulları</a>
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#FF6B35] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">Gizlilik Politikası</a>
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#FF6B35] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">Çerez Ayarları</a>
<a class="text-slate-500 hover:text-slate-300 hover:underline decoration-[#FF6B35] underline-offset-4 opacity-80 hover:opacity-100 transition-opacity" href="#">İletişim</a>
</div>
<div class="text-center md:text-right">
                © <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Nexus | Dijital Ajans.
            </div>
</div>
</footer>
</body></html>
