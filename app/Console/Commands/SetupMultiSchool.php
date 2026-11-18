<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\School;
use App\Models\SppTariff;
use App\Models\User;
use Illuminate\Console\Command;

class SetupMultiSchool extends Command
{
    protected $signature = 'app:setup-multischool';

    protected $description = 'Membuat sekolah utama, akun superadmin, dan memindahkan data lama ke sekolah utama.';

    public function handle(): int
    {
        $school = School::where('status', School::STATUS_ACTIVE)->orderBy('id')->first();

        if (! $school) {
            $school = School::create([
                'name' => 'Sekolah Utama',
                'address' => null,
                'phone' => null,
                'status' => School::STATUS_ACTIVE,
                'api_key' => School::generateApiKey(),
            ]);
            $this->info("Sekolah utama dibuat: {$school->name} (id={$school->id})");
        } else {
            $this->info("Sekolah aktif ditemukan: {$school->name} (id={$school->id})");
        }

        $targets = [
            'Kelas' => [Kelas::class, ['branch_id' => null]],
            'Santri' => [Santri::class, ['branch_id' => null]],
            'Jurnal' => [Journal::class, ['branch_id' => null]],
            'Tarif SPP' => [SppTariff::class, ['branch_id' => null]],
        ];

        foreach ($targets as $label => [$model, $criteria]) {
            $count = $model::where('branch_id', null)->update(['branch_id' => $school->id]);
            if ($count > 0) {
                $this->info("{$label}: {$count} baris dipindah ke {$school->name}.");
            }
        }

        $email = env('SUPERADMIN_EMAIL', 'admin@siponpes.id');
        $password = env('SUPERADMIN_PASSWORD', 'admin12345');

        if (! User::where('email', $email)->exists()) {
            User::create([
                'name' => 'Superadmin',
                'email' => $email,
                'password' => $password,
                'role' => User::ROLE_SUPERADMIN,
                'email_verified_at' => now(),
            ]);
            $this->info("Akun superadmin dibuat: {$email} / {$password}");
        } else {
            $this->info("Akun superadmin sudah ada: {$email}");
        }

        $this->info('Setup multi-sekolah selesai.');

        return 0;
    }
}
