<?php
/**
 * GTA World TR UCP OAuth Servisi
 * OAuth 2.0 akışı: yönlendirme → token → karakter listesi.
 */

class OAuthGtaWorld
{
    private const AUTH_URL  = 'https://ucp-tr.gta.world/oauth/authorize';
    private const TOKEN_URL = 'https://ucp-tr.gta.world/oauth/token';
    private const USER_URL  = 'https://ucp-tr.gta.world/api/user';
    private const CHARS_URL = 'https://ucp-tr.gta.world/api/characters';

    /**
     * OAuth yetkilendirme URL'si oluştur
     */
    public static function getAuthorizationUrl(): string
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = http_build_query([
            'client_id'     => OAUTH_CLIENT_ID,
            'redirect_uri'  => OAUTH_REDIRECT_URI,
            'response_type' => 'code',
            'scope'         => '',
            'state'         => $state,
        ]);

        return self::AUTH_URL . '?' . $params;
    }

    /**
     * State parametresini doğrula
     */
    public static function verifyState(?string $state): bool
    {
        if (empty($state) || empty($_SESSION['oauth_state'])) {
            return false;
        }
        $valid = hash_equals($_SESSION['oauth_state'], $state);
        unset($_SESSION['oauth_state']);
        return $valid;
    }

    /**
     * Authorization code ile access token al
     */
    public static function getAccessToken(string $code): ?array
    {
        $data = [
            'grant_type'    => 'authorization_code',
            'client_id'     => OAUTH_CLIENT_ID,
            'client_secret' => OAUTH_CLIENT_SECRET,
            'redirect_uri'  => OAUTH_REDIRECT_URI,
            'code'          => $code,
        ];

        $response = self::httpPost(self::TOKEN_URL, $data);

        if (!$response || empty($response['access_token'])) {
            Logger::error('OAuth token exchange failed', ['response' => $response]);
            return null;
        }

        return $response;
    }

    /**
     * Access token ile kullanıcı bilgilerini al
     */
    public static function getUser(string $accessToken): ?array
    {
        return self::httpGet(self::USER_URL, $accessToken);
    }

    /**
     * Access token ile karakter listesini al
     */
    public static function getCharacters(string $accessToken): ?array
    {
        return self::httpGet(self::CHARS_URL, $accessToken);
    }

    // ── HTTP Helper'lar ───────────────────────────────────

    private static function httpPost(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('OAuth HTTP POST error', ['url' => $url, 'error' => $error]);
            return null;
        }

        return json_decode($response, true);
    }

    private static function httpGet(string $url, string $token): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('OAuth HTTP GET error', ['url' => $url, 'error' => $error]);
            return null;
        }

        return json_decode($response, true);
    }
}
