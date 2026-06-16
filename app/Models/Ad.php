<?php
/**
 * Ad Model — Reklam CRUD
 */
class AdModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAll(): array
    {
        return $this->db->query("SELECT * FROM ads ORDER BY position, sort_order, id DESC")->fetchAll();
    }

    public function getByPosition(string $position, int $limit = 10): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ads WHERE position = ? AND status = 'approved' AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY sort_order, id DESC LIMIT ?
        ");
        $stmt->execute([$position, $limit]);
        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM ads WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $title, string $imageUrl, ?string $linkUrl, string $position, int $sortOrder = 0): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO ads (title, image_url, link_url, position, sort_order) VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $imageUrl, $linkUrl, $position, $sortOrder]);
        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $this->db->prepare("DELETE FROM ads WHERE id = ?")->execute([$id]);
    }

    public function toggleActive(int $id): void
    {
        $this->db->prepare("UPDATE ads SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    }

    public function getActiveForFeed(int $limit = 5): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ads WHERE position = 'feed' AND status = 'approved' AND is_active = 1
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY sort_order ASC, id DESC LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Kullanıcı sponsorlu reklamı oluştur
     */
    public function createSponsored(string $title, string $imageUrl, ?string $linkUrl, int $userId, string $mediaType = 'image'): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO ads (title, image_url, link_url, position, is_active, status, user_id, expires_at, media_type) 
            VALUES (?, ?, ?, 'feed', 0, 'pending', ?, NULL, ?)
        ");
        $stmt->execute([$title, $imageUrl, $linkUrl, $userId, $mediaType]);
        return (int) $this->db->lastInsertId();
    }

    public function approve(int $id): void
    {
        $this->db->prepare("UPDATE ads SET status = 'approved', is_active = 1, expires_at = DATE_ADD(NOW(), INTERVAL 1 WEEK) WHERE id = ?")->execute([$id]);
    }

    public function reject(int $id): void
    {
        $this->db->prepare("UPDATE ads SET status = 'rejected', is_active = 0 WHERE id = ?")->execute([$id]);
    }

    /**
     * Kullanıcının oluşturduğu reklamları getir
     */
    public function getByUserId(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ads WHERE user_id = ? ORDER BY id DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

