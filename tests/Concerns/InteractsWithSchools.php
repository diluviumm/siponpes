<?php

namespace Tests\Concerns;

use App\Models\Kelas;
use App\Models\School;
use App\Models\User;

trait InteractsWithSchools
{
    protected function createSchool(string $name = 'Sekolah Test'): School
    {
        return School::create([
            'name' => $name,
            'address' => 'Jl. Test No. 1',
            'status' => School::STATUS_ACTIVE,
            'api_key' => School::generateApiKey(),
        ]);
    }

    protected function createSchoolAdmin(School $school, ?string $email = null): User
    {
        return User::factory()->create([
            'name' => 'Admin '.$school->name,
            'email' => $email ?? 'admin'.uniqid().'@test.sch.id',
            'role' => User::ROLE_SCHOOL_ADMIN,
            'branch_id' => $school->id,
        ]);
    }

    protected function createSuperadmin(): User
    {
        return User::factory()->create([
            'name' => 'Superadmin',
            'email' => 'super'.uniqid().'@siponpes.test',
            'role' => User::ROLE_SUPERADMIN,
        ]);
    }

    protected function actingAsSchoolAdmin(School $school): User
    {
        $user = $this->createSchoolAdmin($school);
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsSuperadmin(): User
    {
        $user = $this->createSuperadmin();
        $this->actingAs($user);

        return $user;
    }

    protected function newKelas(School $school, string $nama): Kelas
    {
        return Kelas::create([
            'nama_kelas' => $nama,
            'wali_kelas' => '-',
            'kapasitas' => 0,
            'branch_id' => $school->id,
        ]);
    }
}
