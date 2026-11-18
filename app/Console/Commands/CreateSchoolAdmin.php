<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\User;
use Illuminate\Console\Command;

class CreateSchoolAdmin extends Command
{
    protected $signature = 'app:create-school-admin {email : Email admin} {name : Nama admin} {--password= : Password (default: admin12345)} {--school= : Nama sekolah aktif (default: sekolah aktif pertama)} {--role=admin_sekolah : Peran akun: admin_sekolah atau kasir}';

    protected $description = 'Membuat akun admin sekolah / kasir untuk sekolah aktif.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $name = $this->argument('name');
        $password = $this->option('password') ?? 'admin12345';

        $role = $this->option('role');

        if (! in_array($role, [User::ROLE_SCHOOL_ADMIN, User::ROLE_CASHIER], true)) {
            $this->error('Peran tidak valid. Gunakan --role=admin_sekolah atau --role=kasir.');

            return 1;
        }

        $query = School::where('status', School::STATUS_ACTIVE);

        if ($schoolName = $this->option('school')) {
            $query->where('name', $schoolName);
        }

        $school = $query->orderBy('id')->first();

        if (! $school) {
            $this->error('Tidak ada sekolah aktif yang cocok.');

            return 1;
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => $role,
                'branch_id' => $school->id,
                'email_verified_at' => now(),
            ]
        );

        $this->info('Akun '.($role === User::ROLE_CASHIER ? 'kasir' : 'admin sekolah').' dibuat/diperbarui:');
        $this->info("  Email   : {$user->email}");
        $this->info("  Password: {$password}");
        $this->info("  Sekolah : {$school->name} (id={$school->id})");

        return 0;
    }
}
