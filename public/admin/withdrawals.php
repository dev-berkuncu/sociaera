<?php
/**
 * Admin Panel â€” Para Ã‡ekme Talepleri
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Wallet.php';
require_once __DIR__ . '/../../app/Models/Notification.php';

Auth::requireAccess('withdrawals');

$db = Database::getConnection();
$walletModel = new WalletModel();
$notifModel = new NotificationModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $adminNote = trim($_POST['admin_note'] ?? '');

    $stmt = $db->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    $req = $stmt->fetch();

    if ($req) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE withdrawal_requests SET status = 'approved', admin_note = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$adminNote, $id]);
            
            $notifModel->create(
                $req['user_id'], 
                Auth::id(), 
                'wallet', 
                "Para Ã§ekim talebiniz onaylandÄ± ve ".number_format($req['amount'])."$ banka hesabÄ±nÄ±za transfer edildi."
            );
            Auth::setFlash('success', "Talep onaylandÄ± ve tamamlandÄ±.");

        } elseif ($action === 'reject') {
            try {
                $db->beginTransaction();

                $stmt = $db->prepare("UPDATE withdrawal_requests SET status = 'rejected', admin_note = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$adminNote, $id]);

                // Bakiye iadesi
                $walletModel->deposit($req['user_id'], $req['amount'], "Para Ã‡ekim Talebi Reddedildi (Ä°ade) - #" . $id);

                $notifModel->create(
                    $req['user_id'], 
                    Auth::id(), 
                    'wallet', 
                    "Para Ã§ekim talebiniz reddedildi. Kesilen tutar cÃ¼zdanÄ±nÄ±za iade edildi. Sebep: " . htmlspecialchars($adminNote)
                );
                
                $db->commit();
                Auth::setFlash('success', "Talep reddedildi ve bakiye kullanÄ±cÄ±ya iade edildi.");
            } catch (\Exception $e) {
                $db->rollBack();
                Auth::setFlash('error', "Red iÅŸlemi sÄ±rasÄ±nda bir hata oluÅŸtu: " . $e->getMessage());
            }
        }
    } else {
        Auth::setFlash('error', "GeÃ§ersiz veya iÅŸlenmiÅŸ talep.");
    }
    header("Location: " . BASE_URL . "/admin/withdrawals");
    exit;
}

// Talepleri getir
$page = (int)($_GET['p'] ?? 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;

$filter = $_GET['status'] ?? 'pending';
$statusCondition = "";
$params = [];
if (in_array($filter, ['pending', 'approved', 'rejected'])) {
    $statusCondition = "WHERE wr.status = ?";
    $params[] = $filter;
}

$stmt = $db->prepare("SELECT COUNT(*) FROM withdrawal_requests wr $statusCondition");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare("
    SELECT wr.*, u.username, u.avatar 
    FROM withdrawal_requests wr 
    JOIN users u ON wr.user_id = u.id 
    $statusCondition 
    ORDER BY wr.created_at DESC 
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Para Ã‡ekme Talepleri';
$adminPage = 'withdrawals';
require_once __DIR__ . '/_header.php';
?>

<div class="admin-section-header">
    <h1 class="admin-page-title">
        <span class="material-symbols-outlined" style="color:var(--cp);font-size:22px;" data-fill="1">payments</span>
        Para Ã‡ekme Talepleri
    </h1>
</div>

<div class="admin-table-card" style="padding:16px;">
    <div style="display:flex;gap:12px;margin-bottom:16px;">
        <a href="?status=pending" class="btn-admin <?php echo $filter==='pending' ? 'btn-admin-primary' : 'btn-admin-ghost'; ?>">Bekleyenler</a>
        <a href="?status=approved" class="btn-admin <?php echo $filter==='approved' ? 'btn-admin-primary' : 'btn-admin-ghost'; ?>">Onaylananlar</a>
        <a href="?status=rejected" class="btn-admin <?php echo $filter==='rejected' ? 'btn-admin-primary' : 'btn-admin-ghost'; ?>">Reddedilenler</a>
        <a href="?" class="btn-admin <?php echo empty($_GET['status']) ? 'btn-admin-primary' : 'btn-admin-ghost'; ?>">TÃ¼mÃ¼</a>
    </div>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;text-align:left;">
            <thead>
                <tr style="border-bottom:1.5px solid var(--border);color:var(--t2);">
                    <th style="padding:12px;">KullanÄ±cÄ±</th>
                    <th style="padding:12px;">Tutar</th>
                    <th style="padding:12px;">Hesap / IBAN</th>
                    <th style="padding:12px;">Durum</th>
                    <th style="padding:12px;">Tarih</th>
                    <th style="padding:12px;text-align:right;">Ä°ÅŸlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                <tr style="border-bottom:1px solid var(--border-l);">
                    <td style="padding:12px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <img src="<?php echo safeAvatarUrl($r['avatar'], $r['username']); ?>" style="width:28px;height:28px;border-radius:50%;">
                            <a href="<?php echo BASE_URL . '/admin/users?q=' . escape($r['username']); ?>" class="admin-table-link">
                                @<?php echo escape($r['username']); ?>
                            </a>
                        </div>
                    </td>
                    <td style="padding:12px;font-weight:700;">$<?php echo number_format($r['amount'],2); ?></td>
                    <td style="padding:12px;font-family:monospace;"><?php echo escape($r['account_info']); ?></td>
                    <td style="padding:12px;">
                        <?php if($r['status'] === 'pending'): ?>
                            <span class="badge badge-warning">Bekliyor</span>
                        <?php elseif($r['status'] === 'approved'): ?>
                            <span class="badge badge-success">OnaylandÄ±</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Reddedildi</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:12px;color:var(--t2);"><?php echo date('d.m.Y H:i', strtotime($r['created_at'])); ?></td>
                    <td style="padding:12px;text-align:right;">
                        <?php if($r['status'] === 'pending'): ?>
                            <button onclick="openActionModal(<?php echo $r['id']; ?>, 'approve', <?php echo $r['amount']; ?>)" class="btn-admin btn-admin-primary btn-admin-sm">Onayla</button>
                            <button onclick="openActionModal(<?php echo $r['id']; ?>, 'reject', <?php echo $r['amount']; ?>)" class="btn-admin btn-admin-danger btn-admin-sm">Reddet</button>
                        <?php else: ?>
                            <span style="color:var(--t3);font-size:11px;"><?php echo escape($r['admin_note'] ?: '-'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($requests)): ?>
                <tr>
                    <td colspan="6" style="padding:24px;text-align:center;color:var(--t3);">KayÄ±t bulunamadÄ±.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="actionModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;padding:24px;border-radius:14px;width:90%;max-width:400px;">
        <h3 id="modalTitle" style="margin-top:0;">Talebi Ä°ÅŸle</h3>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="id" id="modalId">
            <input type="hidden" name="action" id="modalAction">
            
            <label class="admin-label">YÃ¶netici Notu (KullanÄ±cÄ±ya Ä°letilecek)</label>
            <textarea name="admin_note" class="admin-input" rows="3" placeholder="Ã–rn: Ä°ÅŸlem baÅŸarÄ±lÄ± / IBAN hatalÄ±..." required></textarea>
            
            <div style="display:flex;gap:12px;margin-top:16px;">
                <button type="button" onclick="document.getElementById('actionModal').style.display='none'" class="btn-admin btn-admin-ghost" style="flex:1;justify-content:center;">VazgeÃ§</button>
                <button type="submit" id="modalSubmitBtn" class="btn-admin btn-admin-primary" style="flex:1;justify-content:center;">Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
function openActionModal(id, action, amount) {
    document.getElementById('modalId').value = id;
    document.getElementById('modalAction').value = action;
    
    const title = document.getElementById('modalTitle');
    const btn = document.getElementById('modalSubmitBtn');
    
    if(action === 'approve') {
        title.innerHTML = 'Talebi Onayla ($' + amount + ')';
        btn.className = 'btn-admin btn-admin-primary';
        btn.innerHTML = 'Onayla';
    } else {
        title.innerHTML = 'Talebi Reddet ve Ä°ade Et';
        btn.className = 'btn-admin btn-admin-danger';
        btn.innerHTML = 'Reddet (Bakiye Ä°ade Edilir)';
    }
    
    document.getElementById('actionModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/_footer.php'; ?>
