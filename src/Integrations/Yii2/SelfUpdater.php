<?php
/**
 * Yii2 Component for PHP Self-Updater
 * 
 * Quraşdırma:
 * 1. config/web.php-də komponent olaraq qeydiyyat edin:
 * 
 * 'components' => [
 *     'selfUpdater' => [
 *         'class' => 'app\components\SelfUpdater',
 *         'config' => [
 *             'current_version' => '1.0.0',
 *             'update_server_url' => 'https://...',
 *             // ... digər konfiqurasiya
 *         ],
 *     ],
 * ],
 * 
 * İstifadə:
 * Yii::$app->selfUpdater->check();
 * 
 * @package SelfUpdater
 */

namespace app\components;

use yii\base\Component;
use SelfUpdater\Updater;

class SelfUpdater extends Component
{
    /**
     * Konfiqurasiya
     */
    public array $config = [];

    /**
     * Component init
     */
    public function init(): void
    {
        parent::init();

        // Default dəyərləri əlavə et
        $this->config = array_merge($this->getDefaultConfig(), $this->config);
    }

    /**
     * Yeniləmələri yoxlayır
     */
    public function check(): bool
    {
        return Updater::check($this->config, \Yii::getAlias('@app'));
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
        return Updater::run($basePath ?? \Yii::getAlias('@app'));
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
            'update_paths'              => [],
            'exclude_paths'             => ['config', 'runtime', 'vendor', 'web/assets'],
            'temp_directory'            => \Yii::getAlias('@runtime') . '/self-updater',
            'backup_enabled'            => true,
            'backup_directory'          => \Yii::getAlias('@runtime') . '/self-updater/backups',
            'autoupdate'                => false,
            'timeout'                   => 30,
            'verify_ssl'                => true,
        ];
    }
}
