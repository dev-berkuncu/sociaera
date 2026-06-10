<?php
/**
 * Admin Login — Sociaera Light Design
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Leaderboard.php';
require_once __DIR__ . '/../../app/Models/Ad.php';
require_once __DIR__ . '/../../app/Models/Settings.php';

if (Auth::isAdmin()) {
    header('Location: ' . BASE_URL . '/admin');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Kullanıcı adı ve şifre gerekli.';
    } else {
        $userModel = new UserModel();
        $result = $userModel->login($username, $password);

        if (!$result['ok']) {
            $error = 'Kullanıcı adı veya şifre hatalı.';
        } elseif (empty($result['user']['is_admin'])) {
            $error = 'Bu hesabın admin yetkisi yok.';
        } else {
            Auth::login($result['user']);
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Girişi — Sociaera</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=block" rel="stylesheet"/>
<style>
:root {
    --cp: #F06D1F; --cp-hover: #D95E10; --cp-bg: #FFF3EB;
    --bg: #F5F4F0; --card: #ffffff; --section: #F8F7F5; --input: #F2F1EE;
    --border: #E8E7E3; --t1: #1A1A1A; --t2: #5C5C5C; --t3: #A0A0A0;
    --font: 'Plus Jakarta Sans','Inter',sans-serif;
}
*, *::before, *::after { box-sizing:border-box; }
html, body {
    margin:0; padding:0; min-height:100vh;
    background: var(--bg); color:var(--t1);
    font-family:var(--font);
}
body {
    display:flex; align-items:center; justify-content:center;
    padding:20px;
    background-image: radial-gradient(circle at 20% 50%, rgba(240,109,31,.06) 0%, transparent 60%),
                      radial-gradient(circle at 80% 20%, rgba(255,166,51,.05) 0%, transparent 50%);
}

.login-wrap { width:100%; max-width:400px; }

/* Brand */
.login-brand {
    text-align:center; margin-bottom:28px;
}
.login-brand-logo {
    display:inline-flex; align-items:center; gap:10px;
    margin-bottom:8px;
}
.login-brand-icon {
    width:48px; height:48px; border-radius:14px;
    background: linear-gradient(135deg, #F06D1F, #FFA633);
    display:flex; align-items:center; justify-content:center;
    color:#fff; box-shadow:0 8px 20px rgba(240,109,31,.3);
}
.login-brand-icon .material-symbols-outlined { font-size:24px; font-variation-settings:'FILL' 1; display:inline; width:auto; height:auto; }
.login-brand-name { font-size:22px; font-weight:800; color:var(--t1); letter-spacing:-.4px; }
.login-brand-sub { font-size:13px; color:var(--t3); font-weight:500; }

/* Card */
.login-card {
    background:#fff; border:1.5px solid var(--border); border-radius:20px;
    padding:32px; box-shadow:0 4px 24px rgba(0,0,0,.08);
}
.login-card-title {
    font-size:17px; font-weight:800; color:var(--t1);
    margin:0 0 22px; text-align:center;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.login-card-title .material-symbols-outlined { font-size:20px; color:var(--cp); font-variation-settings:'FILL' 1; display:inline; width:auto; height:auto; }

/* Error */
.login-error {
    background:#FEF2F2; border:1.5px solid #FCA5A5; border-radius:10px;
    padding:10px 14px; font-size:13px; color:#DC2626; font-weight:600;
    margin-bottom:18px; display:flex; align-items:center; gap:8px;
}
.login-error .material-symbols-outlined { font-size:16px; flex-shrink:0; display:inline; width:auto; height:auto; }

/* Form */
.form-group { margin-bottom:16px; }
.form-label {
    display:block; font-size:11px; font-weight:700;
    text-transform:uppercase; letter-spacing:.6px;
    color:var(--t3); margin-bottom:6px;
}
.form-input {
    width:100%; background:var(--input); border:1.5px solid transparent;
    border-radius:11px; padding:10px 14px; font-size:14px;
    font-family:var(--font); outline:none; color:var(--t1);
    transition:all .13s;
}
.form-input:focus { border-color:var(--cp); background:#fff; }
.form-input::placeholder { color:var(--t3); }

/* Button */
.btn-login {
    width:100%; background:var(--cp); color:#fff;
    border:none; border-radius:12px; padding:12px;
    font-size:14px; font-weight:800; font-family:var(--font);
    cursor:pointer; transition:all .13s;
    box-shadow:0 4px 14px rgba(240,109,31,.3);
    margin-top:4px;
    display:flex; align-items:center; justify-content:center; gap:8px;
}
.btn-login:hover { background:var(--cp-hover); transform:translateY(-1px); box-shadow:0 6px 18px rgba(240,109,31,.4); }
.btn-login .material-symbols-outlined { font-size:18px; font-variation-settings:'FILL' 1; display:inline; width:auto; height:auto; }

/* Footer link */
.login-footer { text-align:center; margin-top:18px; }
.login-footer a { font-size:12px; color:var(--t3); text-decoration:none; font-weight:600; transition:color .13s; }
.login-footer a:hover { color:var(--cp); }

/* Admin badge */
.admin-badge {
    display:inline-flex; align-items:center; gap:5px;
    background:var(--cp-bg); color:var(--cp);
    font-size:11px; font-weight:700; padding:4px 10px; border-radius:99px;
    margin-bottom:20px;
}
.admin-badge .material-symbols-outlined { font-size:14px; font-variation-settings:'FILL' 1; display:inline; width:auto; height:auto; }
</style>
</head>
<body>

<div class="login-wrap">
    <!-- Brand -->
    <div class="login-brand">
        <div class="login-brand-logo">
            <div class="login-brand-icon">
                <span class="material-symbols-outlined">hive</span>
            </div>
            <span class="login-brand-name">Sociaera</span>
        </div>
        <div class="login-brand-sub">Yönetim Paneli</div>
    </div>

    <!-- Card -->
    <div class="login-card">
        <h1 class="login-card-title">
            <span class="material-symbols-outlined">admin_panel_settings</span>
            Yönetici Girişi
        </h1>

        <div style="text-align:center; margin-bottom:20px;">
            <span class="admin-badge">
                <span class="material-symbols-outlined">lock</span>
                Yalnızca yetkili personel
            </span>
        </div>

        <?php if ($error): ?>
        <div class="login-error">
            <span class="material-symbols-outlined">error</span>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-input" required autofocus
                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                       placeholder="admin kullanıcı adınız"/>
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-input" required
                       placeholder="••••••••"/>
            </div>
            <button type="submit" class="btn-login">
                <span class="material-symbols-outlined">login</span>
                Giriş Yap
            </button>
        </form>
    </div>

    <div class="login-footer">
        <a href="<?php echo BASE_URL; ?>/dashboard">← Siteye dön</a>
    </div>
</div>

</body>
</html>
