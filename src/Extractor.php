<?php
/**
 * PHP Self-Updater - Çıxarma Sınıfı
 * 
 * ZIP fayllarını çıxarır, path traversal qoruması edir,
 * yalnız icazə verilmiş yolları çıxarır.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\ExtractionException;
use ZipArchive;

/**
 * Extractor - ZIP çıxarma
 * 
 * Bu sınıf yeniləmə ZIP faylını təhlükəsiz şəkildə çıxarır.
 * İcazə verilmiş və istisna edilmiş yolları nəzərə alır.
 */
class Extractor
{
    /**
     * Config instansı
     * @var Config
     */
    private Config $config;

    /**
     * Çıxarılan faylların siyahısı
     * @var array
     */
    private array $extractedFiles = [];

    /**
     * Konstruktor
     * 
     * @param Config $config Konfiqurasiya instansı
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * ZIP faylını çıxarır
     * 
     * @param string $zipPath ZIP faylının yolu
     * @param string $destPath Çıxarılacaq yer
     * @param array $allowedFiles Manifestdəki icazə verilmiş fayllar
     * @return array Çıxarılan faylların siyahısı
     * @throws ExtractionException Çıxarma uğursuz olduqda
     */
    public function extract(string $zipPath, string $destPath, array $allowedFiles = []): array
    {
        // ZipArchive mövcudluğunu yoxla
        if (!class_exists('ZipArchive')) {
            throw new ExtractionException(
                "ZipArchive sınıfı mövcud deyil. PHP-nin zip uzantısını quraşdırın."
            );
        }

        // ZIP faylını yoxla
        if (!file_exists($zipPath)) {
            throw new ExtractionException(
                "ZIP faylı tapılmadı: {$zipPath}"
            );
        }

        // Hədəf qovluğu hazırla
        $this->ensureDirectoryExists($destPath);

        // ZIP-i aç
        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            throw new ExtractionException(
                "ZIP faylı açıla bilmədi: {$zipPath}, Xəta kodu: {$result}"
            );
        }

        $this->extractedFiles = [];
        $updatePaths = $this->config->getUpdatePaths();
        $excludePaths = $this->config->getExcludePaths();

        // Bütün faylları yoxla və çıxar
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            
            // Fayl yoxlanışı
            if (!$this->shouldExtractFile($entryName, $allowedFiles, $updatePaths, $excludePaths, $destPath)) {
                continue;
            }

            // Faylı çıxar
            $this->extractFile($zip, $entryName, $destPath);
        }

        $zip->close();

        return $this->extractedFiles;
    }

    /**
     * Faylın çıxarılıb-çıxarılmayacağını yoxlayır
     * 
     * @param string $entryName ZIP içindəki fayl adı
     * @param array $allowedFiles Manifestdəki icazəli fayl siyahısı
     * @param array $updatePaths Konfiqurasiyadan icazəli yollar
     * @param array $excludePaths İstisna edilmiş yollar
     * @param string $destPath Hədəf qovluğu
     * @return bool Çıxarılmalıdırsa true
     */
    private function shouldExtractFile(
        string $entryName,
        array $allowedFiles,
        array $updatePaths,
        array $excludePaths,
        string $destPath
    ): bool {
        // Boş və ya qovluq olan girişləri atla
        if (empty($entryName) || substr($entryName, -1) === '/') {
            return false;
        }

        // Path traversal yoxlaması
        if (!Security::isPathSafe($entryName, $destPath)) {
            return false;
        }

        // İstisna edilmiş yolları atla
        if (Security::isPathExcluded($entryName, $excludePaths)) {
            return false;
        }

        // Manifestdə icazəli fayl siyahısı varsa, yalnız onları al
        if (!empty($allowedFiles)) {
            // Faylın manifestdə olub-olmadığını yoxla
            $normalizedEntry = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $entryName);
            $normalizedEntry = trim($normalizedEntry, DIRECTORY_SEPARATOR);
            
            $found = false;
            foreach ($allowedFiles as $allowedFile) {
                $normalizedAllowed = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $allowedFile);
                $normalizedAllowed = trim($normalizedAllowed, DIRECTORY_SEPARATOR);
                
                if ($normalizedEntry === $normalizedAllowed) {
                    $found = true;
                    break;
                }
                
                // Qovluq yoxlaması
                if (strpos($normalizedEntry, $normalizedAllowed . DIRECTORY_SEPARATOR) === 0) {
                    $found = true;
                    break;
                }
                
                // Əks istiqamət - normalizedAllowed normalizedEntry-nin altındadırsa
                if (strpos($normalizedAllowed, $normalizedEntry . DIRECTORY_SEPARATOR) === 0) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                return false;
            }
        }

        // Konfiqurasiyadan icazəli yollar varsa, onları yoxla
        if (!Security::isPathAllowed($entryName, $updatePaths)) {
            return false;
        }

        return true;
    }

    /**
     * Tək fayl çıxarır
     * 
     * @param ZipArchive $zip ZipArchive instansı
     * @param string $entryName Fayl adı
     * @param string $destPath Hədəf qovluğu
     * @return void
     * @throws ExtractionException
     */
    private function extractFile(ZipArchive $zip, string $entryName, string $destPath): void
    {
        // Hədəf yolunu hesabla
        $targetPath = $destPath . DIRECTORY_SEPARATOR . $entryName;
        $targetPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetPath);

        // Üst qovluğu yarat
        $targetDir = dirname($targetPath);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new ExtractionException(
                    "Qovluq yaradıla bilmədi: {$targetDir}"
                );
            }
        }

        // Faylı çıxar
        $content = $zip->getFromName($entryName);
        
        if ($content === false) {
            throw new ExtractionException(
                "Fayl oxuna bilmədi: {$entryName}"
            );
        }

        if (file_put_contents($targetPath, $content) === false) {
            throw new ExtractionException(
                "Fayl yazıla bilmədi: {$targetPath}"
            );
        }

        $this->extractedFiles[] = $entryName;
    }

    /**
     * Çıxarılan faylların siyahısını qaytarır
     * 
     * @return array
     */
    public function getExtractedFiles(): array
    {
        return $this->extractedFiles;
    }

    /**
     * Qovluğun mövcud olduğundan əmin olur
     * 
     * @param string $directory Qovluq yolu
     * @return void
     * @throws ExtractionException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new ExtractionException(
                    "Hədəf qovluğu yaradıla bilmədi: {$directory}"
                );
            }
        }

        if (!is_writable($directory)) {
            throw new ExtractionException(
                "Hədəf qovluğuna yazma icazəsi yoxdur: {$directory}"
            );
        }
    }
}
