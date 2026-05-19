<?php
/**
 * MysteryShopperModel — Gizli Müşteri Sistemi
 */
class MysteryShopperModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // ── Başvuru ───────────────────────────────────────────

    public function apply(int $userId, string $motivation): array
    {
        // Zaten başvurmuş mu?
        $existing = $this->getByUser($userId);
        if ($existing) {
            if ($existing['status'] === 'approved') {
                return ['ok' => false, 'error' => 'Zaten onaylı bir gizli müşterisiniz.'];
            }
            if ($existing['status'] === 'pending') {
                return ['ok' => false, 'error' => 'Başvurunuz inceleniyor. Lütfen bekleyin.'];
            }
            // Reddedilmiş → tekrar başvurabilir, güncelle
            $this->db->prepare("
                UPDATE mystery_shoppers
                SET motivation = ?, status = 'pending', admin_note = NULL,
                    reviewed_by = NULL, reviewed_at = NULL, applied_at = NOW()
                WHERE user_id = ?
            ")->execute([trim($motivation), $userId]);
            return ['ok' => true];
        }

        $this->db->prepare("
            INSERT INTO mystery_shoppers (user_id, motivation) VALUES (?, ?)
        ")->execute([$userId, trim($motivation)]);
        return ['ok' => true];
    }

    public function getByUser(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM mystery_shoppers WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    public function isApproved(int $userId): bool
    {
        $stmt = $this->db->prepare("SELECT status FROM mystery_shoppers WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row && $row['status'] === 'approved';
    }

    // ── Admin işlemleri ───────────────────────────────────

    public function getAll(string $status = '', int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;
        $where  = $status ? "WHERE ms.status = ?" : "";
        $params = $status ? [$status, $perPage, $offset] : [$perPage, $offset];

        $stmt = $this->db->prepare("
            SELECT ms.*, u.username, u.avatar, u.tag,
                   a.username as reviewer_name
            FROM mystery_shoppers ms
            JOIN users u ON ms.user_id = u.id
            LEFT JOIN users a ON ms.reviewed_by = a.id
            {$where}
            ORDER BY
                FIELD(ms.status, 'pending', 'approved', 'rejected'),
                ms.applied_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function approve(int $applicationId, int $adminId, ?string $note = null): void
    {
        $this->db->prepare("
            UPDATE mystery_shoppers
            SET status = 'approved', reviewed_by = ?, admin_note = ?, reviewed_at = NOW()
            WHERE id = ?
        ")->execute([$adminId, $note, $applicationId]);
    }

    public function reject(int $applicationId, int $adminId, ?string $note = null): void
    {
        $this->db->prepare("
            UPDATE mystery_shoppers
            SET status = 'rejected', reviewed_by = ?, admin_note = ?, reviewed_at = NOW()
            WHERE id = ?
        ")->execute([$adminId, $note, $applicationId]);
    }

    public function countPending(): int
    {
        return (int)$this->db->query("SELECT COUNT(*) FROM mystery_shoppers WHERE status = 'pending'")->fetchColumn();
    }
}
