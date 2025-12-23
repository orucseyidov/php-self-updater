<?php
/**
 * PHP Self-Updater - Endirmə Sınıfı
 * 
 * Yeniləmə fayllarını serverdən endirir.
 * cURL və file_get_contents dəstəyi.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\DownloadException;

/**
 * Downloader - Fayl endirmə
 * 
 * Bu sınıf yeniləmə ZIP faylını serverdən endirir
 * və müvəqqəti qovluğa saxlayır.
 */
class Downloader
{
    /**
     * Config instansı
     * @var Config
     */
    private Config $config;

    /**
     * Konstruktor
     * 
     * @param Config $config Konfiqurasiya instansı
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Faylı endirir və müvəqqəti qovluğa saxlayır
     * 
     * @param string $url Endiriləcək fayl URL-i
     * @param string|null $savePath Saxlanacaq yol (null olsa avtomatik yaradılır)
     * @return string Endirilmiş faylın yolu
     * @throws DownloadException Endirmə uğursuz olduqda
     */
    public function download(string $url, ?string $savePath = null): string
    {
        // Müvəqqəti qovluğu hazırla
        $tempDir = $this->config->getTempDirectory();
        $this->ensureDirectoryExists($tempDir);

        // Saxlanacaq fayl yolunu müəyyən et
        if ($savePath === null) {
            $fileName = 'update_' . time() . '.zip';
            $savePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
        }

        // Faylı endir
        $success = $this->downloadFile($url, $savePath);

        if (!$success) {
            throw new DownloadException(
                "Fayl endirilə bilmədi: {$url}"
            );
        }

        return $savePath;
    }

    /**
     * Faylı endirir
     * 
     * @param string $url URL
     * @param string $savePath Saxlanacaq yol
     * @return bool Uğurlu olub-olmadığı
     */
    private function downloadFile(string $url, string $savePath): bool
    {
        $timeout = $this->config->getTimeout();
        $verifySsl = $this->config->shouldVerifySSL();

        // cURL mövcuddursa istifadə et (daha etibarlı)
        if (function_exists('curl_init')) {
            return $this->curlDownload($url, $savePath, $timeout, $verifySsl);
        }

        // file_get_contents ilə endir
        return $this->fileGetContentsDownload($url, $savePath, $timeout, $verifySsl);
    }

    /**
     * cURL ilə fayl endirir
     * 
     * @param string $url URL
     * @param string $savePath Saxlanacaq yol
     * @param int $timeout Timeout
     * @param bool $verifySsl SSL verifikasiyası
     * @return bool
     */
    private function curlDownload(string $url, string $savePath, int $timeout, bool $verifySsl): bool
    {
        $fp = fopen($savePath, 'wb');
        
        if ($fp === false) {
            return false;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $timeout * 10, // Endirmə üçün daha uzun timeout
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            CURLOPT_USERAGENT      => 'PHP-Self-Updater/1.0',
            CURLOPT_BUFFERSIZE     => 8192,
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);

        // Uğursuz olduqda faylı sil
        if (!$success || $httpCode !== 200) {
            @unlink($savePath);
            return false;
        }

        return true;
    }

    /**
     * file_get_contents ilə fayl endirir
     * 
     * @param string $url URL
     * @param string $savePath Saxlanacaq yol
     * @param int $timeout Timeout
     * @param bool $verifySsl SSL verifikasiyası
     * @return bool
     */
    private function fileGetContentsDownload(string $url, string $savePath, int $timeout, bool $verifySsl): bool
    {
        $context = stream_context_create([
            'http' => [
                'timeout'         => $timeout * 10,
                'follow_location' => true,
                'max_redirects'   => 5,
                'user_agent'      => 'PHP-Self-Updater/1.0',
            ],
            'ssl' => [
                'verify_peer'      => $verifySsl,
                'verify_peer_name' => $verifySsl,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            return false;
        }

        // HTTP cavab kodunu yoxla
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;
            
            if ($httpCode !== 200) {
                return false;
            }
        }

        $result = file_put_contents($savePath, $content);
        
        return $result !== false;
    }

    /**
     * Qovluğun mövcud olduğundan əmin olur
     * 
     * @param string $directory Qovluq yolu
     * @return void
     * @throws DownloadException Qovluq yaradıla bilmədikdə
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new DownloadException(
                    "Müvəqqəti qovluq yaradıla bilmədi: {$directory}"
                );
            }
        }

        if (!is_writable($directory)) {
            throw new DownloadException(
                "Müvəqqəti qovluğa yazma icazəsi yoxdur: {$directory}"
            );
        }
    }

    /**
     * Müvəqqəti faylları təmizləyir
     * 
     * @return void
     */
    public function cleanup(): void
    {
        $tempDir = $this->config->getTempDirectory();

        if (!is_dir($tempDir)) {
            return;
        }

        // Yalnız .zip fayllarını sil
        $files = glob($tempDir . DIRECTORY_SEPARATOR . 'update_*.zip');
        
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            @unlink($file);
        }
    }
}
