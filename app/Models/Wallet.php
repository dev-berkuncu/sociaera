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

    public function deposit(int $userId, float $amount, string $description = ''): void
    {
        $this->db->beginTransaction();
        try {
            // Bakiyeyi güncelle
            $stmt = $this->db->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ?");
            $stmt->execute([$amount, $userId]);

            // İşlem kaydı oluştur
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'deposit', ?, ?)
            ");
            $stmt->execute([$userId, $amount, $description]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function withdraw(int $userId, float $amount, string $description = ''): bool
    {
        $this->db->beginTransaction();
        try {
            $balance = $this->getBalance($userId);
            if ($balance < $amount) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$amount, $userId]);

            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'withdraw', ?, ?)
            ");
            $stmt->execute([$userId, $amount, $description]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
