<?php
/**
 * PHP Self-Updater - Widget İstifadə Nümunəsi
 * 
 * Bu nümunə UpdateWidget-in necə istifadə edildiyini göstərir.
 * SaaS layihələrinə inteqrasiya üçün.
 * 
 * @package SelfUpdater
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SelfUpdater\UpdateWidget;
use SelfUpdater\Updater;

// Konfiqurasiya
$configPath = __DIR__ . '/../config/updater.php';

?>
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeniləmə Widget Nümunəsi</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 40px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            padding: 40px;
            margin-bottom: 24px;
        }
        h1 { color: #333; margin-top: 0; }
        h2 { color: #555; font-size: 18px; }
        p { color: #666; line-height: 1.6; }
        pre {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 14px;
        }
        code { color: #e91e63; }
        .widget-container {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin: 24px 0;
        }
        .example-section {
            border-top: 1px solid #eee;
            padding-top: 24px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔄 PHP Self-Updater Widget</h1>
            <p>
                SaaS layihələri üçün hazır yeniləmə düyməsi.
                Müştəriləriniz bu düyməyə basaraq tətbiqi yeniləyə bilərlər.
            </p>
            
            <h2>Nümunə 1: Sadə İstifadə</h2>
            <p>Yeniləmə düyməsini göstərmək üçün:</p>
            <div class="widget-container">
                <?php
                // Sadə widget - həmişə göstərilir
                echo UpdateWidget::render([
                    'button_text' => 'Yeni versiya mövcuddur!',
                    'api_endpoint' => '../api/self-updater.php',
                ]);
                ?>
            </div>
            
            <pre><code>&lt;?php
echo UpdateWidget::render([
    'button_text' => 'Yeni versiya mövcuddur!',
    'api_endpoint' => '/api/self-updater.php',
]);
?&gt;</code></pre>
        </div>
        
        <div class="card">
            <h2>Nümunə 2: Yalnız Yeniləmə Mövcuddursa Göstər</h2>
            <p>Bu üsul serveri yoxlayır və yalnız yeniləmə varsa düyməni göstərir:</p>
            
            <div class="widget-container">
                <?php
                // Yalnız yeniləmə varsa göstər (demo üçün həmişə göstəririk)
                // Real istifadədə: echo UpdateWidget::renderIfAvailable($configPath);
                echo UpdateWidget::render([
                    'button_text' => 'v2.1.0 mövcuddur',
                    'button_class' => 'self-updater-btn',
                    'remote_version' => '2.1.0',
                    'changelog' => "## v2.1.0\n- Yeni özəlliklər\n- Xəta düzəlişləri",
                    'api_endpoint' => '../api/self-updater.php',
                    'include_css' => false, // CSS artıq yüklənib
                ]);
                ?>
            </div>
            
            <pre><code>&lt;?php
// Konfiqurasiya ilə yoxla
echo UpdateWidget::renderIfAvailable('/config/updater.php');
?&gt;</code></pre>
        </div>
        
        <div class="card">
            <h2>Nümunə 3: Xüsusi Stillərlə</h2>
            <p>Dark tema və xüsusi mətnlərlə:</p>
            
            <div class="widget-container">
                <?php
                echo UpdateWidget::render([
                    'button_text' => 'Yenilə',
                    'theme' => 'dark',
                    'confirm_title' => 'Sistem Yeniləməsi',
                    'confirm_message' => 'Sistem yenilənəcək. Bu bir neçə dəqiqə çəkə bilər.',
                    'confirm_yes' => 'Davam et',
                    'confirm_no' => 'Ləğv et',
                    'api_endpoint' => '../api/self-updater.php',
                    'include_css' => false,
                    'include_js' => false, // JS artıq yüklənib
                ]);
                ?>
            </div>
        </div>
        
        <div class="card">
            <h2>Admin Panelə İnteqrasiya</h2>
            <p>Öz admin panelinizə widget əlavə etmək üçün:</p>
            
            <pre><code>&lt;?php
// Admin panel header və ya sidebar-da
use SelfUpdater\UpdateWidget;

// Yeniləmə varsa göstər
echo UpdateWidget::renderIfAvailable('/config/updater.php', [
    'api_endpoint' => '/admin/api/updater.php',
    'button_text' => '🔔 Yeniləmə',
    'theme' => 'dark',
]);
?&gt;</code></pre>
            
            <div class="example-section">
                <h3>API Endpoint Quraşdırması</h3>
                <p>
                    <code>api/self-updater.php</code> faylını public qovluğunuza kopyalayın
                    və konfiqurasiya yolunu düzəldin.
                </p>
            </div>
        </div>
        
        <div class="card">
            <h2>Öz Buttonunuzu İstifadə Edin</h2>
            <p>Mövcud düymənizi istifadə etmək istəyirsinizsə:</p>
            
            <pre><code>&lt;!-- Öz buttonunuz --&gt;
&lt;button id="my-update-btn" class="my-btn" data-updater-trigger&gt;
    Sistemi Yenilə
&lt;/button&gt;

&lt;?php
// Yalnız modal və JS-i render et
echo UpdateWidget::renderModal();
echo UpdateWidget::renderJS(['api_endpoint' => '/api/self-updater.php']);
?&gt;</code></pre>
            
            <p>
                <strong>Qeyd:</strong> <code>data-updater-trigger</code> atributunu
                öz düymənizə əlavə etməyi unutmayın!
            </p>
        </div>
    </div>
</body>
</html>
