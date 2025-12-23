<?php
/**
 * PHP Self-Updater - Versiya Yoxlayıcı Sınıfı
 * 
 * Lokal və uzaq versiyaları müqayisə edir.
 * Semantik versiya formatını dəstəkləyir.
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater;

use SelfUpdater\Exceptions\DownloadException;

/**
 * VersionChecker - Versiya müqayisəsi
 * 
 * Bu sınıf lokal versiyanı serverdəki ilə müqayisə edir
 * və yeniləmə lazım olub-olmadığını müəyyən edir.
 */
class VersionChecker
{
    /**
     * Config instansı
     * @var Config
     */
    private Config $config;

    /**
     * Uzaq versiya məlumatları (cache)
     * @var array|null
     */
    private ?array $remoteVersionData = null;

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
     * Uzaq versiya məlumatlarını serverdən alır
     * 
     * @return array Versiya məlumatları
     * @throws DownloadException Serverlə əlaqə qurula bilmədikdə
     */
    public function fetchRemoteVersion(): array
    {
        // Cache-dən qaytarabilər
        if ($this->remoteVersionData !== null) {
            return $this->remoteVersionData;
        }

        $url = $this->config->getVersionUrl();
        $response = $this->makeHttpRequest($url);

        if ($response === false) {
            throw new DownloadException(
                "Versiya serveri ilə əlaqə qurula bilmədi: {$url}"
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DownloadException(
                "Serverdən gələn cavab düzgün JSON formatında deyil"
            );
        }

        // Məcburi sahələri yoxla
        if (!isset($data['latest_version'])) {
            throw new DownloadException(
                "Server cavabında 'latest_version' sahəsi tapılmadı"
            );
        }

        $this->remoteVersionData = $data;
        return $data;
    }

    /**
     * Yeniləmə manifestini serverdən alır
     * 
     * @return array Manifest məlumatları
     * @throws DownloadException Serverlə əlaqə qurula bilmədikdə
     */
    public function fetchManifest(): array
    {
        $url = $this->config->getManifestUrl();
        $response = $this->makeHttpRequest($url);

        if ($response === false) {
            throw new DownloadException(
                "Manifest serveri ilə əlaqə qurula bilmədi: {$url}"
            );
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DownloadException(
                "Manifest cavabı düzgün JSON formatında deyil"
            );
        }

        // Məcburi sahələri yoxla
        $requiredFields = ['latest_version', 'download_url', 'checksum', 'files'];
        
        if (!Security::validateServerResponse($data, $requiredFields)) {
            throw new DownloadException(
                "Manifest cavabında məcburi sahələr eksikdir: " . implode(', ', $requiredFields)
            );
        }

        return $data;
    }

    /**
     * Yeniləmə olub-olmadığını yoxlayır
     * 
     * @return bool Yeniləmə varsa true
     */
    public function hasUpdate(): bool
    {
        try {
            $remoteData = $this->fetchRemoteVersion();
            $remoteVersion = $remoteData['latest_version'];
            $localVersion = $this->config->getCurrentVersion();

            return $this->compareVersions($localVersion, $remoteVersion) < 0;
        } catch (\Exception $e) {
            // Xəta olduqda yeniləmə yoxdur deyirik
            return false;
        }
    }

    /**
     * Cari versiyanı qaytarır
     * 
     * @return string
     */
    public function getCurrentVersion(): string
    {
        return $this->config->getCurrentVersion();
    }

    /**
     * Uzaq (server) versiyanı qaytarır
     * 
     * @return string|null Səhv olarsa null
     */
    public function getRemoteVersion(): ?string
    {
        try {
            $data = $this->fetchRemoteVersion();
            return $data['latest_version'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * İki versiyanı müqayisə edir (semantik versiya)
     * 
     * @param string $version1 Birinci versiya
     * @param string $version2 İkinci versiya
     * @return int -1 əgər v1 < v2, 0 əgər v1 = v2, 1 əgər v1 > v2
     */
    public function compareVersions(string $version1, string $version2): int
    {
        // PHP-nin versiya müqayisə funksiyasını istifadə et
        return version_compare($version1, $version2);
    }

    /**
     * HTTP sorğusu göndərir
     * 
     * cURL varsa onu istifadə edir, yoxsa file_get_contents.
     * 
     * @param string $url URL
     * @return string|false Cavab və ya false
     */
    private function makeHttpRequest(string $url)
    {
        $timeout = $this->config->getTimeout();
        $verifySsl = $this->config->shouldVerifySSL();

        // cURL mövcuddursa istifadə et
        if (function_exists('curl_init')) {
            return $this->curlRequest($url, $timeout, $verifySsl);
        }

        // file_get_contents ilə istifadə et
        return $this->fileGetContentsRequest($url, $timeout, $verifySsl);
    }

    /**
     * cURL ilə HTTP sorğusu
     * 
     * @param string $url URL
     * @param int $timeout Timeout (saniyə)
     * @param bool $verifySsl SSL verifikasiyası
     * @return string|false
     */
    private function curlRequest(string $url, int $timeout, bool $verifySsl)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => $timeout,
            CURLOPT_SSL_VERIFYPEER => $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
            CURLOPT_USERAGENT      => 'PHP-Self-Updater/1.0',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return false;
        }

        return $response;
    }

    /**
     * file_get_contents ilə HTTP sorğusu
     * 
     * @param string $url URL
     * @param int $timeout Timeout (saniyə)
     * @param bool $verifySsl SSL verifikasiyası
     * @return string|false
     */
    private function fileGetContentsRequest(string $url, int $timeout, bool $verifySsl)
    {
        $context = stream_context_create([
            'http' => [
                'timeout'         => $timeout,
                'follow_location' => true,
                'max_redirects'   => 5,
                'user_agent'      => 'PHP-Self-Updater/1.0',
                'ignore_errors'   => true,
            ],
            'ssl' => [
                'verify_peer'      => $verifySsl,
                'verify_peer_name' => $verifySsl,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        // HTTP cavab kodunu yoxla
        if (isset($http_response_header[0])) {
            preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $http_response_header[0], $matches);
            $httpCode = isset($matches[1]) ? (int)$matches[1] : 0;
            
            if ($httpCode !== 200) {
                return false;
            }
        }

        return $response;
    }

    /**
     * Cache-i təmizləyir
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $this->remoteVersionData = null;
    }
}
