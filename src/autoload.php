<?php
/**
 * PHP Self-Updater - Manual Autoloader
 * 
 * Composer olmadan istifadə üçün bu faylı daxil edin.
 * 
 * @package SelfUpdater
 */

// Exceptions
require_once __DIR__ . '/Exceptions/UpdaterException.php';
require_once __DIR__ . '/Exceptions/ConfigException.php';
require_once __DIR__ . '/Exceptions/DownloadException.php';
require_once __DIR__ . '/Exceptions/ChecksumException.php';
require_once __DIR__ . '/Exceptions/ExtractionException.php';

// Core classes
require_once __DIR__ . '/Config.php';
require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/Downloader.php';
require_once __DIR__ . '/Extractor.php';
require_once __DIR__ . '/Backup.php';
require_once __DIR__ . '/VersionChecker.php';
require_once __DIR__ . '/Updater.php';
require_once __DIR__ . '/UpdateWidget.php';

// Framework integrations (lazy load - yalnız framework-da istifadə ediləcək)
// Laravel: SelfUpdater\Integrations\Laravel\SelfUpdaterServiceProvider
// CodeIgniter 3: src/Integrations/CodeIgniter3/Self_updater.php
// CodeIgniter 4: SelfUpdater\Integrations\CodeIgniter4\SelfUpdater
// Symfony: SelfUpdater\Integrations\Symfony\SelfUpdaterService
// Yii2: app\components\SelfUpdater
// Slim: App\Middleware\SelfUpdaterMiddleware
