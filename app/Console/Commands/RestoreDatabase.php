<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RestoreDatabase extends Command
{
    protected $signature = 'restore:db {filename}';
    protected $description = 'Restore SQLite database dari file backup di storage/backups/';

    public function handle(): int
    {
        $filename = $this->argument('filename');
        $backupPath = storage_path('backups/' . $filename);

        if (!file_exists($backupPath)) {
            $this->error("File backup tidak ditemukan: {$backupPath}");
            return self::FAILURE;
        }

        $dbPath = database_path('database.sqlite');

        // Backup dulu database yg sekarang sebelum overwrite
        if (file_exists($dbPath)) {
            $timestamp = now()->format('Y-m-d_His');
            $safetyCopy = storage_path("backups/auto_before_restore_{$timestamp}.sqlite");
            copy($dbPath, $safetyCopy);
            $this->info("✔ Database sekarang disimpan dulu sebagai: {$safetyCopy}");
        }

        // Overwrite database
        if (!copy($backupPath, $dbPath)) {
            $this->error("Gagal restore database.");
            return self::FAILURE;
        }

        $this->info("🎉 Restore selesai dari file:");
        $this->info($backupPath);

        // Clear Laravel cache
        $this->call('optimize:clear');

        $this->info("✔ Cache dibersihkan. Aplikasi siap dipakai.");

        return self::SUCCESS;
    }
}
