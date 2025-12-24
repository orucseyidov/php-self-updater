<?php
/**
 * Laravel Artisan Command - Yeniləmə yoxla
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater\Integrations\Laravel\Commands;

use Illuminate\Console\Command;
use SelfUpdater\Integrations\Laravel\SelfUpdater;

class CheckUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'self-updater:check';

    /**
     * The console command description.
     */
    protected $description = 'Yeniləmələri yoxlayır';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Yeniləmələr yoxlanılır...');

        try {
            $updater = app('self-updater');
            $hasUpdate = $updater->check();

            $this->line('');
            $this->line('Cari versiya: ' . $updater->getCurrentVersion());
            $this->line('Server versiyası: ' . ($updater->getRemoteVersion() ?? 'Alınamadı'));

            if ($hasUpdate) {
                $this->newLine();
                $this->info('✅ Yeni versiya mövcuddur!');

                $changelog = $updater->getChangelog();
                if ($changelog) {
                    $this->newLine();
                    $this->line('Dəyişiklik qeydləri:');
                    $this->line($changelog);
                }

                $this->newLine();
                $this->line('Yeniləmək üçün: php artisan self-updater:run');

                return self::SUCCESS;
            }

            $this->newLine();
            $this->info('✓ Ən son versiyanı istifadə edirsiniz.');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Xəta: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
