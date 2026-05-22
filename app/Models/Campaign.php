<?php
/**
 * Campaign Model — Mekan kampanya sistemi
 */
class CampaignModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── CRUD ──────────────────────────────────────────────

    public function create(int $venueId, array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO venue_campaigns
                (venue_id, title, description, trigger_type, trigger_value,
                 reward_type, reward_value, reward_text, is_active, starts_at, ends_at, max_redemptions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $venueId,
            trim($data['title']),
            trim($data['description'] ?? '') ?: null,
            $data['trigger_type'],
            (int)$data['trigger_value'],
            $data['reward_type'],
            isset($data['reward_value']) && $data['reward_value'] !== '' ? (float)$data['reward_value'] : null,
            trim($data['reward_text'] ?? '') ?: null,
            isset($data['is_active']) ? 1 : 0,
            !empty($data['starts_at']) ? $data['starts_at'] : null,
            !empty($data['ends_at'])   ? $data['ends_at']   : null,
            !empty($data['max_redemptions']) ? (int)$data['max_redemptions'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $campaignId, int $venueId, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE venue_campaigns SET
                title = ?, description = ?, trigger_type = ?, trigger_value = ?,
                reward_type = ?, reward_value = ?, reward_text = ?, is_active = ?,
                starts_at = ?, ends_at = ?, max_redemptions = ?
            WHERE id = ? AND venue_id = ?
        ");
        $stmt->execute([
            trim($data['title']),
            trim($data['description'] ?? '') ?: null,
            $data['trigger_type'],
            (int)$data['trigger_value'],
            $data['reward_type'],
            isset($data['reward_value']) && $data['reward_value'] !== '' ? (float)$data['reward_value'] : null,
            trim($data['reward_text'] ?? '') ?: null,
            isset($data['is_active']) ? 1 : 0,
            !empty($data['starts_at']) ? $data['starts_at'] : null,
            !empty($data['ends_at'])   ? $data['ends_at']   : null,
            !empty($data['max_redemptions']) ? (int)$data['max_redemptions'] : null,
            $campaignId,
            $venueId,
        ]);
    }

    public function delete(int $campaignId, int $venueId): void
    {
        $this->db->prepare("DELETE FROM venue_campaigns WHERE id = ? AND venue_id = ?")
                 ->execute([$campaignId, $venueId]);
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM venue_campaigns WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Mekanın tüm kampanyaları (redemption sayısıyla)
     */
    public function getByVenue(int $venueId): array
    {
        $stmt = $this->db->prepare("
            SELECT vc.*,
                   COUNT(cr.id) as redemption_count
            FROM venue_campaigns vc
            LEFT JOIN campaign_redemptions cr ON cr.campaign_id = vc.id
            WHERE vc.venue_id = ?
            GROUP BY vc.id
            ORDER BY vc.created_at DESC
        ");
        $stmt->execute([$venueId]);
        return $stmt->fetchAll();
    }

    /**
     * Mekanın aktif kampanyaları (venue-detail'de gösterilir)
     */
    public function getActiveCampaigns(int $venueId): array
    {
        $stmt = $this->db->prepare("
            SELECT vc.*,
                   COUNT(cr.id) as redemption_count
            FROM venue_campaigns vc
            LEFT JOIN campaign_redemptions cr ON cr.campaign_id = vc.id
            WHERE vc.venue_id = ?
              AND vc.is_active = 1
              AND (vc.starts_at IS NULL OR vc.starts_at <= NOW())
              AND (vc.ends_at   IS NULL OR vc.ends_at   >= NOW())
              AND (vc.max_redemptions IS NULL OR (SELECT COUNT(*) FROM campaign_redemptions WHERE campaign_id = vc.id) < vc.max_redemptions)
            GROUP BY vc.id
            ORDER BY vc.created_at DESC
        ");
        $stmt->execute([$venueId]);
        return $stmt->fetchAll();
    }

    // ── Kullanıcı ilerleme & kazanım ──────────────────────

    /**
     * Kullanıcının bu mekandaki check-in sayısı
     */
    public function getUserCheckinCount(int $userId, int $venueId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM checkins
            WHERE user_id = ? AND venue_id = ? AND is_deleted = 0
        ");
        $stmt->execute([$userId, $venueId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Kullanıcının bu kampanyayı daha önce kazanıp kazanmadığı
     */
    public function hasEarned(int $campaignId, int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM campaign_redemptions WHERE campaign_id = ? AND user_id = ?");
        $stmt->execute([$campaignId, $userId]);
        return (bool)$stmt->fetch();
    }

    /**
     * Kampanya kazanımını kaydet ve kod üret
     */
    public function earn(int $campaignId, int $userId, int $venueId): string
    {
        $code = strtoupper(bin2hex(random_bytes(4)));
        $this->db->prepare("
            INSERT IGNORE INTO campaign_redemptions (campaign_id, user_id, venue_id, code)
            VALUES (?, ?, ?, ?)
        ")->execute([$campaignId, $userId, $venueId, $code]);
        return $code;
    }

    /**
     * Kullanıcının kazanımlarını getir
     */
    public function getUserRedemptions(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT cr.*, vc.title, vc.reward_text, vc.reward_type, vc.reward_value, v.name as venue_name, v.id as venue_id
            FROM campaign_redemptions cr
            JOIN venue_campaigns vc ON cr.campaign_id = vc.id
            JOIN venues v ON cr.venue_id = v.id
            WHERE cr.user_id = ?
            ORDER BY cr.earned_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Check-in sonrası otomatik kampanya kontrolü
     * Yeni check-in yapıldığında bu metod çağrılır
     */
    public function checkAndAwardCampaigns(int $userId, int $venueId): array
    {
        $campaigns = $this->getActiveCampaigns($venueId);
        $userCount = $this->getUserCheckinCount($userId, $venueId);
        $awarded   = [];

        foreach ($campaigns as $c) {
            if ($this->hasEarned($c['id'], $userId)) continue;

            $earned = false;
            switch ($c['trigger_type']) {
                case 'nth_checkin':
                    $earned = ($userCount === (int)$c['trigger_value']);
                    break;
                case 'total_checkins':
                    $earned = ($userCount >= (int)$c['trigger_value']);
                    break;
                case 'first_checkin':
                    $earned = ($userCount === 1);
                    break;
            }

            if ($earned) {
                $code = $this->earn($c['id'], $userId, $venueId);
                $awarded[] = array_merge($c, ['code' => $code]);
            }
        }

        return $awarded;
    }

    // ── Label yardımcıları ────────────────────────────────

    public static function triggerLabels(): array
    {
        return [
            'nth_checkin'     => 'N\'inci Check-in\'de',
            'total_checkins'  => 'Toplam Check-in\'de',
            'first_checkin'   => 'İlk Check-in\'de',
        ];
    }

    public static function rewardLabels(): array
    {
        return [
            'discount_percent' => '% İndirim',
            'discount_fixed'   => 'Sabit İndirim (₺)',
            'free_item'        => 'Ücretsiz Ürün',
            'custom'           => 'Özel Ödül',
        ];
    }

    public static function formatReward(array $campaign): string
    {
        return match($campaign['reward_type']) {
            'discount_percent' => '%' . (int)$campaign['reward_value'] . ' İndirim',
            'discount_fixed'   => '₺' . number_format($campaign['reward_value'], 0) . ' İndirim',
            'free_item'        => 'Ücretsiz: ' . ($campaign['reward_text'] ?? 'Ürün'),
            default            => $campaign['reward_text'] ?? 'Özel Ödül',
        };
    }

    public static function formatTrigger(array $campaign): string
    {
        return match($campaign['trigger_type']) {
            'nth_checkin'    => $campaign['trigger_value'] . '. check-in\'inde',
            'total_checkins' => $campaign['trigger_value'] . ' check-in yapınca',
            'first_checkin'  => 'ilk check-in\'inde',
            default          => $campaign['trigger_value'] . '. check-in',
        };
    }
}
