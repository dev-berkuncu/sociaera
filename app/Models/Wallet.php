<?php
/**
 * Wallet Model
 */
class WalletModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getBalance(int $userId): float
    {
        $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (float) $row['balance'] : 0.0;
    }

    public function getTransactions(int $userId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function ensureWallet(int $userId): void
    {
        $this->db->prepare("INSERT IGNORE INTO wallets (user_id, balance) VALUES (?, 0)")->execute([$userId]);
    }
}
