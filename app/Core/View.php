<?php
/**
 * Basit Görünüm (View) Yardımcıları
 * Partial dosyaları (header, navbar, footer vb.) include eder.
 */

class View
{
    /**
     * Partial dosyayı include et
     * @param string $name  Partial adı (header, navbar, footer, vb.)
     * @param array  $data  View'a aktarılacak değişkenler
     */
    public static function partial(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = PUBLIC_PATH . '/partials/' . $name . '.php';

        if (file_exists($file)) {
            include $file;
        }
    }

    /**
     * Sayfa başlığını oluştur
     */
    public static function title(string $pageTitle = ''): string
    {
        return $pageTitle ? escape($pageTitle) . ' — ' . APP_NAME : APP_NAME;
    }

    /**
     * Navbar aktif link kontrolü
     */
    public static function isActive(string $page, string $current): string
    {
        return $page === $current ? 'active' : '';
    }
}
