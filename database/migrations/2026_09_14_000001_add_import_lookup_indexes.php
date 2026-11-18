<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->index(['branch_id', 'nama_kelas'], 'kelas_branch_nama_kelas_index');
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->index(['branch_id', 'nama'], 'santris_branch_nama_index');
        });
    }

    public function down(): void
    {
        Schema::table('kelas', function (Blueprint $table) {
            $table->dropIndex('kelas_branch_nama_kelas_index');
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->dropIndex('santris_branch_nama_index');
        });
    }
};
