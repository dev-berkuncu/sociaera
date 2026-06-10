<?php
/**
 * Sociaera — Giriş Sayfası (Sadece GTA World OAuth)
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';

if (Auth::check()) { header('Location: ' . BASE_URL . '/dashboard'); exit; }

$pageTitle = 'Giriş Yap';
$hideSidebar = true;
require_once __DIR__ . '/partials/app_header.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div style="flex:1; display:flex; align-items:center; justify-content:center; width:100%; position:relative; overflow:hidden; padding:24px 16px;">
    <!-- Background blob decorations -->
    <div style="position:absolute; inset:0; z-index:0; pointer-events:none;">
        <div style="position:absolute; top:25%; left:25%; width:400px; height:400px; border-radius:50%; background:rgba(240,109,31,0.12); filter:blur(80px); opacity:.6;"></div>
        <div style="position:absolute; bottom:25%; right:25%; width:320px; height:320px; border-radius:50%; background:rgba(255,158,125,0.1); filter:blur(60px); opacity:.5;"></div>
    </div>

    <div style="width:100%; max-width:420px; position:relative; z-index:1;">
        <div style="background:#fff; border:1.5px solid var(--border); border-radius:20px; padding:36px 32px; box-shadow:0 20px 60px rgba(0,0,0,.1);">
            <div style="text-align:center; margin-bottom:28px;">
                <div style="width:64px; height:64px; margin:0 auto 16px; background:linear-gradient(135deg, var(--color-primary), #ff9e7d); border-radius:18px; display:flex; align-items:center; justify-content:center; transform:rotate(-6deg); box-shadow:0 10px 20px rgba(240,109,31,0.3);">
                    <span class="material-symbols-outlined" style="font-size:32px; color:#fff;">location_on</span>
                </div>
                <h1 style="font-size:1.6rem; font-weight:900; color:var(--text-1); margin:0 0 6px;"><?php echo APP_NAME; ?>'ya Hoş Geldin</h1>
                <p style="color:var(--text-3); font-size:13px; margin:0;">GTA World hesabınla giriş yap ve keşfe başla</p>
            </div>

            <a href="<?php echo BASE_URL; ?>/oauth-login"
               style="display:flex; align-items:center; justify-content:center; gap:12px; width:100%; background:var(--color-primary); color:#fff; border:none; padding:16px 24px; border-radius:14px; font-weight:800; font-size:15px; text-decoration:none; box-shadow:0 4px 20px rgba(240,109,31,0.25); transition:opacity .15s; box-sizing:border-box;"
               onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
                <span class="material-symbols-outlined" style="font-size:22px;">sports_esports</span>
                GTA World ile Giriş Yap
            </a>

            <div style="margin-top:24px; text-align:center; font-size:11px; color:var(--text-3); line-height:1.6; border-top:1px solid var(--border); padding-top:20px;">
                GTA World UCP hesabınız ile güvenli bir şekilde giriş yaparsınız.<br>
                Şifreniz bizimle paylaşılmaz.
            </div>

            <div style="margin-top:16px; text-align:center; font-size:13px; color:var(--text-3);">
                Hesabın yok mu? <a href="<?php echo BASE_URL; ?>/register" style="color:var(--color-primary); font-weight:700; text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Kayıt Ol</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
