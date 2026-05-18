<?php
/**
 * Report Model — İçerik raporları CRUD
 */

class ReportModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Oluşturma ────────────────────────────────────────

    public function create(int $reporterId, string $entityType, int $entityId, string $reason, ?string $description = null): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO content_reports (reporter_id, entity_type, entity_id, reason, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$reporterId, $entityType, $entityId, $reason, $description]);
        return (int) $this->db->lastInsertId();
    }

    // ── Listeleme ────────────────────────────────────────

    public function getAll(int $page = 1, int $perPage = 30, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $where .= " AND cr.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['entity_type'])) {
            $where .= " AND cr.entity_type = ?";
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['reason'])) {
            $where .= " AND cr.reason = ?";
            $params[] = $filters['reason'];
        }

        $countParams = $params;
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM content_reports cr WHERE {$where}");
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare("
            SELECT cr.*, u.username as reporter_name, u.avatar as reporter_avatar
            FROM content_reports cr
            JOIN users u ON cr.reporter_id = u.id
            WHERE {$where}
            ORDER BY
                FIELD(cr.status, 'pending', 'reviewed', 'resolved', 'dismissed'),
                cr.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);

        return [
            'reports' => $stmt->fetchAll(),
            'total'   => $total,
            'pages'   => ceil($total / $perPage),
        ];
    }

    // ── Detay ────────────────────────────────────────────

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT cr.*, u.username as reporter_name, u.avatar as reporter_avatar
            FROM content_reports cr
            JOIN users u ON cr.reporter_id = u.id
            WHERE cr.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Raporlanan entity'nin bilgilerini getir
     */
    public function getReportedEntity(string $type, int $id): ?array
    {
        switch ($type) {
            case 'checkin':
                $stmt = $this->db->prepare("
                    SELECT c.*, u.username, v.name as venue_name
                    FROM checkins c
                    JOIN users u ON c.user_id = u.id
                    JOIN venues v ON c.venue_id = v.id
                    WHERE c.id = ?
                ");
                break;
            case 'comment':
                $stmt = $this->db->prepare("
                    SELECT pc.*, u.username
                    FROM post_comments pc
                    JOIN users u ON pc.user_id = u.id
                    WHERE pc.id = ?
                ");
                break;
            case 'user':
                $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
                break;
            case 'venue':
                $stmt = $this->db->prepare("SELECT * FROM venues WHERE id = ?");
                break;
            default:
                return null;
        }
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ── Aksiyon ──────────────────────────────────────────

    public function resolve(int $id, int $adminId, string $note, string $status = 'resolved'): void
    {
        $stmt = $this->db->prepare("
            UPDATE content_reports
            SET status = ?, admin_id = ?, admin_note = ?, resolved_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $adminId, $note, $id]);
    }

    public function dismiss(int $id, int $adminId, string $note = ''): void
    {
        $this->resolve($id, $adminId, $note, 'dismissed');
    }

    // ── Sayaçlar ─────────────────────────────────────────

    public function getPendingCount(): int
    {
        try {
            return (int) $this->db->query(
                "SELECT COUNT(*) FROM content_reports WHERE status = 'pending'"
            )->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getEntityReportCount(string $entityType, int $entityId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM content_reports WHERE entity_type = ? AND entity_id = ?"
        );
        $stmt->execute([$entityType, $entityId]);
        return (int) $stmt->fetchColumn();
    }
}
