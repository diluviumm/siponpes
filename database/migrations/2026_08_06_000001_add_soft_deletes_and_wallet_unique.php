<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->unique('santri_id', 'wallets_santri_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique('wallets_santri_id_unique');
        });

        Schema::table('santris', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
