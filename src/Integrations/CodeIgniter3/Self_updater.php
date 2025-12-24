<?php
/**
 * CodeIgniter 3 Library for PHP Self-Updater
 * 
 * Quraşdırma:
 * 1. Bu faylı application/libraries/ qovluğuna kopyalayın
 * 2. application/config/self_updater.php konfiqurasiya faylı yaradın
 * 
 * İstifadə:
 * $this->load->library('Self_updater');
 * if ($this->self_updater->check()) {
 *     $this->self_updater->run();
 * }
 * 
 * @package SelfUpdater
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// SelfUpdater autoload
require_once APPPATH . '../vendor/orucseyidov/php-self-updater/src/autoload.php';

use SelfUpdater\Updater;

class Self_updater
{
    /**
     * CI instance
     */
    protected $CI;

    /**
     * Konfiqurasiya
     */
    protected $config = [];

    /**
     * Konstruktor
     */
    public function __construct($params = [])
    {
        $this->CI =& get_instance();

        // Konfiqurasiyanı yüklə
        $this->CI->config->load('self_updater', TRUE, TRUE);
        $config = $this->CI->config->item('self_updater');

        if (is_array($config)) {
            $this->config = array_merge($this->getDefaultConfig(), $config);
        } else {
            $this->config = array_merge($this->getDefaultConfig(), $params);
        }
    }

    /**
     * Yeniləmələri yoxlayır
     */
    public function check()
    {
        return Updater::check($this->config, FCPATH);
    }

    /**
     * Yeniləmə varsa true qaytarır
     */
    public function has_update()
    {
        return Updater::hasUpdate();
    }

    /**
     * Yeniləməni icra edir
     */
    public function run($base_path = NULL)
    {
        return Updater::run($base_path ?? FCPATH);
    }

    /**
     * Cari versiyanı qaytarır
     */
    public function get_current_version()
    {
        return Updater::getCurrentVersion();
    }

    /**
     * Server versiyasını qaytarır
     */
    public function get_remote_version()
    {
        return Updater::getRemoteVersion();
    }

    /**
     * Changelog qaytarır
     */
    public function get_changelog()
    {
        return Updater::getChangelog();
    }

    /**
     * Son xəta mesajını qaytarır
     */
    public function get_last_error()
    {
        return Updater::getLastError();
    }

    /**
     * State-i sıfırlayır
     */
    public function reset()
    {
        Updater::reset();
    }

    /**
     * Default konfiqurasiya
     */
    protected function getDefaultConfig()
    {
        return [
            'current_version'           => '1.0.0',
            'update_server_url'         => '',
            'channel'                   => 'production',
            'version_endpoint'          => '/api/version.json',
            'update_manifest_endpoint'  => '/api/manifest.json',
            'update_paths'              => ['application'],
            'exclude_paths'             => ['application/config', 'application/logs', 'uploads'],
            'temp_directory'            => APPPATH . 'cache/self-updater',
            'backup_enabled'            => TRUE,
            'backup_directory'          => APPPATH . 'cache/self-updater/backups',
            'autoupdate'                => FALSE,
            'timeout'                   => 30,
            'verify_ssl'                => TRUE,
        ];
    }
}
