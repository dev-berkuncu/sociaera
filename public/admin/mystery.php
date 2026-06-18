<?php
/**
 * Admin â€” Gizli MÃ¼ÅŸteri BaÅŸvuru YÃ¶netimi
 */
require_once __DIR__ . '/../../app/Config/env.php';
loadEnv(dirname(__DIR__, 2) . '/.env');
require_once __DIR__ . '/../../app/Config/app.php';
require_once __DIR__ . '/../../app/Config/database.php';
require_once __DIR__ . '/../../app/Core/RateLimit.php';
require_once __DIR__ . '/../../app/Core/Response.php';
require_once __DIR__ . '/../../app/Core/View.php';
require_once __DIR__ . '/../../app/Services/Logger.php';
require_once __DIR__ . '/../../app/Models/User.php';
require_once __DIR__ . '/../../app/Models/Venue.php';
require_once __DIR__ . '/../../app/Models/MysteryShopperModel.php';
require_once __DIR__ . '/../../app/Models/Notification.php';
require_once __DIR__ . '/../../app/Models/Leaderboard.php';
require_once __DIR__ . '/../../app/Models/Ad.php';
require_once __DIR__ . '/../../app/Models/Settings.php';
require_once __DIR__ . '/../../app/Helpers/ads_logic.php';

Auth::requireAccess('mystery');

$mysteryModel = new MysteryShopperModel();

// POST â€” Onayla / Reddet
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Csrf::requireValid();
    $appId  = (int)($_POST['application_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $note   = trim($_POST['admin_note'] ?? '');

    if ($appId && in_array($action, ['approve', 'reject'])) {
        if ($action === 'approve') {
            $mysteryModel->approve($appId, Auth::id(), $note ?: null);
            Logger::adminAudit('mystery_approve', 'mystery_shoppers', $appId, null, ['status' => 'approved']);
            Auth::setFlash('success', 'BaÅŸvuru onaylandÄ±.');
        } else {
            $mysteryModel->reject($appId, Auth::id(), $note ?: null);
            Logger::adminAudit('mystery_reject', 'mystery_shoppers', $appId, null, ['status' => 'rejected', 'note' => $note]);
            Auth::setFlash('success', 'BaÅŸvuru reddedildi.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/mystery'); exit;
}

$filterStatus = $_GET['status'] ?? 'pending';
$page         = max(1, (int)($_GET['page'] ?? 1));
$applications = $mysteryModel->getAll($filterStatus, $page, 25);
$pendingCount = $mysteryModel->countPending();

$pageTitle = 'Gizli MÃ¼ÅŸteri BaÅŸvurularÄ± â€” Admin';
$hideSidebar = true;
require_once __DIR__ . '/../partials/app_header.php';
?>

<section class="flex-1 flex flex-col gap-6 max-w-4xl w-full">

    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="<?php echo BASE_URL; ?>/admin"
           class="w-10 h-10 bg-white/5 rounded-full flex items-center justify-center hover:bg-white/10 transition-colors border border-white/10">
            <span class="material-symbols-outlined text-slate-400">arrow_back</span>
        </a>
        <div class="flex-grow">
            <h1 class="text-2xl font-black text-on-surface tracking-tight flex items-center gap-2">
                <span class="material-symbols-outlined text-indigo-400">person_search</span>
                Gizli MÃ¼ÅŸteri BaÅŸvurularÄ±
            </h1>
        </div>
        <?php if ($pendingCount > 0): ?>
        <span class="px-3 py-1 bg-amber-500/15 text-amber-400 border border-amber-500/20 rounded-full text-sm font-bold">
            <?php echo $pendingCount; ?> bekliyor
        </span>
        <?php endif; ?>
    </div>

    <!-- Filtre Tabs -->
    <div class="flex gap-2 border-b border-white/10 pb-0">
        <?php foreach (['pending' => 'Bekleyenler', 'approved' => 'Onaylananlar', 'rejected' => 'Reddedilenler', '' => 'TÃ¼mÃ¼'] as $s => $l): ?>
        <a href="?status=<?php echo $s; ?>"
           class="px-5 py-3 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                  <?php echo $filterStatus === $s
                      ? 'text-primary-container border-primary-container'
                      : 'text-slate-400 border-transparent hover:text-white hover:border-white/20'; ?>">
            <?php echo $l; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Liste -->
    <?php if (empty($applications)): ?>
    <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-10 text-center text-slate-400">
        <span class="material-symbols-outlined text-[48px] mb-3 opacity-40 block">person_search</span>
        <p>Bu kategoride baÅŸvuru yok.</p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($applications as $app):
            $statusBadge = match($app['status']) {
                'pending'  => ['bg-amber-500/10 text-amber-400',   'hourglass_top',  'Bekliyor'],
                'approved' => ['bg-emerald-500/10 text-emerald-400','check_circle',   'OnaylÄ±'],
                'rejected' => ['bg-red-500/10 text-red-400',       'cancel',         'Reddedildi'],
                default    => ['bg-white/5 text-slate-400',        'info',           $app['status']],
            };
        ?>
        <div class="bg-[#1E293B]/80 border border-white/10 rounded-xl p-5">
            <div class="flex items-start gap-4 flex-wrap md:flex-nowrap">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    <?php echo avatarHtml($app['avatar'] ?? null, $app['username'], '48'); ?>
                </div>

                <!-- Info -->
                <div class="flex-grow min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo escape($app['tag'] ?: $app['username']); ?>"
                           class="font-bold text-on-surface hover:text-primary-container transition-colors">
                            <?php echo escape($app['username']); ?>
                        </a>
                        <span class="text-xs px-2 py-0.5 rounded-full font-bold <?php echo $statusBadge[0]; ?> flex items-center gap-1">
                            <span class="material-symbols-outlined text-[12px]"><?php echo $statusBadge[1]; ?></span>
                            <?php echo $statusBadge[2]; ?>
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        BaÅŸvuru: <?php echo formatDate($app['applied_at']); ?>
                        <?php if ($app['reviewed_at']): ?>
                        Â· Ä°nceleme: <?php echo formatDate($app['reviewed_at']); ?>
                        (<?php echo escape($app['reviewer_name'] ?? 'Admin'); ?>)
                        <?php endif; ?>
                    </div>

                    <!-- Motivasyon -->
                    <?php if ($app['motivation']): ?>
                    <div class="mt-3 bg-white/5 rounded-lg px-4 py-3 text-sm text-slate-300 border border-white/5">
                        <?php echo nl2brSafe($app['motivation']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Admin Notu -->
                    <?php if ($app['admin_note']): ?>
                    <div class="mt-2 bg-slate-700/40 rounded-lg px-3 py-2 text-xs text-slate-400 border border-white/5">
                        <span class="font-semibold text-slate-500">Admin Notu:</span> <?php echo escape($app['admin_note']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Aksiyonlar (sadece pending iÃ§in) -->
                <?php if ($app['status'] === 'pending'): ?>
                <div class="flex-shrink-0 w-full md:w-auto">
                    <form method="POST" class="space-y-2">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                        <textarea name="admin_note" rows="2" placeholder="Not (isteÄŸe baÄŸlÄ±)..."
                                  class="w-full bg-white/5 border border-white/10 text-on-surface rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-primary-container/40 resize-none"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="approve"
                                    class="flex-1 px-4 py-2 rounded-lg bg-emerald-500/15 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/25 text-xs font-bold transition-colors flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">check</span> Onayla
                            </button>
                            <button type="submit" name="action" value="reject"
                                    class="flex-1 px-4 py-2 rounded-lg bg-red-500/15 text-red-400 border border-red-500/20 hover:bg-red-500/25 text-xs font-bold transition-colors flex items-center justify-center gap-1">
                                <span class="material-symbols-outlined text-[14px]">close</span> Reddet
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/../partials/app_footer.php'; ?>
