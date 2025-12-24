<?php
/**
 * PHP Self-Updater - Təhlükəsizlik Sınıfı
 * 
 * Checksum validasiyası, path traversal qoruması və
 * server cavablarının yoxlanması üçün.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\ChecksumException;

/**
 * Security - Təhlükəsizlik yoxlamaları
 * 
 * Bu sınıf yeniləmə prosesinin təhlükəsizliyini təmin edir.
 */
class Security
{
    /**
     * SHA256 checksum validasiyası
     * 
     * Endirilmiş faylın checksum-ını yoxlayır.
     * 
     * @param string $filePath Fayl yolu
     * @param string $expectedChecksum Gözlənilən checksum
     * @return bool
     * @throws ChecksumException Checksum uyğun gəlmədikdə
     */
    public static function validateChecksum(string $filePath, string $expectedChecksum): bool
    {
        if (!file_exists($filePath)) {
            throw new ChecksumException(
                "Checksum yoxlanacaq fayl tapılmadı: {$filePath}"
            );
        }

        $actualChecksum = trim(hash_file('sha256', $filePath));
        $expectedChecksum = trim($expectedChecksum);

        if (strtolower($actualChecksum) !== strtolower($expectedChecksum)) {
            throw new ChecksumException(
                "Checksum uyğunsuzluğu! Gözlənilən: {$expectedChecksum}, Alınan: {$actualChecksum}"
            );
        }

        return true;
    }

    /**
     * Path traversal hücumunu yoxlayır
     * 
     * Yolun əsas qovluqdan kənara çıxmadığını təmin edir.
     * "../" və ya absolut yollarla hücumların qarşısını alır.
     * 
     * @param string $path Yoxlanacaq yol
     * @param string $basePath Əsas qovluq
     * @return bool Təhlükəsizdirsə true
     */
    public static function isPathSafe(string $path, string $basePath): bool
    {
        // Absolut yolları normallaşdır
        $realBasePath = realpath($basePath);
        
        if ($realBasePath === false) {
            return false;
        }

        // Yolu birləşdir və normallaşdır
        $fullPath = $basePath . DIRECTORY_SEPARATOR . $path;
        
        // Qovluq yaratmadan əvvəl üst qovluğu yoxla (fayl hələ mövcud olmaya bilər)
        $parentDir = dirname($fullPath);
        
        // Əgər üst qovluq mövcuddursa, onu yoxla
        if (is_dir($parentDir)) {
            $realParentDir = realpath($parentDir);
            if ($realParentDir === false) {
                return false;
            }
            
            // Üst qovluğun əsas qovluq altında olduğunu yoxla
            if (strpos($realParentDir, $realBasePath) !== 0) {
                return false;
            }
        }

        // Zərərli simvolları yoxla
        if (
            strpos($path, '..') !== false ||
            strpos($path, "\0") !== false ||
            preg_match('/^[a-zA-Z]:/', $path) || // Windows absolut yolu
            strpos($path, '/') === 0            // Unix absolut yolu
        ) {
            return false;
        }

        return true;
    }

    /**
     * Yolun istisna edilmiş siyahıda olub-olmadığını yoxlayır
     * 
     * @param string $path Yoxlanacaq yol
     * @param array $excludePaths İstisna edilmiş yollar
     * @return bool İstisna edilmişdirsə true
     */
    public static function isPathExcluded(string $path, array $excludePaths): bool
    {
        // Yolu normallaşdır (separatorları vahid et)
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $normalizedPath = trim($normalizedPath, DIRECTORY_SEPARATOR);

        foreach ($excludePaths as $excludePath) {
            $normalizedExclude = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $excludePath);
            $normalizedExclude = trim($normalizedExclude, DIRECTORY_SEPARATOR);

            // Tam uyğunluq
            if ($normalizedPath === $normalizedExclude) {
                return true;
            }

            // Qovluq altında olan faylları da istisna et
            if (strpos($normalizedPath, $normalizedExclude . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Yolun icazə verilmiş siyahıda olub-olmadığını yoxlayır
     * 
     * Əgər icazə verilmiş yollar siyahısı boşdursa, bütün yollar icazəlidir.
     * 
     * @param string $path Yoxlanacaq yol
     * @param array $allowedPaths İcazə verilmiş yollar
     * @return bool İcazəlidirsə true
     */
    public static function isPathAllowed(string $path, array $allowedPaths): bool
    {
        // Əgər icazə verilmiş yollar boşdursa, hər şey icazəlidir
        if (empty($allowedPaths)) {
            return true;
        }

        // Yolu normallaşdır
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $normalizedPath = trim($normalizedPath, DIRECTORY_SEPARATOR);

        foreach ($allowedPaths as $allowedPath) {
            $normalizedAllowed = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedPath);
            $normalizedAllowed = trim($normalizedAllowed, DIRECTORY_SEPARATOR);

            // Tam uyğunluq
            if ($normalizedPath === $normalizedAllowed) {
                return true;
            }

            // Qovluq altında olan faylları da icazəli say
            if (strpos($normalizedPath, $normalizedAllowed . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }

            // İcazəli yol, yoxlanılan yolun altındadırsa (əks istiqamət)
            if (strpos($normalizedAllowed, $normalizedPath . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Server cavabını validasiya edir
     * 
     * @param array $response Server cavabı
     * @param array $requiredFields Məcburi sahələr
     * @return bool
     */
    public static function validateServerResponse(array $response, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $response)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Versiya formatını yoxlayır (semantik versiya)
     * 
     * @param string $version Versiya
     * @return bool Düzgündürsə true
     */
    public static function isValidVersion(string $version): bool
    {
        // Semantik versiya formatı: major.minor.patch[-prerelease][+build]
        $pattern = '/^(\d+)\.(\d+)\.(\d+)(?:-([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?(?:\+([0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*))?$/';
        return (bool) preg_match($pattern, $version);
    }

    /**
     * URL-in HTTPS olduğunu yoxlayır
     * 
     * @param string $url URL
     * @return bool
     */
    public static function isSecureUrl(string $url): bool
    {
        return strpos(strtolower($url), 'https://') === 0;
    }
}
