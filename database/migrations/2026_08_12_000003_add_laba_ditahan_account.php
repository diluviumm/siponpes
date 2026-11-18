<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('accounts')->updateOrInsert(
            ['account_code' => '3010'],
            [
                'account_name' => 'Laba Ditahan / Surplus Ditahan',
                'type' => 'equity',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('accounts')->where('account_code', '3010')->delete();
    }
};
