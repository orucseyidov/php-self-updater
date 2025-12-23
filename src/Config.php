<?php
/**
 * PHP Self-Updater - Konfiqurasiya Sınıfı
 * 
 * Konfiqurasiya faylını yükləyir və idarə edir.
 * Singleton dizaynı ilə tək instans saxlayır.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\ConfigException;

/**
 * Config - Konfiqurasiya idarəetmə sınıfı
 * 
 * Bu sınıf konfiqurasiya faylını yükləyir, validasiya edir
 * və bütün parametrlərə giriş təmin edir.
 */
class Config
{
    /**
     * Singleton instansı
     * @var Config|null
     */
    private static ?Config $instance = null;

    /**
     * Konfiqurasiya dəyərləri
     * @var array
     */
    private array $config = [];

    /**
     * Varsayılan konfiqurasiya dəyərləri
     * 
     * Bu dəyərlər konfiqurasiya faylında verilmədikdə istifadə olunur.
     * 
     * @var array
     */
    private array $defaults = [
        'current_version'           => '1.0.0',
        'update_server_url'         => '',
        'version_endpoint'          => '/version.json',
        'update_manifest_endpoint'  => '/manifest.json',
        'update_paths'              => [],
        'exclude_paths'             => ['.env', 'storage', 'uploads', 'config'],
        'temp_directory'            => '/tmp/self-updater',
        'backup_enabled'            => true,
        'backup_directory'          => '/tmp/self-updater/backups',
        'autoupdate'                => false,  // Avtomatik yeniləmə - əgər açıqdırsa, check() çağrıldığında yeniləmə yoxlanılır
        'timeout'                   => 30,     // HTTP sorğu timeout (saniyə)
        'verify_ssl'                => true,   // SSL sertifikatını yoxla
    ];

    /**
     * Məcburi konfiqurasiya açarları
     * 
     * Bu açarlar mütləq konfiqurasiya faylında olmalıdır.
     * 
     * @var array
     */
    private array $required = [
        'current_version',
        'update_server_url',
    ];

    /**
     * Konstruktor - xaricdən instans yaradılmasının qarşısını alır
     */
    private function __construct()
    {
        // Singleton - xaricdən çağırıla bilməz
    }

    /**
     * Singleton instansını qaytarır
     * 
     * @return Config
     */
    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konfiqurasiya faylını yükləyir
     * 
     * @param string $configPath Konfiqurasiya faylının yolu
     * @return void
     * @throws ConfigException Fayl tapılmadıqda və ya yanlış formatda olduqda
     */
    public function load(string $configPath): void
    {
        // Faylın mövcudluğunu yoxla
        if (!file_exists($configPath)) {
            throw new ConfigException(
                "Konfiqurasiya faylı tapılmadı: {$configPath}"
            );
        }

        // Faylın oxuna biləcəyini yoxla
        if (!is_readable($configPath)) {
            throw new ConfigException(
                "Konfiqurasiya faylı oxuna bilmir: {$configPath}"
            );
        }

        // Konfiqurasiyanı yüklə
        $config = require $configPath;

        // Array olduğunu yoxla
        if (!is_array($config)) {
            throw new ConfigException(
                "Konfiqurasiya faylı array qaytarmalıdır: {$configPath}"
            );
        }

        // Varsayılan dəyərlərlə birləşdir
        $this->config = array_merge($this->defaults, $config);

        // Məcburi açarları yoxla
        $this->validateRequired();
    }

    /**
     * Array-dən konfiqurasiyanı yükləyir
     * 
     * Konfiqurasiya faylı olmadan birbaşa array ilə konfiqurasiya etmək üçün.
     * 
     * @param array $config Konfiqurasiya dəyərləri
     * @return void
     * @throws ConfigException Məcburi açarlar eksik olduqda
     */
    public function loadFromArray(array $config): void
    {
        $this->config = array_merge($this->defaults, $config);
        $this->validateRequired();
    }

    /**
     * Məcburi açarların mövcudluğunu yoxlayır
     * 
     * @return void
     * @throws ConfigException
     */
    private function validateRequired(): void
    {
        foreach ($this->required as $key) {
            if (empty($this->config[$key])) {
                throw new ConfigException(
                    "Məcburi konfiqurasiya açarı eksik və ya boşdur: {$key}"
                );
            }
        }

        // update_server_url format yoxlaması
        if (!filter_var($this->config['update_server_url'], FILTER_VALIDATE_URL)) {
            throw new ConfigException(
                "Yanlış URL formatı: update_server_url"
            );
        }
    }

    /**
     * Konfiqurasiya dəyərini qaytarır
     * 
     * Nöqtəli notasiya ilə iç-içə dəyərlərə giriş:
     * Məsələn: get('database.host')
     * 
     * @param string $key Açar
     * @param mixed $default Varsayılan dəyər (tapılmadıqda)
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // Nöqtəli notasiya dəstəyi
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Konfiqurasiya dəyərini təyin edir
     * 
     * @param string $key Açar
     * @param mixed $value Dəyər
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Bütün konfiqurasiyanı qaytarır
     * 
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * Yeniləmə server URL-ini qaytarır
     * 
     * @return string
     */
    public function getServerUrl(): string
    {
        return rtrim($this->config['update_server_url'], '/');
    }

    /**
     * Versiya endpoint URL-ini qaytarır
     * 
     * @return string
     */
    public function getVersionUrl(): string
    {
        return $this->getServerUrl() . $this->config['version_endpoint'];
    }

    /**
     * Manifest endpoint URL-ini qaytarır
     * 
     * @return string
     */
    public function getManifestUrl(): string
    {
        return $this->getServerUrl() . $this->config['update_manifest_endpoint'];
    }

    /**
     * Avtomatik yeniləmənin açıq olub-olmadığını yoxlayır
     * 
     * @return bool
     */
    public function isAutoUpdateEnabled(): bool
    {
        return (bool) $this->config['autoupdate'];
    }

    /**
     * Yedəkləmənin açıq olub-olmadığını yoxlayır
     * 
     * @return bool
     */
    public function isBackupEnabled(): bool
    {
        return (bool) $this->config['backup_enabled'];
    }

    /**
     * İcazə verilmiş yolları qaytarır
     * 
     * @return array
     */
    public function getUpdatePaths(): array
    {
        return $this->config['update_paths'];
    }

    /**
     * İstisna edilmiş yolları qaytarır
     * 
     * @return array
     */
    public function getExcludePaths(): array
    {
        return $this->config['exclude_paths'];
    }

    /**
     * Müvəqqəti qovluq yolunu qaytarır
     * 
     * @return string
     */
    public function getTempDirectory(): string
    {
        return $this->config['temp_directory'];
    }

    /**
     * Yedəkləmə qovluğunu qaytarır
     * 
     * @return string
     */
    public function getBackupDirectory(): string
    {
        return $this->config['backup_directory'];
    }

    /**
     * Cari versiyası qaytarır
     * 
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return $this->config['current_version'];
    }

    /**
     * HTTP timeout dəyərini qaytarır
     * 
     * @return int
     */
    public function getTimeout(): int
    {
        return (int) $this->config['timeout'];
    }

    /**
     * SSL verifikasiyasının açıq olub-olmadığını yoxlayır
     * 
     * @return bool
     */
    public function shouldVerifySSL(): bool
    {
        return (bool) $this->config['verify_ssl'];
    }

    /**
     * Singleton instansını sıfırlayır (test üçün)
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
