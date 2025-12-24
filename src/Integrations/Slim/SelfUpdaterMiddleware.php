<?php
/**
 * Slim Framework Middleware for PHP Self-Updater
 * 
 * Bu middleware admin paneldə yeniləmə statusunu göstərmək üçün istifadə edilə bilər.
 * 
 * @package SelfUpdater
 */

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use SelfUpdater\Updater;

class SelfUpdaterMiddleware implements MiddlewareInterface
{
    private array $config;
    private string $basePath;

    public function __construct(array $config, string $basePath)
    {
        $this->config = $config;
        $this->basePath = $basePath;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Yeniləmə yoxla (cache ilə)
        try {
            $hasUpdate = Updater::check($this->config, $this->basePath);

            // Request attribute-a əlavə et
            $request = $request
                ->withAttribute('self_updater_available', $hasUpdate)
                ->withAttribute('self_updater_current_version', Updater::getCurrentVersion())
                ->withAttribute('self_updater_remote_version', Updater::getRemoteVersion());

        } catch (\Exception $e) {
            $request = $request->withAttribute('self_updater_error', $e->getMessage());
        }

        return $handler->handle($request);
    }
}

/**
 * Helper class for Slim routes
 */
class SelfUpdaterHelper
{
    private array $config;
    private string $basePath;

    public function __construct(array $config, string $basePath)
    {
        $this->config = $config;
        $this->basePath = $basePath;
    }

    public function check(): bool
    {
        return Updater::check($this->config, $this->basePath);
    }

    public function hasUpdate(): bool
    {
        return Updater::hasUpdate();
    }

    public function run(): bool
    {
        return Updater::run($this->basePath);
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
}
