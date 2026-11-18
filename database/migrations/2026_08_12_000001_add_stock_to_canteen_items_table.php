<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canteen_items', function (Blueprint $table) {
            $table->decimal('stock', 12, 2)->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table('canteen_items', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }
};
