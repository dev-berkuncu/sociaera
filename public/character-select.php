<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Services/Logger.php';
require_once __DIR__ . '/../app/Models/User.php';

$isLoggedIn = Auth::check();
$currentUserData = null;
if ($isLoggedIn) {
    $currentUserData = (new UserModel())->getById(Auth::id());
}

$characters   = $_SESSION['oauth_characters'] ?? [];
$gtaUserId    = $_SESSION['oauth_gta_user_id'] ?? ($currentUserData ? $currentUserData['gta_user_id'] : null);
$gtaUsername  = $_SESSION['oauth_gta_username'] ?? ($currentUserData ? $currentUserData['gta_username'] : '');

// Eğer giriş yapmışsa ve OAuth akışında değilsek, DB'den diğer karakterleri çek
$isSwitching = ($isLoggedIn && !isset($_SESSION['oauth_gta_user_id']));
if ($isSwitching && $gtaUserId) {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, username as name, gta_character_id FROM users WHERE gta_user_id = ? AND id != ? AND is_active = 1");
        $stmt->execute([$gtaUserId, Auth::id()]);
        $dbChars = $stmt->fetchAll();
        $characters = [];
        foreach ($dbChars as $dc) {
            $characters[] = ['id' => $dc['id'], 'name' => $dc['name']];
        }
    } catch (Exception $e) {}
}

if (!$gtaUserId) {
    Auth::setFlash('error', 'Oturum bilgisi bulunamadı.');
    header('Location: ' . BASE_URL . '/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isSwitching) {
    Csrf::requireValid();
    $charId   = (int) ($_POST['character_id'] ?? 0);
    $charName = '';

    foreach ($characters as $char) {
        if ((int)($char['id'] ?? 0) === $charId) {
            $charName = $char['name'] ?? trim(($char['firstname'] ?? '') . ' ' . ($char['lastname'] ?? ''));
            break;
        }
    }

    if ($charId && $charName) {
        $userModel = new UserModel();
        $result = $userModel->findOrCreateByCharacter($gtaUserId, $gtaUsername, $charId, $charName);

        if (!$result['ok']) {
            Auth::setFlash('error', 'Hesap oluşturulamadı. Lütfen tekrar deneyin.');
            header('Location: ' . BASE_URL . '/character-select');
            exit;
        }

        $user = $result['user'];
        Auth::login($user);
        Csrf::regenerate();
        unset($_SESSION['oauth_characters'], $_SESSION['oauth_gta_user_id'], $_SESSION['oauth_gta_username']);

        if ($result['is_new']) {
            Logger::info('New character account created', ['user_id' => $user['id'], 'char' => $charName, 'char_id' => $charId]);
            Auth::setFlash('success', 'Hoş geldin ' . $charName . '! 🎭 Yeni hesabın oluşturuldu.');
        } else {
            Logger::info('Character login', ['user_id' => $user['id'], 'char' => $charName]);
            Auth::setFlash('success', $charName . ' olarak giriş yaptın! 🎭');
        }

        $redirectUrl = !empty($user['bank_account']) ? '/dashboard' : '/settings';
        header('Location: ' . BASE_URL . $redirectUrl);
        exit;
    } elseif ($charId && !$charName) {
        Auth::setFlash('error', 'Seçilen karakter için isim bilgisi alınamadı. Lütfen tekrar deneyin.');
        header('Location: ' . BASE_URL . '/character-select');
        exit;
    }
}

$pageTitle   = $isSwitching ? 'Karakter Değiştir' : 'Karakter Seçimi';
$hideSidebar = true;
require_once __DIR__ . '/partials/app_header.php';
?>
<style>
/* ── Character Select Page ─────────────────────── */
.cs-page {
  min-height: calc(100vh - 60px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px 16px 80px;
}
.cs-wrap {
  width: 100%;
  max-width: 660px;
}
.cs-header {
  text-align: center;
  margin-bottom: 36px;
}
.cs-icon-ring {
  width: 72px; height: 72px;
  border-radius: 22px;
  background: linear-gradient(135deg, #F06D1F 0%, #FF9D4D 100%);
  display: inline-flex; align-items: center; justify-content: center;
  box-shadow: 0 12px 32px rgba(240,109,31,.30);
  margin-bottom: 18px;
  animation: floatBob 3s ease-in-out infinite;
}
@keyframes floatBob {
  0%,100% { transform: translateY(0); }
  50%      { transform: translateY(-6px); }
}
.cs-title {
  font-size: 1.75rem; font-weight: 900;
  color: var(--text-1); letter-spacing: -.5px;
  margin: 0 0 8px;
}
.cs-sub {
  font-size: 14px; color: var(--text-3);
  margin: 0; font-weight: 500;
}

/* ── Character Cards ─── */
.cs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 14px;
  margin-bottom: 28px;
}
.cs-label { cursor: pointer; display: block; }
.cs-label input[type="radio"] { display: none; }

.cs-card {
  background: #fff;
  border: 2px solid #EAE9E5;
  border-radius: 20px;
  padding: 28px 16px 22px;
  display: flex; flex-direction: column; align-items: center;
  text-align: center;
  transition: all .2s cubic-bezier(.34,1.56,.64,1);
  position: relative;
  overflow: hidden;
  user-select: none;
}
.cs-card::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(135deg, rgba(240,109,31,.06) 0%, transparent 60%);
  opacity: 0;
  transition: opacity .2s;
}
.cs-card:hover { border-color: rgba(240,109,31,.4); transform: translateY(-3px); box-shadow: 0 8px 28px rgba(240,109,31,.12); }
.cs-card:hover::before { opacity: 1; }

/* Selected state */
.cs-label input:checked ~ .cs-card {
  border-color: var(--color-primary) !important;
  background: #FFFAF7 !important;
  box-shadow: 0 0 0 4px rgba(240,109,31,.15), 0 8px 28px rgba(240,109,31,.18) !important;
  transform: translateY(-4px) scale(1.02) !important;
}
.cs-label input:checked ~ .cs-card::before { opacity: 1 !important; }

/* Avatar */
.cs-avatar {
  width: 72px; height: 72px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px; font-weight: 900;
  color: #fff;
  margin-bottom: 14px;
  position: relative;
  box-shadow: 0 6px 20px rgba(0,0,0,.15);
  transition: transform .2s;
  flex-shrink: 0;
}
.cs-label input:checked ~ .cs-card .cs-avatar {
  transform: scale(1.08);
  box-shadow: 0 8px 24px rgba(240,109,31,.3);
}

.cs-checkmark {
  position: absolute;
  bottom: -4px; right: -4px;
  width: 24px; height: 24px;
  background: var(--color-primary);
  border-radius: 50%;
  border: 3px solid #fff;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; transform: scale(0);
  transition: all .2s cubic-bezier(.34,1.56,.64,1);
}
.cs-label input:checked ~ .cs-card .cs-checkmark {
  opacity: 1; transform: scale(1);
}

.cs-name {
  font-size: 15px; font-weight: 800;
  color: var(--text-1); line-height: 1.2;
  margin-bottom: 5px;
}
.cs-id {
  font-size: 11px; font-weight: 600;
  color: var(--text-3);
  background: var(--bg-section);
  border-radius: 99px;
  padding: 3px 10px;
}

/* Selected badge */
.cs-badge {
  position: absolute; top: 12px; right: 12px;
  background: var(--color-primary);
  color: #fff;
  font-size: 10px; font-weight: 800;
  border-radius: 99px;
  padding: 3px 8px;
  opacity: 0; transform: translateY(-4px);
  transition: all .2s;
  letter-spacing: .3px;
}
.cs-label input:checked ~ .cs-card .cs-badge {
  opacity: 1; transform: translateY(0);
}

/* Actions */
.cs-actions {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
}
.cs-btn-submit {
  display: inline-flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, #F06D1F 0%, #FF8C3A 100%);
  color: #fff;
  border: none; cursor: pointer;
  font-size: 15px; font-weight: 800;
  padding: 14px 36px;
  border-radius: 99px;
  box-shadow: 0 8px 24px rgba(240,109,31,.35);
  transition: all .2s;
  letter-spacing: -.2px;
}
.cs-btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(240,109,31,.45); }
.cs-btn-submit:active { transform: scale(.97); }

.cs-btn-cancel {
  display: inline-flex; align-items: center; gap: 6px;
  background: transparent;
  color: var(--text-3);
  border: 1.5px solid var(--border);
  font-size: 14px; font-weight: 700;
  padding: 13px 22px;
  border-radius: 99px;
  text-decoration: none;
  transition: all .15s;
}
.cs-btn-cancel:hover { border-color: var(--text-3); color: var(--text-2); }
</style>

<div class="cs-page">
  <div class="cs-wrap">

    <!-- Header -->
    <div class="cs-header">
      <div class="cs-icon-ring">
        <span class="material-symbols-outlined" style="font-size:34px;color:#fff;font-variation-settings:'FILL' 1;">manage_accounts</span>
      </div>
      <h1 class="cs-title">
        <?php echo $isSwitching ? 'Karakter Değiştir' : 'Karakter Seçimi'; ?>
      </h1>
      <p class="cs-sub">
        <?php echo $isSwitching
          ? 'Aynı UCP hesabına bağlı diğer karakterlere geçiş yap'
          : 'Hangi karakter ile devam etmek istiyorsun?'; ?>
      </p>
    </div>

    <?php if (empty($characters)): ?>
    <!-- Boş durum -->
    <div style="background:#fff;border-radius:24px;padding:48px 24px;text-align:center;border:1.5px solid var(--border);box-shadow:0 4px 24px rgba(0,0,0,.06);">
      <span class="material-symbols-outlined" style="font-size:56px;color:var(--text-3);opacity:.4;display:block;margin-bottom:16px;">person_off</span>
      <div style="font-size:17px;font-weight:800;color:var(--text-2);margin-bottom:8px;">
        <?php echo $isSwitching ? 'Başka karakter bulunamadı' : 'Karakter bulunamadı'; ?>
      </div>
      <p style="font-size:13px;color:var(--text-3);margin:0 0 24px;">
        <?php echo $isSwitching
          ? 'Hesabına bağlı başka aktif karakter yok.'
          : 'UCP hesabına kayıtlı karakter bulunamadı.'; ?>
      </p>
      <a href="<?php echo BASE_URL; ?>/<?php echo $isSwitching ? 'dashboard' : 'login'; ?>" class="cs-btn-cancel">
        ← <?php echo $isSwitching ? 'Ana Sayfaya Dön' : 'Giriş Sayfasına Dön'; ?>
      </a>
    </div>

    <?php else: ?>
    <!-- Karakter seçim formu -->
    <form method="POST" action="<?php echo $isSwitching ? BASE_URL . '/switch-character' : ''; ?>">
      <?php echo csrfField(); ?>

      <div class="cs-grid">
        <?php
        // Renkler — her karakter farklı bir ton alsın
        $avatarColors = [
          'linear-gradient(135deg,#F06D1F,#FF9D4D)',
          'linear-gradient(135deg,#7C3AED,#A855F7)',
          'linear-gradient(135deg,#0D9488,#2DD4BF)',
          'linear-gradient(135deg,#2563EB,#60A5FA)',
          'linear-gradient(135deg,#DC2626,#F87171)',
          'linear-gradient(135deg,#D97706,#FCD34D)',
        ];
        foreach ($characters as $i => $char):
          $name      = $char['name'];
          $cid       = (int)$char['id'];
          $radioName = $isSwitching ? 'target_user_id' : 'character_id';
          $initials  = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', trim($name)), 0, 2))));
          $color     = $avatarColors[$i % count($avatarColors)];
        ?>
        <label class="cs-label">
          <input type="radio" name="<?php echo $radioName; ?>" value="<?php echo $cid; ?>" required>
          <div class="cs-card">
            <span class="cs-badge">✓ Seçildi</span>
            <div class="cs-avatar" style="background:<?php echo $color; ?>;">
              <?php echo htmlspecialchars($initials); ?>
              <div class="cs-checkmark">
                <span class="material-symbols-outlined" style="font-size:12px;color:#fff;font-variation-settings:'FILL' 1;">check</span>
              </div>
            </div>
            <div class="cs-name"><?php echo escape($name); ?></div>
            <div class="cs-id">ID <?php echo $cid; ?></div>
          </div>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="cs-actions">
        <?php if ($isSwitching): ?>
        <a href="<?php echo BASE_URL; ?>/dashboard" class="cs-btn-cancel">
          <span class="material-symbols-outlined" style="font-size:16px;">arrow_back</span>
          İptal
        </a>
        <?php endif; ?>
        <button type="submit" class="cs-btn-submit">
          <span class="material-symbols-outlined" style="font-size:20px;font-variation-settings:'FILL' 1;">sync_alt</span>
          Seçili Karakterle Devam Et
        </button>
      </div>
    </form>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
