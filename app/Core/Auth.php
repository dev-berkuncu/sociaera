<?php
/**
 * Kimlik Doğrulama Yardımcıları
 * Session tabanlı auth, login/logout, erişim kontrolleri.
 */

class Auth
{
    /**
     * Kullanıcı giriş yapmış mı?
     */
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    /**
     * Giriş yapan kullanıcının ID'si
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Giriş yapan kullanıcının username'i
     */
    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Admin mi? (backward-compatible)
     */
    public static function isAdmin(): bool
    {
        return !empty($_SESSION['is_admin']) || !empty($_SESSION['admin_role']);
    }

    /**
     * Belirli bir admin rolüne sahip mi?
     */
    public static function hasRole(string $role): bool
    {
        return ($_SESSION['admin_role'] ?? '') === $role;
    }

    /**
     * Admin rolü
     */
    public static function adminRole(): ?string
    {
        return $_SESSION['admin_role'] ?? null;
    }

    /**
     * Belirli admin bölümüne erişim var mı?
     */
    public static function canAccess(string $section): bool
    {
        if (!self::isAdmin()) return false;
        $role = $_SESSION['admin_role'] ?? 'super_admin';

        $permissions = [
            'super_admin'    => ['*'],
            'moderator'      => ['dashboard', 'users', 'venues', 'checkins', 'comments', 'moderation'],
            'finance_admin'  => ['dashboard', 'wallet', 'users'],
            'business_admin' => ['dashboard', 'venues', 'business', 'campaigns'],
            'readonly_admin' => ['dashboard', 'users', 'venues', 'checkins', 'comments', 'wallet', 'moderation', 'audit'],
        ];

        $allowed = $permissions[$role] ?? [];
        return in_array('*', $allowed) || in_array($section, $allowed);
    }

    /**
     * Yazma (değişiklik yapma) yetkisi var mı?
     */
    public static function canWrite(): bool
    {
        $role = $_SESSION['admin_role'] ?? 'super_admin';
        return $role !== 'readonly_admin';
    }

    /**
     * Kullanıcıyı oturuma kaydet
     */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['username']   = !empty($user['gta_character_name']) ? $user['gta_character_name'] : $user['username'];
        $_SESSION['email']      = $user['email'] ?? '';
        $_SESSION['is_admin']   = (bool) ($user['is_admin'] ?? false);
        $_SESSION['admin_role'] = $user['admin_role'] ?? null;
        $_SESSION['avatar']     = $user['avatar'] ?? null;
        $_SESSION['tag']        = $user['tag'] ?? '';
        $_SESSION['logged_in_at'] = time();
    }

    /**
     * Çıkış yap
     */
    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Giriş kontrolü — giriş yapmamışsa login sayfasına yönlendir
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            self::setFlash('error', 'Bu sayfayı görüntülemek için giriş yapmalısınız.');
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        // Ban kontrolü
        if (!empty($_SESSION['user_id'])) {
            self::checkBan();
        }
    }

    /**
     * Admin kontrolü
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    /**
     * Ban kontrolü
     */
    private static function checkBan(): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT banned_until FROM users WHERE id = ?");
            $stmt->execute([self::id()]);
            $user = $stmt->fetch();

            if ($user && $user['banned_until'] && strtotime($user['banned_until']) > time()) {
                self::logout();
                session_start();
                self::setFlash('error', 'Hesabınız askıya alınmıştır.');
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        } catch (Exception $e) {
            // Sessizce devam et
        }
    }

    /**
     * Flash mesaj ayarla
     */
    public static function setFlash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    /**
     * Flash mesaj al ve temizle
     */
    public static function getFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Session'daki kullanıcı bilgilerini güncelle
     */
    public static function refresh(array $data): void
    {
        foreach ($data as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
}

// ── Global shortcut fonksiyonlar ──────────────────────────
function isLoggedIn(): bool
{
    return Auth::check();
}

function requireLogin(): void
{
    Auth::requireLogin();
}

function requireAdmin(): void
{
    Auth::requireAdmin();
}

function currentUserId(): ?int
{
    return Auth::id();
}

function currentUsername(): ?string
{
    return Auth::username();
}
