<?php
/**
 * PHP Self-Updater - Yedəkləmə Sınıfı
 * 
 * Yeniləmədən əvvəl mövcud faylları yedəkləyir.
 * Timestamp ilə adlandırılmış yedəklər yaradır.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\UpdaterException;

/**
 * Backup - Yedəkləmə idarəetməsi
 * 
 * Bu sınıf yeniləmədən əvvəl köhnə faylları yedəkləyir
 * və lazım olduqda bərpa etməyə imkan verir.
 */
class Backup
{
    /**
     * Config instansı
     * @var Config
     */
    private Config $config;

    /**
     * Son yaradılan yedəyin yolu
     * @var string|null
     */
    private ?string $lastBackupPath = null;

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
     * Faylları yedəkləyir
     * 
     * @param array $files Yedəklənəcək fayl yolları
     * @param string $basePath Əsas qovluq
     * @return string Yedək qovluğunun yolu
     * @throws UpdaterException Yedəkləmə uğursuz olduqda
     */
    public function create(array $files, string $basePath): string
    {
        if (!$this->config->isBackupEnabled()) {
            return '';
        }

        // Yedək qovluğunu hazırla
        $backupDir = $this->config->getBackupDirectory();
        $this->ensureDirectoryExists($backupDir);

        // Timestamp ilə yedək adı
        $backupName = 'backup_' . date('Y-m-d_H-i-s');
        $backupPath = $backupDir . DIRECTORY_SEPARATOR . $backupName;

        if (!mkdir($backupPath, 0755, true)) {
            throw new UpdaterException(
                "Yedək qovluğu yaradıla bilmədi: {$backupPath}"
            );
        }

        // Faylları yedəklə
        foreach ($files as $file) {
            $sourcePath = $basePath . DIRECTORY_SEPARATOR . $file;
            
            if (!file_exists($sourcePath)) {
                continue; // Fayl yoxdursa atla
            }

            $destPath = $backupPath . DIRECTORY_SEPARATOR . $file;
            
            // Üst qovluğu yarat
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Faylı və ya qovluğu kopyala
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }

        $this->lastBackupPath = $backupPath;
        return $backupPath;
    }

    /**
     * Yedəkdən bərpa edir
     * 
     * @param string $backupPath Yedək qovluğu
     * @param string $restorePath Bərpa ediləcək yer
     * @return bool
     * @throws UpdaterException Bərpa uğursuz olduqda
     */
    public function restore(string $backupPath, string $restorePath): bool
    {
        if (!is_dir($backupPath)) {
            throw new UpdaterException(
                "Yedək qovluğu tapılmadı: {$backupPath}"
            );
        }

        // Yedəkdəki bütün faylları bərpa et
        $files = $this->scanDirectory($backupPath);

        foreach ($files as $file) {
            $sourcePath = $backupPath . DIRECTORY_SEPARATOR . $file;
            $destPath = $restorePath . DIRECTORY_SEPARATOR . $file;

            // Üst qovluğu yarat
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }

        return true;
    }

    /**
     * Köhnə yedəkləri təmizləyir
     * 
     * @param int $keepCount Saxlanacaq yedək sayı
     * @return int Silinən yedək sayı
     */
    public function cleanup(int $keepCount = 5): int
    {
        $backupDir = $this->config->getBackupDirectory();

        if (!is_dir($backupDir)) {
            return 0;
        }

        $backups = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*');
        
        if ($backups === false || count($backups) <= $keepCount) {
            return 0;
        }

        // Tarixə görə sırala (köhnədən yeniyə)
        usort($backups, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });

        // Köhnəni sil, yeni olanları saxla
        $toDelete = count($backups) - $keepCount;
        $deleted = 0;

        for ($i = 0; $i < $toDelete; $i++) {
            if ($this->deleteDirectory($backups[$i])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Son yedəyin yolunu qaytarır
     * 
     * @return string|null
     */
    public function getLastBackupPath(): ?string
    {
        return $this->lastBackupPath;
    }

    /**
     * Bütün yedəkləri siyahılayır
     * 
     * @return array Yedək qovluqlarının siyahısı
     */
    public function list(): array
    {
        $backupDir = $this->config->getBackupDirectory();

        if (!is_dir($backupDir)) {
            return [];
        }

        $backups = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*');
        
        if ($backups === false) {
            return [];
        }

        // Tarixə görə sırala (yenidən köhnəyə)
        usort($backups, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return array_map('basename', $backups);
    }

    /**
     * Qovluğu kopyalayır
     * 
     * @param string $source Mənbə qovluq
     * @param string $dest Hədəf qovluq
     * @return bool
     */
    private function copyDirectory(string $source, string $dest): bool
    {
        if (!is_dir($dest)) {
            mkdir($dest, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item->getPathname(), $destPath);
            }
        }

        return true;
    }

    /**
     * Qovluğu silir
     * 
     * @param string $directory Qovluq yolu
     * @return bool
     */
    private function deleteDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        return rmdir($directory);
    }

    /**
     * Qovluqdakı bütün faylları siyahılayır
     * 
     * @param string $directory Qovluq yolu
     * @return array Fayl yolları
     */
    private function scanDirectory(string $directory): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $files[] = $iterator->getSubPathname();
        }

        return $files;
    }

    /**
     * Qovluğun mövcud olduğundan əmin olur
     * 
     * @param string $directory Qovluq yolu
     * @return void
     * @throws UpdaterException
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new UpdaterException(
                    "Yedək qovluğu yaradıla bilmədi: {$directory}"
                );
            }
        }

        if (!is_writable($directory)) {
            throw new UpdaterException(
                "Yedək qovluğuna yazma icazəsi yoxdur: {$directory}"
            );
        }
    }
}
