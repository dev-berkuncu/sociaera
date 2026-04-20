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

Auth::requireLogin();

$walletModel = new WalletModel();
$walletModel->ensureWallet(Auth::id());
$balance = $walletModel->getBalance(Auth::id());
$transactions = $walletModel->getTransactions(Auth::id());

$pageTitle = 'Cüzdan';
$activeNav = 'wallet';
require_once __DIR__ . '/partials/header.php';
require_once __DIR__ . '/partials/navbar.php';
require_once __DIR__ . '/partials/flash.php';
?>

<div class="app-layout">
    <main class="main-feed" style="max-width:640px; margin:0 auto;">
        <div class="page-header">
            <h1><i class="bi bi-wallet2" style="color:var(--primary)"></i> Cüzdan</h1>
        </div>

        <div class="wallet-balance-card">
            <div class="wallet-label">Mevcut Bakiye</div>
            <div class="wallet-balance"><?php echo number_format($balance, 2, ',', '.'); ?> <span class="wallet-currency">SparkC</span></div>
            <button class="wallet-topup-btn" onclick="openTopupModal()">
                <i class="bi bi-plus-circle-fill"></i> Bakiye Yükle
            </button>
        </div>

        <h2 style="font-size:1.1rem; font-weight:700; margin-bottom:12px;">İşlem Geçmişi</h2>
        <div class="card-box" style="overflow:hidden;">
            <?php if (empty($transactions)): ?>
                <div class="empty-state"><i class="bi bi-receipt"></i><p>Henüz işlem yok.</p></div>
            <?php else: ?>
                <?php foreach ($transactions as $tx):
                    $isIn = in_array($tx['type'], ['deposit', 'transfer_in']);
                ?>
                <div class="notif-item" style="cursor:default;">
                    <div class="notif-icon <?php echo $isIn ? 'follow' : 'like'; ?>">
                        <i class="bi bi-<?php echo $isIn ? 'arrow-down-left' : 'arrow-up-right'; ?>"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:600; font-size:0.9rem;"><?php echo escape($tx['description'] ?: $tx['type']); ?></div>
                        <div style="font-size:0.78rem; color:var(--text-muted);"><?php echo formatDate($tx['created_at'], true); ?></div>
                    </div>
                    <div style="font-weight:700; color:<?php echo $isIn ? 'var(--success)' : 'var(--error)'; ?>;">
                        <?php echo $isIn ? '+' : '-'; ?><?php echo number_format($tx['amount'], 2, ',', '.'); ?> SC
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Bakiye Yükle Modal -->
<div class="topup-overlay" id="topupOverlay" onclick="closeTopupModal(event)">
    <div class="topup-modal" onclick="event.stopPropagation()">
        <div class="topup-icon">
            <i class="bi bi-cash-stack"></i>
        </div>
        <h2 class="topup-title">Bakiye Yükle</h2>
        <p class="topup-desc">
            Güvenli Fleeca altyapısı ile bakiyenizi yükleyin.
            <br><strong>1 USD = 1 SparkC</strong>
        </p>
        <div class="topup-input-group">
            <input type="number" id="topupAmount" class="topup-input" placeholder="Tutar (USD)" min="1" max="10000" step="1" oninput="updateSparkC()">
        </div>
        <div class="topup-sparkc-bar" id="sparkcBar">
            Kazanılacak: <span id="sparkcValue">0</span> SPARKC
        </div>
        <div class="topup-actions">
            <button class="topup-cancel-btn" onclick="closeTopupModal()">VAZGEÇ</button>
            <button class="topup-pay-btn" id="topupPayBtn" onclick="processFleecaPayment()" disabled>
                <i class="bi bi-lock-fill"></i> FLEECA İLE ÖDE
            </button>
        </div>
        <p class="topup-footnote">
            (( ÖDEMENİZ FLEECA BANKİNG GTAW ALTYAPISI İLE GERÇEKLEŞTİRİLECEKTİR ))
        </p>
    </div>
</div>

<script>
function openTopupModal() {
    document.getElementById('topupOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
    document.getElementById('topupAmount').value = '';
    updateSparkC();
}

function closeTopupModal(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('topupOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

function updateSparkC() {
    const amount = parseFloat(document.getElementById('topupAmount').value) || 0;
    document.getElementById('sparkcValue').textContent = amount;
    document.getElementById('topupPayBtn').disabled = amount <= 0;

    const bar = document.getElementById('sparkcBar');
    if (amount > 0) {
        bar.classList.add('has-value');
    } else {
        bar.classList.remove('has-value');
    }
}

function processFleecaPayment() {
    const amount = parseFloat(document.getElementById('topupAmount').value) || 0;
    if (amount <= 0) return;

    // Fleeca Banking ödeme sayfasına yönlendir
    App.flash('Fleeca Banking ödeme sistemi yakında aktif olacak.', 'info');
    closeTopupModal();
}

// ESC ile kapatma
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeTopupModal();
});
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
