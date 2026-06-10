<?php
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

Auth::requireLogin();

$notifModel = new NotificationModel();

// Tümünü okundu olarak işaretle
$notifModel->markAllRead(Auth::id());
$notifications = $notifModel->getForUser(Auth::id(), 50);

$trendVenues = [];
$miniLeaderboard = [];
try {
    $trendVenues = (new VenueModel())->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

$pageTitle = 'Bildirimler';
$activeNav = 'notifications';
require_once __DIR__ . '/partials/app_header.php';
?>

<div style="min-width:0;display:flex;flex-direction:column;gap:20px;padding-bottom:40px;">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:16px;">
        <h1 style="font-size:1.75rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text-1);margin:0;"><span class="material-symbols-outlined" style="color:var(--color-primary);font-size:32px;font-variation-settings:'FILL' 1;">notifications</span> Bildirimler</h1>
        <?php if (!empty($notifications)): ?>
            <button style="background:var(--bg-section);border:1.5px solid var(--border);color:var(--text-2);padding:8px 16px;border-radius:10px;font-size:.875rem;font-weight:700;transition:background .14s;display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="App.clearNotifications(this)">
                <span class="material-symbols-outlined" style="font-size:18px;">delete</span> Temizle
            </button>
        <?php endif; ?>
    </div>

    <div style="background:#fff;border:1.5px solid var(--border);border-radius:14px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.05);">
        <?php if (empty($notifications)): ?>
            <div style="padding:48px 24px;text-align:center;color:var(--text-3);">
                <span class="material-symbols-outlined" style="font-size:64px;display:block;margin-bottom:16px;opacity:.4;">notifications_off</span>
                <p style="font-size:1.125rem;color:var(--text-2);">Yeni bildirim yok.</p>
            </div>
        <?php else: ?>
            <div style="display:flex;flex-direction:column;">
                <?php foreach ($notifications as $n):
                    $type = $n['type'] ?? 'info';
                    $icon = 'info';
                                    $iconStyles = 'background:rgba(148,163,184,0.15);color:#94a3b8;'; // info default
                    
                    if ($type === 'like')             { $icon = 'favorite';               $iconStyles = 'background:rgba(239,68,68,0.1);color:#ef4444;'; }
                    elseif ($type === 'comment')       { $icon = 'chat_bubble';            $iconStyles = 'background:rgba(59,130,246,0.1);color:#3b82f6;'; }
                    elseif ($type === 'follow')        { $icon = 'person_add';             $iconStyles = 'background:rgba(16,185,129,0.1);color:#10b981;'; }
                    elseif ($type === 'repost')        { $icon = 'repeat';                 $iconStyles = 'background:rgba(139,92,246,0.1);color:#8b5cf6;'; }
                    elseif ($type === 'mention')       { $icon = 'alternate_email';        $iconStyles = 'background:rgba(245,158,11,0.1);color:#f59e0b;'; }
                    elseif ($type === 'campaign_earned') { $icon = 'redeem';              $iconStyles = 'background:rgba(168,85,247,0.1);color:#a855f7;'; }
                    elseif ($type === 'new_campaign')  { $icon = 'campaign';               $iconStyles = 'background:rgba(249,115,22,0.1);color:#f97316;'; }
                    elseif ($type === 'wallet')        { $icon = 'account_balance_wallet'; $iconStyles = 'background:rgba(245,158,11,0.1);color:#f59e0b;'; }
                    
                    $link = $n['checkin_id']
                        ? BASE_URL . '/post?id=' . (int)$n['checkin_id']
                        : ($n['from_user_id'] ? BASE_URL . '/profile?u=' . escape($n['from_username'] ?? '') : '#');
                ?>
                <a href="<?php echo escape($link); ?>" style="display:flex;align-items:center;gap:16px;padding:14px 16px;border-bottom:1px solid var(--border-light);transition:background .12s;text-decoration:none;<?php echo !$n['is_read'] ? 'background:rgba(240,109,31,0.04);' : ''; ?>" onmouseover="this.style.background='#faf8f5'" onmouseout="this.style.background='<?php echo !$n['is_read'] ? 'rgba(240,109,31,0.04)' : ''; ?>'">
                    <div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;<?php echo $iconStyles; ?>">
                        <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                    </div>
                    
                    <div style="flex:1;min-width:0;font-size:.9375rem;line-height:1.6;">
                        <div style="color:var(--text-1);">
                            <?php if (!empty($n['from_avatar'])): ?>
                                <span style="display:inline-block;vertical-align:middle;margin-right:6px;">
                                    <?php echo avatarHtml($n['from_avatar'], $n['from_username'] ?? '', '24'); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo escape($n['content']); ?>
                        </div>
                        <div style="font-size:11px;color:var(--text-3);margin-top:4px;display:flex;align-items:center;gap:4px;">
                            <span class="material-symbols-outlined" style="font-size:12px;">schedule</span>
                            <?php echo timeAgo($n['created_at']); ?>
                        </div>
                    </div>
                    
                    <?php if (!$n['is_read']): ?>
                        <div style="width:10px;height:10px;border-radius:50%;background:var(--color-primary);flex-shrink:0;box-shadow:0 0 8px rgba(240,109,31,0.5);"></div>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div><!-- /grid cell -->

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
