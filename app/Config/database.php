<?php
/**
 * PDO Veritabanı Bağlantı Fabrikası
 * Lazy singleton pattern — ilk çağrıda bağlantı oluşturur.
 */

class Database
{
    private static ?PDO $instance = null;

    /**
     * Singleton PDO instance döndürür
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host    = env('DB_HOST', 'localhost');
            $dbname  = env('DB_NAME', 'sociaera');
            $user    = env('DB_USER', 'root');
            $pass    = env('DB_PASS', '');
            $charset = env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

            // APP_TIMEZONE sabitini MySQL offset'e çevir
            $phpTz    = new DateTimeZone(defined('APP_TIMEZONE') ? APP_TIMEZONE : 'Europe/Istanbul');
            $utcTz    = new DateTimeZone('UTC');
            $now      = new DateTime('now', $phpTz);
            $offset   = $phpTz->getOffset($now);
            $sign     = $offset >= 0 ? '+' : '-';
            $hours    = str_pad((int) abs($offset / 3600), 2, '0', STR_PAD_LEFT);
            $minutes  = str_pad((int) (abs($offset) % 3600 / 60), 2, '0', STR_PAD_LEFT);
            $mysqlTz  = "{$sign}{$hours}:{$minutes}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci, time_zone = '{$mysqlTz}'",
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                if (APP_ENV === 'development') {
                    die('DB Bağlantı Hatası: ' . $e->getMessage());
                }
                die('Veritabanı bağlantısı kurulamadı. Lütfen daha sonra tekrar deneyin.');
            }
        }

        return self::$instance;
    }

    /**
     * Bağlantıyı sıfırla (test/reconnect için)
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    // Clone ve unserialize engelle
    private function __clone() {}
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton');
    }
}
