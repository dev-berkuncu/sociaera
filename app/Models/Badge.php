<?php
/**
 * Badge Model — Rozet tanımları ve kazanım kontrolü
 */
class BadgeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        try {
            // Tablo yoksa catch bloğuna düşer
            $stmt = $this->db->query("SHOW COLUMNS FROM user_badges LIKE 'week_start'");
            if ($stmt && $stmt->rowCount() === 0) {
                // week_start kolonu yoksa ekle ve indeksleri güncelle
                $this->db->exec("ALTER TABLE user_badges ADD COLUMN week_start DATE NOT NULL AFTER badge_key");
                $weekStart = self::currentWeekStart();
                $stmt = $this->db->prepare("UPDATE user_badges SET week_start = ? WHERE week_start = '0000-00-00' OR week_start IS NULL");
                $stmt->execute([$weekStart]);
                
                try { $this->db->exec("ALTER TABLE user_badges DROP INDEX uk_user_badge"); } catch (\Throwable $e) {}
                try { $this->db->exec("ALTER TABLE user_badges ADD UNIQUE KEY uk_user_badge_week (user_id, badge_key, week_start)"); } catch (\Throwable $e) {}
                try { $this->db->exec("ALTER TABLE user_badges ADD KEY idx_week_start (week_start)"); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            // Tablo hiç yoksa oluştur
            if (strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'doesn\'t exist') !== false) {
                $sql = file_get_contents(dirname(__DIR__, 2) . '/migrations/create_user_badges.sql');
                if ($sql) {
                    $this->db->exec($sql);
                }
            }
        }
    }

    /**
     * Tüm rozet tanımları
     */
    public static function definitions(): array
    {
        return [
            'newbie' => [
                'name'  => 'Newbie',
                'desc'  => 'İlk check-in\'ini yap',
                'icon'  => 'waving_hand',
                'color' => '#10b981',
                'goal'  => 1,
            ],
            'adventurer' => [
                'name'  => 'Adventurer',
                'desc'  => '10 farklı mekânda check-in yap',
                'icon'  => 'hiking',
                'color' => '#3b82f6',
                'goal'  => 10,
            ],
            'explorer' => [
                'name'  => 'Explorer',
                'desc'  => '25 farklı mekânda check-in yap',
                'icon'  => 'explore',
                'color' => '#8b5cf6',
                'goal'  => 25,
            ],
            'superstar' => [
                'name'  => 'Superstar',
                'desc'  => '50 farklı mekânda check-in yap',
                'icon'  => 'stars',
                'color' => '#f59e0b',
                'goal'  => 50,
            ],
            'bender' => [
                'name'  => 'Bender',
                'desc'  => '4 gün üst üste check-in yap',
                'icon'  => 'local_bar',
                'color' => '#ef4444',
                'goal'  => 4,
            ],
            'crunked' => [
                'name'  => 'Crunked',
                'desc'  => 'Aynı gün 4+ farklı mekânda check-in yap',
                'icon'  => 'celebration',
                'color' => '#ec4899',
                'goal'  => 4,
            ],
            'local' => [
                'name'  => 'Local',
                'desc'  => 'Aynı mekâna bir haftada 3 kez git',
                'icon'  => 'home_pin',
                'color' => '#14b8a6',
                'goal'  => 3,
            ],
            'super_user' => [
                'name'  => 'Super User',
                'desc'  => 'Bir ayda 30 check-in yap',
                'icon'  => 'bolt',
                'color' => '#f97316',
                'goal'  => 30,
            ],
            'night_owl' => [
                'name'  => 'Night Owl',
                'desc'  => 'Gece 03:00 sonrası check-in yap',
                'icon'  => 'dark_mode',
                'color' => '#6366f1',
                'goal'  => 1,
            ],
            'photogenic' => [
                'name'  => 'Photogenic',
                'desc'  => '10 fotoğraflı check-in paylaş',
                'icon'  => 'photo_camera',
                'color' => '#06b6d4',
                'goal'  => 10,
            ],
            'overshare' => [
                'name'  => 'Overshare',
                'desc'  => '12 saat içinde 10+ check-in yap',
                'icon'  => 'share',
                'color' => '#d946ef',
                'goal'  => 10,
            ],
            'social_butterfly' => [
                'name'  => 'Social Butterfly',
                'desc'  => '20 kullanıcı takip et',
                'icon'  => 'groups',
                'color' => '#0ea5e9',
                'goal'  => 20,
            ],
            'trendsetter' => [
                'name'  => 'Trendsetter',
                'desc'  => 'Postların 10 kez repost edilsin',
                'icon'  => 'trending_up',
                'color' => '#a855f7',
                'goal'  => 10,
            ],
            'heartbreaker' => [
                'name'  => 'Heartbreaker',
                'desc'  => 'Toplam 50 beğeni al',
                'icon'  => 'favorite',
                'color' => '#e11d48',
                'goal'  => 50,
            ],
            'centurion' => [
                'name'  => 'Centurion',
                'desc'  => 'Toplam 100 check-in yap',
                'icon'  => 'military_tech',
                'color' => '#ffd700',
                'goal'  => 100,
            ],
            'popular' => [
                'name'  => 'Popular',
                'desc'  => '50 takipçiye ulaş',
                'icon'  => 'people',
                'color' => '#22d3ee',
                'goal'  => 50,
            ],
            'vip' => [
                'name'  => 'VIP',
                'desc'  => 'Premium üye ol',
                'icon'  => 'diamond',
                'color' => '#60A5FA',
                'goal'  => 1,
                'premium_only' => true,
            ],
            'high_roller' => [
                'name'  => 'High Roller',
                'desc'  => 'Premium\'ken 50 check-in yap',
                'icon'  => 'casino',
                'color' => '#FBBF24',
                'goal'  => 50,
                'premium_only' => true,
            ],
            'socialite' => [
                'name'  => 'Sosyetik',
                'desc'  => 'Premium\'ken 10 farklı mekan keşfet',
                'icon'  => 'flare',
                'color' => '#C084FC',
                'goal'  => 10,
                'premium_only' => true,
            ],
            'diamond_life' => [
                'name'  => 'Diamond Life',
                'desc'  => '30 gün boyunca premium ol',
                'icon'  => 'workspace_premium',
                'color' => '#2DD4BF',
                'goal'  => 30,
                'premium_only' => true,
            ],
        ];
    }

    /**
     * Mevcut haftanın başlangıç tarihini al
     */
    private static function currentWeekStart(): string
    {
        $tz = new DateTimeZone(APP_TIMEZONE);
        $now = new DateTime('now', $tz);
        $now->modify('monday this week');
        $now->setTime(0, 0, 0);
        return $now->format('Y-m-d');
    }

    /**
     * Kullanıcının kazandığı rozetleri toplam sayılarıyla getir
     */
    public function getUserBadges(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT badge_key, COUNT(*) as total_count, MAX(earned_at) as last_earned
                FROM user_badges WHERE user_id = ?
                GROUP BY badge_key ORDER BY last_earned DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Bu hafta kazanılan rozet key'lerini döndür
     */
    public function getCurrentWeekBadgeKeys(int $userId): array
    {
        try {
            $weekStart = self::currentWeekStart();
            $stmt = $this->db->prepare("SELECT badge_key FROM user_badges WHERE user_id = ? AND week_start = ?");
            $stmt->execute([$userId, $weekStart]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Rozet ver (haftalık)
     */
    public function award(int $userId, string $badgeKey): bool
    {
        try {
            $weekStart = self::currentWeekStart();
            $stmt = $this->db->prepare("INSERT IGNORE INTO user_badges (user_id, badge_key, week_start, earned_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$userId, $badgeKey, $weekStart]);
            
            if ($stmt->rowCount() > 0) {
                // Kaç kez kazanıldığını bul
                $cntStmt = $this->db->prepare("SELECT COUNT(*) FROM user_badges WHERE user_id = ? AND badge_key = ?");
                $cntStmt->execute([$userId, $badgeKey]);
                $totalCount = (int)$cntStmt->fetchColumn();
                
                try {
                    $defs = self::definitions();
                    $badge = $defs[$badgeKey] ?? null;
                    if ($badge) {
                        $notif = new NotificationModel();
                        $countText = $totalCount > 1 ? ' (x' . $totalCount . ')' : '';
                        $notif->create($userId, null, 'badge', '🏆 "' . $badge['name'] . '" rozetini kazandın' . $countText . '! ' . $badge['desc']);
                    }
                } catch (\Throwable $e) {}
                return true;
            }
            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Her rozet için ilerleme hesapla
     */
    public function getProgress(int $userId): array
    {
        $defs = self::definitions();
        $thisWeekKeys = $this->getCurrentWeekBadgeKeys($userId);
        $allBadges = $this->getUserBadges($userId);
        $badgeCounts = [];
        foreach ($allBadges as $b) {
            $badgeCounts[$b['badge_key']] = (int)$b['total_count'];
        }
        $progress = [];

        foreach ($defs as $key => $def) {
            $current = $this->calculateProgress($userId, $key);
            $thisWeek = in_array($key, $thisWeekKeys);
            $count    = $badgeCounts[$key] ?? 0;
            $progress[$key] = [
                'key'         => $key,
                'name'        => $def['name'],
                'desc'        => $def['desc'],
                'icon'        => $def['icon'],
                'color'       => $def['color'],
                'goal'        => $def['goal'],
                'current'     => min($current, $def['goal']),
                // Daha önce hiç kazanıldıysa VEYA bu hafta kazanıldıysa earned=true
                'earned'      => $count > 0 || $thisWeek,
                'this_week'   => $thisWeek,
                'total_count' => $count,
                'percent'     => min(100, round(($current / max(1, $def['goal'])) * 100)),
                'premium_only' => $def['premium_only'] ?? false,
            ];
        }

        return $progress;
    }

    /**
     * Tek bir rozet için mevcut ilerlemeyi hesapla
     */
    private function calculateProgress(int $userId, string $key): int
    {
        try {
            switch ($key) {
                case 'newbie':
                case 'centurion':
                    // Toplam check-in sayısı
                    $goal = $key === 'newbie' ? 1 : 100;
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM checkins WHERE user_id = ? AND is_deleted = 0");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'adventurer':
                case 'explorer':
                case 'superstar':
                    // Farklı mekan sayısı
                    $stmt = $this->db->prepare("SELECT COUNT(DISTINCT venue_id) FROM checkins WHERE user_id = ? AND is_deleted = 0");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'bender':
                    // Ardışık gün sayısı
                    return $this->getConsecutiveDays($userId);

                case 'crunked':
                    // Bugün kaç farklı mekanda check-in
                    $stmt = $this->db->prepare("
                        SELECT COUNT(DISTINCT venue_id) FROM checkins 
                        WHERE user_id = ? AND is_deleted = 0 AND DATE(created_at) = CURDATE()
                    ");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'local':
                    // Son 7 günde aynı mekana en fazla kaç kez
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) as cnt FROM checkins 
                        WHERE user_id = ? AND is_deleted = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                        GROUP BY venue_id ORDER BY cnt DESC LIMIT 1
                    ");
                    $stmt->execute([$userId]);
                    $row = $stmt->fetch();
                    return $row ? (int)$row['cnt'] : 0;

                case 'super_user':
                    // Son 30 günde check-in sayısı
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) FROM checkins 
                        WHERE user_id = ? AND is_deleted = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'night_owl':
                    // Gece 03:00-05:00 arası check-in
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) FROM checkins 
                        WHERE user_id = ? AND is_deleted = 0 AND HOUR(created_at) BETWEEN 3 AND 4
                    ");
                    $stmt->execute([$userId]);
                    return min(1, (int)$stmt->fetchColumn());

                case 'photogenic':
                    // Fotoğraflı check-in sayısı
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) FROM checkins 
                        WHERE user_id = ? AND is_deleted = 0 AND image IS NOT NULL AND image != ''
                    ");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'overshare':
                    // Son 12 saatte check-in sayısı
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) FROM checkins 
                        WHERE user_id = ? AND is_deleted = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 12 HOUR)
                    ");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'social_butterfly':
                    // Takip edilen kişi sayısı
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_follows WHERE follower_id = ?");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'trendsetter':
                    // Toplam repost edilme sayısı
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) FROM post_reposts pr
                        JOIN checkins c ON pr.checkin_id = c.id
                        WHERE c.user_id = ? AND c.is_deleted = 0
                    ");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'heartbreaker':
                    // Toplam beğeni sayısı
                    $stmt = $this->db->prepare("
                        SELECT COUNT(*) FROM post_likes pl
                        JOIN checkins c ON pl.checkin_id = c.id
                        WHERE c.user_id = ? AND c.is_deleted = 0
                    ");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'popular':
                    // Takipçi sayısı
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_follows WHERE following_id = ?");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'vip':
                    // Premium üye mi?
                    $user = (new UserModel())->getById($userId);
                    return UserModel::isPremiumActive($user) ? 1 : 0;

                case 'high_roller':
                    // 50 check-in (premium iken)
                    $user = (new UserModel())->getById($userId);
                    if (!UserModel::isPremiumActive($user)) return 0;
                    $stmt = $this->db->prepare("SELECT COUNT(*) FROM checkins WHERE user_id = ? AND is_deleted = 0");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'socialite':
                    // 10 farklı mekan (premium iken)
                    $user = (new UserModel())->getById($userId);
                    if (!UserModel::isPremiumActive($user)) return 0;
                    $stmt = $this->db->prepare("SELECT COUNT(DISTINCT venue_id) FROM checkins WHERE user_id = ? AND is_deleted = 0");
                    $stmt->execute([$userId]);
                    return (int)$stmt->fetchColumn();

                case 'diamond_life':
                    // Premium olarak geçen gün sayısı
                    $user = (new UserModel())->getById($userId);
                    if (!UserModel::isPremiumActive($user) || empty($user['premium_until'])) return 0;
                    $premiumUntil = new DateTime($user['premium_until']);
                    $now = new DateTime();
                    // premium_until gelecekte ise, kullanıcı premium_until - şimdi = kalan gün
                    // toplam süre hesaplamak için: premium başlangıç bilinmiyorsa basitleştir
                    $remainingDays = max(0, (int)$now->diff($premiumUntil)->days);
                    // Kalan günleri 365'ten çıkarak yaklaşık süre hesapla (basit)
                    // Daha doğru: premium_until'a kadar olan süreyi hesapla, ama başlangıç yok
                    // Basit yaklaşım: premium aktifse ve premium_until - now farkından toplam süreyi tahmin et
                    return max(1, 365 - $remainingDays); // En az 1 gün premium

                default:
                    return 0;
            }
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Ardışık gün hesapla
     */
    private function getConsecutiveDays(int $userId): int
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT DATE(created_at) as d FROM checkins 
            WHERE user_id = ? AND is_deleted = 0 
            ORDER BY d DESC LIMIT 30
        ");
        $stmt->execute([$userId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($dates)) return 0;

        $streak = 1;
        $today = date('Y-m-d');
        
        // Bugün veya dün check-in yoksa streak 0
        if ($dates[0] !== $today && $dates[0] !== date('Y-m-d', strtotime('-1 day'))) {
            return 0;
        }

        for ($i = 0; $i < count($dates) - 1; $i++) {
            $d1   = new DateTime($dates[$i]);
            $d2   = new DateTime($dates[$i + 1]);
            $diff = (int)$d1->diff($d2)->days;
            if ($diff === 1) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Check-in sonrası tüm rozetleri kontrol et ve kazan
     */
    public function checkAndAward(int $userId): array
    {
        $defs = self::definitions();
        $thisWeekKeys = $this->getCurrentWeekBadgeKeys($userId);
        $newBadges = [];

        foreach ($defs as $key => $def) {
            if (in_array($key, $thisWeekKeys)) continue; // Bu hafta zaten kazanılmış

            // Premium-only rozetleri sadece premium kullanıcılara kontrol et
            if (!empty($def['premium_only'])) continue;

            $current = $this->calculateProgress($userId, $key);
            if ($current >= $def['goal']) {
                if ($this->award($userId, $key)) {
                    $newBadges[] = $def;
                }
            }
        }

        // Premium badges
        $user = (new UserModel())->getById($userId);
        if (UserModel::isPremiumActive($user)) {
            // VIP — Premium üye ol
            if (!in_array('vip', $thisWeekKeys)) {
                $this->award($userId, 'vip');
            }

            // High Roller — 50 check-in (while premium)
            if (!in_array('high_roller', $thisWeekKeys)) {
                $totalCheckins = $this->db->prepare("SELECT COUNT(*) FROM checkins WHERE user_id = ? AND is_deleted = 0");
                $totalCheckins->execute([$userId]);
                if ((int)$totalCheckins->fetchColumn() >= 50) {
                    if ($this->award($userId, 'high_roller')) {
                        $newBadges[] = $defs['high_roller'];
                    }
                }
            }

            // Sosyetik — 10 farklı mekan
            if (!in_array('socialite', $thisWeekKeys)) {
                $uniqueVenues = $this->db->prepare("SELECT COUNT(DISTINCT venue_id) FROM checkins WHERE user_id = ? AND is_deleted = 0");
                $uniqueVenues->execute([$userId]);
                if ((int)$uniqueVenues->fetchColumn() >= 10) {
                    if ($this->award($userId, 'socialite')) {
                        $newBadges[] = $defs['socialite'];
                    }
                }
            }
        }

        return $newBadges;
    }
}
