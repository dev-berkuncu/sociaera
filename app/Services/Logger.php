<?php
/**
 * Loglama Servisi
 * Dosya tabanlı uygulama logu + admin audit log.
 */

class Logger
{
    /**
     * Uygulama logu yaz
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        $logDir  = STORAGE_PATH . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/app.log';
        $date    = date('Y-m-d H:i:s');
        $ctx     = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line    = "[{$date}] [{$level}] {$message}{$ctx}" . PHP_EOL;

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    /**
     * Admin işlem logla (veritabanına)
     */
    public static function adminAudit(string $actionType, string $targetType, ?int $targetId = null, string $details = '', ?string $oldValue = null, ?string $newValue = null): void
    {
        if (!Auth::isAdmin()) return;

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, details, ip, old_value, new_value, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                Auth::id(),
                $actionType,
                $targetType,
                $targetId,
                $details,
                RateLimit::getClientIp(),
                $oldValue,
                $newValue,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            ]);
        } catch (Exception $e) {
            self::error('Admin audit log failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Audit log'ları getir (admin panel için)
     */
    public static function getAuditLogs(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;
        $where = "1=1";
        $params = [];

        if (!empty($filters['admin_id'])) {
            $where .= " AND al.admin_id = ?";
            $params[] = $filters['admin_id'];
        }
        if (!empty($filters['action_type'])) {
            $where .= " AND al.action_type = ?";
            $params[] = $filters['action_type'];
        }

        $countParams = $params;
        $countStmt = $db->prepare("SELECT COUNT(*) FROM admin_logs al WHERE {$where}");
        $countStmt->execute($countParams);
        $total = (int) $countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $db->prepare("
            SELECT al.*, u.username as admin_name
            FROM admin_logs al
            JOIN users u ON al.admin_id = u.id
            WHERE {$where}
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);

        return [
            'logs'  => $stmt->fetchAll(),
            'total' => $total,
            'pages' => ceil($total / $perPage),
        ];
    }
}
