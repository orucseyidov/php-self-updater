<?php
/**
 * PHP Self-Updater - İstifadə Nümunəsi
 * 
 * Bu nümunə kütüphanənin necə istifadə edildiyini göstərir.
 * 
 * @package SelfUpdater
 */

// Autoload - Composer istifadə edirsinizsə
require_once __DIR__ . '/../vendor/autoload.php';

// Və ya manual olaraq sınıfları daxil edin
// require_once __DIR__ . '/../src/Updater.php';
// ... digər sınıflar

use SelfUpdater\Updater;
use SelfUpdater\Exceptions\UpdaterException;

// ============================================
// NÜMUNƏ 1: Sadə istifadə
// ============================================

echo "=== PHP Self-Updater Nümunəsi ===\n\n";

try {
    // Konfiqurasiya faylı ilə yoxlama
    $configPath = __DIR__ . '/../config/updater.php';
    
    // Yeniləmələri yoxla
    $hasUpdate = Updater::check($configPath);

    echo "Cari versiya: " . Updater::getCurrentVersion() . "\n";
    echo "Server versiyası: " . (Updater::getRemoteVersion() ?? 'Alınamadı') . "\n";
    
    if ($hasUpdate) {
        echo "\n✅ Yeni versiya mövcuddur!\n";
        
        // Changelog göstər
        $changelog = Updater::getChangelog();
        if ($changelog) {
            echo "\n--- Dəyişiklik qeydləri ---\n";
            echo $changelog . "\n";
        }
        
        // Yeniləməni icra et
        // DİQQƏT: Bu mövcud faylları dəyişdirəcək!
        // $projectPath = dirname(__DIR__);
        // Updater::run($projectPath);
        // echo "\n🎉 Yeniləmə uğurla tamamlandı!\n";
        
    } else {
        echo "\n✓ Ən son versiyanı istifadə edirsiniz.\n";
    }

} catch (UpdaterException $e) {
    echo "\n❌ Xəta: " . $e->getMessage() . "\n";
}

// ============================================
// NÜMUNƏ 2: Array konfiqurasiya ilə
// ============================================

echo "\n\n=== Array Konfiqurasiya Nümunəsi ===\n\n";

try {
    // State-i sıfırla
    Updater::reset();
    
    // Array ilə konfiqurasiya
    $config = [
        'current_version'          => '1.0.0',
        'update_server_url'        => 'https://api.example.com',
        'version_endpoint'         => '/updates/version.json',
        'update_manifest_endpoint' => '/updates/manifest.json',
        'update_paths'             => ['src', 'lib'],
        'exclude_paths'            => ['.env', 'storage', 'uploads'],
        'temp_directory'           => sys_get_temp_dir() . '/my-app-updater',
        'backup_enabled'           => true,
        'autoupdate'               => false,  // Manuel yeniləmə
    ];

    $hasUpdate = Updater::check($config);
    
    echo "Yeniləmə mövcuddur: " . ($hasUpdate ? 'Bəli' : 'Xeyr') . "\n";

} catch (UpdaterException $e) {
    echo "Xəta: " . $e->getMessage() . "\n";
}

// ============================================
// NÜMUNƏ 3: Avtomatik yeniləmə
// ============================================

echo "\n\n=== Avtomatik Yeniləmə Nümunəsi ===\n\n";

try {
    Updater::reset();
    
    $config = [
        'current_version'          => '1.0.0',
        'update_server_url'        => 'https://api.example.com',
        'version_endpoint'         => '/updates/version.json',
        'update_manifest_endpoint' => '/updates/manifest.json',
        'autoupdate'               => true,  // Avtomatik yeniləmə açıq
        'backup_enabled'           => true,
    ];

    // autoupdate açıqdırsa, check() yeniləmə varsa avtomatik run() çağırır
    $projectPath = dirname(__DIR__);
    Updater::check($config, $projectPath);
    
    echo "Avtomatik yeniləmə yoxlandı\n";

} catch (UpdaterException $e) {
    echo "Xəta: " . $e->getMessage() . "\n";
}

// ============================================
// NÜMUNƏ 4: Web tətbiqi inteqrasiyası
// ============================================

/*
// Controller və ya route içində:

use SelfUpdater\Updater;

class UpdateController
{
    public function checkForUpdates()
    {
        $configPath = __DIR__ . '/../config/updater.php';
        
        try {
            $hasUpdate = Updater::check($configPath);
            
            return [
                'has_update'      => $hasUpdate,
                'current_version' => Updater::getCurrentVersion(),
                'remote_version'  => Updater::getRemoteVersion(),
                'changelog'       => Updater::getChangelog(),
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function performUpdate()
    {
        $configPath = __DIR__ . '/../config/updater.php';
        $projectPath = dirname(__DIR__);
        
        try {
            Updater::check($configPath);
            
            if (Updater::hasUpdate()) {
                Updater::run($projectPath);
                return ['success' => true, 'message' => 'Yeniləmə tamamlandı'];
            }
            
            return ['success' => false, 'message' => 'Yeniləmə yoxdur'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
*/

echo "\n\n=== Nümunələr tamamlandı ===\n";
