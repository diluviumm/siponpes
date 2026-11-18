<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('spp_tariffs', function (Blueprint $table) {
            $table->enum('periode_type', ['bulanan', 'semester'])->default('bulanan')->after('angkatan');
        });
    }

    public function down(): void
    {
        Schema::table('spp_tariffs', function (Blueprint $table) {
            $table->dropColumn('periode_type');
        });
    }
};
