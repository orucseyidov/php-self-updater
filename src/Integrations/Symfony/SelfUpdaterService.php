<?php
/**
 * Symfony Service for PHP Self-Updater
 * 
 * Quraşdırma:
 * 1. Bu faylı src/Service/ qovluğuna kopyalayın
 * 2. services.yaml-da qeydiyyat edin
 * 
 * services.yaml:
 *   App\Service\SelfUpdaterService:
 *       arguments:
 *           $config: '%self_updater%'
 * 
 * @package SelfUpdater
 */

namespace App\Service;

use SelfUpdater\Updater;

class SelfUpdaterService
{
    private array $config;
    private string $projectDir;

    public function __construct(array $config, string $projectDir)
    {
        $this->config = $config;
        $this->projectDir = $projectDir;
    }

    /**
     * Yeniləmələri yoxlayır
     */
    public function check(): bool
    {
        return Updater::check($this->config, $this->projectDir);
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
        return Updater::run($basePath ?? $this->projectDir);
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
}
