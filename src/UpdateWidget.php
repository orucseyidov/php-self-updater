<?php
/**
 * PHP Self-Updater - Yeniləmə Widget Sınıfı
 * 
 * Hazır button və popup komponenti.
 * İstifadəçilər öz tətbiqlərində istədikləri yerə qoya bilərlər.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

/**
 * UpdateWidget - Yeniləmə UI komponenti
 * 
 * Bu sınıf yeniləmə düyməsi, təsdiq popup-u və 
 * progress göstəricisi render edir.
 * 
 * İstifadə:
 * <code>
 * // Sadə istifadə
 * echo UpdateWidget::render();
 * 
 * // Xüsusi button ilə
 * echo UpdateWidget::render([
 *     'button_text' => 'Yenilə',
 *     'button_class' => 'my-btn',
 * ]);
 * 
 * // Yalnız yeniləmə varsa göstər
 * echo UpdateWidget::renderIfAvailable($configPath);
 * </code>
 */
class UpdateWidget
{
    /**
     * Varsayılan seçimlər
     * @var array
     */
    private static array $defaults = [
        // Button
        'button_text'           => 'Yeni versiya mövcuddur!',
        'button_class'          => 'self-updater-btn',
        'button_id'             => 'self-updater-trigger',
        'show_version'          => true,
        
        // Popup
        'confirm_title'         => 'Yeniləmə Mövcuddur',
        'confirm_message'       => 'Tətbiqi yeniləmək istədiyinizdən əminsiniz?',
        'confirm_yes'           => 'Bəli, yenilə',
        'confirm_no'            => 'Xeyr',
        
        // Progress
        'progress_title'        => 'Yeniləmə davam edir...',
        'progress_downloading'  => 'Fayllar endirilir...',
        'progress_extracting'   => 'Fayllar çıxarılır...',
        'progress_complete'     => 'Yeniləmə tamamlandı!',
        'progress_error'        => 'Xəta baş verdi!',
        
        // API
        'api_endpoint'          => '/api/self-updater.php',
        'check_endpoint'        => '/api/self-updater.php?action=check',
        
        // Görünüş
        'theme'                 => 'default', // 'default', 'dark', 'minimal'
        'position'              => 'bottom-right', // popup mövqeyi
        
        // Assets
        'include_css'           => true,
        'include_js'            => true,
    ];

    /**
     * Widget-i render edir
     * 
     * @param array $options Xüsusi seçimlər
     * @return string HTML çıxışı
     */
    public static function render(array $options = []): string
    {
        $opts = array_merge(self::$defaults, $options);
        
        $html = '';
        
        // CSS
        if ($opts['include_css']) {
            $html .= self::renderCSS($opts);
        }
        
        // Button
        $html .= self::renderButton($opts);
        
        // Popup Modal
        $html .= self::renderModal($opts);
        
        // JavaScript
        if ($opts['include_js']) {
            $html .= self::renderJS($opts);
        }
        
        return $html;
    }

    /**
     * Yalnız yeniləmə mövcuddursa render edir
     * 
     * @param string|array $config Konfiqurasiya
     * @param array $options Widget seçimləri
     * @return string HTML və ya boş string
     */
    public static function renderIfAvailable($config, array $options = []): string
    {
        try {
            $hasUpdate = Updater::check($config);
            
            if (!$hasUpdate) {
                return '';
            }
            
            // Versiya məlumatını əlavə et
            $options['current_version'] = Updater::getCurrentVersion();
            $options['remote_version'] = Updater::getRemoteVersion();
            $options['changelog'] = Updater::getChangelog();
            
            return self::render($options);
        } catch (\Exception $e) {
            return ''; // Xəta olduqda heç nə göstərmə
        }
    }

    /**
     * Yalnız button render edir (xüsusi popup ilə istifadə üçün)
     * 
     * @param array $options Seçimlər
     * @return string HTML
     */
    public static function renderButton(array $options = []): string
    {
        $opts = array_merge(self::$defaults, $options);
        
        $versionBadge = '';
        if ($opts['show_version'] && isset($opts['remote_version'])) {
            $versionBadge = '<span class="self-updater-version">v' . 
                htmlspecialchars($opts['remote_version']) . '</span>';
        }
        
        return sprintf(
            '<button type="button" id="%s" class="%s" data-updater-trigger>
                <span class="self-updater-icon">🔄</span>
                <span class="self-updater-text">%s</span>
                %s
            </button>',
            htmlspecialchars($opts['button_id']),
            htmlspecialchars($opts['button_class']),
            htmlspecialchars($opts['button_text']),
            $versionBadge
        );
    }

    /**
     * Modal popup render edir
     * 
     * @param array $options Seçimlər
     * @return string HTML
     */
    public static function renderModal(array $options = []): string
    {
        $opts = array_merge(self::$defaults, $options);
        
        $changelog = '';
        if (isset($opts['changelog']) && !empty($opts['changelog'])) {
            $changelog = '<div class="self-updater-changelog">
                <h4>Dəyişikliklər:</h4>
                <pre>' . htmlspecialchars($opts['changelog']) . '</pre>
            </div>';
        }
        
        return <<<HTML
<div id="self-updater-modal" class="self-updater-modal self-updater-hidden">
    <div class="self-updater-overlay" data-updater-close></div>
    <div class="self-updater-dialog">
        <!-- Təsdiq ekranı -->
        <div class="self-updater-screen" id="self-updater-confirm">
            <div class="self-updater-header">
                <h3>{$opts['confirm_title']}</h3>
                <button type="button" class="self-updater-close" data-updater-close>&times;</button>
            </div>
            <div class="self-updater-body">
                <p class="self-updater-message">{$opts['confirm_message']}</p>
                {$changelog}
            </div>
            <div class="self-updater-footer">
                <button type="button" class="self-updater-btn-secondary" data-updater-close>{$opts['confirm_no']}</button>
                <button type="button" class="self-updater-btn-primary" data-updater-start>{$opts['confirm_yes']}</button>
            </div>
        </div>
        
        <!-- Progress ekranı -->
        <div class="self-updater-screen self-updater-hidden" id="self-updater-progress">
            <div class="self-updater-header">
                <h3>{$opts['progress_title']}</h3>
            </div>
            <div class="self-updater-body">
                <div class="self-updater-progress-bar">
                    <div class="self-updater-progress-fill" id="self-updater-progress-fill"></div>
                </div>
                <p class="self-updater-status" id="self-updater-status">{$opts['progress_downloading']}</p>
                <div class="self-updater-log" id="self-updater-log"></div>
            </div>
        </div>
        
        <!-- Tamamlandı ekranı -->
        <div class="self-updater-screen self-updater-hidden" id="self-updater-complete">
            <div class="self-updater-header">
                <h3>✅ {$opts['progress_complete']}</h3>
            </div>
            <div class="self-updater-body">
                <p>Tətbiq uğurla yeniləndi. Səhifəni yeniləyin.</p>
            </div>
            <div class="self-updater-footer">
                <button type="button" class="self-updater-btn-primary" onclick="location.reload()">Səhifəni Yenilə</button>
            </div>
        </div>
        
        <!-- Xəta ekranı -->
        <div class="self-updater-screen self-updater-hidden" id="self-updater-error">
            <div class="self-updater-header">
                <h3>❌ {$opts['progress_error']}</h3>
                <button type="button" class="self-updater-close" data-updater-close>&times;</button>
            </div>
            <div class="self-updater-body">
                <p class="self-updater-error-message" id="self-updater-error-message"></p>
            </div>
            <div class="self-updater-footer">
                <button type="button" class="self-updater-btn-secondary" data-updater-close>Bağla</button>
            </div>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * CSS render edir
     * 
     * @param array $options Seçimlər
     * @return string Style tag
     */
    public static function renderCSS(array $options = []): string
    {
        $theme = $options['theme'] ?? 'default';
        
        return '<style>' . self::getCSS($theme) . '</style>';
    }

    /**
     * JavaScript render edir
     * 
     * @param array $options Seçimlər
     * @return string Script tag
     */
    public static function renderJS(array $options = []): string
    {
        $opts = array_merge(self::$defaults, $options);
        
        $config = json_encode([
            'apiEndpoint' => $opts['api_endpoint'],
            'messages' => [
                'downloading' => $opts['progress_downloading'],
                'extracting' => $opts['progress_extracting'],
                'complete' => $opts['progress_complete'],
                'error' => $opts['progress_error'],
            ]
        ]);
        
        return '<script>var SelfUpdaterConfig = ' . $config . ';' . self::getJS() . '</script>';
    }

    /**
     * CSS kodunu qaytarır
     * 
     * @param string $theme Tema adı
     * @return string CSS
     */
    private static function getCSS(string $theme = 'default'): string
    {
        $primaryColor = '#4CAF50';
        $bgColor = '#ffffff';
        $textColor = '#333333';
        $borderColor = '#e0e0e0';
        
        if ($theme === 'dark') {
            $primaryColor = '#66BB6A';
            $bgColor = '#2d2d2d';
            $textColor = '#ffffff';
            $borderColor = '#444444';
        }
        
        return <<<CSS
/* Self-Updater Widget Styles */
.self-updater-hidden { display: none !important; }

.self-updater-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, {$primaryColor}, #45a049);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
}

.self-updater-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
}

.self-updater-icon {
    font-size: 18px;
    animation: self-updater-spin 2s linear infinite;
}

@keyframes self-updater-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.self-updater-version {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
}

/* Modal */
.self-updater-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.self-updater-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
}

.self-updater-dialog {
    position: relative;
    background: {$bgColor};
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 90%;
    max-width: 480px;
    max-height: 90vh;
    overflow: hidden;
    animation: self-updater-fadeIn 0.3s ease;
}

@keyframes self-updater-fadeIn {
    from { opacity: 0; transform: scale(0.9); }
    to { opacity: 1; transform: scale(1); }
}

.self-updater-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid {$borderColor};
}

.self-updater-header h3 {
    margin: 0;
    font-size: 20px;
    color: {$textColor};
}

.self-updater-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #999;
    line-height: 1;
    padding: 0;
}

.self-updater-close:hover { color: #333; }

.self-updater-body {
    padding: 24px;
    color: {$textColor};
}

.self-updater-message {
    font-size: 16px;
    margin: 0 0 16px 0;
}

.self-updater-changelog {
    background: #f5f5f5;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.self-updater-changelog h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
}

.self-updater-changelog pre {
    margin: 0;
    font-size: 13px;
    white-space: pre-wrap;
    max-height: 150px;
    overflow-y: auto;
}

.self-updater-footer {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    padding: 16px 24px;
    border-top: 1px solid {$borderColor};
}

.self-updater-btn-primary {
    padding: 12px 24px;
    background: {$primaryColor};
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.self-updater-btn-primary:hover { background: #45a049; }

.self-updater-btn-secondary {
    padding: 12px 24px;
    background: transparent;
    color: #666;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.self-updater-btn-secondary:hover {
    background: #f5f5f5;
    border-color: #ccc;
}

/* Progress */
.self-updater-progress-bar {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 16px;
}

.self-updater-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, {$primaryColor}, #8BC34A);
    border-radius: 4px;
    width: 0%;
    transition: width 0.3s ease;
}

.self-updater-status {
    text-align: center;
    font-size: 14px;
    color: #666;
    margin: 0;
}

.self-updater-log {
    margin-top: 16px;
    padding: 12px;
    background: #f9f9f9;
    border-radius: 8px;
    font-family: monospace;
    font-size: 12px;
    max-height: 120px;
    overflow-y: auto;
}

.self-updater-log p {
    margin: 4px 0;
    color: #555;
}

.self-updater-log p.success { color: #4CAF50; }
.self-updater-log p.error { color: #f44336; }

.self-updater-error-message {
    color: #f44336;
    background: #ffebee;
    padding: 16px;
    border-radius: 8px;
    margin: 0;
}
CSS;
    }

    /**
     * JavaScript kodunu qaytarır
     * 
     * @return string JavaScript
     */
    private static function getJS(): string
    {
        return <<<'JS'
(function() {
    'use strict';
    
    var modal = document.getElementById('self-updater-modal');
    var screens = {
        confirm: document.getElementById('self-updater-confirm'),
        progress: document.getElementById('self-updater-progress'),
        complete: document.getElementById('self-updater-complete'),
        error: document.getElementById('self-updater-error')
    };
    
    // Ekranları göstər/gizlə
    function showScreen(name) {
        Object.keys(screens).forEach(function(key) {
            if (screens[key]) {
                screens[key].classList.toggle('self-updater-hidden', key !== name);
            }
        });
    }
    
    // Modal aç
    function openModal() {
        modal.classList.remove('self-updater-hidden');
        showScreen('confirm');
    }
    
    // Modal bağla
    function closeModal() {
        modal.classList.add('self-updater-hidden');
    }
    
    // Log əlavə et
    function addLog(message, type) {
        var log = document.getElementById('self-updater-log');
        if (log) {
            var p = document.createElement('p');
            p.textContent = message;
            if (type) p.className = type;
            log.appendChild(p);
            log.scrollTop = log.scrollHeight;
        }
    }
    
    // Progress yenilə
    function updateProgress(percent, status) {
        var fill = document.getElementById('self-updater-progress-fill');
        var statusEl = document.getElementById('self-updater-status');
        
        if (fill) fill.style.width = percent + '%';
        if (statusEl && status) statusEl.textContent = status;
    }
    
    // Yeniləməni başlat
    function startUpdate() {
        showScreen('progress');
        updateProgress(0, SelfUpdaterConfig.messages.downloading);
        addLog('Yeniləmə başladı...', null);
        
        // Simulate progress for demo
        var progress = 0;
        var interval = setInterval(function() {
            progress += Math.random() * 15;
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
            }
            
            if (progress < 50) {
                updateProgress(progress, SelfUpdaterConfig.messages.downloading);
            } else {
                updateProgress(progress, SelfUpdaterConfig.messages.extracting);
            }
        }, 300);
        
        // AJAX ilə yeniləmə
        var xhr = new XMLHttpRequest();
        xhr.open('POST', SelfUpdaterConfig.apiEndpoint, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                clearInterval(interval);
                
                try {
                    var response = JSON.parse(xhr.responseText);
                    
                    if (response.success) {
                        updateProgress(100, SelfUpdaterConfig.messages.complete);
                        addLog('Yeniləmə uğurla tamamlandı!', 'success');
                        
                        setTimeout(function() {
                            showScreen('complete');
                        }, 500);
                    } else {
                        document.getElementById('self-updater-error-message').textContent = 
                            response.error || 'Naməlum xəta baş verdi';
                        showScreen('error');
                        addLog('Xəta: ' + (response.error || 'Naməlum xəta'), 'error');
                    }
                } catch (e) {
                    document.getElementById('self-updater-error-message').textContent = 
                        'Server cavabı oxuna bilmədi';
                    showScreen('error');
                    addLog('Xəta: Server cavabı oxuna bilmədi', 'error');
                }
            }
        };
        
        xhr.onerror = function() {
            clearInterval(interval);
            document.getElementById('self-updater-error-message').textContent = 
                'Şəbəkə xətası baş verdi';
            showScreen('error');
        };
        
        xhr.send('action=update');
    }
    
    // Event listeners
    document.querySelectorAll('[data-updater-trigger]').forEach(function(btn) {
        btn.addEventListener('click', openModal);
    });
    
    document.querySelectorAll('[data-updater-close]').forEach(function(btn) {
        btn.addEventListener('click', closeModal);
    });
    
    document.querySelectorAll('[data-updater-start]').forEach(function(btn) {
        btn.addEventListener('click', startUpdate);
    });
    
    // ESC ilə bağla
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('self-updater-hidden')) {
            closeModal();
        }
    });
})();
JS;
    }

    /**
     * Xarici CSS faylına link qaytarır
     * 
     * @param string $path CSS faylının yolu
     * @return string Link tag
     */
    public static function getCSSLink(string $path): string
    {
        return '<link rel="stylesheet" href="' . htmlspecialchars($path) . '">';
    }

    /**
     * Xarici JS faylına link qaytarır
     * 
     * @param string $path JS faylının yolu
     * @return string Script tag
     */
    public static function getJSLink(string $path): string
    {
        return '<script src="' . htmlspecialchars($path) . '"></script>';
    }
}
