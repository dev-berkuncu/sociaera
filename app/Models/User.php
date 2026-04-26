<?php
/**
 * User Model — Kullanıcı CRUD ve iş mantığı
 */

class UserModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Kayıt ─────────────────────────────────────────────

    public function register(string $username, string $email, string $password): array
    {
        // Validasyon
        if (strlen($username) < 3 || strlen($username) > 50) {
            return ['ok' => false, 'error' => 'Kullanıcı adı 3-50 karakter arası olmalıdır.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Geçerli bir e-posta adresi giriniz.'];
        }
        if (strlen($password) < 6) {
            return ['ok' => false, 'error' => 'Şifre en az 6 karakter olmalıdır.'];
        }

        // Benzersizlik kontrolü
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'error' => 'Bu kullanıcı adı veya e-posta zaten kullanılıyor.'];
        }

        // Kayıt
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $tag  = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));

        $stmt = $this->db->prepare("
            INSERT INTO users (username, tag, email, password_hash, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $tag, $email, $hash]);

        $userId = (int) $this->db->lastInsertId();

        // Cüzdan oluştur
        $this->db->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$userId]);

        return ['ok' => true, 'user_id' => $userId];
    }

    // ── Giriş ─────────────────────────────────────────────

    public function login(string $usernameOrEmail, string $password): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1
        ");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['ok' => false, 'error' => 'Kullanıcı bulunamadı.'];
        }

        if ($user['banned_until'] && strtotime($user['banned_until']) > time()) {
            $until = formatDate($user['banned_until'], true);
            return ['ok' => false, 'error' => "Hesabınız {$until} tarihine kadar askıya alınmıştır."];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Şifre hatalı.'];
        }

        // Son giriş güncelle
        $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);

        return ['ok' => true, 'user' => $user];
    }

    // ── OAuth ─────────────────────────────────────────────

    public function findOrCreateByOAuth(int $gtaUserId, string $gtaUsername, string $email): array
    {
        // Mevcut kullanıcı var mı?
        $stmt = $this->db->prepare("SELECT * FROM users WHERE gta_user_id = ?");
        $stmt->execute([$gtaUserId]);
        $user = $stmt->fetch();

        if ($user) {
            $this->db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?")->execute([$user['id']]);
            return ['ok' => true, 'user' => $user, 'is_new' => false];
        }

        // Yeni kullanıcı
        $username = $gtaUsername;
        $tag = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $username));

        // Username çakışma kontrolü
        $counter = 0;
        $originalTag = $tag;
        while (true) {
            $checkName = $counter > 0 ? $username . $counter : $username;
            $checkTag  = $counter > 0 ? $originalTag . $counter : $tag;
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR tag = ?");
            $stmt->execute([$checkName, $checkTag]);
            if (!$stmt->fetch()) {
                $username = $checkName;
                $tag = $checkTag;
                break;
            }
            $counter++;
        }

        $randomPass = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $this->db->prepare("
            INSERT INTO users (username, tag, email, password_hash, gta_user_id, gta_username, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$username, $tag, $email ?: ($username . '@gta.local'), $randomPass, $gtaUserId, $gtaUsername]);

        $userId = (int) $this->db->lastInsertId();
        $this->db->prepare("INSERT INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$userId]);

        $user = $this->getById($userId);
        return ['ok' => true, 'user' => $user, 'is_new' => true];
    }

    public function updateCharacter(int $userId, int $characterId, string $characterName): void
    {
        $tag = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $characterName)));

        // Tag çakışma kontrolü
        $originalTag = $tag;
        $counter = 0;
        while (true) {
            $checkTag = $counter > 0 ? $originalTag . $counter : $tag;
            $stmt = $this->db->prepare("SELECT id FROM users WHERE tag = ? AND id != ?");
            $stmt->execute([$checkTag, $userId]);
            if (!$stmt->fetch()) {
                $tag = $checkTag;
                break;
            }
            $counter++;
        }

        $stmt = $this->db->prepare("
            UPDATE users SET gta_character_id = ?, gta_character_name = ?, username = ?, tag = ? WHERE id = ?
        ");
        $stmt->execute([$characterId, $characterName, $characterName, $tag, $userId]);
    }

    // ── CRUD ──────────────────────────────────────────────

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR tag = ?");
        $stmt->execute([$username, $username]);
        return $stmt->fetch() ?: null;
    }

    public function updateProfile(int $userId, array $data): array
    {
        $username = trim($data['username'] ?? '');
        $tag      = trim(ltrim($data['tag'] ?? '', '@'));
        $email    = trim($data['email'] ?? '');
        $bio      = trim($data['bio'] ?? '');

        if (empty($username) || empty($email)) {
            return ['ok' => false, 'error' => 'Kullanıcı adı ve e-posta gereklidir.'];
        }

        if (!empty($tag) && !preg_match('/^[a-zA-Z0-9_]{3,30}$/', $tag)) {
            return ['ok' => false, 'error' => 'Etiket 3-30 karakter, harf/rakam/alt çizgi olmalıdır.'];
        }

        // Benzersizlik
        $stmt = $this->db->prepare("
            SELECT id FROM users WHERE (username = ? OR email = ? OR (tag = ? AND tag != '' AND tag IS NOT NULL)) AND id != ?
        ");
        $stmt->execute([$username, $email, $tag, $userId]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'error' => 'Bu kullanıcı adı, etiket veya e-posta zaten kullanılıyor.'];
        }

        $stmt = $this->db->prepare("UPDATE users SET username = ?, tag = ?, email = ?, bio = ? WHERE id = ?");
        $stmt->execute([$username, $tag ?: null, $email, $bio, $userId]);

        return ['ok' => true];
    }

    public function updateAvatar(int $userId, string $filename): void
    {
        $stmt = $this->db->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$filename, $userId]);
    }

    public function updateBanner(int $userId, string $filename): void
    {
        $stmt = $this->db->prepare("UPDATE users SET banner = ? WHERE id = ?");
        $stmt->execute([$filename, $userId]);
    }

    public function changePassword(int $userId, string $current, string $new): array
    {
        if (strlen($new) < 6) return ['ok' => false, 'error' => 'Yeni şifre en az 6 karakter olmalıdır.'];

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!password_verify($current, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Mevcut şifre yanlış.'];
        }

        $hash = password_hash($new, PASSWORD_DEFAULT);
        $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);

        return ['ok' => true];
    }

    // ── Takip ─────────────────────────────────────────────

    public function follow(int $followerId, int $followingId): bool
    {
        if ($followerId === $followingId) return false;

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO user_follows (follower_id, following_id) VALUES (?, ?)
        ");
        return $stmt->execute([$followerId, $followingId]);
    }

    public function unfollow(int $followerId, int $followingId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM user_follows WHERE follower_id = ? AND following_id = ?");
        return $stmt->execute([$followerId, $followingId]);
    }

    public function isFollowing(int $followerId, int $followingId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM user_follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);
        return (bool) $stmt->fetch();
    }

    public function getFollowerCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function getFollowingCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    // ── İstatistikler ─────────────────────────────────────

    public function getStats(int $userId): array
    {
        $checkins = $this->db->prepare("SELECT COUNT(*) FROM checkins WHERE user_id = ? AND is_deleted = 0");
        $checkins->execute([$userId]);

        $venues = $this->db->prepare("SELECT COUNT(DISTINCT venue_id) FROM checkins WHERE user_id = ? AND is_deleted = 0");
        $venues->execute([$userId]);

        return [
            'checkins'  => (int) $checkins->fetchColumn(),
            'venues'    => (int) $venues->fetchColumn(),
            'followers' => $this->getFollowerCount($userId),
            'following' => $this->getFollowingCount($userId),
        ];
    }

    public function getFavoriteVenue(int $userId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT v.id, v.name, COUNT(*) as cnt
            FROM checkins c JOIN venues v ON c.venue_id = v.id
            WHERE c.user_id = ? AND c.is_deleted = 0
            GROUP BY v.id ORDER BY cnt DESC LIMIT 1
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    // ── Arama ─────────────────────────────────────────────

    public function search(string $query, int $limit = 8): array
    {
        $like = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT id, username, tag, avatar FROM users
            WHERE (username LIKE ? OR tag LIKE ?) AND is_active = 1
            ORDER BY username LIMIT ?
        ");
        $stmt->execute([$like, $like, $limit]);
        return $stmt->fetchAll();
    }

    // ── Admin ─────────────────────────────────────────────

    public function getAll(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;

        $where = "WHERE username != 'SYSTEM'";
        $params = [];

        if ($search) {
            $where .= " AND (username LIKE ? OR email LIKE ? OR tag LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        $total = $this->db->prepare("SELECT COUNT(*) FROM users {$where}");
        $total->execute($params);
        $totalCount = (int) $total->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare("SELECT * FROM users {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute($params);

        return [
            'users' => $stmt->fetchAll(),
            'total' => $totalCount,
            'pages' => ceil($totalCount / $perPage),
        ];
    }

    public function ban(int $userId, string $until): void
    {
        $this->db->prepare("UPDATE users SET banned_until = ? WHERE id = ?")->execute([$until, $userId]);
    }

    public function unban(int $userId): void
    {
        $this->db->prepare("UPDATE users SET banned_until = NULL WHERE id = ?")->execute([$userId]);
    }

    public function toggleAdmin(int $userId): void
    {
        $this->db->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?")->execute([$userId]);
    }

    public function delete(int $userId): void
    {
        $this->db->prepare("UPDATE users SET is_active = 0, email = CONCAT('deleted_', id, '@deleted'), username = CONCAT('deleted_', id) WHERE id = ?")->execute([$userId]);
    }

    // ── Üye Listesi ───────────────────────────────────────

    public function getMembers(int $page = 1, int $perPage = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;

        $where = "WHERE u.is_active = 1";
        $params = [];

        if ($search) {
            $where .= " AND (u.username LIKE ? OR u.tag LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like];
        }

        $total = $this->db->prepare("SELECT COUNT(*) FROM users u {$where}");
        $total->execute($params);
        $totalCount = (int) $total->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare("
            SELECT u.*,
                (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) as follower_count,
                (SELECT COUNT(*) FROM checkins WHERE user_id = u.id AND is_deleted = 0) as checkin_count
            FROM users u
            {$where}
            ORDER BY follower_count DESC, u.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);

        return [
            'members' => $stmt->fetchAll(),
            'total'   => $totalCount,
            'pages'   => ceil($totalCount / $perPage),
        ];
    }

    // ── Premium ───────────────────────────────────────────

    public function setPremium(int $userId, int $days = 7): void
    {
        try {
            $this->db->prepare("
                UPDATE users SET is_premium = 1, premium_until = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?
            ")->execute([$days, $userId]);
        } catch (\Throwable $e) {
            // premium_until kolonu yoksa sadece flag'i set et
            $this->db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")->execute([$userId]);
        }
    }

    public function renewPremium(int $userId, int $days = 7): void
    {
        try {
            $this->db->prepare("
                UPDATE users SET is_premium = 1, 
                    premium_until = DATE_ADD(GREATEST(COALESCE(premium_until, NOW()), NOW()), INTERVAL ? DAY) 
                WHERE id = ?
            ")->execute([$days, $userId]);
        } catch (\Throwable $e) {
            $this->db->prepare("UPDATE users SET is_premium = 1 WHERE id = ?")->execute([$userId]);
        }
    }

    /**
     * Kullanıcının premium'u aktif mi kontrol et
     */
    public static function isPremiumActive(?array $user): bool
    {
        if (!$user || empty($user['is_premium'])) return false;
        if (!isset($user['premium_until'])) return true;
        if (empty($user['premium_until'])) return false;
        return strtotime($user['premium_until']) > time();
    }

    /**
     * Premium kalan süreyi insan-okunur döndür
     */
    public static function premiumRemainingText(?array $user): string
    {
        if (!self::isPremiumActive($user)) return 'Süresi dolmuş';
        $remaining = strtotime($user['premium_until']) - time();
        $days = floor($remaining / 86400);
        $hours = floor(($remaining % 86400) / 3600);
        if ($days > 0) return $days . ' gün ' . $hours . ' saat';
        if ($hours > 0) return $hours . ' saat';
        return 'Son dakikalar';
    }

    public function updateBadge(int $userId, ?string $badge): void
    {
        try {
            $this->db->prepare("UPDATE users SET badge = ? WHERE id = ?")->execute([$badge, $userId]);
        } catch (\Throwable $e) {
            // badge kolonu yoksa sessizce geç
        }
    }

    public static function availableBadges(): array
    {
        return [
            'diamond'      => ['icon' => 'diamond',        'label' => 'Diamond',       'color' => '#7bd0ff'],
            'crown'        => ['icon' => 'crown',          'label' => 'Crown',         'color' => '#ffd700'],
            'star'         => ['icon' => 'star',           'label' => 'Star',          'color' => '#ff6b35'],
            'verified'     => ['icon' => 'verified',       'label' => 'Verified',      'color' => '#10b981'],
            'bolt'         => ['icon' => 'bolt',           'label' => 'Bolt',          'color' => '#f59e0b'],
            'favorite'     => ['icon' => 'favorite',       'label' => 'Heart',         'color' => '#ef4444'],
            'rocket'       => ['icon' => 'rocket_launch',  'label' => 'Rocket',        'color' => '#8b5cf6'],
            'fire'         => ['icon' => 'local_fire_department', 'label' => 'Fire',   'color' => '#f97316'],
            'shield'       => ['icon' => 'shield',         'label' => 'Shield',        'color' => '#06b6d4'],
            'eco'          => ['icon' => 'eco',            'label' => 'Eco',           'color' => '#22c55e'],
        ];
    }
}
