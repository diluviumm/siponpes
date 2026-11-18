<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_code')->unique();
            $table->string('account_name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->timestamps();
        });

        DB::table('accounts')->insert([
            ['account_code' => '1010', 'account_name' => 'Kas/Bank', 'type' => 'asset', 'created_at' => now(), 'updated_at' => now()],
            ['account_code' => '2010', 'account_name' => 'Titipan Santri / Wallet Liabilities', 'type' => 'liability', 'created_at' => now(), 'updated_at' => now()],
            ['account_code' => '4010', 'account_name' => 'Pendapatan Kantin', 'type' => 'revenue', 'created_at' => now(), 'updated_at' => now()],
            ['account_code' => '4020', 'account_name' => 'Pendapatan SPP', 'type' => 'revenue', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
