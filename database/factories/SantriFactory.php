<?php

namespace Database\Factories;

use App\Models\Santri;
use Illuminate\Database\Eloquent\Factories\Factory;

class SantriFactory extends Factory
{
    protected $model = Santri::class;

    public function definition()
    {
        return [
            'nis' => $this->faker->unique()->numerify('S-#####'),
            'nama' => $this->faker->name(),
            'jenis_kelamin' => $this->faker->randomElement(['L', 'P']),
            'tempat_lahir' => $this->faker->city(),
            'tanggal_lahir' => $this->faker->date(),
            'alamat' => $this->faker->address(),
            'no_hp_wali' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'santri_card_token' => null,
            'kelas_id' => 1,
            'branch_id' => null,
        ];
    }
}
