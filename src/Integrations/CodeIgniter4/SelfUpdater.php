<?php
/**
 * CodeIgniter 4 Library for PHP Self-Updater
 * 
 * Quraşdırma:
 * 1. Bu faylı app/Libraries/ qovluğuna kopyalayın
 * 2. app/Config/SelfUpdater.php konfiqurasiya faylı yaradın
 * 
 * İstifadə:
 * $selfUpdater = new \App\Libraries\SelfUpdater();
 * if ($selfUpdater->check()) {
 *     $selfUpdater->run();
 * }
 * 
 * @package SelfUpdater
 */

namespace App\Libraries;

use SelfUpdater\Updater;
use SelfUpdater\Config as UpdaterConfig;

class SelfUpdater
{
    /**
     * Konfiqurasiya
     */
    protected array $config;

    /**
     * Konstruktor
     */
    public function __construct(?array $config = null)
    {
        if ($config === null) {
            // CI4 konfiqurasiya faylından oxu
            $config = config('SelfUpdater');
            if ($config && is_object($config)) {
                $this->config = $this->objectToArray($config);
            } else {
                $this->config = $this->getDefaultConfig();
            }
        } else {
            $this->config = array_merge($this->getDefaultConfig(), $config);
        }
    }

    /**
     * Yeniləmələri yoxlayır
     */
    public function check(): bool
    {
        return Updater::check($this->config, ROOTPATH);
    }

    /**
     * Yeniləmə varsa true qaytarır
     */
    public function hasUpdate(): bool
    {
        return Updater::hasUpdate();
    }

    /**
     * Yeniləməni icra edir
     */
    public function run(?string $basePath = null): bool
    {
        return Updater::run($basePath ?? ROOTPATH);
    }

    /**
     * Cari versiyanı qaytarır
     */
    public function getCurrentVersion(): ?string
    {
        return Updater::getCurrentVersion();
    }

    /**
     * Server versiyasını qaytarır
     */
    public function getRemoteVersion(): ?string
    {
        return Updater::getRemoteVersion();
    }

    /**
     * Changelog qaytarır
     */
    public function getChangelog(): ?string
    {
        return Updater::getChangelog();
    }

    /**
     * Son xəta mesajını qaytarır
     */
    public function getLastError(): ?string
    {
        return Updater::getLastError();
    }

    /**
     * State-i sıfırlayır
     */
    public function reset(): void
    {
        Updater::reset();
    }

    /**
     * Default konfiqurasiya
     */
    protected function getDefaultConfig(): array
    {
        return [
            'current_version'           => '1.0.0',
            'update_server_url'         => '',
            'channel'                   => 'production',
            'version_endpoint'          => '/api/version.json',
            'update_manifest_endpoint'  => '/api/manifest.json',
            'update_paths'              => ['app'],
            'exclude_paths'             => ['.env', 'writable', 'vendor'],
            'temp_directory'            => WRITEPATH . 'self-updater',
            'backup_enabled'            => true,
            'backup_directory'          => WRITEPATH . 'self-updater/backups',
            'autoupdate'                => false,
            'timeout'                   => 30,
            'verify_ssl'                => true,
        ];
    }

    /**
     * Config object-i array-ə çevirir
     */
    protected function objectToArray(object $config): array
    {
        $result = [];
        foreach (get_object_vars($config) as $key => $value) {
            $result[$key] = $value;
        }
        return array_merge($this->getDefaultConfig(), $result);
    }
}
