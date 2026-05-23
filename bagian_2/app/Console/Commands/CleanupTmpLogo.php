<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupTmpLogo extends Command
{
    /**
     * Hapus tmp logo file di storage/app/tmp yang umurnya melebihi threshold.
     *
     * Default: 60 menit (cukup lama untuk user perbaiki form, tidak lama agar disk tidak penuh).
     * Bisa di-schedule via routes/console.php (Laravel 11):
     *   Schedule::command('tmp:cleanup-logo')->hourly();
     *
     * Atau dijalankan manual:
     *   php artisan tmp:cleanup-logo
     *   php artisan tmp:cleanup-logo --minutes=30
     *   php artisan tmp:cleanup-logo --dry-run
     */
    protected $signature = 'tmp:cleanup-logo
                            {--minutes=60 : Threshold umur file dalam menit}
                            {--dry-run : Hanya tampilkan file yang akan dihapus, tidak benar-benar hapus}';

    protected $description = 'Hapus tmp logo file yang sudah tidak terpakai (preserve-logo feature).';

    public function handle(): int
    {
        $minutes = (int) $this->option('minutes');
        $dryRun = (bool) $this->option('dry-run');

        if ($minutes < 1) {
            $this->error('Option --minutes harus >= 1');
            return self::FAILURE;
        }

        $disk = Storage::disk('local');

        if (!$disk->exists('tmp')) {
            $this->info('Folder tmp/ tidak ada. Tidak ada yang perlu dibersihkan.');
            return self::SUCCESS;
        }

        $threshold = now()->subMinutes($minutes)->getTimestamp();
        $files = $disk->files('tmp');

        $deleted = 0;
        $skipped = 0;

        foreach ($files as $file) {
            // Hanya proses file dengan ekstensi .png (sesuai format tmp logo).
            if (!str_ends_with(strtolower($file), '.png')) {
                continue;
            }

            $lastModified = $disk->lastModified($file);

            if ($lastModified < $threshold) {
                if ($dryRun) {
                    $this->line("[DRY-RUN] Akan dihapus: {$file}");
                } else {
                    $disk->delete($file);
                    $this->line("Dihapus: {$file}");
                }
                $deleted++;
            } else {
                $skipped++;
            }
        }

        $action = $dryRun ? 'akan dihapus' : 'dihapus';
        $this->info("Selesai. {$deleted} file {$action}, {$skipped} file masih dalam threshold.");

        return self::SUCCESS;
    }
}
