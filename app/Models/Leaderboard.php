<?php
/**
 * Leaderboard Model — Haftalık sıralama
 */
class LeaderboardModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public static function getWeekRange(): array
    {
        $tz = new DateTimeZone(APP_TIMEZONE);
        $now = new DateTime('now', $tz);

        // Bu haftanın pazartesi 00:00
        $start = clone $now;
        $start->modify('monday this week');
        $start->setTime(0, 0, 0);

        // Bu haftanın pazar 23:59:59
        $end = clone $start;
        $end->modify('+6 days');
        $end->setTime(23, 59, 59);

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end'   => $end->format('Y-m-d H:i:s'),
        ];
    }

    public function getTopUsers(int $limit = 10): array
    {
        $week = self::getWeekRange();

        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.tag, u.avatar, u.is_premium, u.badge,
                   COUNT(c.id) as checkin_count,
                   MIN(c.created_at) as first_checkin
            FROM checkins c
            JOIN users u ON c.user_id = u.id
            WHERE c.is_deleted = 0 AND c.is_excluded_from_leaderboard = 0
              AND c.created_at BETWEEN ? AND ?
              AND u.is_active = 1
            GROUP BY u.id
            ORDER BY checkin_count DESC, u.is_premium DESC, first_checkin ASC
            LIMIT ?
        ");
        $stmt->execute([$week['start'], $week['end'], $limit]);
        return $stmt->fetchAll();
    }

    public function getTopVenues(int $limit = 10): array
    {
        $week = self::getWeekRange();

        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.category,
                   COUNT(c.id) as checkin_count
            FROM checkins c
            JOIN venues v ON c.venue_id = v.id
            WHERE c.is_deleted = 0 AND c.is_excluded_from_leaderboard = 0
              AND c.created_at BETWEEN ? AND ?
              AND v.status = 'approved'
            GROUP BY v.id
            ORDER BY checkin_count DESC
            LIMIT ?
        ");
        $stmt->execute([$week['start'], $week['end'], $limit]);
        return $stmt->fetchAll();
    }

    public function getUserRank(int $userId): ?int
    {
        $week = self::getWeekRange();

        $stmt = $this->db->prepare("
            SELECT user_id, COUNT(*) as cnt
            FROM checkins
            WHERE is_deleted = 0 AND is_excluded_from_leaderboard = 0
              AND created_at BETWEEN ? AND ?
            GROUP BY user_id
            ORDER BY cnt DESC, MIN(created_at) ASC
        ");
        $stmt->execute([$week['start'], $week['end']]);
        $rows = $stmt->fetchAll();

        foreach ($rows as $i => $row) {
            if ((int) $row['user_id'] === $userId) {
                return $i + 1;
            }
        }
        return null;
    }
}
