<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SafeMigrate extends Command
{
    /**
     * Nama perintah:
     *
     * php artisan migrate:safe
     */
    protected $signature = 'migrate:safe
        {--force : Jalankan migrasi tanpa konfirmasi (untuk production/deploy)}
        {--step= : Jalankan migrasi dengan step tertentu}
        {--seed : Jalankan db:seed setelah migrate}
    ';

    protected $description = 'Backup database dulu, lalu jalankan migrate (aman untuk dipakai sehari-hari).';

    public function handle(): int
    {
        $this->info('=== SAFE MIGRATE ===');

        // 1) Konfirmasi kalau bukan pakai --force
        if (!$this->option('force') && app()->environment('production')) {
            if (!$this->confirm('Environment PRODUCTION. Yakin mau backup & migrate?', false)) {
                $this->warn('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        // 2) Jalankan backup:db
        $this->info('📦 Membuat backup database terlebih dahulu...');
        $backupExit = $this->call('backup:db');

        if ($backupExit !== self::SUCCESS) {
            $this->error('❌ Backup gagal. Migrasi dibatalkan.');
            return self::FAILURE;
        }

        $this->info('✅ Backup selesai. Lanjut migrate...');

        // 3) Siapkan opsi untuk migrate
        $migrateOptions = [
            '--force' => $this->option('force') || app()->environment('production'),
        ];

        if ($this->option('step')) {
            $migrateOptions['--step'] = (int) $this->option('step');
        }

        // 4) Jalankan migrate
        $exitCode = $this->call('migrate', $migrateOptions);

        if ($exitCode !== self::SUCCESS) {
            $this->error('❌ Migrasi gagal. Silakan cek error di atas.');
            return self::FAILURE;
        }

        $this->info('✅ Migrasi selesai.');

        // 5) Optional: jalankan seeder kalau diminta
        if ($this->option('seed')) {
            $this->info('🌱 Menjalankan db:seed...');
            $seedExit = $this->call('db:seed', [
                '--force' => $this->option('force') || app()->environment('production'),
            ]);

            if ($seedExit !== self::SUCCESS) {
                $this->error('❌ db:seed gagal. Silakan cek error di atas.');
                return self::FAILURE;
            }

            $this->info('✅ db:seed selesai.');
        }

        $this->info('🎉 SAFE MIGRATE beres semua.');

        return self::SUCCESS;
    }
}
