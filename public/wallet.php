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
            <div class="wallet-balance">$<?php echo number_format($balance, 2, ',', '.'); ?></div>
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
                        <?php echo $isIn ? '+' : '-'; ?>$<?php echo number_format($tx['amount'], 2, ',', '.'); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
