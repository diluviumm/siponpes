<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            ['account_code' => '4030', 'account_name' => 'Pendapatan Lain-lain', 'type' => 'revenue'],
            ['account_code' => '5010', 'account_name' => 'Beban Listrik & Air', 'type' => 'expense'],
            ['account_code' => '5020', 'account_name' => 'Beban Operasional & Pemeliharaan', 'type' => 'expense'],
            ['account_code' => '5030', 'account_name' => 'Beban Seragam & Perlengkapan', 'type' => 'expense'],
            ['account_code' => '5040', 'account_name' => 'Beban Gaji & Kesejahteraan', 'type' => 'expense'],
            ['account_code' => '5050', 'account_name' => 'Beban Lain-lain', 'type' => 'expense'],
        ];

        DB::table('accounts')->insertOrIgnore(
            collect($accounts)->map(fn ($a) => $a + ['created_at' => now(), 'updated_at' => now()])->all()
        );
    }

    public function down(): void
    {
        DB::table('accounts')->whereIn('account_code', [
            '4030', '5010', '5020', '5030', '5040', '5050',
        ])->delete();
    }
};
