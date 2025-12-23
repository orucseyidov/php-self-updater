# PHP Self-Updater

Framework-agnostik, öz-özünü yeniləyən PHP kütüphanəsi.

PHP tətbiqlərinə xüsusi yeniləmə serverindən avtomatik yeniləmə imkanı verir.

## Xüsusiyyətlər

- ✅ **Statik API**: `Updater::check()`, `Updater::hasUpdate()`, `Updater::run()`
- ✅ **Semantik versiya** dəstəyi
- ✅ **SHA256 checksum** validasiyası
- ✅ **Path traversal qoruması**
- ✅ **Avtomatik yedəkləmə**
- ✅ **Seçici yeniləmə**: Yalnız icazə verilmiş yollar
- ✅ **İstisna yolları**: `.env`, `uploads` və s. heç vaxt dəyişmir
- ✅ **Avtomatik yeniləmə** seçimi
- ✅ **Composer asılılığı yoxdur**
- ✅ **PHP 7.4+** uyğun

## Quraşdırma

### Composer ilə

```bash
composer require orucseyidov/php-self-updater
```

### Manual

Faylları proyektinizə kopyalayın və autoload edin.

## Sürətli Başlanğıc

```php
<?php
use SelfUpdater\Updater;

// Yeniləmələri yoxla
Updater::check('/path/to/config/updater.php');

// Yeniləmə varsa icra et
if (Updater::hasUpdate()) {
    Updater::run('/path/to/project');
}
```

## Konfiqurasiya

`config/updater.php` faylı yaradın:

```php
<?php
return [
    // Cari versiya (semantik versiya formatı)
    'current_version' => '1.0.0',

    // Yeniləmə server URL-i
    'update_server_url' => 'https://your-update-server.com',

    // Versiya endpoint-i
    'version_endpoint' => '/api/version.json',

    // Manifest endpoint-i
    'update_manifest_endpoint' => '/api/manifest.json',

    // Yenilənəcək yollar (boş = hamısı)
    'update_paths' => ['src', 'lib', 'templates'],

    // HEÇ VAXT yenilənməyəcək yollar
    'exclude_paths' => ['.env', 'storage', 'uploads', 'vendor'],

    // Müvəqqəti qovluq
    'temp_directory' => sys_get_temp_dir() . '/php-self-updater',

    // Yedəkləmə
    'backup_enabled' => true,
    'backup_directory' => sys_get_temp_dir() . '/php-self-updater/backups',

    // Avtomatik yeniləmə (check() çağrılanda avtomatik yenilə)
    'autoupdate' => false,

    // HTTP timeout (saniyə)
    'timeout' => 30,

    // SSL verifikasiyası
    'verify_ssl' => true,
];
```

## Server Tərəfi

### version.json

```json
{
    "latest_version": "2.1.0",
    "released_at": "2024-01-15T10:30:00Z"
}
```

### manifest.json

```json
{
    "latest_version": "2.1.0",
    "download_url": "https://your-server.com/releases/v2.1.0/update.zip",
    "checksum": "sha256_checksum_here",
    "files": [
        "src/Core/Application.php",
        "src/Controllers/HomeController.php",
        "lib/helpers.php"
    ],
    "changelog": "## v2.1.0\n- Yeni özəlliklər\n- Xəta düzəlişləri"
}
```

## API

### Updater::check($config, $basePath = null)

Yeniləmələri yoxlayır. `autoupdate` açıqdırsa avtomatik icra edir.

```php
// Fayl ilə
Updater::check('/path/to/config.php');

// Array ilə
Updater::check([
    'current_version' => '1.0.0',
    'update_server_url' => 'https://...',
    'autoupdate' => true,
], '/path/to/project');
```

### Updater::hasUpdate()

Yeniləmə olub-olmadığını qaytarır.

```php
if (Updater::hasUpdate()) {
    // Yeniləmə var
}
```

### Updater::run($basePath)

Yeniləməni icra edir.

```php
Updater::run('/path/to/project');
```

### Digər metodlar

```php
Updater::getCurrentVersion();  // Cari versiya
Updater::getRemoteVersion();   // Server versiyası
Updater::getChangelog();       // Dəyişiklik qeydləri
Updater::getLastError();       // Son xəta mesajı
```

## Təhlükəsizlik

- **Checksum**: SHA256 ilə fayl bütünlüyü yoxlanılır
- **Path traversal qoruması**: Zərərli yollar bloklanır
- **HTTPS**: SSL verifikasiyası dəstəklənir
- **Manifestə güvən**: Yalnız manifestdəki fayllar çıxarılır

## Lisenziya

MIT License © 2024
