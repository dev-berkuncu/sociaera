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
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
        <h1 style="font-size:1.75rem;font-weight:900;display:flex;align-items:center;gap:8px;color:var(--text-1);"><span class="material-symbols-outlined" style="color:var(--color-primary);font-size:32px;font-variation-settings:'FILL' 1;">notifications</span> Bildirimler</h1>
        <?php if (!empty($notifications)): ?>
            <button style="background:var(--bg-section);border:1.5px solid var(--border);color:var(--text-2);padding:8px 16px;border-radius:10px;font-size:.875rem;font-weight:700;transition:background .14s;display:flex;align-items:center;gap:8px;cursor:pointer;" onclick="App.clearNotifications(this)">
                <span class="material-symbols-outlined text-[18px]">delete</span> Temizle
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
            <div class="flex flex-col">
                <?php foreach ($notifications as $n):
                    $type = $n['type'] ?? 'info';
                    $icon = 'info';
                    $iconColor = 'text-slate-400 bg-surface-container';
                    
                    if ($type === 'like') { $icon = 'favorite'; $iconColor = 'text-[#ef4444] bg-[#ef4444]/10'; }
                    elseif ($type === 'comment') { $icon = 'chat_bubble'; $iconColor = 'text-[#3b82f6] bg-[#3b82f6]/10'; }
                    elseif ($type === 'follow') { $icon = 'person_add'; $iconColor = 'text-[#10b981] bg-[#10b981]/10'; }
                    elseif ($type === 'repost') { $icon = 'repeat'; $iconColor = 'text-[#8b5cf6] bg-[#8b5cf6]/10'; }
                    elseif ($type === 'mention') { $icon = 'alternate_email'; $iconColor = 'text-[#f59e0b] bg-[#f59e0b]/10'; }
                    elseif ($type === 'campaign_earned') { $icon = 'redeem'; $iconColor = 'text-[#a855f7] bg-[#a855f7]/10'; }
                    elseif ($type === 'new_campaign') { $icon = 'campaign'; $iconColor = 'text-[#f97316] bg-[#f97316]/10'; }
                    elseif ($type === 'wallet') { $icon = 'account_balance_wallet'; $iconColor = 'text-[#f59e0b] bg-[#f59e0b]/10'; }
                    
                    $link = $n['checkin_id']
                        ? BASE_URL . '/post?id=' . (int)$n['checkin_id']
                        : ($n['from_user_id'] ? BASE_URL . '/profile?u=' . escape($n['from_username'] ?? '') : '#');
                ?>
                <a href="<?php echo escape($link); ?>" style="display:flex;align-items:center;gap:16px;padding:14px 16px;border-bottom:1px solid var(--border-light);transition:background .12s;text-decoration:none;<?php echo !$n['is_read'] ? 'background:rgba(240,109,31,0.04);' : ''; ?>" onmouseover="this.style.background='#faf8f5'" onmouseout="this.style.background='<?php echo !$n['is_read'] ? 'rgba(240,109,31,0.04)' : ''; ?>'">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 <?php echo $iconColor; ?>">
                        <span class="material-symbols-outlined"><?php echo $icon; ?></span>
                    </div>
                    
                    <div style="flex:1;min-width:0;font-size:.9375rem;line-height:1.6;">
                        <div style="color:var(--text-1);">
                            <?php if (!empty($n['from_avatar'])): ?>
                                <span class="inline-block align-middle mr-1.5">
                                    <?php echo avatarHtml($n['from_avatar'], $n['from_username'] ?? '', '24'); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo escape($n['content']); ?>
                        </div>
                        <div style="font-size:11px;color:var(--text-3);margin-top:4px;display:flex;align-items:center;gap:4px;">
                            <span class="material-symbols-outlined text-[12px]">schedule</span>
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
