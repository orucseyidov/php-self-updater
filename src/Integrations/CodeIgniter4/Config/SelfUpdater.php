<?php
/**
 * CodeIgniter 4 Konfiqurasiya Faylı
 * 
 * Bu faylı app/Config/SelfUpdater.php olaraq kopyalayın
 * 
 * @package SelfUpdater
 */

namespace Config;

use CodeIgniter\Config\BaseConfig;

class SelfUpdater extends BaseConfig
{
    /**
     * Cari versiya
     */
    public string $current_version = '1.0.0';

    /**
     * Yeniləmə server URL
     */
    public string $update_server_url = '';

    /**
     * Channel: 'development', 'staging', 'production'
     */
    public string $channel = 'production';

    /**
     * Versiya endpoint
     */
    public string $version_endpoint = '/api/version.json';

    /**
     * Manifest endpoint
     */
    public string $update_manifest_endpoint = '/api/manifest.json';

    /**
     * Yenilənəcək yollar (boş = hamısı)
     */
    public array $update_paths = ['app'];

    /**
     * İstisna yollar
     */
    public array $exclude_paths = [
        '.env',
        'writable',
        'vendor',
        'node_modules',
    ];

    /**
     * Müvəqqəti qovluq
     */
    public string $temp_directory = WRITEPATH . 'self-updater';

    /**
     * Yedəkləmə
     */
    public bool $backup_enabled = true;
    public string $backup_directory = WRITEPATH . 'self-updater/backups';

    /**
     * Avtomatik yeniləmə
     */
    public bool $autoupdate = false;

    /**
     * HTTP ayarları
     */
    public int $timeout = 30;
    public bool $verify_ssl = true;
}
