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
            SELECT * FROM ads WHERE position = ? AND is_active = 1
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
}
