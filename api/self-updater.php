<?php
/**
 * PHP Self-Updater - API Handler
 * 
 * AJAX sorğularını qəbul edir və yeniləməni icra edir.
 * Bu faylı public qovluğa kopyalayın.
 * 
 * @package SelfUpdater
 */

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

use SelfUpdater\Updater;
use SelfUpdater\Config;

// CORS (lazım olduqda)
header('Content-Type: application/json; charset=utf-8');

// JSON cavabı göndər
function sendResponse(bool $success, string $message = '', array $data = []): void
{
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $data));
    exit;
}

// Action yoxla
$action = $_REQUEST['action'] ?? '';

try {
    // Konfiqurasiya yolu - öz yolunuza uyğunlaşdırın
    $configPath = __DIR__ . '/../config/updater.php';
    
    // Proyekt əsas qovluğu
    $basePath = dirname(__DIR__);
    
    switch ($action) {
        case 'check':
            // Yeniləmə yoxla
            $hasUpdate = Updater::check($configPath);
            
            sendResponse(true, '', [
                'has_update'      => $hasUpdate,
                'current_version' => Updater::getCurrentVersion(),
                'remote_version'  => Updater::getRemoteVersion(),
                'changelog'       => Updater::getChangelog(),
            ]);
            break;
            
        case 'update':
            // Yeniləməni icra et
            Updater::check($configPath);
            
            if (!Updater::hasUpdate()) {
                sendResponse(false, 'Yeniləmə mövcud deyil');
            }
            
            $result = Updater::run($basePath);
            
            if ($result) {
                sendResponse(true, 'Yeniləmə uğurla tamamlandı', [
                    'new_version' => Updater::getRemoteVersion(),
                ]);
            } else {
                sendResponse(false, Updater::getLastError() ?? 'Naməlum xəta');
            }
            break;
            
        default:
            sendResponse(false, 'Yanlış action: ' . $action);
    }
    
} catch (\Exception $e) {
    sendResponse(false, $e->getMessage());
}
