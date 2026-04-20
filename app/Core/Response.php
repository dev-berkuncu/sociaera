<?php
/**
 * HTTP Yanıt Yardımcıları
 * JSON çıktı ve yönlendirme kolaylıkları.
 */

class Response
{
    /**
     * JSON yanıt gönder
     */
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Başarılı JSON yanıt
     */
    public static function success($data = null, string $message = ''): void
    {
        $response = ['ok' => true];
        if ($message)     $response['message'] = $message;
        if ($data !== null) $response['data']  = $data;
        self::json($response);
    }

    /**
     * Hata JSON yanıt
     */
    public static function error(string $message, int $code = 400, $data = null): void
    {
        $response = ['ok' => false, 'error' => $message];
        if ($data !== null) $response['data'] = $data;
        self::json($response, $code);
    }

    /**
     * Yönlendirme
     */
    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Flash mesajlı yönlendirme
     */
    public static function redirectWithFlash(string $url, string $type, string $message): void
    {
        Auth::setFlash($type, $message);
        self::redirect($url);
    }

    /**
     * 404 sayfası
     */
    public static function notFound(string $message = 'Sayfa bulunamadı'): void
    {
        http_response_code(404);
        echo '<h1>404</h1><p>' . escape($message) . '</p>';
        exit;
    }

    /**
     * POST method kontrolü
     */
    public static function requirePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::error('Geçersiz istek metodu.', 405);
        }
    }

    /**
     * Auth + CSRF ile korumalı API endpoint kontrolü
     */
    public static function requireAuthApi(): void
    {
        if (!Auth::check()) {
            self::error('Oturum açmanız gerekiyor.', 401);
        }
        // Ban kontrolü
        Auth::requireLogin();
    }
}
