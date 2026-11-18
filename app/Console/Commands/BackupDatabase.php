<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database
                            {--keep=15 : Jumlah backup terakhir yang dipertahankan}';

    protected $description = 'Membuat backup database MySQL ke database/backup.';

    public function handle(): int
    {
        if (! in_array(config('database.default'), ['mysql', 'mariadb'], true)) {
            $this->warn('Backup hanya mendukung koneksi MySQL/MariaDB. Lewati.');

            return 0;
        }

        $binary = $this->resolveDumpBinary();

        if (! $binary) {
            $this->error('mysqldump tidak ditemukan. Atur path lewat env DB_DUMP_BINARY.');

            return 1;
        }

        $config = config('database.connections.mysql');
        $directory = database_path('backup');
        File::ensureDirectoryExists($directory);

        $filename = sprintf(
            '%s_backup_%s.sql',
            $config['database'],
            now()->format('Ymd_His')
        );
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        $process = new Process([
            $binary,
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--password='.$config['password'],
            '--single-transaction',
            '--routines',
            $config['database'],
        ]);
        $process->setTimeout(300);

        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Backup gagal: '.trim($process->getErrorOutput()));

            return 1;
        }

        File::put($path, $process->getOutput());

        $this->pruneOldBackups($directory, (int) $this->option('keep'));

        $this->info("Backup berhasil disimpan: {$path}");

        return 0;
    }

    protected function resolveDumpBinary(): ?string
    {
        $binary = env('DB_DUMP_BINARY');

        if ($binary && File::exists($binary)) {
            return $binary;
        }

        $candidates = PHP_OS_FAMILY === 'Windows'
            ? ['D:\\xampp\\mysql\\bin\\mysqldump.exe']
            : [];

        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        return 'mysqldump';
    }

    protected function pruneOldBackups(string $directory, int $keep): void
    {
        $files = collect(File::files($directory))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.sql'))
            ->sortByDesc(fn ($file) => $file->getFilename())
            ->values();

        $files->slice($keep)->each(fn ($file) => File::delete($file->getPathname()));
    }
}
