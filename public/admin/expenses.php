<?php
/**
 * Admin — Platform Giderleri
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';

Auth::requireAccess('dashboard'); // Assuming any admin who can see dashboard can manage expenses? Or maybe specific 'expenses' access? Let's use Auth::canWrite() and super_admin for safety or just dashboard since it's an admin page. We'll use 'dashboard' and restrict writes.

$db = Database::getConnection();

// Tablo yoksa otomatik oluştur (Güvenlik için)
$db->exec("
CREATE TABLE IF NOT EXISTS `platform_expenses` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `expense_date` DATE NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::canWrite()) {
    Csrf::requireValid();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $amount = (float)($_POST['amount'] ?? 0);
        $date = $_POST['expense_date'] ?? date('Y-m-d');
        
        if ($title && $amount > 0) {
            $stmt = $db->prepare("INSERT INTO platform_expenses (admin_id, title, amount, expense_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([Auth::id(), $title, $amount, $date]);
            Logger::adminAudit('add_expense', 'expense', $db->lastInsertId(), "Gider: $title ($amount)");
            Auth::setFlash('success', 'Gider başarıyla eklendi.');
        } else {
            Auth::setFlash('error', 'Lütfen geçerli bir başlık ve tutar girin.');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['expense_id'] ?? 0);
        if ($id && Auth::user()['admin_role'] === 'super_admin') {
            $stmt = $db->prepare("DELETE FROM platform_expenses WHERE id = ?");
            $stmt->execute([$id]);
            Logger::adminAudit('delete_expense', 'expense', $id);
            Auth::setFlash('success', 'Gider kaydı silindi.');
        } else {
            Auth::setFlash('error', 'Bu işlem için Super Admin yetkisi gereklidir.');
        }
    }
    
    header('Location: ' . BASE_URL . '/admin/expenses');
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Toplam kayıt sayısı
$total = (int)$db->query("SELECT COUNT(*) FROM platform_expenses")->fetchColumn();
$pages = ceil($total / $limit);

// Listeyi çek
$stmt = $db->prepare("SELECT e.*, u.username as admin_name FROM platform_expenses e LEFT JOIN users u ON e.admin_id = u.id ORDER BY e.expense_date DESC, e.id DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$expenses = $stmt->fetchAll();

// İstatistikler
$monthlyExpenses = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM platform_expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())")->fetchColumn();
$totalExpenses = (float) $db->query("SELECT COALESCE(SUM(amount), 0) FROM platform_expenses")->fetchColumn();

$pageTitle = 'Platform Giderleri';
$adminPage = 'expenses';
require_once __DIR__ . '/_header.php';
?>

<div class="admin-section-header">
    <h1 class="admin-page-title">
        <span class="material-symbols-outlined" style="color:var(--cp);font-size:22px;" data-fill="1">receipt_long</span>
        Platform Giderleri
    </h1>
    <div style="display:flex;align-items:center;gap:12px;">
        <span style="font-size:12px;color:var(--t3);"><?php echo date('d M Y, H:i'); ?></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <div class="lg:col-span-1">
        <div class="grid grid-cols-1 gap-4 mb-6">
            <div class="admin-stat-card border-red-500/20 bg-red-50">
                <span class="material-symbols-outlined text-red-500 mb-1">trending_down</span>
                <div class="admin-stat-value text-red-600">$<?php echo number_format($monthlyExpenses, 2); ?></div>
                <div class="admin-stat-label text-red-400">Bu Ayki Toplam Gider</div>
            </div>
            <div class="admin-stat-card border-red-500/20 bg-red-50">
                <span class="material-symbols-outlined text-red-500 mb-1">money_off</span>
                <div class="admin-stat-value text-red-600">$<?php echo number_format($totalExpenses, 2); ?></div>
                <div class="admin-stat-label text-red-400">Genel Toplam Gider</div>
            </div>
        </div>

        <?php if (Auth::canWrite()): ?>
        <div class="bg-white rounded-xl border border-[var(--border)] shadow-sm p-6">
            <h2 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined">add_circle</span> Yeni Gider Ekle
            </h2>
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Gider Başlığı / Açıklama</label>
                    <input type="text" name="title" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-primary focus:ring-primary text-sm" placeholder="Örn: Avukat Masrafı, Sunucu Gideri" required>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tutar ($)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-primary focus:ring-primary text-sm" placeholder="0.00" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Tarih</label>
                    <input type="date" name="expense_date" value="<?php echo date('Y-m-d'); ?>" class="w-full rounded-lg border-slate-200 bg-slate-50 focus:border-primary focus:ring-primary text-sm" required>
                </div>
                
                <button type="submit" class="w-full btn-admin btn-admin-primary flex justify-center py-2.5">
                    Gideri Kaydet
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="bg-slate-50 text-slate-500 p-4 rounded-xl text-center text-sm border border-slate-200">
            Gider eklemek için yetkiniz bulunmuyor.
        </div>
        <?php endif; ?>
    </div>
    
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-[var(--border)] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[var(--border)] bg-slate-50 flex justify-between items-center">
                <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Son Giderler</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-[var(--border)] text-xs uppercase text-slate-500 font-semibold tracking-wider">
                            <th class="px-6 py-3">Tarih</th>
                            <th class="px-6 py-3">Açıklama</th>
                            <th class="px-6 py-3">Tutar</th>
                            <th class="px-6 py-3">Ekleyen</th>
                            <th class="px-6 py-3 text-right">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <?php if(empty($expenses)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-500">
                                Henüz hiçbir gider kaydı eklenmemiş.
                            </td>
                        </tr>
                        <?php else: foreach($expenses as $e): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-3 text-sm font-medium text-slate-700">
                                <?php echo date('d M Y', strtotime($e['expense_date'])); ?>
                            </td>
                            <td class="px-6 py-3 text-sm text-slate-800">
                                <?php echo escape($e['title']); ?>
                                <div class="text-[10px] text-slate-400 mt-0.5">Eklenme: <?php echo date('d.m.Y H:i', strtotime($e['created_at'])); ?></div>
                            </td>
                            <td class="px-6 py-3 text-sm font-bold text-red-600">
                                -$<?php echo number_format($e['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-3 text-xs text-slate-500">
                                <?php echo escape($e['admin_name'] ?? 'Bilinmiyor'); ?>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <?php if((Auth::user()['admin_role'] ?? '') === 'super_admin'): ?>
                                <form method="POST" class="inline" onsubmit="return confirm('Bu kaydı silmek istediğinize emin misiniz?');">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="expense_id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 flex items-center justify-center transition-colors" title="Sil">
                                        <span class="material-symbols-outlined text-[18px]">delete</span>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pages > 1): ?>
            <div class="px-6 py-4 border-t border-[var(--border)] flex justify-center gap-2">
                <?php for ($p = 1; $p <= $pages; $p++):
                    $pgCls = $p === $page ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>
                <a href="?page=<?php echo $p; ?>" class="<?php echo $pgCls; ?> px-3 py-1 rounded-lg text-sm font-medium transition-colors"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
