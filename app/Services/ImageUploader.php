<?php
/**
 * Güvenli Resim Yükleyici
 * MIME kontrolü, boyut sınırlama, opsiyonel WebP dönüştürme, random dosya adı.
 */

class ImageUploader
{
    /** İzin verilen MIME türleri */
    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    /**
     * Dosya yükle
     *
     * @param array $file      $_FILES['name'] dizisi
     * @param string $folder   uploads altındaki klasör (avatars, banners, posts, ads)
     * @param array $options   Opsiyonlar:
     *   - maxSize (int): byte cinsinden max dosya boyutu
     *   - maxWidth (int): max genişlik px
     *   - maxHeight (int): max yükseklik px
     *   - outputFormat (string): 'webp' ise WebP'ye dönüştürür
     *   - quality (int): 1-100 kalite (varsayılan 85)
     *
     * @return array ['success' => bool, 'filename' => string|null, 'path' => string|null, 'error' => string|null]
     */
    public function upload(array $file, string $folder, array $options = []): array
    {
        // Yükleme hatası kontrolü
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->fail($this->uploadErrorMessage($file['error']));
        }

        // Boyut kontrolü
        $maxSize = $options['maxSize'] ?? MAX_POST_SIZE;
        if ($file['size'] > $maxSize) {
            $mb = round($maxSize / 1024 / 1024, 1);
            return $this->fail("Dosya boyutu en fazla {$mb}MB olabilir.");
        }

        // MIME kontrolü
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIMES[$mime])) {
            return $this->fail('Desteklenmeyen dosya formatı. (JPEG, PNG, GIF, WebP)');
        }

        // Gerçekten bir resim mi?
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return $this->fail('Geçersiz resim dosyası.');
        }

        // Hedef klasörü oluştur
        $uploadDir = UPLOAD_PATH . '/' . $folder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Çıktı formatı belirle
        $outputFormat = $options['outputFormat'] ?? self::ALLOWED_MIMES[$mime];
        $ext          = ($outputFormat === 'webp') ? 'webp' : self::ALLOWED_MIMES[$mime];

        // Random dosya adı
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;

        // WebP dönüştürme veya direkt taşıma
        if ($outputFormat === 'webp' && function_exists('imagewebp') && $mime !== 'image/gif') {
            $result = $this->convertToWebP(
                $file['tmp_name'],
                $destPath,
                $mime,
                $options['quality'] ?? 85,
                $options['maxWidth'] ?? null,
                $options['maxHeight'] ?? null
            );
            if (!$result) {
                return $this->fail('Resim işlenirken hata oluştu.');
            }
        } else {
            // Boyut sınırlama (WebP olmadan)
            if (!empty($options['maxWidth']) || !empty($options['maxHeight'])) {
                $result = $this->resize(
                    $file['tmp_name'],
                    $destPath,
                    $mime,
                    $options['maxWidth'] ?? null,
                    $options['maxHeight'] ?? null,
                    $options['quality'] ?? 85
                );
                if (!$result) {
                    // Resize başarısızsa doğrudan taşı
                    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                        return $this->fail('Dosya yüklenemedi.');
                    }
                }
            } else {
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    return $this->fail('Dosya yüklenemedi.');
                }
            }
        }

        return [
            'success'  => true,
            'filename' => $filename,
            'path'     => 'uploads/' . $folder . '/' . $filename,
            'error'    => null,
        ];
    }

    /**
     * Dosyayı sil
     */
    public function delete(string $folder, ?string $filename): bool
    {
        if (!$filename) return false;

        $path = UPLOAD_PATH . '/' . $folder . '/' . $filename;
        if (file_exists($path)) {
            return @unlink($path);
        }
        return false;
    }

    // ── Private ───────────────────────────────────────────

    private function convertToWebP(string $source, string $dest, string $mime, int $quality, ?int $maxW, ?int $maxH): bool
    {
        $image = $this->createFromMime($source, $mime);
        if (!$image) return false;

        // Boyutlandırma
        if ($maxW || $maxH) {
            $image = $this->resizeImage($image, $maxW, $maxH);
        }

        $result = imagewebp($image, $dest, $quality);
        imagedestroy($image);

        return $result;
    }

    private function resize(string $source, string $dest, string $mime, ?int $maxW, ?int $maxH, int $quality): bool
    {
        $image = $this->createFromMime($source, $mime);
        if (!$image) return false;

        $image = $this->resizeImage($image, $maxW, $maxH);

        $result = false;
        switch ($mime) {
            case 'image/jpeg':
                $result = imagejpeg($image, $dest, $quality);
                break;
            case 'image/png':
                $result = imagepng($image, $dest, min(9, (int)(9 - ($quality / 11))));
                break;
            case 'image/gif':
                $result = imagegif($image, $dest);
                break;
            case 'image/webp':
                $result = imagewebp($image, $dest, $quality);
                break;
        }

        imagedestroy($image);
        return $result;
    }

    private function createFromMime(string $path, string $mime)
    {
        switch ($mime) {
            case 'image/jpeg': return @imagecreatefromjpeg($path);
            case 'image/png':  return @imagecreatefrompng($path);
            case 'image/gif':  return @imagecreatefromgif($path);
            case 'image/webp': return @imagecreatefromwebp($path);
            default: return false;
        }
    }

    private function resizeImage($image, ?int $maxW, ?int $maxH)
    {
        $origW = imagesx($image);
        $origH = imagesy($image);
        $newW  = $origW;
        $newH  = $origH;

        if ($maxW && $origW > $maxW) {
            $ratio = $maxW / $origW;
            $newW  = $maxW;
            $newH  = (int)($origH * $ratio);
        }

        if ($maxH && $newH > $maxH) {
            $ratio = $maxH / $newH;
            $newH  = $maxH;
            $newW  = (int)($newW * $ratio);
        }

        if ($newW === $origW && $newH === $origH) {
            return $image;
        }

        $resized = imagecreatetruecolor($newW, $newH);

        // PNG/WebP şeffaflık
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($image);

        return $resized;
    }

    private function fail(string $message): array
    {
        return ['success' => false, 'filename' => null, 'path' => null, 'error' => $message];
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'Dosya sunucu limitini aşıyor.',
            UPLOAD_ERR_FORM_SIZE  => 'Dosya form limitini aşıyor.',
            UPLOAD_ERR_PARTIAL    => 'Dosya kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE    => 'Dosya seçilmedi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Geçici klasör bulunamadı.',
            UPLOAD_ERR_CANT_WRITE => 'Dosya diske yazılamadı.',
            default               => 'Bilinmeyen yükleme hatası.',
        };
    }
}
