<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_registration_creates_pending_school_and_blocks_login(): void
    {
        $response = $this->post(route('register.store'), [
            'school_name' => 'Ponpes Contoh',
            'address' => 'Jl. Contoh No. 1',
            'phone' => '081234567890',
            'name' => 'Admin Contoh',
            'email' => 'admin.contoh@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));

        $school = School::where('name', 'Ponpes Contoh')->first();
        $this->assertNotNull($school);
        $this->assertEquals(School::STATUS_PENDING, $school->status);

        $user = User::where('email', 'admin.contoh@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::ROLE_SCHOOL_ADMIN, $user->role);
        $this->assertEquals($school->id, $user->branch_id);

        $this->post(route('login.attempt'), [
            'email' => 'admin.contoh@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_superadmin_can_approve_school_and_then_school_admin_can_login(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Ponpes Disetujui');
        $this->createSchoolAdmin($school, 'disetujui@test.sch.id');
        $school->update(['status' => School::STATUS_PENDING]);

        $this->post(route('schools.approve', $school))
            ->assertRedirect(route('schools.index'));

        $this->assertDatabaseHas('branches', [
            'id' => $school->id,
            'status' => School::STATUS_ACTIVE,
        ]);

        $this->post(route('logout'));

        $this->post(route('login.attempt'), [
            'email' => $school->users()->first()->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_school_admin_cannot_access_superadmin_pages(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $this->get(route('schools.index'))->assertForbidden();
        $this->get(route('schools.create'))->assertForbidden();
    }

    public function test_login_rate_limited_after_repeated_failures(): void
    {
        $school = $this->createSchool();
        $this->createSchoolAdmin($school, 'ratelimit@test.sch.id');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => 'ratelimit@test.sch.id',
                'password' => 'wrong-password',
            ]);
        }

        $this->post(route('login.attempt'), [
            'email' => 'ratelimit@test.sch.id',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }
}
