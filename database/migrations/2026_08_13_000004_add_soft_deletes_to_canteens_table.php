<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->index('branch_id');
            $table->dropUnique('canteens_branch_id_name_unique');
            $table->softDeletes()->after('is_active');
        });

        Schema::table('canteen_items', function (Blueprint $table) {
            $table->index('canteen_id');
            $table->dropUnique('canteen_items_canteen_id_name_unique');
            $table->softDeletes()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('canteens', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->unique(['branch_id', 'name']);
            $table->dropIndex(['branch_id']);
        });

        Schema::table('canteen_items', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->unique(['canteen_id', 'name']);
            $table->dropIndex(['canteen_id']);
        });
    }
};
