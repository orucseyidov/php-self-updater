<?php
/**
 * PHP Self-Updater - Konfiqurasiya Nümunəsi
 * 
 * Bu fayl kütüphanənin konfiqurasiyasını təyin edir.
 * Bütün dəyərləri öz ehtiyaclarınıza görə dəyişdirin.
 * 
 * @package SelfUpdater
 */

return [
    /**
     * Cari versiya
     * 
     * Tətbiqinizin hazırki versiyası.
     * Semantik versiya formatı istifadə edin: major.minor.patch
     * Məsələn: 1.0.0, 2.1.3, 1.0.0-beta.1
     */
    'current_version' => '1.0.0',

    /**
     * Yeniləmə server URL-i
     * 
     * Yeniləmələrin yüklənəcəyi serverin əsas URL-i.
     * HTTPS istifadə etmək tövsiyə olunur.
     */
    'update_server_url' => 'https://your-update-server.com',

    /**
     * Versiya endpoint-i
     * 
     * Serverdən cari versiya məlumatlarını almaq üçün endpoint.
     * Bu endpoint JSON formatında cavab verməlidir.
     */
    'version_endpoint' => '/api/version.json',

    /**
     * Yeniləmə manifest endpoint-i
     * 
     * Tam yeniləmə məlumatlarını almaq üçün endpoint.
     * Fayl siyahısı, checksum, download URL və s.
     */
    'update_manifest_endpoint' => '/api/manifest.json',

    /**
     * Yenilənəcək yollar
     * 
     * Yalnız bu yollardakı fayllar yenilənəcək.
     * Boş buraxsanız, bütün fayllar yenilənəcək (exclude_paths istisna olmaqla).
     * 
     * Nümunə: ['src', 'lib', 'templates']
     */
    'update_paths' => [
        'src',
        'lib',
        'templates',
        'public/assets',
    ],

    /**
     * İstisna edilmiş yollar
     * 
     * Bu yollar HEÇ VAXT yenilənməyəcək, silinməyəcək və ya üzərinə yazılmayacaq.
     * Konfiqurasiya faylları, yüklənmiş fayllar və s. üçün istifadə edin.
     */
    'exclude_paths' => [
        '.env',
        '.env.local',
        'config/local.php',
        'storage',
        'uploads',
        'var/cache',
        'var/logs',
        'vendor',
    ],

    /**
     * Müvəqqəti qovluq
     * 
     * ZIP fayllarının endiriləcəyi müvəqqəti qovluq.
     * PHP prosesinin bu qovluğa yazma icazəsi olmalıdır.
     */
    'temp_directory' => sys_get_temp_dir() . '/php-self-updater',

    /**
     * Yedəkləmə
     * 
     * true olduqda, yeniləmədən əvvəl köhnə fayllar yedəklənir.
     * Problein olduqda köhnə vəziyyətə qayıtmağa imkan verir.
     */
    'backup_enabled' => true,

    /**
     * Yedək qovluğu
     * 
     * Yedəklərin saxlanacağı qovluq.
     */
    'backup_directory' => sys_get_temp_dir() . '/php-self-updater/backups',

    /**
     * Avtomatik yeniləmə
     * 
     * true olduqda, check() çağrılanda yeniləmə avtomatik icra edilir.
     * false olduqda, yeniləməni manual olaraq run() ilə icra etməlisiniz.
     * 
     * DİQQƏT: Prodakşn mühitində bu seçimi diqqətlə istifadə edin!
     */
    'autoupdate' => false,

    /**
     * HTTP timeout (saniyə)
     * 
     * Server sorğuları üçün maksimum gözləmə müddəti.
     */
    'timeout' => 30,

    /**
     * SSL verifikasiyası
     * 
     * true olduqda, SSL sertifikatları yoxlanılır.
     * Prodakşnda həmişə true olmalıdır!
     */
    'verify_ssl' => true,
];
