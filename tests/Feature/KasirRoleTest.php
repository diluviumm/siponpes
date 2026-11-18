<?php

namespace Tests\Feature;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class KasirRoleTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    private function actingAsKasir($school): User
    {
        $user = User::factory()->create([
            'name' => 'Petugas Kasir',
            'email' => 'kasir'.uniqid().'@test.sch.id',
            'role' => User::ROLE_CASHIER,
            'branch_id' => $school->id,
        ]);
        $this->actingAs($user);

        return $user;
    }

    public function test_kasir_can_access_dashboard_and_kasir_pages(): void
    {
        $school = $this->createSchool('Sekolah Kasir');
        $this->actingAsKasir($school);

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('kantin.index'))->assertOk();
        $this->get(route('kantin.rekap'))->assertOk();
    }

    public function test_kasir_cannot_access_staff_only_pages(): void
    {
        $school = $this->createSchool('Sekolah Kasir B');
        $this->actingAsKasir($school);

        $this->get(route('kantin.manage'))->assertForbidden();
        $this->get(route('wallet.index'))->assertForbidden();
        $this->get(route('financial.journal'))->assertForbidden();
        $this->get(route('invoices.index'))->assertForbidden();
        $this->get(route('kas.index'))->assertForbidden();
        $this->get(route('accounts.index'))->assertForbidden();
    }

    public function test_kasir_cannot_access_other_school_data(): void
    {
        $schoolA = $this->createSchool('Sekolah Kasir A');
        $this->actingAsKasir($schoolA);

        $schoolB = $this->createSchool('Sekolah Kasir B');
        $kelasB = $this->newKelas($schoolB, '9B');

        $santriB = Santri::factory()->create([
            'nama' => 'Anak Lain',
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
        ]);

        $this->post(route('kantin.store'), [
            'santri_id' => $santriB->id,
            'canteen_name' => 'Kantin',
            'amount' => 5000,
        ])->assertSessionHasErrors('santri_id');
    }

    public function test_admin_sekolah_can_access_manage_pages(): void
    {
        $school = $this->createSchool('Sekolah Admin');
        $this->actingAsSchoolAdmin($school);

        $this->get(route('kantin.manage'))->assertOk();
    }
}
