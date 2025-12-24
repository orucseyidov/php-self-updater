<?php
/**
 * Laravel Artisan Command - Yeniləməni icra et
 * 
 * @package SelfUpdater
 */

namespace SelfUpdater\Integrations\Laravel\Commands;

use Illuminate\Console\Command;

class RunUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'self-updater:run 
                            {--force : Təsdiq soruşmadan yenilə}';

    /**
     * The console command description.
     */
    protected $description = 'Yeniləməni icra edir';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updater = app('self-updater');

        // Əvvəlcə yeniləmə yoxla
        $this->info('Yeniləmələr yoxlanılır...');

        try {
            $hasUpdate = $updater->check();

            if (!$hasUpdate) {
                $this->info('✓ Ən son versiyanı istifadə edirsiniz.');
                return self::SUCCESS;
            }

            $this->newLine();
            $this->info('Yeni versiya: ' . $updater->getRemoteVersion());

            // Təsdiq
            if (!$this->option('force')) {
                if (!$this->confirm('Yeniləməni icra etmək istəyirsiniz?')) {
                    $this->line('Yeniləmə ləğv edildi.');
                    return self::SUCCESS;
                }
            }

            // Maintenance mode
            $this->line('Maintenance mode aktivləşdirilir...');
            $this->call('down');

            try {
                $this->line('Yeniləmə icra olunur...');
                $updater->run();

                $this->newLine();
                $this->info('🎉 Yeniləmə uğurla tamamlandı!');

                // Cache təmizlə
                $this->line('Cache təmizlənir...');
                $this->call('cache:clear');
                $this->call('config:clear');
                $this->call('view:clear');

            } finally {
                // Maintenance mode-u bağla
                $this->line('Maintenance mode deaktivləşdirilir...');
                $this->call('up');
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Xəta: ' . $e->getMessage());

            // Maintenance mode-u bağla
            $this->call('up');

            return self::FAILURE;
        }
    }
}
