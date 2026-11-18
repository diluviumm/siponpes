<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropUnique('santris_nis_unique');
            $table->unique(['branch_id', 'nis'], 'santris_nis_branch_unique');
            $table->dropUnique('santris_santri_card_token_unique');
            $table->unique(['branch_id', 'santri_card_token'], 'santris_card_branch_unique');
        });
    }

    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropUnique('santris_card_branch_unique');
            $table->unique('santri_card_token', 'santris_santri_card_token_unique');
            $table->dropUnique('santris_nis_branch_unique');
            $table->unique('nis', 'santris_nis_unique');
        });
    }
};
