<?php
/**
 * Checkin Model — Check-in (post) oluşturma, feed, etkileşimler
 */

class CheckinModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Check-in Oluştur ──────────────────────────────────

    public function create(int $userId, int $venueId, ?string $note = null, ?string $image = null): array
    {
        // Cooldown kontrolü
        $settings = new SettingsModel();
        $cooldown = (int) $settings->get('checkin_cooldown', 300);

        $stmt = $this->db->prepare("
            SELECT id FROM checkins
            WHERE user_id = ? AND venue_id = ? AND is_deleted = 0
              AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
            LIMIT 1
        ");
        $stmt->execute([$userId, $venueId, $cooldown]);
        if ($stmt->fetch()) {
            $mins = ceil($cooldown / 60);
            return ['ok' => false, 'error' => "Aynı mekana {$mins} dakika içinde tekrar check-in yapamazsınız."];
        }

        // Rate limit kontrolü
        $rateLimit = (int) $settings->get('checkin_rate_limit', 10);
        $rateWindow = (int) $settings->get('checkin_rate_window', 3600);

        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM checkins
            WHERE user_id = ? AND is_deleted = 0
              AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$userId, $rateWindow]);
        if ((int) $stmt->fetchColumn() >= $rateLimit) {
            return ['ok' => false, 'error' => 'Check-in limitine ulaştınız. Lütfen daha sonra tekrar deneyin.'];
        }

        // Insert
        $stmt = $this->db->prepare("
            INSERT INTO checkins (user_id, venue_id, note, image, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $venueId, $note, $image]);

        $checkinId = (int) $this->db->lastInsertId();

        // Mention bildirimlerini oluştur
        if ($note) {
            $this->processMentions($userId, $checkinId, $note);
        }

        return ['ok' => true, 'checkin_id' => $checkinId];
    }

    // ── Feed ──────────────────────────────────────────────

    public function getGlobalFeed(int $page = 1, int $perPage = 20, ?int $viewerId = null): array
    {
        $offset = ($page - 1) * $perPage;

        try {
            $stmt = $this->db->prepare("
                (SELECT c.id, c.user_id, c.venue_id, c.note, c.image, c.created_at,
                       u.username, u.tag, u.avatar, u.is_premium,
                       v.name as venue_name, v.category as venue_category,
                       (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                       (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                       (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count,
                       NULL as reposted_by, NULL as reposted_by_tag, c.created_at as sort_time
                FROM checkins c
                JOIN users u ON c.user_id = u.id
                JOIN venues v ON c.venue_id = v.id
                WHERE c.is_deleted = 0 AND u.is_active = 1)
                UNION ALL
                (SELECT c.id, c.user_id, c.venue_id, c.note, c.image, c.created_at,
                       u.username, u.tag, u.avatar, u.is_premium,
                       v.name as venue_name, v.category as venue_category,
                       (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                       (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                       (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count,
                       ru.username as reposted_by, ru.tag as reposted_by_tag, pr.created_at as sort_time
                FROM post_reposts pr
                JOIN checkins c ON pr.checkin_id = c.id
                JOIN users u ON c.user_id = u.id
                JOIN venues v ON c.venue_id = v.id
                JOIN users ru ON pr.user_id = ru.id
                WHERE c.is_deleted = 0 AND u.is_active = 1)
                ORDER BY sort_time DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$perPage, $offset]);
            $posts = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // Fallback: UNION başarısız olursa basit sorguya dön
            $stmt = $this->db->prepare("
                SELECT c.id, c.user_id, c.venue_id, c.note, c.image, c.created_at,
                       u.username, u.tag, u.avatar, u.is_premium,
                       v.name as venue_name, v.category as venue_category,
                       (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                       (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                       (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count,
                       NULL as reposted_by, NULL as reposted_by_tag
                FROM checkins c
                JOIN users u ON c.user_id = u.id
                JOIN venues v ON c.venue_id = v.id
                WHERE c.is_deleted = 0 AND u.is_active = 1
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$perPage, $offset]);
            $posts = $stmt->fetchAll();
        }

        // Viewer etkileşimleri
        if ($viewerId) {
            $posts = $this->attachViewerInteractions($posts, $viewerId);
        }

        return $posts;
    }

    public function getFollowingFeed(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.tag, u.avatar, u.is_premium,
                   v.name as venue_name, v.category as venue_category,
                   (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                   (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count
            FROM checkins c
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE c.is_deleted = 0
              AND (c.user_id = ? OR c.user_id IN (SELECT following_id FROM user_follows WHERE follower_id = ?))
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $userId, $perPage, $offset]);
        $posts = $stmt->fetchAll();

        return $this->attachViewerInteractions($posts, $userId);
    }

    public function getUserCheckins(int $userId, int $page = 1, int $perPage = 20, ?int $viewerId = null): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.tag, u.avatar, u.is_premium,
                   v.name as venue_name, v.category as venue_category,
                   (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                   (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count
            FROM checkins c
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE c.user_id = ? AND c.is_deleted = 0
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $perPage, $offset]);
        $posts = $stmt->fetchAll();

        if ($viewerId) {
            $posts = $this->attachViewerInteractions($posts, $viewerId);
        }
        return $posts;
    }

    public function getVenueCheckins(int $venueId, int $page = 1, int $perPage = 20, ?int $viewerId = null): array
    {
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.tag, u.avatar, u.is_premium,
                   v.name as venue_name, v.category as venue_category,
                   (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                   (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count
            FROM checkins c
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE c.venue_id = ? AND c.is_deleted = 0
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$venueId, $perPage, $offset]);
        $posts = $stmt->fetchAll();

        if ($viewerId) {
            $posts = $this->attachViewerInteractions($posts, $viewerId);
        }
        return $posts;
    }

    // ── Tekil Post ────────────────────────────────────────

    public function getById(int $id, ?int $viewerId = null): ?array
    {
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.tag, u.avatar, u.is_premium,
                   v.name as venue_name, v.category as venue_category, v.id as venue_id_ref,
                   (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                   (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count
            FROM checkins c
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE c.id = ? AND c.is_deleted = 0
        ");
        $stmt->execute([$id]);
        $post = $stmt->fetch();

        if (!$post) return null;

        if ($viewerId) {
            $posts = $this->attachViewerInteractions([$post], $viewerId);
            $post = $posts[0];
        }

        return $post;
    }

    // ── Etkileşimler ──────────────────────────────────────

    public function like(int $userId, int $checkinId): bool
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO post_likes (user_id, checkin_id) VALUES (?, ?)");
        $result = $stmt->execute([$userId, $checkinId]);

        if ($stmt->rowCount() > 0) {
            $this->createNotification($checkinId, $userId, 'like');
        }
        return $result;
    }

    public function unlike(int $userId, int $checkinId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM post_likes WHERE user_id = ? AND checkin_id = ?");
        return $stmt->execute([$userId, $checkinId]);
    }

    public function hasLiked(int $userId, int $checkinId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM post_likes WHERE user_id = ? AND checkin_id = ?");
        $stmt->execute([$userId, $checkinId]);
        return (bool) $stmt->fetch();
    }

    public function repost(int $userId, int $checkinId, ?string $quote = null): bool
    {
        $stmt = $this->db->prepare("INSERT IGNORE INTO post_reposts (user_id, checkin_id, quote) VALUES (?, ?, ?)");
        $result = $stmt->execute([$userId, $checkinId, $quote]);

        if ($stmt->rowCount() > 0) {
            $this->createNotification($checkinId, $userId, 'repost');
        }
        return $result;
    }

    public function unrepost(int $userId, int $checkinId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM post_reposts WHERE user_id = ? AND checkin_id = ?");
        return $stmt->execute([$userId, $checkinId]);
    }

    public function hasReposted(int $userId, int $checkinId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM post_reposts WHERE user_id = ? AND checkin_id = ?");
        $stmt->execute([$userId, $checkinId]);
        return (bool) $stmt->fetch();
    }

    public function addComment(int $userId, int $checkinId, string $comment, ?string $image = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO post_comments (user_id, checkin_id, comment, image) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $checkinId, $comment, $image]);

        $this->createNotification($checkinId, $userId, 'comment');

        // Yorum içindeki mention'ları işle
        $this->processMentions($userId, $checkinId, $comment);

        return (int) $this->db->lastInsertId();
    }

    public function getComments(int $checkinId): array
    {
        $stmt = $this->db->prepare("
            SELECT pc.*, u.username, u.tag, u.avatar
            FROM post_comments pc
            JOIN users u ON pc.user_id = u.id
            WHERE pc.checkin_id = ? AND pc.is_deleted = 0
            ORDER BY pc.created_at ASC
        ");
        $stmt->execute([$checkinId]);
        return $stmt->fetchAll();
    }

    public function softDelete(int $checkinId, int $userId): bool
    {
        $stmt = $this->db->prepare("UPDATE checkins SET is_deleted = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$checkinId, $userId]);
    }

    // ── Beğenilen / Repost Edilen Postlar ─────────────────

    public function getLikedByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.tag, u.avatar, u.is_premium,
                   v.name as venue_name, v.category as venue_category,
                   (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                   (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count
            FROM post_likes pl
            JOIN checkins c ON pl.checkin_id = c.id
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE pl.user_id = ? AND c.is_deleted = 0
            ORDER BY pl.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $perPage, $offset]);
        return $this->attachViewerInteractions($stmt->fetchAll(), $userId);
    }

    public function getRepostedByUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare("
            SELECT c.*, u.username, u.tag, u.avatar, u.is_premium,
                   v.name as venue_name, v.category as venue_category,
                   pr.quote as repost_quote,
                   (SELECT COUNT(*) FROM post_likes WHERE checkin_id = c.id) as like_count,
                   (SELECT COUNT(*) FROM post_comments WHERE checkin_id = c.id AND is_deleted = 0) as comment_count,
                   (SELECT COUNT(*) FROM post_reposts WHERE checkin_id = c.id) as repost_count
            FROM post_reposts pr
            JOIN checkins c ON pr.checkin_id = c.id
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE pr.user_id = ? AND c.is_deleted = 0
            ORDER BY pr.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $perPage, $offset]);
        return $this->attachViewerInteractions($stmt->fetchAll(), $userId);
    }

    // ── Haftalık İstatistikler ────────────────────────────

    public function getWeeklyCheckinCount(int $userId): int
    {
        $week = LeaderboardModel::getWeekRange();
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM checkins
            WHERE user_id = ? AND is_deleted = 0 AND is_excluded_from_leaderboard = 0
              AND created_at BETWEEN ? AND ?
        ");
        $stmt->execute([$userId, $week['start'], $week['end']]);
        return (int) $stmt->fetchColumn();
    }

    // ── Admin ─────────────────────────────────────────────

    public function adminGetAll(int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->query("SELECT COUNT(*) FROM checkins WHERE is_deleted = 0")->fetchColumn();

        $stmt = $this->db->prepare("
            SELECT c.*, u.username, v.name as venue_name
            FROM checkins c
            JOIN users u ON c.user_id = u.id
            JOIN venues v ON c.venue_id = v.id
            WHERE c.is_deleted = 0
            ORDER BY c.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$perPage, $offset]);

        return [
            'posts' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => ceil($total / $perPage),
        ];
    }

    public function adminDelete(int $id): void
    {
        $this->db->prepare("UPDATE checkins SET is_deleted = 1 WHERE id = ?")->execute([$id]);
    }

    public function toggleFlag(int $id): void
    {
        $this->db->prepare("UPDATE checkins SET is_flagged = NOT is_flagged WHERE id = ?")->execute([$id]);
    }

    public function toggleExclude(int $id): void
    {
        $this->db->prepare("UPDATE checkins SET is_excluded_from_leaderboard = NOT is_excluded_from_leaderboard WHERE id = ?")->execute([$id]);
    }

    // ── Private ───────────────────────────────────────────

    private function attachViewerInteractions(array $posts, int $viewerId): array
    {
        foreach ($posts as &$post) {
            $post['viewer_liked']    = $this->hasLiked($viewerId, $post['id']);
            $post['viewer_reposted'] = $this->hasReposted($viewerId, $post['id']);
            $post['is_own']          = ((int)$post['user_id'] === $viewerId);
        }
        return $posts;
    }

    private function createNotification(int $checkinId, int $fromUserId, string $type): void
    {
        $stmt = $this->db->prepare("SELECT user_id FROM checkins WHERE id = ?");
        $stmt->execute([$checkinId]);
        $post = $stmt->fetch();

        if (!$post || (int)$post['user_id'] === $fromUserId) return;

        $fromUser = $this->db->prepare("SELECT username FROM users WHERE id = ?");
        $fromUser->execute([$fromUserId]);
        $from = $fromUser->fetch();

        $messages = [
            'like'    => ($from['username'] ?? 'Birisi') . ' gönderini beğendi.',
            'comment' => ($from['username'] ?? 'Birisi') . ' gönderine yorum yaptı.',
            'repost'  => ($from['username'] ?? 'Birisi') . ' gönderini paylaştı.',
        ];

        $notif = new NotificationModel();
        $notif->create($post['user_id'], $fromUserId, $type, $messages[$type] ?? '', $checkinId);
    }

    private function processMentions(int $fromUserId, int $checkinId, string $text): void
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $text, $matches);
        if (empty($matches[1])) return;

        $userModel = new UserModel();
        $notifModel = new NotificationModel();

        foreach (array_unique($matches[1]) as $mentionedTag) {
            $mentioned = $userModel->getByUsername($mentionedTag);
            if ($mentioned && (int)$mentioned['id'] !== $fromUserId) {
                $from = $userModel->getById($fromUserId);
                $notifModel->create(
                    $mentioned['id'],
                    $fromUserId,
                    'mention',
                    ($from['username'] ?? 'Birisi') . ' senden bahsetti.',
                    $checkinId
                );
            }
        }
    }
}
