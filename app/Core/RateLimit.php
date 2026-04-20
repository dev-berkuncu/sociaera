<?php
/**
 * Dosya Tabanlı Rate Limiter
 * IP bazında istek sınırlama (login, check-in vb.)
 */

class RateLimit
{
    private string $storageDir;

    public function __construct()
    {
        $this->storageDir = STORAGE_PATH . '/ratelimits';
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
    }

    /**
     * Rate limit kontrolü
     *
     * @param string $key      Benzersiz anahtar (örn: "login_192.168.1.1")
     * @param int    $maxAttempts  İzin verilen maksimum deneme
     * @param int    $windowSeconds  Zaman penceresi (saniye)
     * @return bool  true = izin verildi, false = limit aşıldı
     */
    public function attempt(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $file = $this->getFilePath($key);
        $data = $this->loadData($file);
        $now  = time();

        // Süresi dolmuş kayıtları temizle
        $data = array_filter($data, fn($timestamp) => ($now - $timestamp) < $windowSeconds);

        if (count($data) >= $maxAttempts) {
            return false;
        }

        // Yeni denemeyi kaydet
        $data[] = $now;
        $this->saveData($file, $data);

        return true;
    }

    /**
     * Kalan deneme sayısı
     */
    public function remaining(string $key, int $maxAttempts, int $windowSeconds): int
    {
        $file = $this->getFilePath($key);
        $data = $this->loadData($file);
        $now  = time();

        $data = array_filter($data, fn($timestamp) => ($now - $timestamp) < $windowSeconds);

        return max(0, $maxAttempts - count($data));
    }

    /**
     * Belirli anahtarın limitini sıfırla
     */
    public function reset(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Eski rate limit dosyalarını temizle
     */
    public function cleanup(int $olderThanSeconds = 7200): void
    {
        $files = glob($this->storageDir . '/*.json');
        $now   = time();

        foreach ($files as $file) {
            if (($now - filemtime($file)) > $olderThanSeconds) {
                @unlink($file);
            }
        }
    }

    /**
     * İstemci IP adresi
     */
    public static function getClientIp(): string
    {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        // Birden fazla IP varsa ilkini al
        if (strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }

    // ── Private ───────────────────────────────────────────
    private function getFilePath(string $key): string
    {
        return $this->storageDir . '/' . md5($key) . '.json';
    }

    private function loadData(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }
        $content = @file_get_contents($file);
        return $content ? (json_decode($content, true) ?: []) : [];
    }

    private function saveData(string $file, array $data): void
    {
        @file_put_contents($file, json_encode(array_values($data)), LOCK_EX);
    }
}
