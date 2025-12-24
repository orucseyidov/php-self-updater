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

    // Channel: 'development', 'staging', və ya 'production'
    'channel' => 'production',

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

Server JSON faylları iki formatda ola bilər:

### Channel-li Format (tövsiyə olunur)

Eyni faylda birdən çox channel:

**version.json**
```json
{
    "development": {
        "latest_version": "2.1.0",
        "released_at": "2024-01-20T10:30:00Z"
    },
    "production": {
        "latest_version": "2.0.0",
        "released_at": "2024-01-15T10:30:00Z"
    }
}
```

**manifest.json**
```json
{
    "development": {
        "latest_version": "2.1.0",
        "download_url": "https://your-server.com/releases/v2.1.0/update.zip",
        "checksum": "sha256_checksum_here",
        "files": ["src/file.php"],
        "changelog": "## v2.1.0\n- Yeni özəlliklər"
    },
    "production": {
        "latest_version": "2.0.0",
        "download_url": "https://your-server.com/releases/v2.0.0/update.zip",
        "checksum": "sha256_checksum_here",
        "files": ["src/file.php"],
        "changelog": "## v2.0.0\n- Stabil versiya"
    }
}
```

### Sadə Format (geriyə uyğun)

Köhnə format da dəstəklənir (production kimi işləyir):

**version.json**
```json
{
    "latest_version": "2.0.0",
    "released_at": "2024-01-15T10:30:00Z"
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

## 🎨 Update Widget (UI Komponenti)

SaaS layihələri üçün hazır yeniləmə düyməsi və popup.

### Sadə İstifadə

```php
<?php
use SelfUpdater\UpdateWidget;

// Yeniləmə düyməsi (həmişə göstər)
echo UpdateWidget::render([
    'api_endpoint' => '/api/self-updater.php',
]);
```

### Yalnız Yeniləmə Varsa Göstər

```php
<?php
// Server yoxlanır, yeniləmə varsa düymə göstərilir
echo UpdateWidget::renderIfAvailable('/config/updater.php', [
    'api_endpoint' => '/api/self-updater.php',
]);
```

### Xüsusi Düymə İstifadə Edin

```html
<!-- Öz düyməniz -->
<button class="my-btn" data-updater-trigger>
    Sistemi Yenilə
</button>

<?php
// Yalnız modal və JS render et
echo UpdateWidget::renderModal();
echo UpdateWidget::renderJS(['api_endpoint' => '/api/self-updater.php']);
?>
```

### Widget Seçimləri

| Seçim | Varsayılan | Təsvir |
|-------|------------|--------|
| `button_text` | "Yeni versiya mövcuddur!" | Düymə mətni |
| `api_endpoint` | "/api/self-updater.php" | AJAX endpoint |
| `theme` | "default" | Tema: default/dark |
| `confirm_message` | "...əminsiniz?" | Təsdiq mesajı |
| `include_css` | true | CSS daxil et |
| `include_js` | true | JS daxil et |

### API Endpoint

`api/self-updater.php` faylını public qovluğa kopyalayın:

```php
// GET: /api/self-updater.php?action=check - Yeniləmə yoxla
// POST: /api/self-updater.php?action=update - Yenilə
```

## Təhlükəsizlik

- **Checksum**: SHA256 ilə fayl bütünlüyü yoxlanılır
- **Path traversal qoruması**: Zərərli yollar bloklanır
- **HTTPS**: SSL verifikasiyası dəstəklənir
- **Manifestə güvən**: Yalnız manifestdəki fayllar çıxarılır

## Lisenziya

MIT License © 2024
