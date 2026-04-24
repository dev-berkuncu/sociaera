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
require_once __DIR__ . '/partials/flash.php';
?>

<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-background">
    <!-- Background Design -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-1/4 left-1/4 w-[500px] h-[500px] bg-primary-container/20 rounded-full blur-[100px] opacity-50 mix-blend-screen"></div>
        <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-[#ff9e7d]/20 rounded-full blur-[80px] opacity-40 mix-blend-screen"></div>
    </div>
    
    <div class="w-full max-w-md relative z-10">
        <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-8 md:p-10 shadow-[0_20px_40px_-15px_rgba(15,23,42,0.5)]">
            <div class="text-center mb-8">
                <div class="w-16 h-16 mx-auto bg-primary-container text-white rounded-2xl flex items-center justify-center mb-4 shadow-[0_10px_20px_-5px_rgba(255,107,53,0.4)] transform -rotate-6">
                    <span class="material-symbols-outlined text-[32px]">location_on</span>
                </div>
                <h1 class="text-3xl font-black text-on-surface mb-2"><?php echo APP_NAME; ?>'ya Hoş Geldin</h1>
                <p class="text-slate-400 text-sm">GTA World hesabınla giriş yap ve keşfe başla</p>
            </div>

            <a href="<?php echo BASE_URL; ?>/oauth-login" class="w-full bg-[#1E293B] hover:bg-[#2e3e58] text-white border border-white/10 py-4 px-6 rounded-xl font-bold transition-all flex items-center justify-center gap-3 shadow-lg hover:shadow-xl hover:-translate-y-0.5 active:translate-y-0 active:scale-95 group">
                <span class="material-symbols-outlined text-primary-container group-hover:scale-110 transition-transform">sports_esports</span>
                GTA World ile Giriş Yap
            </a>

            <div class="mt-8 text-center text-xs text-slate-500 leading-relaxed border-t border-white/5 pt-6">
                GTA World UCP hesabınız ile güvenli bir şekilde giriş yaparsınız.<br>
                Şifreniz bizimle paylaşılmaz.
            </div>
            
            <div class="mt-6 text-center text-sm text-slate-400">
                Hesabın yok mu? <a href="<?php echo BASE_URL; ?>/register" class="text-primary-container font-bold hover:underline">Kayıt Ol</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
