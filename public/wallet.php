<?php
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Wallet.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Venue.php';

Auth::requireLogin();

$walletModel = new WalletModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'withdraw') {
        $amount = (float)($_POST['amount'] ?? 0);
        $accountInfo = trim($_POST['account_info'] ?? '');

        if ($amount < 10000) {
            Auth::setFlash('error', 'Minimum para çekme limiti 10.000$ dir.');
            header('Location: ' . BASE_URL . '/wallet');
            exit;
        }
        
        if (empty($accountInfo)) {
            Auth::setFlash('error', 'Lütfen hesap / cüzdan bilgilerinizi giriniz.');
            header('Location: ' . BASE_URL . '/wallet');
            exit;
        }

        try {
            if ($walletModel->requestWithdrawal(Auth::id(), $amount, $accountInfo)) {
                Auth::setFlash('success', "Çekim talebiniz başarıyla oluşturuldu! Yöneticiler onayladığında bakiyeniz hesabınıza yatırılacaktır.");
            } else {
                Auth::setFlash('error', 'Cüzdanınızda yeterli bakiye bulunmuyor.');
            }
        } catch (\Exception $e) {
            Auth::setFlash('error', $e->getMessage());
        }
        header('Location: ' . BASE_URL . '/wallet');
        exit;
    }
}

$walletModel->ensureWallet(Auth::id());
$balance = $walletModel->getBalance(Auth::id());
$transactions = $walletModel->getTransactions(Auth::id());

// Ödeme sonucu mesajları
$paymentSuccess = $_GET['payment'] ?? '';
$paymentMsg = '';
if ($paymentSuccess === 'success') {
    $paymentMsg = 'Bakiye başarıyla yüklendi!';
} elseif ($paymentSuccess === 'failed') {
    $paymentMsg = 'Ödeme doğrulanamadı. Lütfen tekrar deneyin.';
} elseif ($paymentSuccess === 'already') {
    $paymentMsg = 'Bu ödeme daha önce işlenmiş.';
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Cüzdan';
$activeNav = 'wallet';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0;">
<style>
/* Modal CSS since it is a floating overlay */
.topup-overlay {
    position: fixed; inset: 0; background: rgba(19, 19, 20, 0.85); backdrop-filter: blur(8px);
    z-index: 9999; display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity 0.3s ease;
}
.topup-overlay.active { opacity: 1; pointer-events: auto; }
</style>

<div style="display:flex;flex-direction:column;gap:20px;max-width:700px;width:100%;padding-bottom:40px;">
    <div style="margin-bottom:8px;">
        <h1 style="font-size:1.75rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text-1);"><span class="material-symbols-outlined" style="color:var(--color-primary);font-size:32px;font-variation-settings:'FILL' 1;">account_balance_wallet</span> Cüzdan</h1>
    </div>

    <?php if ($paymentMsg): ?>
        <div style="padding:12px 16px;border-radius:10px;margin-bottom:8px;display:flex;align-items:center;gap:12px;<?php echo $paymentSuccess === 'success' ? 'background:rgba(16,185,129,0.08);border:1.5px solid rgba(16,185,129,0.3);color:#10b981;' : 'background:rgba(220,38,38,0.08);border:1.5px solid rgba(220,38,38,0.3);color:#dc2626;'; ?>">
            <span class="material-symbols-outlined"><?php echo $paymentSuccess === 'success' ? 'check_circle' : 'error'; ?></span>
            <span><?php echo escape($paymentMsg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Balance Card -->
    <div style="background:linear-gradient(135deg,var(--color-primary),#ff9e7d);border-radius:20px;padding:32px;box-shadow:0 20px 40px -15px rgba(240,109,31,0.35);color:#fff;position:relative;overflow:hidden;">
        <div style="position:absolute;right:-40px;top:-40px;font-size:150px;opacity:.1;line-height:1;user-select:none;">
            <span class="material-symbols-outlined" style="font-size:inherit;">account_balance_wallet</span>
        </div>
        <div style="position:relative;z-index:1;">
            <div style="color:rgba(255,255,255,0.8);font-weight:600;margin-bottom:4px;letter-spacing:.08em;text-transform:uppercase;font-size:.875rem;">Mevcut Bakiye</div>
            <div style="font-size:3rem;font-weight:900;margin-bottom:24px;">$<?php echo number_format($balance, 0, ',', '.'); ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:12px;">
                <button onclick="openTopupModal()" style="background:#fff;color:var(--color-primary);padding:10px 24px;border-radius:999px;font-weight:700;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all .15s;display:inline-flex;align-items:center;gap:8px;border:none;cursor:pointer;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform=''">
                    <span class="material-symbols-outlined">add_circle</span> Bakiye Yükle
                </button>
                <button onclick="<?php echo $balance >= 10000 ? 'openWithdrawModal()' : "alert('Para çekim talebi oluşturabilmek için bakiyeniz en az 10.000$ olmalıdır.')"; ?>" style="background:rgba(255,255,255,0.1);border:1.5px solid rgba(255,255,255,0.2);color:#fff;padding:10px 24px;border-radius:999px;font-weight:700;transition:all .15s;display:inline-flex;align-items:center;gap:8px;cursor:pointer;" onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <span class="material-symbols-outlined">payments</span> Para Çek
                </button>
            </div>
        </div>
    </div>

    <h2 style="font-size:1.25rem;font-weight:800;color:var(--text-1);margin-top:24px;display:flex;align-items:center;gap:8px;"><span class="material-symbols-outlined" style="color:var(--color-primary);">receipt_long</span> İşlem Geçmişi</h2>
    
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.05);">
        <?php if (empty($transactions)): ?>
            <div style="padding:48px 24px;text-align:center;color:var(--text-3);">
                <span class="material-symbols-outlined" style="font-size:48px;display:block;margin-bottom:8px;opacity:.4;">receipt</span>
                <p>Henüz işlem yok.</p>
            </div>
        <?php else: ?>
            <div style="display:flex; flex-direction:column;">
                <?php foreach ($transactions as $tx):
                    $isIn = in_array($tx['type'], ['deposit', 'transfer_in']);
                ?>
                <div style="display:flex;align-items:center;gap:16px;padding:14px 16px;border-bottom:1px solid var(--border-light);transition:background .12s;" onmouseover="this.style.background='#faf8f5'" onmouseout="this.style.background=''">
                    <div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;<?php echo $isIn ? 'background:rgba(16,185,129,0.12);color:#10b981;' : 'background:rgba(220,38,38,0.12);color:#dc2626;'; ?>">
                        <span class="material-symbols-outlined"><?php echo $isIn ? 'south_west' : 'north_east'; ?></span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-weight:700;color:var(--text-1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo escape($tx['description'] ?: $tx['type']); ?></div>
                        <div style="font-size:11px;color:var(--text-3);display:flex;align-items:center;gap:8px;margin-top:2px;">
                            <span><?php echo formatDate($tx['created_at'], true); ?></span>
                            <?php if ($tx['type'] === 'withdraw'): ?>
                                <span style="padding:2px 6px; border-radius:4px; font-size:10px; text-transform:uppercase; font-weight:700; <?php
                                    $st = $tx['status'] ?? 'approved';
                                    if ($st === 'pending') echo 'background:rgba(245,158,11,0.15);color:#d97706;border:1px solid rgba(245,158,11,0.3);';
                                    elseif ($st === 'approved') echo 'background:rgba(16,185,129,0.15);color:#10b981;border:1px solid rgba(16,185,129,0.3);';
                                    else echo 'background:rgba(220,38,38,0.15);color:#dc2626;border:1px solid rgba(220,38,38,0.3);';
                                ?>"><?php echo escape($tx['status'] ?? 'approved'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="font-weight:900;font-size:1.1rem;flex-shrink:0;<?php echo $isIn ? 'color:#10b981;' : 'color:#dc2626;'; ?>">
                        <?php echo $isIn ? '+' : '-'; ?>$<?php echo number_format($tx['amount'], 0, ',', '.'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div><!-- /grid cell -->

<!-- Bakiye Yükle Modal -->
<div class="topup-overlay" id="topupOverlay" onclick="closeTopupModal(event)">
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:20px;width:90%;max-width:440px;overflow:hidden;box-shadow:0 24px 48px rgba(0,0,0,0.18);position:relative;" onclick="event.stopPropagation()">
        <button onclick="closeTopupModal()" style="position:absolute;top:14px;right:14px;background:none;border:none;cursor:pointer;color:var(--text-3);"><span class="material-symbols-outlined">close</span></button>
        
        <div style="padding:36px 32px;text-align:center;">
            <div style="width:64px;height:64px;margin:0 auto 16px;background:rgba(240,109,31,0.1);color:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:32px;">payments</span>
            </div>
            <h2 style="font-size:1.5rem;font-weight:800;color:var(--text-1);margin-bottom:8px;">Bakiye Yükle</h2>
            <p style="color:var(--text-3);font-size:.875rem;margin-bottom:24px;">Güvenli Fleeca altyapısı ile bakiyenizi yükleyin.</p>
            
            <div style="position:relative;margin-bottom:24px;">
                <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-3);font-weight:700;font-size:1.25rem;">$</span>
                <input type="number" id="topupAmount" style="width:100%;box-sizing:border-box;background:var(--bg-input);border:1.5px solid var(--border);border-radius:12px;padding:14px 14px 14px 36px;color:var(--text-1);font-size:1.25rem;font-weight:700;outline:none;text-align:center;font-family:inherit;transition:border-color .2s;" placeholder="Tutar (USD)" min="1" max="10000" step="1" oninput="updateAmount()" onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            
            <div style="background:var(--bg-section);border-radius:10px;padding:12px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;border:1.5px solid var(--border-light);">
                <span style="color:var(--text-3);font-size:.875rem;">Yüklenecek Tutar</span>
                <span style="font-weight:800;color:var(--text-1);font-size:1.1rem;">$<span id="amountValue">0</span></span>
            </div>
            
            <div style="display:flex;gap:12px;">
                <button onclick="closeTopupModal()" style="flex:1;background:var(--bg-section);border:1.5px solid var(--border);color:var(--text-2);padding:12px;border-radius:12px;font-weight:700;cursor:pointer;transition:background .14s;">VAZGEÇ</button>
                <button id="topupPayBtn" onclick="redirectToFleeca()" disabled style="flex:1;background:#10b981;color:#fff;border:none;padding:12px;border-radius:12px;font-weight:700;box-shadow:0 4px 16px rgba(16,185,129,0.25);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .14s;opacity:0.5;" >
                    <span class="material-symbols-outlined" style="font-size:18px;">lock</span> ÖDE
                </button>
            </div>
            
            <p style="font-size:10px;color:var(--text-3);margin-top:24px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">
                (( ÖDEMENİZ FLEECA BANKING GTAW ALTYAPISI İLE GERÇEKLEŞTİRİLECEKTİR ))
            </p>
        </div>
    </div>
</div>

<!-- Para Çek Modal -->
<div class="topup-overlay" id="withdrawOverlay" onclick="closeWithdrawModal(event)">
    <div style="background:#fff;border:1.5px solid var(--border);border-radius:20px;width:90%;max-width:440px;overflow:hidden;box-shadow:0 24px 48px rgba(0,0,0,0.18);position:relative;" onclick="event.stopPropagation()">
        <button onclick="closeWithdrawModal()" style="position:absolute;top:14px;right:14px;background:none;border:none;cursor:pointer;color:var(--text-3);"><span class="material-symbols-outlined">close</span></button>
        
        <form method="POST" style="padding:36px 32px;text-align:center;margin:0;">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="withdraw">
            
            <div style="width:64px;height:64px;margin:0 auto 16px;background:rgba(240,109,31,0.1);color:var(--color-primary);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <span class="material-symbols-outlined" style="font-size:32px;">account_balance</span>
            </div>
            <h2 style="font-size:1.5rem;font-weight:800;color:var(--text-1);margin-bottom:8px;">Para Çek</h2>
            <p style="color:var(--text-3);font-size:.875rem;margin-bottom:24px;">Cüzdanınızdaki bakiyeyi banka hesabınıza aktarın.</p>
            
            <div style="position:relative;margin-bottom:24px;">
                <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-3);font-weight:700;font-size:1.25rem;">$</span>
                <input type="number" name="amount" id="withdrawAmount" style="width:100%;box-sizing:border-box;background:var(--bg-input);border:1.5px solid var(--border);border-radius:12px;padding:14px 14px 14px 36px;color:var(--text-1);font-size:1.25rem;font-weight:700;outline:none;text-align:center;font-family:inherit;transition:border-color .2s;" placeholder="Tutar (USD)" min="10000" max="<?php echo (int)$balance; ?>" step="1" oninput="updateWithdrawAmount()" required onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            </div>

            <div style="margin-bottom:24px;text-align:left;">
                <label style="display:block;font-size:0.85rem;font-weight:700;color:var(--text-2);margin-bottom:8px;">Hesap / IBAN / Cüzdan Bilgisi</label>
                <input type="text" name="account_info" style="width:100%;box-sizing:border-box;background:var(--bg-input);border:1.5px solid var(--border);border-radius:12px;padding:14px;color:var(--text-1);font-size:1rem;font-weight:500;outline:none;font-family:inherit;transition:border-color .2s;" placeholder="Örn: TR12 3456 7890 1234 5678 90" required onfocus="this.style.borderColor='var(--color-primary)'" onblur="this.style.borderColor='var(--border)'">
            </div>
            
            <div style="background:var(--bg-section);border-radius:10px;padding:12px;margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;border:1.5px solid var(--border-light);">
                <span style="color:var(--text-3);font-size:.875rem;">Çekilecek Tutar</span>
                <span style="font-weight:800;color:var(--text-1);font-size:1.1rem;">$<span id="withdrawValue">0</span></span>
            </div>
            
            <div style="display:flex;gap:12px;">
                <button type="button" onclick="closeWithdrawModal()" style="flex:1;background:var(--bg-section);border:1.5px solid var(--border);color:var(--text-2);padding:12px;border-radius:12px;font-weight:700;cursor:pointer;transition:background .14s;">VAZGEÇ</button>
                <button id="withdrawBtn" type="submit" disabled style="flex:1;background:var(--color-primary);color:#fff;border:none;padding:12px;border-radius:12px;font-weight:700;box-shadow:0 4px 16px rgba(240,109,31,0.25);cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .14s;opacity:0.5;">
                    <span class="material-symbols-outlined" style="font-size:18px;">outbox</span> ÇEK
                </button>
            </div>
            
            <p style="font-size:10px;color:var(--text-3);margin-top:24px;text-transform:uppercase;letter-spacing:.08em;font-weight:700;">
                (( Çekim talebiniz incelendikten sonra belirtilen hesaba aktarılacaktır. ))
            </p>
        </form>
    </div>
</div>
</div><!-- /grid cell -->

<script>
function openTopupModal() {
    document.getElementById('topupOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('topupAmount').value = '';
    updateAmount();
}

function closeTopupModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('topupOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function updateAmount() {
    const amount = parseInt(document.getElementById('topupAmount').value) || 0;
    document.getElementById('amountValue').textContent = amount.toLocaleString('tr-TR');
    document.getElementById('topupPayBtn').disabled = amount <= 0;
}

async function redirectToFleeca() {
    const amount = parseInt(document.getElementById('topupAmount').value) || 0;
    if (amount <= 0) return;

    const btn = document.getElementById('topupPayBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> İşleniyor...';

    try {
        const formData = new FormData();
        formData.append('csrf_token', App.csrfToken);
        formData.append('amount', amount);

        const res = await App.post(App.baseUrl + '/api/fleeca-payment', formData);

        if (res.ok && res.data && res.data.gateway_url) {
            btn.innerHTML = '<span class="material-symbols-outlined animate-spin">progress_activity</span> Yönlendiriliyor...';
            window.location.href = res.data.gateway_url;
        } else {
            App.flash(res.error || 'Token üretilemedi. Lütfen tekrar deneyin.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">lock</span> ÖDE';
        }
    } catch (err) {
        App.flash('Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">lock</span> ÖDE';
    }
}

function openWithdrawModal() {
    document.getElementById('withdrawOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('withdrawAmount').value = '';
    updateWithdrawAmount();
}

function closeWithdrawModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('withdrawOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function updateWithdrawAmount() {
    const amount = parseInt(document.getElementById('withdrawAmount').value) || 0;
    const maxVal = parseInt(document.getElementById('withdrawAmount').max) || 0;
    document.getElementById('withdrawValue').textContent = amount.toLocaleString('tr-TR');
    document.getElementById('withdrawBtn').disabled = amount <= 0 || amount > maxVal;
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeTopupModal();
        closeWithdrawModal();
    }
});
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
