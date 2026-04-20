<?php
/**
 * .env dosya okuyucu
 * Proje kök dizinindeki .env dosyasını parse eder ve $_ENV + putenv() ile yükler.
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Yorum satırlarını atla
        if (str_starts_with($line, '#') || str_starts_with($line, ';')) {
            continue;
        }

        // KEY=VALUE formatı
        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Tırnak içindeki değerleri temizle
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

/**
 * Ortam değişkeni al
 */
function env(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false || $value === null) {
        return $default;
    }

    // Boolean dönüşümleri
    $lower = strtolower($value);
    if ($lower === 'true')  return true;
    if ($lower === 'false') return false;
    if ($lower === 'null')  return null;

    return $value;
}
