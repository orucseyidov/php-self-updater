<?php
/**
 * Laravel Service Provider for PHP Self-Updater
 * 
 * Laravel 8.x, 9.x, 10.x, 11.x dəstəkləyir
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater\Integrations\Laravel;

use Illuminate\Support\ServiceProvider;
use SelfUpdater\Updater;
use SelfUpdater\Config;

class SelfUpdaterServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Konfiqurasiyanı birləşdir
        $this->mergeConfigFrom(
            __DIR__ . '/config/self-updater.php',
            'self-updater'
        );

        // Updater singleton
        $this->app->singleton('self-updater', function ($app) {
            return new class {
                public function check(?string $basePath = null): bool
                {
                    $config = config('self-updater');
                    return Updater::check($config, $basePath ?? base_path());
                }

                public function hasUpdate(): bool
                {
                    return Updater::hasUpdate();
                }

                public function run(?string $basePath = null): bool
                {
                    return Updater::run($basePath ?? base_path());
                }

                public function getCurrentVersion(): ?string
                {
                    return Updater::getCurrentVersion();
                }

                public function getRemoteVersion(): ?string
                {
                    return Updater::getRemoteVersion();
                }

                public function getChangelog(): ?string
                {
                    return Updater::getChangelog();
                }

                public function getLastError(): ?string
                {
                    return Updater::getLastError();
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Konfiqurasiyanı publish et
        $this->publishes([
            __DIR__ . '/config/self-updater.php' => config_path('self-updater.php'),
        ], 'self-updater-config');

        // Artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\CheckUpdateCommand::class,
                Commands\RunUpdateCommand::class,
            ]);
        }
    }
}
