<?php
/**
 * Rapor Sayfası — İçerik raporlama ve geçmiş raporlar
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Report.php';

Auth::requireLogin();

$reportModel = new ReportModel();

// Kullanıcının gönderdiği raporlar
$db = Database::getConnection();
$myReports = [];
try {
    $stmt = $db->prepare("
        SELECT cr.*, 
               CASE cr.entity_type
                   WHEN 'checkin' THEN (SELECT CONCAT(u2.username, ' - ', v.name) FROM checkins c JOIN users u2 ON c.user_id = u2.id JOIN venues v ON c.venue_id = v.id WHERE c.id = cr.entity_id)
                   WHEN 'comment' THEN (SELECT CONCAT(u2.username, ': ', LEFT(pc.comment, 50)) FROM post_comments pc JOIN users u2 ON pc.user_id = u2.id WHERE pc.id = cr.entity_id)
                   WHEN 'user' THEN (SELECT username FROM users WHERE id = cr.entity_id)
                   WHEN 'venue' THEN (SELECT name FROM venues WHERE id = cr.entity_id)
               END as entity_info
        FROM content_reports cr
        WHERE cr.reporter_id = ?
        ORDER BY cr.created_at DESC
        LIMIT 50
    ");
    $stmt->execute([Auth::id()]);
    $myReports = $stmt->fetchAll();
} catch (\Throwable $e) {}

// POST ile doğrudan rapor gönderimi
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $entityType = $_POST['entity_type'] ?? '';
    $entityId = (int)($_POST['entity_id'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    $description = trim($_POST['description'] ?? '');

    $validTypes = ['checkin', 'comment', 'user', 'venue'];
    $validReasons = ['spam','harassment','inappropriate','wrong_venue','fake_checkin','fraud','privacy','copyright','other'];

    if (!in_array($entityType, $validTypes) || !$entityId || !in_array($reason, $validReasons)) {
        $error = 'Lütfen tüm alanları doğru doldurun.';
    } else {
        try {
            $reportModel->create(Auth::id(), $entityType, $entityId, $reason, $description ?: null);
            $success = 'Raporunuz başarıyla gönderildi. Ekibimiz en kısa sürede inceleyecek.';
            // Refresh list
            $stmt = $db->prepare("SELECT cr.* FROM content_reports cr WHERE cr.reporter_id = ? ORDER BY cr.created_at DESC LIMIT 50");
            $stmt->execute([Auth::id()]);
            $myReports = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $error = 'Rapor gönderilemedi. Lütfen tekrar deneyin.';
        }
    }
}

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$reasonLabels = [
    'spam' => 'Spam / Reklam',
    'harassment' => 'Taciz / Zorbalık',
    'inappropriate' => 'Uygunsuz İçerik',
    'wrong_venue' => 'Yanlış Mekan',
    'fake_checkin' => 'Sahte Check-in',
    'fraud' => 'Dolandırıcılık',
    'privacy' => 'Gizlilik İhlali',
    'copyright' => 'Telif Hakkı',
    'other' => 'Diğer',
];

$statusLabels = [
    'pending' => ['label' => 'Beklemede', 'color' => 'text-amber-400', 'bg' => 'bg-amber-500/10 border-amber-500/20'],
    'reviewed' => ['label' => 'İnceleniyor', 'color' => 'text-blue-400', 'bg' => 'bg-blue-500/10 border-blue-500/20'],
    'resolved' => ['label' => 'Çözüldü', 'color' => 'text-emerald-400', 'bg' => 'bg-emerald-500/10 border-emerald-500/20'],
    'dismissed' => ['label' => 'Reddedildi', 'color' => 'text-slate-400', 'bg' => 'bg-slate-500/10 border-slate-500/20'],
];

$pageTitle = 'Raporla';
$activeNav = 'report';
require_once __DIR__ . '/partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-stack-md max-w-2xl w-full mx-auto lg:mx-0">
    <div class="mb-2">
        <h1 class="text-3xl font-bold flex items-center gap-2 text-on-surface">
            <span class="material-symbols-outlined text-red-400 text-[32px]">flag</span> İçerik Raporlama
        </h1>
        <p class="text-slate-400 text-sm mt-2">Sakıncalı içerikleri bize bildirin. Her rapor ekibimiz tarafından incelenir.</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-3 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined">check_circle</span>
            <span><?php echo escape($success); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl flex items-center gap-3">
            <span class="material-symbols-outlined">error</span>
            <span><?php echo escape($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Hızlı Rapor Formu -->
    <div class="bg-[#2a2a2b]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl p-6 md:p-8 shadow-[0_15px_30px_-15px_rgba(19,19,20,0.5)]">
        <h2 class="text-xl font-bold flex items-center gap-2 mb-2 text-on-surface">
            <span class="material-symbols-outlined text-primary-container text-[24px]">edit_note</span> Rapor Gönder
        </h2>
        <p class="text-slate-400 text-sm mb-6">Gönderi üzerindeki <span class="material-symbols-outlined text-[14px] align-middle">flag</span> butonunu kullanarak da raporlayabilirsiniz.</p>

        <form method="POST" class="flex flex-col gap-5">
            <?php echo csrfField(); ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-300">İçerik Türü</label>
                    <select name="entity_type" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface text-sm focus:border-primary-container outline-none transition-all appearance-none">
                        <option value="">Seçin...</option>
                        <option value="checkin">Check-in / Gönderi</option>
                        <option value="comment">Yorum</option>
                        <option value="user">Kullanıcı</option>
                        <option value="venue">Mekan</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2">
                    <label class="text-sm font-bold text-slate-300">İçerik ID</label>
                    <input type="number" name="entity_id" required min="1" placeholder="Örn: 42"
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface text-sm focus:border-primary-container outline-none transition-all">
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-slate-300">Neden</label>
                <select name="reason" required class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface text-sm focus:border-primary-container outline-none transition-all appearance-none">
                    <option value="">Seçin...</option>
                    <?php foreach ($reasonLabels as $k => $l): ?>
                    <option value="<?php echo $k; ?>"><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex flex-col gap-2">
                <label class="text-sm font-bold text-slate-300">Açıklama <span class="text-slate-500 font-normal">(opsiyonel)</span></label>
                <textarea name="description" rows="3" maxlength="500" placeholder="Detay eklemek isterseniz..."
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-on-surface text-sm focus:border-primary-container outline-none transition-all resize-none"></textarea>
            </div>

            <button type="submit" class="bg-red-500/20 text-red-400 px-6 py-3 rounded-xl font-bold text-sm border border-red-500/30 hover:bg-red-500/30 transition-all active:scale-95 w-full sm:w-auto flex justify-center items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">send</span> Rapor Gönder
            </button>
        </form>
    </div>

    <!-- Raporlarım -->
    <div class="bg-[#2a2a2b]/80 backdrop-blur-[20px] border border-white/10 rounded-2xl shadow-[0_15px_30px_-15px_rgba(19,19,20,0.5)] overflow-hidden">
        <div class="px-6 py-5 border-b border-white/5">
            <h2 class="text-xl font-bold flex items-center gap-2 text-on-surface">
                <span class="material-symbols-outlined text-primary-container text-[24px]">history</span> Gönderdiğim Raporlar
            </h2>
        </div>

        <?php if (empty($myReports)): ?>
            <div class="p-8 text-center">
                <span class="material-symbols-outlined text-slate-600 text-[48px] mb-3">inbox</span>
                <p class="text-slate-400">Henüz rapor göndermediniz.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-white/5">
                <?php foreach ($myReports as $r): 
                    $st = $statusLabels[$r['status']] ?? $statusLabels['pending'];
                ?>
                <div class="flex items-center gap-4 px-6 py-4 hover:bg-white/[0.02] transition-colors">
                    <div class="w-10 h-10 rounded-xl bg-red-500/10 flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-red-400 text-[20px]">flag</span>
                    </div>
                    <div class="flex-grow min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-sm font-bold text-on-surface"><?php echo escape(ucfirst($r['entity_type'])); ?> #<?php echo $r['entity_id']; ?></span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full border <?php echo $st['bg']; ?> <?php echo $st['color']; ?>"><?php echo $st['label']; ?></span>
                        </div>
                        <div class="text-xs text-slate-400 truncate">
                            <?php echo escape($reasonLabels[$r['reason']] ?? $r['reason']); ?>
                            <?php if (!empty($r['entity_info'])): ?>
                                — <span class="text-slate-500"><?php echo escape(truncate($r['entity_info'], 40)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <div class="text-[11px] text-slate-500"><?php echo timeAgo($r['created_at']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bilgi -->
    <div class="bg-white/5 border border-white/10 rounded-xl p-5 flex items-start gap-4">
        <span class="material-symbols-outlined text-blue-400 text-[24px] flex-shrink-0 mt-0.5">info</span>
        <div class="text-sm text-slate-400 leading-relaxed">
            <p class="font-bold text-slate-300 mb-1">Raporlama Kuralları</p>
            <ul class="list-disc list-inside space-y-1">
                <li>Her rapor ekibimiz tarafından 24 saat içinde incelenir.</li>
                <li>Asılsız raporlar hesabınızın askıya alınmasına neden olabilir.</li>
                <li>Acil durumlarda <a href="mailto:info@sociaera.online" class="text-primary-container hover:underline">info@sociaera.online</a> adresinden ulaşın.</li>
            </ul>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
