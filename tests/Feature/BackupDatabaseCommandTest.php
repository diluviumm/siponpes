<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_database_skips_when_connection_is_not_mysql(): void
    {
        config(['database.default' => 'sqlite']);

        $this->artisan('backup:database')
            ->expectsOutputToContain('Backup hanya mendukung koneksi MySQL/MariaDB')
            ->assertExitCode(0);
    }
}
