<?php
/**
 * GTA World TR UCP OAuth Servisi
 * Dokümantasyon: https://ucp-tr.gta.world/oauth
 *
 * Akış: authorize → token → /api/user (kullanıcı + karakterler tek endpoint)
 */

class OAuthGtaWorld
{
    private const AUTH_URL  = 'https://ucp-tr.gta.world/oauth/authorize';
    private const TOKEN_URL = 'https://ucp-tr.gta.world/oauth/token';
    private const USER_URL  = 'https://ucp-tr.gta.world/api/user';

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
     *
     * POST https://ucp-tr.gta.world/oauth/token
     * grant_type=authorization_code&client_id=...&client_secret=...&redirect_uri=...&code=...
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
     *
     * GET https://ucp-tr.gta.world/api/user
     * Authorization: Bearer TOKEN
     *
     * Yanıt yapısı:
     * {
     *   "user": {
     *     "id": 1,
     *     "username": "TestUser",
     *     "confirmed": 1,
     *     "role": { ... },
     *     "character": [
     *       { "id": 425345, "memberid": 1, "firstname": "Johnny", "lastname": "Parker" },
     *       ...
     *     ]
     *   }
     * }
     */
    public static function getUser(string $accessToken): ?array
    {
        $response = self::httpGet(self::USER_URL, $accessToken);

        if (!$response) {
            Logger::error('OAuth user fetch failed - null response');
            return null;
        }

        // API "user" anahtarı içinde döner
        if (isset($response['user'])) {
            return $response['user'];
        }

        // Doğrudan user objesi dönmüş olabilir (fallback)
        if (isset($response['id'])) {
            return $response;
        }

        Logger::error('OAuth user fetch - unexpected format', ['response' => $response]);
        return null;
    }

    /**
     * Kullanıcı yanıtından karakter listesini çıkar
     * Karakterler /api/user yanıtındaki "character" dizisinde geliyor
     *
     * @param array $userData getUser() çıktısı
     * @return array Karakter listesi [['id', 'name'], ...]
     */
    public static function extractCharacters(array $userData): array
    {
        $characters = $userData['character'] ?? [];
        $result = [];

        foreach ($characters as $char) {
            $result[] = [
                'id'        => $char['id'] ?? 0,
                'memberid'  => $char['memberid'] ?? 0,
                'firstname' => $char['firstname'] ?? '',
                'lastname'  => $char['lastname'] ?? '',
                'name'      => trim(($char['firstname'] ?? '') . ' ' . ($char['lastname'] ?? '')),
            ];
        }

        return $result;
    }

    // ── HTTP Helper'lar ───────────────────────────────────

    private static function httpPost(string $url, array $data): ?array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('OAuth HTTP POST error', ['url' => $url, 'error' => $error, 'http_code' => $httpCode]);
            return null;
        }

        Logger::info('OAuth POST response', ['url' => $url, 'http_code' => $httpCode]);

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
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Logger::error('OAuth HTTP GET error', ['url' => $url, 'error' => $error, 'http_code' => $httpCode]);
            return null;
        }

        Logger::info('OAuth GET response', ['url' => $url, 'http_code' => $httpCode]);

        return json_decode($response, true);
    }
}
