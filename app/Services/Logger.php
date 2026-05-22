<?php
/**
 * Loglama Servisi
 * Dosya tabanlı uygulama logu. Kullanıcıya ait IP/UA bilgisi tutulmaz.
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
     * Admin işlem logu — kaldırıldı (kullanıcı verisi tutulmayacak politikası).
     * Geriye dönük uyumluluk için metot korunuyor, içi boş.
     */
    public static function adminAudit(
        string $actionType,
        string $targetType,
        ?int   $targetId = null,
        mixed  $details  = '',
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        // no-op — IP, user-agent ve kullanıcı işlem verisi artık kaydedilmiyor.
    }
}
