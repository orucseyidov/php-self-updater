<?php
/**
 * Laravel Facade for PHP Self-Updater
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater\Integrations\Laravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool check(?string $basePath = null)
 * @method static bool hasUpdate()
 * @method static bool run(?string $basePath = null)
 * @method static string|null getCurrentVersion()
 * @method static string|null getRemoteVersion()
 * @method static string|null getChangelog()
 * @method static string|null getLastError()
 * 
 * @see \SelfUpdater\Updater
 */
class SelfUpdater extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'self-updater';
    }
}
