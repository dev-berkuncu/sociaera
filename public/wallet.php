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

<style>
/* Modal CSS since it is a floating overlay */
.topup-overlay {
    position: fixed; inset: 0; background: rgba(11, 19, 38, 0.85); backdrop-filter: blur(8px);
    z-index: 9999; display: flex; align-items: center; justify-content: center;
    opacity: 0; pointer-events: none; transition: opacity 0.3s ease;
}
.topup-overlay.active { opacity: 1; pointer-events: auto; }
</style>

<section class="flex-1 flex flex-col gap-stack-md max-w-2xl w-full mx-auto lg:mx-0">
    <div class="mb-2">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface"><span class="material-symbols-outlined text-primary-container text-[32px]">account_balance_wallet</span> Cüzdan</h1>
    </div>

    <?php if ($paymentMsg): ?>
        <div class="px-4 py-3 rounded-lg mb-2 flex items-center gap-3 <?php echo $paymentSuccess === 'success' ? 'bg-[#10b981]/10 border border-[#10b981]/50 text-[#10b981]' : 'bg-error/10 border border-error/50 text-error'; ?>">
            <span class="material-symbols-outlined"><?php echo $paymentSuccess === 'success' ? 'check_circle' : 'error'; ?></span>
            <span><?php echo escape($paymentMsg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Balance Card -->
    <div class="bg-gradient-to-br from-primary-container to-[#ff9e7d] rounded-2xl p-8 shadow-[0_20px_40px_-15px_rgba(255,107,53,0.4)] text-white relative overflow-hidden">
        <div class="absolute -right-10 -top-10 text-[150px] opacity-10 leading-none select-none">
            <span class="material-symbols-outlined" style="font-size:inherit;">account_balance_wallet</span>
        </div>
        <div class="relative z-10">
            <div class="text-white/80 font-semibold mb-1 tracking-wider uppercase text-sm">Mevcut Bakiye</div>
            <div class="text-5xl font-black mb-6">$<?php echo number_format($balance, 2, ',', '.'); ?></div>
            <button onclick="openTopupModal()" class="bg-white text-primary-container px-6 py-3 rounded-full font-bold shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all flex items-center gap-2 w-fit">
                <span class="material-symbols-outlined">add_circle</span> Bakiye Yükle
            </button>
        </div>
    </div>

    <h2 class="text-xl font-bold text-on-surface mt-6 flex items-center gap-2"><span class="material-symbols-outlined text-primary-container">receipt_long</span> İşlem Geçmişi</h2>
    
    <div class="bg-[#1E293B]/80 backdrop-blur-[20px] border border-white/10 rounded-xl overflow-hidden shadow-[0_15px_30px_-15px_rgba(15,23,42,0.3)]">
        <?php if (empty($transactions)): ?>
            <div class="p-8 text-center text-slate-400">
                <span class="material-symbols-outlined text-[48px] mb-2 opacity-50">receipt</span>
                <p>Henüz işlem yok.</p>
            </div>
        <?php else: ?>
            <div class="flex flex-col">
                <?php foreach ($transactions as $tx):
                    $isIn = in_array($tx['type'], ['deposit', 'transfer_in']);
                ?>
                <div class="flex items-center gap-4 p-4 border-b border-white/5 hover:bg-white/5 transition-colors last:border-0">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 <?php echo $isIn ? 'bg-[#10b981]/20 text-[#10b981]' : 'bg-error/20 text-error'; ?>">
                        <span class="material-symbols-outlined"><?php echo $isIn ? 'south_west' : 'north_east'; ?></span>
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="font-bold text-on-surface truncate"><?php echo escape($tx['description'] ?: $tx['type']); ?></div>
                        <div class="text-xs text-slate-400"><?php echo formatDate($tx['created_at'], true); ?></div>
                    </div>
                    <div class="font-black text-lg flex-shrink-0 <?php echo $isIn ? 'text-[#10b981]' : 'text-error'; ?>">
                        <?php echo $isIn ? '+' : '-'; ?>$<?php echo number_format($tx['amount'], 2, ',', '.'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Bakiye Yükle Modal -->
<div class="topup-overlay" id="topupOverlay" onclick="closeTopupModal(event)">
    <div class="bg-[#1E293B] border border-white/10 rounded-2xl w-[90%] max-w-md overflow-hidden shadow-2xl relative" onclick="event.stopPropagation()">
        <button onclick="closeTopupModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white"><span class="material-symbols-outlined">close</span></button>
        
        <div class="p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-primary-container/20 text-primary-container rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-[32px]">payments</span>
            </div>
            <h2 class="text-2xl font-bold text-white mb-2">Bakiye Yükle</h2>
            <p class="text-slate-400 text-sm mb-6">Güvenli Fleeca altyapısı ile bakiyenizi yükleyin.</p>
            
            <div class="relative mb-6">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xl">$</span>
                <input type="number" id="topupAmount" class="w-full bg-background border border-white/10 rounded-xl pl-8 pr-4 py-4 text-white text-xl font-bold focus:border-primary-container focus:outline-none transition-colors text-center" placeholder="Tutar (USD)" min="1" max="10000" step="1" oninput="updateAmount()">
            </div>
            
            <div class="bg-surface-container rounded-lg p-3 mb-6 text-sm flex justify-between items-center border border-white/5">
                <span class="text-slate-400">Yüklenecek Tutar</span>
                <span class="font-bold text-white text-lg">$<span id="amountValue">0.00</span></span>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closeTopupModal()" class="flex-1 bg-white/10 hover:bg-white/20 text-white py-3 rounded-xl font-bold transition-colors border border-white/10">VAZGEÇ</button>
                <button id="topupPayBtn" onclick="redirectToFleeca()" disabled class="flex-1 bg-[#10b981] text-white py-3 rounded-xl font-bold shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:bg-[#059669] transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-[18px]">lock</span> ÖDE
                </button>
            </div>
            
            <p class="text-[10px] text-slate-500 mt-6 uppercase tracking-wider font-bold">
                (( ÖDEMENİZ FLEECA BANKING GTAW ALTYAPISI İLE GERÇEKLEŞTİRİLECEKTİR ))
            </p>
        </div>
    </div>
</div>

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
    const amount = parseFloat(document.getElementById('topupAmount').value) || 0;
    document.getElementById('amountValue').textContent = amount.toFixed(2);
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
            btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">lock</span> ÖDE';
        }
    } catch (err) {
        App.flash('Bağlantı hatası. Lütfen tekrar deneyin.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined text-[18px]">lock</span> ÖDE';
    }
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeTopupModal();
});
</script>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
