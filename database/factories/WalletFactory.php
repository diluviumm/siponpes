<?php

namespace Database\Factories;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Factories\Factory;

class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition()
    {
        return [
            'santri_id' => null,
            'balance' => 0,
            'daily_spending_limit' => 0,
        ];
    }
}
