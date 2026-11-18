<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->integer('angkatan')->nullable()->after('kelas_id');
        });

        Schema::table('spp_tariffs', function (Blueprint $table) {
            $table->enum('type', ['semua', 'kelas', 'angkatan'])->default('kelas')->after('branch_id');
            $table->integer('angkatan')->nullable()->after('kelas_id');
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropColumn('angkatan');
        });

        Schema::table('spp_tariffs', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('angkatan');
        });
    }
};
