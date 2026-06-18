<?php
/**
 * Admin — Cüzdan & Ödemeler
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/Wallet.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Report.php';

Auth::requireAccess('wallet');
$db = Database::getConnection();

// Manuel düzeltme POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    if ($action === 'adjustment') {
        $targetUserId = (int)($_POST['user_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $type = $_POST['type'] ?? 'deposit';
        $description = trim($_POST['description'] ?? '');

        if ($targetUserId && $amount > 0 && $description) {
            $walletModel = new WalletModel();
            $walletModel->ensureWallet($targetUserId);
            $oldBalance = $walletModel->getBalance($targetUserId);

            if ($type === 'deposit') {
                $walletModel->deposit($targetUserId, $amount, "[ADMIN] " . $description);
            } else {
                $walletModel->withdraw($targetUserId, $amount, "[ADMIN] " . $description);
            }
            $newBalance = $walletModel->getBalance($targetUserId);
            Logger::adminAudit('wallet_adjustment', 'user', $targetUserId, $description, (string)$oldBalance, (string)$newBalance);
            Auth::setFlash('success', 'Bakiye düzeltmesi uygulandı.');
        } else {
            Auth::setFlash('error', 'Tüm alanları doldurun.');
        }
        header('Location: ' . BASE_URL . '/admin/wallet'); exit;
    } elseif ($action === 'approve_withdrawal') {
        $txId = (int)($_POST['transaction_id'] ?? 0);
        if ($txId) {
            $txStmt = $db->prepare("SELECT * FROM transactions WHERE id = ? AND type = 'withdraw' AND status = 'pending'");
            $txStmt->execute([$txId]);
            $tx = $txStmt->fetch();
            if ($tx) {
                // Update transaction status to approved
                $upStmt = $db->prepare("UPDATE transactions SET status = 'approved' WHERE id = ?");
                $upStmt->execute([$txId]);
                
                // Send notification
                $notifModel = new NotificationModel();
                $refCode = $tx['reference_id'] ?: ('W-' . $txId);
                $notifModel->create(
                    $tx['user_id'],
                    null,
                    'wallet',
                    "Para çekme talebiniz (Ref: {$refCode}, Tutar: $" . number_format($tx['amount'], 0, ',', '.') . ") onaylandı ve banka hesabınıza gönderildi. 💸"
                );
                
                Logger::adminAudit('approve_withdrawal', 'transaction', $txId, "Approved withdrawal of $" . $tx['amount'] . " for user " . $tx['user_id']);
                Auth::setFlash('success', 'Çekim talebi onaylandı ve kullanıcıya bildirim gönderildi.');
            } else {
                Auth::setFlash('error', 'Bekleyen çekim işlemi bulunamadı.');
            }
        }
        header('Location: ' . BASE_URL . '/admin/wallet'); exit;
    } elseif ($action === 'reject_withdrawal') {
        $txId = (int)($_POST['transaction_id'] ?? 0);
        $reason = trim($_POST['reject_reason'] ?? '');
        if (empty($reason)) {
            Auth::setFlash('error', 'Lütfen bir reddetme nedeni girin.');
            header('Location: ' . BASE_URL . '/admin/wallet'); exit;
        }
        if ($txId) {
            $txStmt = $db->prepare("SELECT * FROM transactions WHERE id = ? AND type = 'withdraw' AND status = 'pending'");
            $txStmt->execute([$txId]);
            $tx = $txStmt->fetch();
            if ($tx) {
                $db->beginTransaction();
                try {
                    // Update transaction status to rejected
                    $upStmt = $db->prepare("UPDATE transactions SET status = 'rejected', description = ? WHERE id = ?");
                    $upStmt->execute(["[RED] " . $reason . " (Eski açıklama: " . $tx['description'] . ")", $txId]);
                    
                    // Refund wallet balance
                    $walletStmt = $db->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
                    $walletStmt->execute([$tx['amount'], $tx['user_id']]);
                    
                    $db->commit();
                    
                    // Send notification
                    $notifModel = new NotificationModel();
                    $refCode = $tx['reference_id'] ?: ('W-' . $txId);
                    $notifModel->create(
                        $tx['user_id'],
                        null,
                        'wallet',
                        "Para çekme talebiniz (Ref: {$refCode}, Tutar: $" . number_format($tx['amount'], 0, ',', '.') . ") reddedildi. Neden: {$reason}. Tutar cüzdanınıza iade edildi."
                    );
                    
                    Logger::adminAudit('reject_withdrawal', 'transaction', $txId, "Rejected withdrawal: " . $reason);
                    Auth::setFlash('success', 'Çekim talebi reddedildi, bakiye iade edildi ve kullanıcıya bildirim gönderildi.');
                } catch (Exception $ex) {
                    $db->rollBack();
                    Auth::setFlash('error', 'Bir hata oluştu: ' . $ex->getMessage());
                }
            } else {
                Auth::setFlash('error', 'Bekleyen çekim işlemi bulunamadı.');
            }
        }
        header('Location: ' . BASE_URL . '/admin/wallet'); exit;
    }
}

// İstatistikler
$totalBalance = (float)$db->query("SELECT COALESCE(SUM(balance),0) FROM wallets")->fetchColumn();
$todayDeposits = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='deposit' AND DATE(created_at)=CURDATE()")->fetchColumn();
$totalDeposits = (int)$db->query("SELECT COUNT(*) FROM transactions WHERE type='deposit'")->fetchColumn();

// Toplam Giriş Miktarı (Tüm yüklemeler / deposit)
$totalDepositsSum = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='deposit'")->fetchColumn();

// Ödenen Miktar (Onaylanmış çekimler)
$totalPaidSum = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='withdraw' AND status='approved'")->fetchColumn();

// Bekleyen Çekim Miktarı (Bekleyen çekimler)
$totalPendingWithdrawSum = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='withdraw' AND status='pending'")->fetchColumn();

// İşlem listesi
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;
$typeFilter = $_GET['type'] ?? '';
$where = "1=1";
$params = [];
if ($typeFilter) { $where .= " AND t.type = ?"; $params[] = $typeFilter; }

$total = (int)$db->prepare("SELECT COUNT(*) FROM transactions t WHERE {$where}")->execute($params) ?
    (int)$db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
// re-query for count
$cStmt = $db->prepare("SELECT COUNT(*) FROM transactions t WHERE {$where}");
$cStmt->execute($params);
$total = (int)$cStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$lParams = array_merge($params, [$perPage, $offset]);
$stmt = $db->prepare("
    SELECT t.*, u.username FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE {$where}
    ORDER BY t.created_at DESC LIMIT ? OFFSET ?
");
$stmt->execute($lParams);
$transactions = $stmt->fetchAll();

// Bekleyen Çekim Talepleri
$pendingWithdrawals = $db->query("
    SELECT t.*, u.username, u.bank_account FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.type = 'withdraw' AND t.status = 'pending'
    ORDER BY t.created_at ASC
")->fetchAll();

$pendingVenues = (new VenueModel())->getPendingCount();
$pageTitle = 'Cüzdan & Ödemeler';
$adminPage = 'wallet';
require_once __DIR__ . '/_header.php';
?>

<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <span class="material-symbols-outlined text-yellow-400 text-[28px] mb-2">account_balance_wallet</span>
        <div class="text-2xl font-black text-on-surface">$<?php echo number_format($totalBalance,0,',','.');?></div>
        <div class="text-label-sm text-slate-400 mt-1">Toplam Bakiye</div>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <span class="material-symbols-outlined text-emerald-400 text-[28px] mb-2">payments</span>
        <div class="text-2xl font-black text-on-surface">$<?php echo number_format($totalDepositsSum,0,',','.');?></div>
        <div class="text-label-sm text-slate-400 mt-1">Toplam Giriş (Yüklemeler)</div>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <span class="material-symbols-outlined text-blue-400 text-[28px] mb-2">check_circle</span>
        <div class="text-2xl font-black text-on-surface">$<?php echo number_format($totalPaidSum,0,',','.');?></div>
        <div class="text-label-sm text-slate-400 mt-1">Ödenen Tutar (Çekim)</div>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <span class="material-symbols-outlined text-amber-500 text-[28px] mb-2">hourglass_empty</span>
        <div class="text-2xl font-black text-on-surface">$<?php echo number_format($totalPendingWithdrawSum,0,',','.');?></div>
        <div class="text-label-sm text-slate-400 mt-1">Bekleyen Çekim Talebi</div>
    </div>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
        <span class="material-symbols-outlined text-indigo-400 text-[28px] mb-2">trending_up</span>
        <div class="text-2xl font-black text-on-surface">$<?php echo number_format($todayDeposits,0,',','.');?></div>
        <div class="text-label-sm text-slate-400 mt-1">Bugün Yüklenen</div>
    </div>
</div>

<?php if(Auth::canWrite()):?>
<!-- Manuel Düzeltme -->
<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-6 mb-6">
    <h3 class="font-bold text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-amber-400 text-[20px]">edit</span> Manuel Bakiye Düzeltmesi
    </h3>
    <form method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken();?>">
        <input type="hidden" name="action" value="adjustment">
        <div><label class="block text-label-md text-slate-400 mb-1">Kullanıcı ID</label>
            <input type="number" name="user_id" required class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm"></div>
        <div><label class="block text-label-md text-slate-400 mb-1">Tip</label>
            <select name="type" class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm">
                <option value="deposit" class="bg-background">Ekleme</option>
                <option value="withdraw" class="bg-background">Çıkarma</option>
            </select></div>
        <div><label class="block text-label-md text-slate-400 mb-1">Tutar</label>
            <input type="number" name="amount" step="0.01" min="0.01" required class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm"></div>
        <div><label class="block text-label-md text-slate-400 mb-1">Açıklama (zorunlu)</label>
            <input type="text" name="description" required placeholder="Düzeltme nedeni..." class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-4 py-2.5 text-sm"></div>
        <button class="bg-primary-container text-white px-6 py-2.5 rounded-lg text-label-md font-semibold hover:bg-primary-container/90 transition-colors">Uygula</button>
    </form>
</div>
<?php endif;?>

<!-- Bekleyen Çekim Talepleri -->
<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-6 mb-6">
    <h3 class="font-bold text-on-surface mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined text-amber-400 text-[20px]">hourglass_empty</span> Bekleyen Para Çekme Talepleri
    </h3>
    <?php if (empty($pendingWithdrawals)): ?>
        <p class="text-slate-400 text-sm">Bekleyen para çekme talebi bulunmuyor.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                    <tr>
                        <th class="px-6 py-3">Kullanıcı</th>
                        <th class="px-6 py-3">Banka Hesap No</th>
                        <th class="px-6 py-3">Tutar</th>
                        <th class="px-6 py-3">Referans</th>
                        <th class="px-6 py-3">Tarih</th>
                        <?php if (Auth::canWrite()): ?>
                            <th class="px-6 py-3 text-right">İşlemler</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <?php foreach ($pendingWithdrawals as $pw): ?>
                        <tr class="hover:bg-white/[0.02] transition-colors">
                            <td class="px-6 py-3 font-semibold text-on-surface">
                                <a href="<?php echo BASE_URL; ?>/admin/user-detail?id=<?php echo $pw['user_id']; ?>" class="hover:text-primary-container">
                                    <?php echo escape($pw['username']); ?>
                                </a>
                            </td>
                            <td class="px-6 py-3 text-slate-300 font-mono"><?php echo escape($pw['bank_account'] ?: 'Belirtilmemiş'); ?></td>
                            <td class="px-6 py-3 font-bold text-red-400">$<?php echo number_format($pw['amount'], 0, ',', '.'); ?></td>
                            <td class="px-6 py-3 text-slate-400 text-xs font-mono"><?php echo escape($pw['reference_id']); ?></td>
                            <td class="px-6 py-3 text-slate-500 text-xs"><?php echo timeAgo($pw['created_at']); ?></td>
                            <?php if (Auth::canWrite()): ?>
                                <td class="px-6 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Onayla Form -->
                                        <form method="POST" class="inline m-0" onsubmit="return confirm('Bu para çekme talebini onaylamak istediğinize emin misiniz?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                            <input type="hidden" name="action" value="approve_withdrawal">
                                            <input type="hidden" name="transaction_id" value="<?php echo $pw['id']; ?>">
                                            <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded text-xs font-bold transition-colors">
                                                Onayla
                                            </button>
                                        </form>
                                        
                                        <!-- Reddet Buton -->
                                        <button onclick="openRejectModal(<?php echo $pw['id']; ?>, '<?php echo escape(addslashes($pw['username'])); ?>', <?php echo $pw['amount']; ?>)" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-xs font-bold transition-colors">
                                            Reddet
                                        </button>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Filtreler -->
<div class="flex gap-2 mb-4 flex-wrap">
    <?php foreach(['' => 'Tümü','deposit'=>'Yükleme','withdraw'=>'Çekim'] as $k=>$l):
        $active=$typeFilter===$k; $cls=$active?'bg-primary-container text-white':'bg-white/5 text-slate-400 hover:bg-white/10';?>
    <a href="?type=<?php echo $k;?>" class="<?php echo $cls;?> px-4 py-1.5 rounded-lg text-label-md font-semibold transition-colors"><?php echo $l;?></a>
    <?php endforeach;?>
</div>

<!-- İşlem Tablosu -->
<div class="bg-[#1E293B]/80 border border-white/10 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.03] text-slate-400 text-label-sm uppercase">
                <tr><th class="px-6 py-3">#</th><th class="px-6 py-3">Kullanıcı</th><th class="px-6 py-3">Tip</th><th class="px-6 py-3">Tutar</th><th class="px-6 py-3">Durum</th><th class="px-6 py-3">Açıklama</th><th class="px-6 py-3">Ref</th><th class="px-6 py-3">Tarih</th></tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php foreach($transactions as $t):?>
                <tr class="hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-3 text-slate-500"><?php echo $t['id'];?></td>
                    <td class="px-6 py-3 font-semibold text-on-surface"><a href="<?php echo BASE_URL;?>/admin/user-detail?id=<?php echo $t['user_id'];?>" class="hover:text-primary-container"><?php echo escape($t['username']);?></a></td>
                    <td class="px-6 py-3"><span class="text-xs px-2 py-1 rounded <?php echo $t['type']==='deposit'?'bg-emerald-500/10 text-emerald-400':'bg-red-500/10 text-red-400';?>"><?php echo escape($t['type']);?></span></td>
                    <td class="px-6 py-3 font-bold <?php echo $t['type']==='deposit'?'text-emerald-400':'text-red-400';?>">$<?php echo number_format($t['amount'],0,',','.');?></td>
                    <td class="px-6 py-3">
                        <?php if ($t['type'] === 'withdraw'): ?>
                            <span class="text-xs px-2 py-1 rounded <?php 
                                echo $t['status'] === 'pending' ? 'bg-amber-500/10 text-amber-400' : 
                                    ($t['status'] === 'approved' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'); 
                            ?>"><?php echo escape($t['status']); ?></span>
                        <?php else: ?>
                            <span class="text-xs px-2 py-1 rounded bg-emerald-500/10 text-emerald-400">approved</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-3 text-slate-400 text-xs max-w-[200px] truncate" title="<?php echo escape($t['description']??''); ?>"><?php echo escape(truncate($t['description']??'',40));?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs font-mono"><?php echo escape(truncate($t['reference_id']??'-',12));?></td>
                    <td class="px-6 py-3 text-slate-500 text-xs"><?php echo timeAgo($t['created_at']);?></td>
                </tr>
                <?php endforeach;?>
            </tbody>
        </table>
    </div>
</div>

<?php if($totalPages>1):?>
<div class="flex justify-center gap-2 mt-6">
    <?php for($p=1;$p<=$totalPages;$p++):$cls=$p===$page?'bg-primary-container text-white':'bg-white/5 text-slate-400 hover:bg-white/10';?>
    <a href="?type=<?php echo escape($typeFilter);?>&page=<?php echo $p;?>" class="<?php echo $cls;?> px-3 py-1.5 rounded-lg text-label-md"><?php echo $p;?></a>
    <?php endfor;?>
</div>
<?php endif;?>

<!-- Reddetme Modalı -->
<div id="rejectModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div class="bg-[#1E293B] border border-white/10 w-full max-w-md p-6 rounded-2xl shadow-2xl relative">
        <button onclick="closeRejectModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white">
            <span class="material-symbols-outlined">close</span>
        </button>
        <h3 class="text-xl font-bold text-on-surface mb-4">Talebi Reddet</h3>
        <p class="text-slate-400 text-sm mb-4">
            <span id="rejectUserName" class="text-white font-semibold"></span> kullanıcısının 
            <span id="rejectAmount" class="text-white font-semibold"></span> tutarındaki çekim talebini reddetmek üzeresiniz.
        </p>
        <form method="POST" id="rejectForm">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="action" value="reject_withdrawal">
            <input type="hidden" name="transaction_id" id="rejectTxId">
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-300 mb-1">Red Nedeni</label>
                <textarea name="reject_reason" required rows="3" placeholder="Örn: Geçersiz hesap numarası, yetersiz oyun içi bakiye..." class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2 text-on-surface focus:outline-none focus:border-primary-container"></textarea>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeRejectModal()" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2 rounded-xl transition-colors">İptal</button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-xl font-bold transition-colors">Reddet ve İade Et</button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(txId, username, amount) {
    document.getElementById('rejectTxId').value = txId;
    document.getElementById('rejectUserName').textContent = username;
    document.getElementById('rejectAmount').textContent = '$' + amount.toFixed(2);
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
