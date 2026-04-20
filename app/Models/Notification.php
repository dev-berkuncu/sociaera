<?php
/**
 * Notification Model
 */
class NotificationModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function create(int $userId, int $fromUserId, string $type, string $content, ?int $checkinId = null): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO notifications (user_id, from_user_id, type, content, checkin_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $fromUserId, $type, $content, $checkinId]);
    }

    public function getForUser(int $userId, int $limit = 30): array
    {
        $stmt = $this->db->prepare("
            SELECT n.*, u.username as from_username, u.avatar as from_avatar
            FROM notifications n
            LEFT JOIN users u ON n.from_user_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markAllRead(int $userId): void
    {
        $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
    }

    public function clearAll(int $userId): void
    {
        $this->db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
    }
}
