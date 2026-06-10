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
    'spam'          => 'Spam / Reklam',
    'harassment'    => 'Taciz / Zorbalık',
    'inappropriate' => 'Uygunsuz İçerik',
    'wrong_venue'   => 'Yanlış Mekan',
    'fake_checkin'  => 'Sahte Check-in',
    'fraud'         => 'Dolandırıcılık',
    'privacy'       => 'Gizlilik İhlali',
    'copyright'     => 'Telif Hakkı',
    'other'         => 'Diğer',
];

$statusLabels = [
    'pending'   => ['label' => 'Beklemede',  'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,0.1)',  'border' => 'rgba(245,158,11,0.3)'],
    'reviewed'  => ['label' => 'İnceleniyor','color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.1)',  'border' => 'rgba(59,130,246,0.3)'],
    'resolved'  => ['label' => 'Çözüldü',    'color' => '#10b981', 'bg' => 'rgba(16,185,129,0.1)',  'border' => 'rgba(16,185,129,0.3)'],
    'dismissed' => ['label' => 'Reddedildi', 'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,0.1)', 'border' => 'rgba(148,163,184,0.3)'],
];

$pageTitle = 'Raporla';
$activeNav = 'report';
require_once __DIR__ . '/partials/app_header.php';
?>

<style>
.rpt-input {
    width:100%; border-radius:10px; padding:10px 14px; font-size:13px;
    outline:none; transition:border-color .2s;
    background:var(--bg-section); border:1.5px solid var(--border); color:var(--text-1);
    font-family:inherit; appearance:none; box-sizing:border-box;
}
.rpt-input:focus { border-color:var(--color-primary); }
</style>

<section style="min-width:0; display:flex; flex-direction:column; gap:20px; max-width:640px; width:100%; padding-bottom:40px;">

    <div>
        <h1 style="font-size:1.8rem; font-weight:900; display:flex; align-items:center; gap:10px; color:var(--text-1); margin:0 0 6px;">
            <span class="material-symbols-outlined" style="color:#ef4444; font-size:32px;">flag</span> İçerik Raporlama
        </h1>
        <p style="color:var(--text-3); font-size:13px; margin:0;">Sakıncalı içerikleri bize bildirin. Her rapor ekibimiz tarafından incelenir.</p>
    </div>

    <?php if ($success): ?>
        <div style="background:rgba(16,185,129,0.08); border:1px solid rgba(16,185,129,0.3); color:#10b981; padding:12px 16px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:13px;">
            <span class="material-symbols-outlined">check_circle</span>
            <span><?php echo escape($success); ?></span>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.3); color:#ef4444; padding:12px 16px; border-radius:12px; display:flex; align-items:center; gap:10px; font-size:13px;">
            <span class="material-symbols-outlined">error</span>
            <span><?php echo escape($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- Hızlı Rapor Formu -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:14px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <h2 style="font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:8px; margin:0 0 6px; color:var(--text-1);">
            <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:24px;">edit_note</span> Rapor Gönder
        </h2>
        <p style="color:var(--text-3); font-size:12px; margin:0 0 18px;">Gönderi üzerindeki <span class="material-symbols-outlined" style="font-size:14px; vertical-align:middle;">flag</span> butonunu kullanarak da raporlayabilirsiniz.</p>

        <form method="POST" style="display:flex; flex-direction:column; gap:16px;">
            <?php echo csrfField(); ?>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px;">
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:700; color:var(--text-2);">İçerik Türü</label>
                    <select name="entity_type" required class="rpt-input">
                        <option value="">Seçin...</option>
                        <option value="checkin">Check-in / Gönderi</option>
                        <option value="comment">Yorum</option>
                        <option value="user">Kullanıcı</option>
                        <option value="venue">Mekan</option>
                    </select>
                </div>
                <div style="display:flex; flex-direction:column; gap:6px;">
                    <label style="font-size:12px; font-weight:700; color:var(--text-2);">İçerik ID</label>
                    <input type="number" name="entity_id" required min="1" placeholder="Örn: 42" class="rpt-input">
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:12px; font-weight:700; color:var(--text-2);">Neden</label>
                <select name="reason" required class="rpt-input">
                    <option value="">Seçin...</option>
                    <?php foreach ($reasonLabels as $k => $l): ?>
                    <option value="<?php echo $k; ?>"><?php echo $l; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; flex-direction:column; gap:6px;">
                <label style="font-size:12px; font-weight:700; color:var(--text-2);">Açıklama <span style="color:var(--text-3); font-weight:400;">(opsiyonel)</span></label>
                <textarea name="description" rows="3" maxlength="500" placeholder="Detay eklemek isterseniz..."
                    class="rpt-input" style="resize:none;"></textarea>
            </div>

            <button type="submit"
                    style="background:rgba(239,68,68,0.1); color:#ef4444; padding:12px 24px; border-radius:12px; font-weight:700; font-size:13px; border:1px solid rgba(239,68,68,0.3); cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; font-family:inherit; transition:background .15s; width:fit-content;"
                    onmouseover="this.style.background='rgba(239,68,68,0.18)'" onmouseout="this.style.background='rgba(239,68,68,0.1)'">
                <span class="material-symbols-outlined" style="font-size:18px;">send</span> Rapor Gönder
            </button>
        </form>
    </div>

    <!-- Raporlarım -->
    <div style="background:#fff; border:1px solid var(--border); border-radius:14px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);">
        <div style="padding:16px 20px; border-bottom:1px solid var(--border);">
            <h2 style="font-size:1rem; font-weight:800; display:flex; align-items:center; gap:8px; margin:0; color:var(--text-1);">
                <span class="material-symbols-outlined" style="color:var(--color-primary); font-size:22px;">history</span> Gönderdiğim Raporlar
            </h2>
        </div>

        <?php if (empty($myReports)): ?>
            <div style="padding:40px; text-align:center;">
                <span class="material-symbols-outlined" style="color:#cbd5e1; font-size:48px; display:block; margin-bottom:10px;">inbox</span>
                <p style="color:var(--text-3); font-size:13px; margin:0;">Henüz rapor göndermediniz.</p>
            </div>
        <?php else: ?>
            <div>
                <?php foreach ($myReports as $r):
                    $st = $statusLabels[$r['status']] ?? $statusLabels['pending'];
                ?>
                <div style="display:flex; align-items:center; gap:14px; padding:14px 20px; border-bottom:1px solid var(--border-light); transition:background .12s;"
                     onmouseover="this.style.background='var(--bg-section)'" onmouseout="this.style.background='transparent'">
                    <div style="width:40px; height:40px; border-radius:10px; background:rgba(239,68,68,0.08); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        <span class="material-symbols-outlined" style="color:#ef4444; font-size:20px;">flag</span>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <span style="font-size:13px; font-weight:700; color:var(--text-1);"><?php echo escape(ucfirst($r['entity_type'])); ?> #<?php echo $r['entity_id']; ?></span>
                            <span style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:999px; background:<?php echo $st['bg']; ?>; border:1px solid <?php echo $st['border']; ?>; color:<?php echo $st['color']; ?>;"><?php echo $st['label']; ?></span>
                        </div>
                        <div style="font-size:11px; color:var(--text-3); overflow:hidden; white-space:nowrap; text-overflow:ellipsis;">
                            <?php echo escape($reasonLabels[$r['reason']] ?? $r['reason']); ?>
                            <?php if (!empty($r['entity_info'])): ?>
                                — <span style="color:var(--text-3);"><?php echo escape(truncate($r['entity_info'], 40)); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="text-align:right; flex-shrink:0;">
                        <div style="font-size:11px; color:var(--text-3);"><?php echo timeAgo($r['created_at']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bilgi -->
    <div style="background:rgba(59,130,246,0.06); border:1px solid rgba(59,130,246,0.2); border-radius:12px; padding:18px 20px; display:flex; align-items:flex-start; gap:14px;">
        <span class="material-symbols-outlined" style="color:#3b82f6; font-size:24px; flex-shrink:0; margin-top:2px;">info</span>
        <div style="font-size:13px; color:var(--text-2); line-height:1.6;">
            <p style="font-weight:700; color:var(--text-1); margin:0 0 6px;">Raporlama Kuralları</p>
            <ul style="list-style:disc; padding-left:16px; margin:0; display:flex; flex-direction:column; gap:4px;">
                <li>Her rapor ekibimiz tarafından 24 saat içinde incelenir.</li>
                <li>Asılsız raporlar hesabınızın askıya alınmasına neden olabilir.</li>
                <li>Acil durumlarda <a href="mailto:info@sociaera.online" style="color:var(--color-primary); text-decoration:none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">info@sociaera.online</a> adresinden ulaşın.</li>
            </ul>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
