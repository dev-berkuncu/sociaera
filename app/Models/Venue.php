<?php
/**
 * Venue Model — Mekan CRUD ve iş mantığı
 */

class VenueModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── CRUD ──────────────────────────────────────────────

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM venues WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function getApproved(string $search = '', string $category = '', int $limit = 50): array
    {
        $where  = "WHERE status = 'approved' AND is_active = 1";
        $params = [];

        if ($search) {
            $where .= " AND (name LIKE ? OR description LIKE ? OR address LIKE ?)";
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        if ($category) {
            $where .= " AND category = ?";
            $params[] = $category;
        }

        $params[] = $limit;

        $stmt = $this->db->prepare("
            SELECT v.*,
                (SELECT COUNT(*) FROM checkins WHERE venue_id = v.id AND is_deleted = 0) as checkin_count
            FROM venues v {$where}
            ORDER BY checkin_count DESC, v.name ASC
            LIMIT ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO venues (name, description, address, website, category, facebrowser_url, status, is_active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['address'] ?? null,
            $data['website'] ?? null,
            $data['category'] ?? null,
            $data['facebrowser_url'] ?? null,
            $data['status'] ?? 'pending',
            $data['is_active'] ?? 1,
            $data['created_by'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sets   = [];
        $params = [];

        foreach (['name', 'description', 'address', 'website', 'category', 'facebrowser_url', 'status', 'is_active', 'image'] as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]   = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($sets)) return;

        $params[] = $id;
        $sql = "UPDATE venues SET " . implode(', ', $sets) . " WHERE id = ?";
        $this->db->prepare($sql)->execute($params);
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM venues WHERE id = ?")->execute([$id]);
    }

    // ── Onay İşlemleri ────────────────────────────────────

    public function approve(int $id): void
    {
        $this->update($id, ['status' => 'approved', 'is_active' => 1]);
    }

    public function reject(int $id): void
    {
        $this->update($id, ['status' => 'rejected', 'is_active' => 0]);
    }

    // ── Trend Mekanlar ────────────────────────────────────

    public function getTrending(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT v.id, v.name, v.category, COUNT(c.id) as weekly_checkins
            FROM venues v
            JOIN checkins c ON c.venue_id = v.id AND c.is_deleted = 0
            WHERE c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND v.status = 'approved' AND v.is_active = 1
            GROUP BY v.id
            ORDER BY weekly_checkins DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    // ── İstatistikler ─────────────────────────────────────

    public function getCheckinCount(int $venueId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM checkins WHERE venue_id = ? AND is_deleted = 0");
        $stmt->execute([$venueId]);
        return (int) $stmt->fetchColumn();
    }

    // ── Arama (autocomplete) ──────────────────────────────

    public function search(string $query, int $limit = 8): array
    {
        $like = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT id, name, category FROM venues
            WHERE (name LIKE ? OR address LIKE ?) AND status = 'approved' AND is_active = 1
            ORDER BY name LIMIT ?
        ");
        $stmt->execute([$like, $like, $limit]);
        return $stmt->fetchAll();
    }

    // ── Admin ─────────────────────────────────────────────

    public function getAll(string $status = '', string $search = ''): array
    {
        $where  = "1=1";
        $params = [];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where .= " AND (name LIKE ? OR address LIKE ?)";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $stmt = $this->db->prepare("
            SELECT v.*,
                (SELECT COUNT(*) FROM checkins WHERE venue_id = v.id AND is_deleted = 0) as checkin_count,
                (SELECT username FROM users WHERE id = v.created_by) as creator_name
            FROM venues v
            WHERE {$where}
            ORDER BY
                FIELD(v.status, 'pending', 'approved', 'rejected'),
                v.created_at DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPendingCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM venues WHERE status = 'pending'")->fetchColumn();
    }

    // ── Kategoriler ───────────────────────────────────────

    public static function categories(): array
    {
        return [
            'restoran'  => 'Restoran',
            'kafe'      => 'Kafe',
            'bar'       => 'Bar & Gece Kulübü',
            'otel'      => 'Otel & Konaklama',
            'alisveris' => 'Alışveriş',
            'eglence'   => 'Eğlence',
            'spor'      => 'Spor & Fitness',
            'saglik'    => 'Sağlık & Güzellik',
            'kultur'    => 'Kültür & Sanat',
            'diger'     => 'Diğer',
        ];
    }
}
