<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('period_type', ['bulanan', 'semester'])->default('bulanan')->after('status');
            $table->string('period_label', 50)->default('')->after('period_type');
            $table->tinyInteger('period_number')->unsigned()->default(1)->after('period_label');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['period_type', 'period_label', 'period_number']);
        });
    }
};
