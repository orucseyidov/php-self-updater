<?php
/**
 * CodeIgniter 3 Konfiqurasiya Faylı
 * 
 * Bu faylı application/config/self_updater.php olaraq kopyalayın
 */

defined('BASEPATH') OR exit('No direct script access allowed');

$config['self_updater'] = [
    // Cari versiya
    'current_version' => '1.0.0',

    // Yeniləmə server URL
    'update_server_url' => '',

    // Channel: 'development', 'staging', 'production'
    'channel' => 'production',

    // Versiya endpoint
    'version_endpoint' => '/api/version.json',

    // Manifest endpoint
    'update_manifest_endpoint' => '/api/manifest.json',

    // Yenilənəcək yollar (boş = hamısı)
    'update_paths' => ['application'],

    // İstisna yollar
    'exclude_paths' => [
        'application/config',
        'application/logs',
        'uploads',
    ],

    // Müvəqqəti qovluq
    'temp_directory' => APPPATH . 'cache/self-updater',

    // Yedəkləmə
    'backup_enabled' => TRUE,
    'backup_directory' => APPPATH . 'cache/self-updater/backups',

    // Avtomatik yeniləmə
    'autoupdate' => FALSE,

    // HTTP ayarları
    'timeout' => 30,
    'verify_ssl' => TRUE,
];
