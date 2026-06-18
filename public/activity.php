<?php
/**
 * Sociaera — Aktivite Akışı
 * Tüm kullanıcıların son check-in'lerini gösterir
 */
require_once __DIR__ . '/../app/Config/env.php';
loadEnv(dirname(__DIR__) . '/.env');
require_once __DIR__ . '/../app/Config/app.php';
require_once __DIR__ . '/../app/Config/database.php';
require_once __DIR__ . '/../app/Core/Response.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Models/Venue.php';
require_once __DIR__ . '/../app/Models/Checkin.php';
require_once __DIR__ . '/../app/Models/Notification.php';
require_once __DIR__ . '/../app/Models/Leaderboard.php';
require_once __DIR__ . '/../app/Models/Ad.php';
require_once __DIR__ . '/../app/Models/Settings.php';
require_once __DIR__ . '/../app/Helpers/ads_logic.php';

Auth::requireLogin();

$checkinModel = new CheckinModel();
$venueModel   = new VenueModel();

// Sekme: herkese açık / sadece takip edilenler
$allowedTabs = ['global', 'following'];
$tab  = in_array($_GET['tab'] ?? 'global', $allowedTabs, true) ? ($_GET['tab'] ?? 'global') : 'global';
$page = max(1, (int)($_GET['page'] ?? 1));

if ($tab === 'following') {
    $posts = $checkinModel->getFollowingFeed(Auth::id(), $page, 20);
} else {
    $posts = $checkinModel->getGlobalFeed($page, 20, Auth::id());
}

$trendVenues     = [];
$miniLeaderboard = [];
try {
    $trendVenues     = $venueModel->getTrending(5);
    $miniLeaderboard = (new LeaderboardModel())->getTopUsers(5);
} catch (Exception $e) {}

// Kategori meta
$categoryMeta = [
    'restoran'  => ['icon' => 'restaurant',     'color' => '#ff6b35'],
    'kafe'      => ['icon' => 'local_cafe',      'color' => '#c47c4a'],
    'bar'       => ['icon' => 'sports_bar',      'color' => '#f59e0b'],
    'otel'      => ['icon' => 'hotel',           'color' => '#6366f1'],
    'alisveris' => ['icon' => 'shopping_bag',    'color' => '#3b82f6'],
    'eglence'   => ['icon' => 'theaters',        'color' => '#8b5cf6'],
    'spor'      => ['icon' => 'fitness_center',  'color' => '#ef4444'],
    'saglik'    => ['icon' => 'spa',             'color' => '#ec4899'],
    'kultur'    => ['icon' => 'museum',          'color' => '#14b8a6'],
    'diger'     => ['icon' => 'place',           'color' => '#64748b'],
];

$pageTitle = 'Aktivite';
$activeNav = 'activity';
require_once __DIR__ . '/partials/app_header.php';

// ── Günlere göre grupla ──────────────────────────────────────────────────────
$groupedPosts = [];
foreach ($posts as $post) {
    $day = date('Y-m-d', strtotime($post['created_at']));
    $groupedPosts[$day][] = $post;
}

function feedDayLabel(string $day): string {
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    if ($day === $today)     return 'Bugün';
    if ($day === $yesterday) return 'Dün';
    // Turkish month names
    $months = ['','Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    $parts  = explode('-', $day);
    return (int)$parts[2] . ' ' . ($months[(int)$parts[1]] ?? '') . ' ' . $parts[0];
}
?>

<div style="display:flex;flex-direction:column;gap:20px;padding-bottom:40px;">

    <!-- ── BAŞLIK ── -->
    <div>
        <h1 style="font-size:1.375rem;font-weight:800;color:var(--text-1);margin:0 0 14px;letter-spacing:-0.3px;display:flex;align-items:center;gap:8px;">
            <span class="material-symbols-outlined" style="font-size:22px;color:var(--color-primary);font-variation-settings:'FILL' 1;">explore</span>
            Aktivite
        </h1>

        <!-- ── SEKMELER ── -->
        <div class="swarm-tabs">
            <a href="?tab=global"
               class="swarm-tab <?php echo $tab === 'global' ? 'active' : ''; ?>"
               style="text-decoration:none;">
                🌍 Herkes
            </a>
            <a href="?tab=following"
               class="swarm-tab <?php echo $tab === 'following' ? 'active' : ''; ?>"
               style="text-decoration:none;">
                👥 Takip Ettiklerim
            </a>
        </div>
    </div>

    <!-- ── AKTİVİTE AKIŞI ── -->
    <?php if (empty($posts)): ?>

    <div class="empty-state">
        <span class="empty-state-icon material-symbols-outlined">
            <?php echo $tab === 'following' ? 'group' : 'public'; ?>
        </span>
        <p class="empty-state-title">
            <?php echo $tab === 'following' ? 'Takip ettiğin kişiler henüz check-in yapmadı.' : 'Henüz check-in yok.'; ?>
        </p>
        <?php if ($tab === 'following'): ?>
        <a href="<?php echo BASE_URL; ?>/leaderboard"
           style="color:var(--color-primary);font-size:0.875rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:5px;margin-top:4px;">
            <span class="material-symbols-outlined" style="font-size:16px;">group_add</span>
            Aktif kullanıcıları keşfet
        </a>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <?php
        $globalIndex = 0;  // for ad insertion
        $adIndex     = 0;  // which ad to show next
        $feedAds     = $feedAds ?? [];
    ?>

    <div style="display:flex;flex-direction:column;gap:6px;">

        <?php foreach ($groupedPosts as $day => $dayPosts): ?>

        <!-- Day header -->
        <p class="swarm-section-label" style="margin:10px 0 4px;"><?php echo feedDayLabel($day); ?></p>

        <?php foreach ($dayPosts as $post):
            $pMeta    = $categoryMeta[$post['venue_category'] ?? 'diger'] ?? $categoryMeta['diger'];
            $pAvatar  = safeAvatarUrl($post['avatar'] ?? null, $post['username'] ?? 'U');
            $pTimeAgo = timeAgo($post['created_at']);
            $isOwn    = (int)($post['user_id'] ?? 0) === Auth::id();
            $globalIndex++;
        ?>

        <!-- ── CHECK-IN CARD ── -->
        <div class="checkin-card" style="background:#fff;border-radius:16px;border:1.5px solid #EEECE8;overflow:hidden;transition:box-shadow 0.18s;"
             onmouseover="this.style.boxShadow='0 4px 18px rgba(0,0,0,0.07)'" onmouseout="this.style.boxShadow=''">

            <!-- Top: Avatar + info -->
            <div style="display:flex;align-items:flex-start;gap:12px;padding:14px 16px 10px;">

                <!-- Avatar with category dot -->
                <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo urlencode($post['tag'] ?: $post['username']); ?>"
                   style="position:relative;flex-shrink:0;text-decoration:none;">
                    <img src="<?php echo $pAvatar; ?>" alt="" loading="lazy" decoding="async"
                         style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid var(--border);"
                         width="42" height="42">
                    <!-- Category dot -->
                    <div style="position:absolute;bottom:-2px;right:-2px;width:18px;height:18px;border-radius:50%;background:<?php echo $pMeta['color']; ?>;border:2px solid #fff;display:flex;align-items:center;justify-content:center;">
                        <span class="material-symbols-outlined" style="font-size:9px;color:#fff;font-variation-settings:'FILL' 1;"><?php echo $pMeta['icon']; ?></span>
                    </div>
                </a>

                <!-- Text block -->
                <div style="flex:1;min-width:0;">
                    <div style="font-size:0.875rem;line-height:1.45;color:var(--text-1);">
                        <a href="<?php echo BASE_URL; ?>/profile?u=<?php echo urlencode($post['tag'] ?: $post['username']); ?>"
                           style="font-weight:700;color:var(--text-1);text-decoration:none;"
                           onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-1)'">
                            <?php echo escape($post['username']); ?>
                        </a>
                        <span style="color:var(--text-3);font-weight:400;"> → </span>
                        <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$post['venue_id']; ?>"
                           style="font-weight:700;color:<?php echo $pMeta['color']; ?>;text-decoration:none;"
                           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                            <?php echo escape($post['venue_name']); ?>
                        </a>
                    </div>
                    <div style="font-size:0.6875rem;color:var(--text-3);margin-top:3px;display:flex;align-items:center;gap:5px;">
                        <span class="material-symbols-outlined" style="font-size:11px;">schedule</span>
                        <?php echo $pTimeAgo; ?>
                        <?php if (!empty($post['venue_address'])): ?>
                        <span style="opacity:0.5;">·</span>
                        <span class="material-symbols-outlined" style="font-size:11px;">pin_drop</span>
                        <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:180px;"><?php echo escape(truncate($post['venue_address'], 40)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Check-in badge -->
                <div style="flex-shrink:0;display:inline-flex;align-items:center;gap:4px;font-size:10px;font-weight:700;padding:4px 10px;border-radius:999px;color:<?php echo $pMeta['color']; ?>;background:<?php echo $pMeta['color']; ?>18;border:1px solid <?php echo $pMeta['color']; ?>30;">
                    <span class="material-symbols-outlined" style="font-size:10px;font-variation-settings:'FILL' 1;">verified</span>
                    Check-in
                </div>

            </div>

            <!-- Note (if any) -->
            <?php if (!empty($post['note'])): ?>
            <div style="padding:0 16px 10px;">
                <p style="font-size:0.875rem;color:var(--text-1);font-style:italic;background:#F9F8F5;border:1.5px solid #EEECE8;border-radius:12px;padding:10px 14px;margin:0;line-height:1.55;">
                    "<?php echo escape($post['note']); ?>"
                </p>
            </div>
            <?php endif; ?>

            <!-- Image (if any) -->
            <?php if (!empty($post['image'])): ?>
            <div style="padding:0 16px 10px;">
                <img src="<?php echo uploadUrl('posts', $post['image']); ?>"
                     loading="lazy" decoding="async"
                     style="display:block; width:100%; max-width:100%; height:auto; max-height:400px; object-fit:contain; background:#111; cursor:pointer;"
                     onclick="openLightbox(this.src)"
                     onerror="this.closest('div').style.display='none'">
            </div>
            <?php endif; ?>

            <!-- Footer: venue link + actions -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 16px 12px;border-top:1px solid #F0EDE8;">
                <a href="<?php echo BASE_URL; ?>/venue-detail?id=<?php echo (int)$post['venue_id']; ?>"
                   style="display:inline-flex;align-items:center;gap:5px;font-size:0.75rem;color:var(--text-3);text-decoration:none;font-weight:600;transition:color 0.15s;"
                   onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--text-3)'">
                    <span class="material-symbols-outlined" style="font-size:14px;font-variation-settings:'FILL' 1;"><?php echo $pMeta['icon']; ?></span>
                    <?php echo escape($post['venue_name']); ?>
                </a>

                <div style="display:flex;align-items:center;gap:4px;">
                    <!-- Beğeni -->
                    <button onclick="App.toggleLike(this, <?php echo $post['id']; ?>)"
                            class="checkin-action-btn <?php echo !empty($post['viewer_liked']) ? 'liked' : ''; ?>"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 9px;border:none;background:none;border-radius:20px;cursor:pointer;font-size:12px;font-weight:700;color:<?php echo !empty($post['viewer_liked']) ? '#F06D1F' : 'var(--text-3)'; ?>;font-family:inherit;transition:all .15s;"
                            onmouseover="if(!this.classList.contains('liked')){this.style.background='#FFF3EB';this.style.color='#F06D1F';}" 
                            onmouseout="if(!this.classList.contains('liked')){this.style.background='';this.style.color='var(--text-3)';}">
                        <span class="material-symbols-outlined" style="font-size:16px;font-variation-settings:'FILL' <?php echo !empty($post['viewer_liked']) ? '1' : '0'; ?>;">favorite</span>
                        <span class="action-count"><?php echo (int)($post['like_count'] ?? 0); ?></span>
                    </button>

                    <!-- Yorum -->
                    <button onclick="App.toggleComments(this, <?php echo $post['id']; ?>)"
                            class="checkin-action-btn"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 9px;border:none;background:none;border-radius:20px;cursor:pointer;font-size:12px;font-weight:700;color:var(--text-3);font-family:inherit;transition:all .15s;"
                            onmouseover="this.style.background='#F0F4FF';this.style.color='#4F46E5';" 
                            onmouseout="if(!this.classList.contains('active-comment')){this.style.background='';this.style.color='var(--text-3)';}">
                        <span class="material-symbols-outlined" style="font-size:16px;">chat_bubble</span>
                        <span class="action-count" data-comment-count="<?php echo $post['id']; ?>"><?php echo (int)($post['comment_count'] ?? 0); ?></span>
                    </button>

                    <!-- Paylaş -->
                    <button onclick="navigator.clipboard.writeText('<?php echo BASE_URL; ?>/post?id=<?php echo $post['id']; ?>');App.flash('Link kopyalandı!','success')"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 9px;border:none;background:none;border-radius:20px;cursor:pointer;font-size:12px;font-weight:700;color:var(--text-3);font-family:inherit;transition:all .15s;"
                            onmouseover="this.style.background='#F2F1EE';this.style.color='var(--text-1)';"
                            onmouseout="this.style.background='';this.style.color='var(--text-3)';">
                        <span class="material-symbols-outlined" style="font-size:16px;">share</span>
                    </button>

                    <!-- Sil (sadece kendi gönderileri) -->
                    <?php if ($isOwn): ?>
                    <button onclick="App.deletePost(this, <?php echo $post['id']; ?>)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 9px;border:none;background:none;border-radius:20px;cursor:pointer;font-size:12px;font-weight:700;color:var(--text-3);font-family:inherit;transition:all .15s;"
                            title="Gönderiyi Sil"
                            onmouseover="this.style.background='#FEF2F2';this.style.color='#DC2626';"
                            onmouseout="this.style.background='';this.style.color='var(--text-3)';">
                        <span class="material-symbols-outlined" style="font-size:16px;">delete</span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Yorumlar (gizli, toggle ile açılır) -->
            <div class="post-comments-section" id="comments-section-<?php echo $post['id']; ?>"
                 style="display:none;border-top:1px solid #F0EDE8;padding:12px 16px 14px;background:#FAFAF8;">
                <div id="comments-list-<?php echo $post['id']; ?>" style="display:flex;flex-direction:column;gap:0;max-height:280px;overflow-y:auto;margin-bottom:10px;">
                    <div style="text-align:center;font-size:12px;color:var(--text-3);padding:8px;">Yükleniyor...</div>
                </div>
                <form style="display:flex;gap:8px;align-items:center;"
                      onsubmit="App.submitInlineComment(this, <?php echo $post['id']; ?>); return false;">
                    <img src="<?php echo safeAvatarUrl($currentUser['avatar'] ?? null, $currentUser['username'] ?? 'U'); ?>" loading="lazy" decoding="async"
                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <input type="text" placeholder="Yorum yaz…" maxlength="500" required
                           class="comment-input-inline"
                           style="flex:1;background:#F2F1EE;border:1.5px solid transparent;border-radius:20px;padding:7px 14px;font-size:13px;font-family:var(--font);outline:none;color:var(--text-1);"
                           onfocus="this.style.borderColor='var(--color-primary)';this.style.background='#fff'" 
                           onblur="this.style.borderColor='transparent';this.style.background='#F2F1EE'">
                    <button type="submit" class="comment-send-btn"
                            style="width:32px;height:32px;border-radius:50%;background:var(--color-primary);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <span class="material-symbols-outlined" style="font-size:15px;font-variation-settings:'FILL' 1;">send</span>
                    </button>
                </form>
            </div>

        </div>


        <?php
            // Insert ad after every 5 posts
            if ($globalIndex % 5 === 0 && !empty($feedAds) && isset($feedAds[$adIndex])):
                $ad = $feedAds[$adIndex++];
                
                // Determine ad image path with fallback
                $adImage = '';
                if (!empty($ad['image_url'])) {
                    $adImage = (strpos($ad['image_url'], 'http') === 0) ? $ad['image_url'] : BASE_URL . '/' . $ad['image_url'];
                } elseif (!empty($ad['image'])) {
                    $adImage = $ad['image'];
                }
                
                // Determine ad target URL with fallback
                $adUrl = '';
                if (!empty($ad['link_url'])) {
                    $adUrl = $ad['link_url'];
                } elseif (!empty($ad['url'])) {
                    $adUrl = $ad['url'];
                }
        ?>
        <?php if (empty($ad['user_id'])): ?>
        <!-- ADMIN SPONSOR CARD -->
        <div style="background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius:16px; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 10px 25px -5px rgba(0,0,0,0.3); border:1px solid rgba(255,255,255,0.05); margin-bottom:24px;">
            <div style="padding:12px 16px; display:flex; align-items:center; justify-content:space-between; background:rgba(0,0,0,0.2);">
                <div style="display:flex; align-items:center; gap:8px;">
                    <span class="material-symbols-outlined" style="font-size:18px; color:var(--color-primary);">star</span>
                    <span style="font-size:11px; font-weight:800; color:var(--color-primary); text-transform:uppercase; letter-spacing:1px;">Özel Sponsor</span>
                </div>
            </div>
            
            <?php 
                $mType = $ad['media_type'] ?? 'image';
            ?>
            <div style="width:100%; position:relative; overflow:hidden;">
            <?php if ($mType === 'youtube' && !empty($adImage)): 
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $adImage, $match);
                $ytId = $match[1] ?? '';
                if ($ytId):
            ?>
                <iframe width="100%" height="220" src="https://www.youtube.com/embed/<?php echo $ytId; ?>" frameborder="0" allowfullscreen style="display:block;"></iframe>
                <?php endif; ?>
            <?php elseif ($mType === 'video' && !empty($adImage)): ?>
                <video src="<?php echo escape($adImage); ?>" autoplay muted loop controls playsinline style="width:100%; max-height:260px; object-fit:cover; display:block; background:#000;"></video>
            <?php elseif (!empty($adImage)): ?>
                <img src="<?php echo escape($adImage); ?>" style="width:100%; max-height:260px; object-fit:cover; display:block;" loading="lazy">
            <?php endif; ?>
            </div>

            <div style="padding:16px;">
                <div style="font-size:1.1rem; font-weight:800; color:#ffffff;"><?php echo escape($ad['title'] ?? ''); ?></div>
                <?php if (!empty($ad['description'])): ?>
                <div style="font-size:0.875rem; color:#94a3b8; margin-top:6px; line-height:1.5;"><?php echo escape($ad['description']); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($adUrl)): ?>
                <a href="<?php echo escape($adUrl); ?>" target="_blank" rel="noopener"
                   style="display:flex; align-items:center; justify-content:center; gap:6px; width:100%; background:linear-gradient(to right, var(--color-primary), #d8570e); color:#ffffff; padding:12px; border-radius:10px; font-size:14px; font-weight:800; text-decoration:none; margin-top:16px; box-shadow:0 4px 12px rgba(240,109,31,0.3); transition:transform 0.2s, box-shadow 0.2s;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(240,109,31,0.4)';" onmouseout="this.style.transform='none'; this.style.boxShadow='0 4px 12px rgba(240,109,31,0.3)';">
                   Şimdi Keşfet <span class="material-symbols-outlined" style="font-size:18px;">arrow_forward</span>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- USER AD CARD -->
        <div style="background:#fff; border-radius:16px; border:1.5px dashed var(--border); overflow:hidden; padding:16px; display:flex; flex-direction:column; gap:12px; box-shadow:0 2px 10px rgba(0,0,0,0.02); margin-bottom:24px;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center; gap:6px;">
                    <span class="material-symbols-outlined" style="font-size:16px; color:var(--text-3);">campaign</span>
                    <span style="font-size:11px; font-weight:800; color:var(--text-3); text-transform:uppercase; letter-spacing:0.5px;">Öne Çıkarılan İçerik</span>
                </div>
            </div>
            
            <?php 
                $mType = $ad['media_type'] ?? 'image';
            ?>
            <?php if ($mType === 'youtube' && !empty($adImage)): 
                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $adImage, $match);
                $ytId = $match[1] ?? '';
                if ($ytId):
            ?>
                <iframe width="100%" height="220" src="https://www.youtube.com/embed/<?php echo $ytId; ?>" frameborder="0" allowfullscreen style="border-radius:12px;"></iframe>
                <?php endif; ?>
            <?php elseif ($mType === 'video' && !empty($adImage)): ?>
                <video src="<?php echo escape($adImage); ?>" autoplay muted loop controls playsinline style="width:100%; max-height:220px; object-fit:cover; border-radius:12px; background:#000;"></video>
            <?php elseif (!empty($adImage)): ?>
                <img src="<?php echo escape($adImage); ?>" style="width:100%; max-height:220px; border-radius:12px; object-fit:cover;" loading="lazy">
            <?php endif; ?>

            <div>
                <div style="font-size:1rem; font-weight:800; color:var(--text-1);"><?php echo escape($ad['title'] ?? ''); ?></div>
                <?php if (!empty($ad['description'])): ?>
                <div style="font-size:0.875rem; color:var(--text-2); margin-top:4px; line-height:1.4;"><?php echo escape($ad['description']); ?></div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($adUrl)): ?>
            <a href="<?php echo escape($adUrl); ?>" target="_blank" rel="noopener"
               style="display:flex; align-items:center; justify-content:center; gap:6px; width:100%; background:var(--bg-section); color:var(--text-1); padding:10px; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:background 0.2s;"
               onmouseover="this.style.background='var(--border)'" onmouseout="this.style.background='var(--bg-section)'">
               İncele <span class="material-symbols-outlined" style="font-size:16px;">open_in_new</span>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php endforeach; // dayPosts ?>
        <?php endforeach; // groupedPosts ?>

    </div>

    <!-- ── DAHA FAZLA ── -->
    <?php if (count($posts) >= 20): ?>
    <div style="text-align:center;">
        <a href="?tab=<?php echo $tab; ?>&page=<?php echo $page + 1; ?>"
           class="btn btn-ghost"
           style="display:inline-flex;align-items:center;gap:6px;text-decoration:none;font-size:0.875rem;">
            <span class="material-symbols-outlined" style="font-size:18px;">expand_more</span>
            Daha Fazla Göster
        </a>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/partials/app_footer.php'; ?>
