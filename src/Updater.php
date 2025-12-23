<?php
/**
 * PHP Self-Updater - Əsas Updater Sınıfı
 * 
 * Statik API interfeysi ilə tətbiqin özünü yeniləməsini təmin edir.
 * Updater::check(), Updater::hasUpdate(), Updater::run() metodları.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\UpdaterException;
use SelfUpdater\Exceptions\ConfigException;
use SelfUpdater\Exceptions\DownloadException;
use SelfUpdater\Exceptions\ChecksumException;
use SelfUpdater\Exceptions\ExtractionException;

/**
 * Updater - Əsas yeniləmə sınıfı
 * 
 * Bu sınıf statik metodlar vasitəsilə yeniləmə prosesini idarə edir.
 * 
 * İstifadə:
 * <code>
 * // Konfiqurasiyanı yüklə və yeniləməni yoxla
 * Updater::check('/path/to/config/updater.php');
 * 
 * // Yeniləmə varsa icra et
 * if (Updater::hasUpdate()) {
 *     Updater::run('/path/to/project');
 * }
 * </code>
 */
class Updater
{
    /**
     * Config instansı
     * @var Config|null
     */
    private static ?Config $config = null;

    /**
     * VersionChecker instansı
     * @var VersionChecker|null
     */
    private static ?VersionChecker $versionChecker = null;

    /**
     * Yeniləmə manifest məlumatları
     * @var array|null
     */
    private static ?array $manifest = null;

    /**
     * Yeniləmə olub-olmadığı (cache)
     * @var bool|null
     */
    private static ?bool $updateAvailable = null;

    /**
     * Son xəta mesajı
     * @var string|null
     */
    private static ?string $lastError = null;

    /**
     * Yeniləmələri yoxlayır
     * 
     * Bu metod konfiqurasiyanı yükləyir, serverlə əlaqə qurur
     * və yeniləmənin mövcudluğunu yoxlayır.
     * 
     * Əgər autoupdate konfiqurasiyada açıqdırsa, yeniləmə
     * avtomatik olaraq icra edilir.
     * 
     * @param string|array $config Konfiqurasiya faylı yolu və ya array
     * @param string|null $basePath Əsas qovluq (autoupdate üçün)
     * @return bool Yeniləmə varsa true
     * @throws ConfigException Konfiqurasiya xətası
     */
    public static function check($config, ?string $basePath = null): bool
    {
        self::$lastError = null;
        
        try {
            // Konfiqurasiyanı yüklə
            self::loadConfig($config);

            // VersionChecker yaradıl
            self::$versionChecker = new VersionChecker(self::$config);

            // Yeniləmə olub-olmadığını yoxla
            self::$updateAvailable = self::$versionChecker->hasUpdate();

            // Autoupdate açıqdırsa və yeniləmə varsa, avtomatik yenilə
            if (self::$updateAvailable && self::$config->isAutoUpdateEnabled()) {
                if ($basePath === null) {
                    // Əgər basePath verilməyibsə, cari qovluğu istifadə et
                    $basePath = getcwd();
                }
                self::run($basePath);
            }

            return self::$updateAvailable;
        } catch (\Exception $e) {
            self::$lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Yeniləmə olub-olmadığını qaytarır
     * 
     * Bu metod check() çağrıldıqdan sonra istifadə edilməlidir.
     * 
     * @return bool Yeniləmə varsa true
     */
    public static function hasUpdate(): bool
    {
        if (self::$updateAvailable === null) {
            // check() hələ çağrılmayıb
            return false;
        }

        return self::$updateAvailable;
    }

    /**
     * Yeniləməni icra edir
     * 
     * Bu metod aşağıdakı addımları icra edir:
     * 1. Manifestı yükləyir
     * 2. ZIP faylını endirir
     * 3. Checksum validasiyası
     * 4. Yedəkləmə (əgər açıqdırsa)
     * 5. Faylları çıxarır
     * 
     * @param string $basePath Yenilənəcək proyektin əsas qovluğu
     * @return bool Uğurlu olub-olmadığı
     * @throws UpdaterException Yeniləmə xətası
     */
    public static function run(string $basePath): bool
    {
        self::$lastError = null;

        try {
            // Config yoxlaması
            if (self::$config === null) {
                throw new ConfigException(
                    "Konfiqurasiya yüklənməyib. Əvvəlcə check() çağırın."
                );
            }

            // basePath yoxlaması
            if (!is_dir($basePath)) {
                throw new UpdaterException(
                    "Əsas qovluq mövcud deyil: {$basePath}"
                );
            }

            // Manifestı yüklə
            if (self::$versionChecker === null) {
                self::$versionChecker = new VersionChecker(self::$config);
            }
            
            self::$manifest = self::$versionChecker->fetchManifest();

            // ZIP faylını endir
            $downloader = new Downloader(self::$config);
            $zipPath = $downloader->download(self::$manifest['download_url']);

            // Checksum validasiyası
            Security::validateChecksum($zipPath, self::$manifest['checksum']);

            // Yedəkləmə
            $backup = new Backup(self::$config);
            $filesToBackup = self::$manifest['files'] ?? [];
            $backupPath = $backup->create($filesToBackup, $basePath);

            // Faylları çıxar
            $extractor = new Extractor(self::$config);
            $allowedFiles = self::$manifest['files'] ?? [];
            $extractedFiles = $extractor->extract($zipPath, $basePath, $allowedFiles);

            // Müvəqqəti faylları təmizlə
            $downloader->cleanup();

            // Köhnə yedəkləri təmizlə
            $backup->cleanup(5);

            // Yeniləmə cache-ini sıfırla
            self::$updateAvailable = false;

            return true;
        } catch (\Exception $e) {
            self::$lastError = $e->getMessage();
            throw $e;
        }
    }

    /**
     * Cari versiyanı qaytarır
     * 
     * @return string|null Cari versiya və ya null
     */
    public static function getCurrentVersion(): ?string
    {
        if (self::$config === null) {
            return null;
        }

        return self::$config->getCurrentVersion();
    }

    /**
     * Uzaq (server) versiyanı qaytarır
     * 
     * @return string|null Server versiyası və ya null
     */
    public static function getRemoteVersion(): ?string
    {
        if (self::$versionChecker === null) {
            return null;
        }

        return self::$versionChecker->getRemoteVersion();
    }

    /**
     * Yeniləmə qeydlərini (changelog) qaytarır
     * 
     * @return string|null Changelog və ya null
     */
    public static function getChangelog(): ?string
    {
        if (self::$manifest === null) {
            // Manifestı yükləməyə çalış
            if (self::$versionChecker !== null) {
                try {
                    self::$manifest = self::$versionChecker->fetchManifest();
                } catch (\Exception $e) {
                    return null;
                }
            } else {
                return null;
            }
        }

        return self::$manifest['changelog'] ?? null;
    }

    /**
     * Son xəta mesajını qaytarır
     * 
     * @return string|null Xəta mesajı və ya null
     */
    public static function getLastError(): ?string
    {
        return self::$lastError;
    }

    /**
     * Manifestı qaytarır
     * 
     * @return array|null Manifest məlumatları
     */
    public static function getManifest(): ?array
    {
        return self::$manifest;
    }

    /**
     * Config instansını qaytarır
     * 
     * @return Config|null
     */
    public static function getConfig(): ?Config
    {
        return self::$config;
    }

    /**
     * Konfiqurasiyanı yükləyir
     * 
     * @param string|array $config Konfiqurasiya faylı və ya array
     * @return void
     * @throws ConfigException
     */
    private static function loadConfig($config): void
    {
        self::$config = Config::getInstance();

        if (is_string($config)) {
            self::$config->load($config);
        } elseif (is_array($config)) {
            self::$config->loadFromArray($config);
        } else {
            throw new ConfigException(
                "Konfiqurasiya string (fayl yolu) və ya array olmalıdır."
            );
        }
    }

    /**
     * Vəziyyəti sıfırlayır (test üçün)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$config = null;
        self::$versionChecker = null;
        self::$manifest = null;
        self::$updateAvailable = null;
        self::$lastError = null;
        Config::reset();
    }
}
