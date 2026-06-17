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
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Miktar sıfırdan büyük olmalıdır.');
        }

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
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Miktar sıfırdan büyük olmalıdır.');
        }

        $this->db->beginTransaction();
        try {
            // Kilitli okuma — eşzamanlı çekim işlemlerini önler
            $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $balance = (float)($stmt->fetchColumn() ?: 0);
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

    public function requestWithdrawal(int $userId, float $amount, string $accountInfo): bool
    {
        if ($amount < 10000) {
            throw new \InvalidArgumentException('Minimum çekim tutarı 10.000$ olmalıdır.');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $balance = (float)($stmt->fetchColumn() ?: 0);
            
            if ($balance < $amount) {
                $this->db->rollBack();
                return false;
            }

            // Bakiyeyi düş
            $stmt = $this->db->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$amount, $userId]);

            // İşlem kaydı oluştur
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'withdraw', ?, 'Para Çekme Talebi (Beklemede)')
            ");
            $stmt->execute([$userId, $amount]);

            // Çekim talebini kaydet
            $stmt = $this->db->prepare("
                INSERT INTO withdrawal_requests (user_id, amount, account_info, status) VALUES (?, ?, ?, 'pending')
            ");
            $stmt->execute([$userId, $amount, $accountInfo]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Bakiyeden direkt harcama/ödeme yap
     */
    public function pay(int $userId, float $amount, string $description = ''): bool
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Miktar sıfırdan büyük olmalıdır.');
        }

        $this->db->beginTransaction();
        try {
            // Kilitli okuma — eşzamanlı çekim ve ödeme işlemlerini önler
            $stmt = $this->db->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $balance = (float)($stmt->fetchColumn() ?: 0);
            if ($balance < $amount) {
                $this->db->rollBack();
                return false;
            }

            // Bakiyeyi düş
            $stmt = $this->db->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ?");
            $stmt->execute([$amount, $userId]);

            // İşlem kaydını 'approved' (yani tamamlanmış) olarak ekle
            $referenceId = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
            $stmt = $this->db->prepare("
                INSERT INTO transactions (user_id, type, amount, description, status, reference_id) VALUES (?, 'withdraw', ?, ?, 'approved', ?)
            ");
            $stmt->execute([$userId, $amount, $description, $referenceId]);

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

