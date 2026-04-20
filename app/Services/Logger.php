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
    public static function adminAudit(string $actionType, string $targetType, ?int $targetId = null, string $details = ''): void
    {
        if (!Auth::isAdmin()) return;

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, details, ip, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                Auth::id(),
                $actionType,
                $targetType,
                $targetId,
                $details,
                RateLimit::getClientIp(),
            ]);
        } catch (Exception $e) {
            self::error('Admin audit log failed', ['error' => $e->getMessage()]);
        }
    }
}
