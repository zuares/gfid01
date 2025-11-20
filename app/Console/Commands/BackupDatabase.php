<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    /**
     * Nama perintah artisan.
     *
     * Contoh: php artisan backup:db
     */
    protected $signature = 'backup:db';

    /**
     * Deskripsi singkat perintah.
     */
    protected $description = 'Backup SQLite database ke storage/backups/';

    /**
     * Jalankan perintah.
     */
    public function handle(): int
    {
        $db = database_path('database.sqlite');

        if (!file_exists($db)) {
            $this->error("Database sqlite tidak ditemukan di: {$db}");
            return self::FAILURE;
        }

        // Pastikan folder backup ada
        $backupDir = storage_path('backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Nama file backup
        $date = now()->format('Y-m-d_His');
        $backupFile = $backupDir . "/backup_{$date}.sqlite";

        // Copy file database
        if (@copy($db, $backupFile)) {
            $this->info("✅ Backup berhasil disimpan:");
            $this->line($backupFile);
            return self::SUCCESS;
        }

        $this->error("❌ Gagal melakukan backup.");
        return self::FAILURE;
    }
}
