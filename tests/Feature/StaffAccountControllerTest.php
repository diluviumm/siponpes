<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class StaffAccountControllerTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_admin_sekolah_can_open_staff_page_for_own_school(): void
    {
        $school = $this->createSchool('Sekolah Kelola');
        $this->actingAsSchoolAdmin($school);

        $this->get(route('staff.index'))
            ->assertOk()
            ->assertSee('Buat Akun Kasir');
    }

    public function test_admin_sekolah_can_create_kasir_account(): void
    {
        $school = $this->createSchool('Sekolah Kelola B');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('staff.store'), [
            'admin_name' => 'Kasir Baru',
            'admin_email' => 'kasirbaru'.uniqid().'@test.sch.id',
            'admin_password' => 'rahasia123',
            'admin_password_confirmation' => 'rahasia123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'branch_id' => $school->id,
            'role' => User::ROLE_CASHIER,
        ]);
    }

    public function test_admin_sekolah_cannot_create_admin_account(): void
    {
        $school = $this->createSchool('Sekolah Kelola C');
        $this->actingAsSchoolAdmin($school);

        $email = 'admincurang'.uniqid().'@test.sch.id';

        $this->post(route('staff.store'), [
            'admin_name' => 'Admin Curang',
            'admin_email' => $email,
            'admin_password' => 'rahasia123',
            'admin_password_confirmation' => 'rahasia123',
            'role' => User::ROLE_SCHOOL_ADMIN,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => $email,
            'role' => User::ROLE_CASHIER,
        ]);
    }

    public function test_admin_sekolah_cannot_create_account_for_other_school(): void
    {
        $ownSchool = $this->createSchool('Sekolah Milik Saya');
        $this->actingAsSchoolAdmin($ownSchool);

        $otherSchool = $this->createSchool('Sekolah Lain');

        $this->post(route('staff.store'), [
            'branch_id' => $otherSchool->id,
            'admin_name' => 'Kasir Saya',
            'admin_email' => 'kasirsaya'.uniqid().'@test.sch.id',
            'admin_password' => 'rahasia123',
            'admin_password_confirmation' => 'rahasia123',
        ])->assertRedirect();

        $this->assertDatabaseMissing('users', [
            'branch_id' => $otherSchool->id,
            'name' => 'Kasir Saya',
        ]);
    }

    public function test_superadmin_requires_school_selection(): void
    {
        $this->actingAsSuperadmin();

        $this->post(route('staff.store'), [
            'admin_name' => 'Kasir Tanpa Sekolah',
            'admin_email' => 'kasirtanpa'.uniqid().'@test.sch.id',
            'admin_password' => 'rahasia123',
            'admin_password_confirmation' => 'rahasia123',
        ])->assertSessionHasErrors('branch_id');
    }

    public function test_superadmin_can_create_kasir_or_admin_for_a_school(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah Super');

        $this->post(route('staff.store'), [
            'branch_id' => $school->id,
            'admin_name' => 'Kasir Super',
            'admin_email' => 'kasirsuper'.uniqid().'@test.sch.id',
            'admin_password' => 'rahasia123',
            'admin_password_confirmation' => 'rahasia123',
            'role' => User::ROLE_CASHIER,
        ])->assertRedirect();

        $this->post(route('staff.store'), [
            'branch_id' => $school->id,
            'admin_name' => 'Admin Super',
            'admin_email' => 'adminsuper'.uniqid().'@test.sch.id',
            'admin_password' => 'rahasia123',
            'admin_password_confirmation' => 'rahasia123',
            'role' => User::ROLE_SCHOOL_ADMIN,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'branch_id' => $school->id,
            'role' => User::ROLE_CASHIER,
        ]);
        $this->assertDatabaseHas('users', [
            'branch_id' => $school->id,
            'role' => User::ROLE_SCHOOL_ADMIN,
        ]);
    }

    public function test_kasir_cannot_access_staff_page(): void
    {
        $school = $this->createSchool('Sekolah Kasir Kelola');
        $kasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $school->id,
        ]);
        $this->actingAs($kasir);

        $this->get(route('staff.index'))->assertForbidden();
    }

    public function test_admin_sekolah_can_reset_password_of_own_school_account(): void
    {
        $school = $this->createSchool('Sekolah Reset');
        $this->actingAsSchoolAdmin($school);

        $kasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $school->id,
        ]);

        $this->post(route('staff.reset-password', $kasir), [
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertRedirect();

        $this->assertTrue(
            Hash::check('passwordbaru1', $kasir->fresh()->password)
        );
    }

    public function test_admin_sekolah_cannot_reset_password_of_other_school_account(): void
    {
        $ownSchool = $this->createSchool('Sekolah Reset Saya');
        $this->actingAsSchoolAdmin($ownSchool);

        $otherSchool = $this->createSchool('Sekolah Reset Lain');
        $otherKasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $otherSchool->id,
        ]);

        $this->post(route('staff.reset-password', $otherKasir), [
            'password' => 'passwordbaru1',
            'password_confirmation' => 'passwordbaru1',
        ])->assertForbidden();
    }

    public function test_admin_sekolah_can_delete_kasir_of_own_school(): void
    {
        $school = $this->createSchool('Sekolah Hapus');
        $this->actingAsSchoolAdmin($school);

        $kasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $school->id,
        ]);

        $this->delete(route('staff.destroy', $kasir))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $kasir->id]);
    }

    public function test_admin_sekolah_cannot_delete_admin_account(): void
    {
        $school = $this->createSchool('Sekolah Jaga Admin');
        $this->actingAsSchoolAdmin($school);

        $otherAdmin = $this->createSchoolAdmin($school);

        $this->delete(route('staff.destroy', $otherAdmin))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_admin_sekolah_cannot_delete_kasir_of_other_school(): void
    {
        $ownSchool = $this->createSchool('Sekolah Hapus Saya');
        $this->actingAsSchoolAdmin($ownSchool);

        $otherSchool = $this->createSchool('Sekolah Hapus Lain');
        $otherKasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $otherSchool->id,
        ]);

        $this->delete(route('staff.destroy', $otherKasir))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $otherKasir->id]);
    }

    public function test_superadmin_can_delete_kasir(): void
    {
        $school = $this->createSchool('Sekolah Super Hapus');
        $this->actingAsSuperadmin();

        $kasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $school->id,
        ]);

        $this->delete(route('staff.destroy', $kasir))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $kasir->id]);
    }

    public function test_admin_sekolah_cannot_delete_self(): void
    {
        $school = $this->createSchool('Sekolah Diri Sendiri');
        $admin = $this->actingAsSchoolAdmin($school);

        $this->delete(route('staff.destroy', $admin))->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
