<?php
/**
 * Sociaera — Global Yardımcı Fonksiyonlar
 */

// ── Güvenlik ──────────────────────────────────────────────

/**
 * XSS korumalı çıktı
 */
function escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * HTML içinde güvenli URL
 */
function escapeUrl(?string $url): string
{
    return htmlspecialchars($url ?? '', ENT_QUOTES, 'UTF-8');
}

// ── Tarih / Zaman ─────────────────────────────────────────

/**
 * Türkçe tarih formatla
 */
function formatDate(?string $datetime, bool $withTime = false): string
{
    if (!$datetime) return '-';

    $ts = strtotime($datetime);
    if ($ts === false) return '-';

    $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
    $m = (int) date('n', $ts) - 1;

    $formatted = date('d', $ts) . ' ' . $months[$m] . ' ' . date('Y', $ts);

    if ($withTime) {
        $formatted .= ' ' . date('H:i', $ts);
    }

    return $formatted;
}

/**
 * Göreceli zaman (3 dk önce, 2 saat önce, vb.)
 */
function timeAgo(?string $datetime): string
{
    if (!$datetime) return '-';

    $ts   = strtotime($datetime);
    $diff = time() - $ts;

    if ($diff < 60)    return 'az önce';
    if ($diff < 3600)  return floor($diff / 60) . ' dk önce';
    if ($diff < 86400) return floor($diff / 3600) . ' sa önce';
    if ($diff < 604800) return floor($diff / 86400) . ' gün önce';

    return formatDate($datetime);
}

// ── Metin ──────────────────────────────────────────────────

/**
 * Metni belirli uzunlukta kes
 */
function truncate(?string $text, int $length = 100, string $suffix = '...'): string
{
    if (!$text || mb_strlen($text) <= $length) return $text ?? '';
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * @mention ve #tag parse et — tıklanabilir linklere çevir
 */
function parseMentions(?string $text): string
{
    if (!$text) return '';

    $text = escape($text);

    // @kullanıcı_adı → profil linki
    $text = preg_replace(
        '/@([a-zA-Z0-9_]+)/',
        '<a href="' . BASE_URL . '/profile?u=$1" class="mention-link">@$1</a>',
        $text
    );

    return $text;
}

/**
 * Metin içindeki URL'leri tıklanabilir yap
 */
function linkify(?string $text): string
{
    if (!$text) return '';
    return preg_replace(
        '#(https?://[^\s<]+)#i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    );
}

/**
 * Yeni satırları <br>'ye çevir (escape edilmiş metin için)
 */
function nl2brSafe(?string $text): string
{
    return nl2br(escape($text ?? ''));
}

// ── URL / Path ─────────────────────────────────────────────

/**
 * Asset URL üret
 */
function asset(string $path): string
{
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Upload URL üret
 */
function uploadUrl(string $folder, ?string $filename): ?string
{
    if (!$filename) return null;
    return BASE_URL . '/uploads/' . $folder . '/' . $filename;
}

/**
 * Avatar URL (yoksa null)
 */
function avatarUrl(?string $avatar): ?string
{
    return $avatar ? uploadUrl('avatars', $avatar) : null;
}

/**
 * Banner URL
 */
function bannerUrl(?string $banner): ?string
{
    return $banner ? uploadUrl('banners', $banner) : null;
}

/**
 * Varsayılan avatar HTML (kullanıcı adının ilk harfi)
 */
function defaultAvatar(string $username, string $size = '40'): string
{
    $initial = mb_strtoupper(mb_substr($username, 0, 1));
    return '<div class="avatar-default" style="width:'.$size.'px;height:'.$size.'px;">'
         . escape($initial) . '</div>';
}

/**
 * Avatar img veya default HTML
 */
function avatarHtml(?string $avatar, string $username, string $size = '40'): string
{
    $url = avatarUrl($avatar);
    if ($url) {
        return '<img src="' . escapeUrl($url) . '" alt="' . escape($username) . '" '
             . 'class="avatar-img" width="' . $size . '" height="' . $size . '" loading="lazy">';
    }
    return defaultAvatar($username, $size);
}

// ── Sayısal ────────────────────────────────────────────────

/**
 * Sayıyı kısa formatta göster (1.2K, 3.5M, vb.)
 */
function shortNumber(int $num): string
{
    if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
    if ($num >= 1000)    return round($num / 1000, 1) . 'K';
    return (string) $num;
}

// ── Bootstrap uygulamasına özel bootstrapper ───────────────

/**
 * Uygulamayı başlat (her sayfa başında çağır)
 */
function bootApp(): void
{
    // .env yükle
    require_once APP_PATH . '/Config/env.php';
    loadEnv(ROOT_PATH . '/.env');

    // Ana yapılandırma
    require_once APP_PATH . '/Config/app.php';

    // Veritabanı
    require_once APP_PATH . '/Config/database.php';
}

/**
 * Ortak sayfa başlatıcı — includes bootstrap + DB + ads
 */
function initPage(): PDO
{
    bootApp();
    return Database::getConnection();
}
