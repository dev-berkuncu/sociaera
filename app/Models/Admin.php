<?php
/**
 * Admin Model — Dashboard istatistikleri ve admin-spesifik sorgular
 */

class AdminModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Dashboard İstatistikleri ──────────────────────────

    public function getDashboardStats(): array
    {
        $stats = [];

        // Kullanıcılar
        $stats['total_users'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM users WHERE is_active = 1"
        )->fetchColumn();

        $stats['today_registrations'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();

        $stats['active_users'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM users WHERE is_active = 1 AND last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        )->fetchColumn();

        // Premium
        $stats['premium_users'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM users WHERE is_premium = 1 AND is_active = 1"
        )->fetchColumn();

        // Mekanlar
        $stats['total_venues'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM venues WHERE status = 'approved'"
        )->fetchColumn();

        // Check-in'ler
        $stats['total_checkins'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM checkins WHERE is_deleted = 0"
        )->fetchColumn();

        $stats['today_checkins'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM checkins WHERE is_deleted = 0 AND DATE(created_at) = CURDATE()"
        )->fetchColumn();

        // Raporlar
        try {
            $stats['pending_reports'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM content_reports WHERE status = 'pending'"
            )->fetchColumn();
        } catch (\Throwable $e) {
            $stats['pending_reports'] = 0;
        }

        // Ödemeler (Platform Kazancı)
        try {
            $stats['successful_payments'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM transactions WHERE type = 'deposit'"
            )->fetchColumn();

            $stats['monthly_earnings'] = (float) $this->db->query(
                "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"
            )->fetchColumn();

            $stats['total_earnings'] = (float) $this->db->query(
                "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'deposit'"
            )->fetchColumn();

            $stats['total_wallet_balance'] = (float) $this->db->query(
                "SELECT COALESCE(SUM(balance), 0) FROM wallets"
            )->fetchColumn();
        } catch (\Throwable $e) {
            $stats['successful_payments'] = 0;
            $stats['monthly_earnings'] = 0;
            $stats['total_earnings'] = 0;
            $stats['total_wallet_balance'] = 0;
        }

        // Bekleyen mekanlar
        $stats['pending_venues'] = (int) $this->db->query(
            "SELECT COUNT(*) FROM venues WHERE status = 'pending'"
        )->fetchColumn();

        return $stats;
    }

    // ── Grafik Verileri ──────────────────────────────────

    public function getRegistrationChart(int $days = 7): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) as day, COUNT(*) as count
            FROM users
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $stmt->execute([$days]);
        return $this->fillDays($stmt->fetchAll(), $days);
    }

    public function getCheckinChart(int $days = 7): array
    {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) as day, COUNT(*) as count
            FROM checkins
            WHERE is_deleted = 0 AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY day ASC
        ");
        $stmt->execute([$days]);
        return $this->fillDays($stmt->fetchAll(), $days);
    }

    // ── Top Listeler ─────────────────────────────────────

    public function getTopVenues(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.category, COUNT(c.id) as checkin_count
            FROM venues v
            JOIN checkins c ON c.venue_id = v.id AND c.is_deleted = 0
            WHERE v.status = 'approved'
            GROUP BY v.id
            ORDER BY checkin_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getTopUsers(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.tag, u.avatar, COUNT(c.id) as checkin_count
            FROM users u
            JOIN checkins c ON c.user_id = u.id AND c.is_deleted = 0
            WHERE u.is_active = 1
            GROUP BY u.id
            ORDER BY checkin_count DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function getRecentTransactions(int $limit = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT t.*, u.username
                FROM transactions t
                JOIN users u ON t.user_id = u.id
                ORDER BY t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getRecentReports(int $limit = 5): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT cr.*, u.username as reporter_name
                FROM content_reports cr
                JOIN users u ON cr.reporter_id = u.id
                WHERE cr.status = 'pending'
                ORDER BY cr.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Private Helpers ──────────────────────────────────

    private function fillDays(array $rows, int $days): array
    {
        $map = [];
        foreach ($rows as $r) {
            $map[$r['day']] = (int) $r['count'];
        }

        $result = ['labels' => [], 'data' => []];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $result['labels'][] = date('d M', strtotime($day));
            $result['data'][] = $map[$day] ?? 0;
        }
        return $result;
    }
}
