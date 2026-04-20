<?php
/**
 * CSRF Token Yönetimi
 * Form ve AJAX isteklerinde cross-site request forgery koruması.
 */

class Csrf
{
    private const TOKEN_KEY = 'csrf_token';

    /**
     * Token üret (yoksa yeni oluştur)
     */
    public static function generate(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Token doğrula
     */
    public static function verify(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    /**
     * Token yenile (login/logout sonrası)
     */
    public static function regenerate(): string
    {
        unset($_SESSION[self::TOKEN_KEY]);
        return self::generate();
    }

    /**
     * Form hidden input döndür (HTML)
     */
    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
    }

    /**
     * Meta tag döndür (AJAX istekleri için head'e ekle)
     */
    public static function meta(): string
    {
        $token = self::generate();
        return '<meta name="csrf-token" content="' . escape($token) . '">';
    }

    /**
     * POST veya Header'dan token doğrula — başarısızsa JSON hata döndür
     */
    public static function verifyRequest(): bool
    {
        $token = $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;

        return self::verify($token);
    }

    /**
     * Doğrulama başarısızsa işlemi durdur
     */
    public static function requireValid(): void
    {
        if (!self::verifyRequest()) {
            if (self::isAjax()) {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'Güvenlik hatası (CSRF). Sayfayı yenileyin.']);
                exit;
            }
            Auth::setFlash('error', 'Güvenlik hatası (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.');
            header('Location: ' . $_SERVER['HTTP_REFERER'] ?? BASE_URL);
            exit;
        }
    }

    /**
     * AJAX isteği mi?
     */
    private static function isAjax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

// ── Global shortcut fonksiyonlar ──────────────────────────
function csrfField(): string
{
    return Csrf::field();
}

function csrfMeta(): string
{
    return Csrf::meta();
}

function csrfToken(): string
{
    return Csrf::generate();
}

function validateCsrfToken(?string $token = null): bool
{
    if ($token !== null) {
        return Csrf::verify($token);
    }
    return Csrf::verifyRequest();
}
