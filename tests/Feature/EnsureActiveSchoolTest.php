<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class EnsureActiveSchoolTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_school_admin_with_active_school_can_access_dashboard(): void
    {
        $school = $this->createSchool('Sekolah Aktif');
        $this->actingAsSchoolAdmin($school);

        $this->get(route('dashboard'))->assertOk();
    }

    public function test_school_admin_is_logged_out_when_school_set_to_pending(): void
    {
        $school = $this->createSchool('Sekolah Pending');
        $this->actingAsSchoolAdmin($school);

        $school->update(['status' => School::STATUS_PENDING]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_school_admin_is_logged_out_when_school_rejected(): void
    {
        $school = $this->createSchool('Sekolah Ditolak');
        $this->actingAsSchoolAdmin($school);

        $school->update(['status' => School::STATUS_REJECTED]);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_cashier_is_logged_out_when_school_not_active(): void
    {
        $school = $this->createSchool('Sekolah Kasir Nonaktif');
        $kasir = User::factory()->create([
            'role' => User::ROLE_CASHIER,
            'branch_id' => $school->id,
        ]);
        $this->actingAs($kasir);

        $school->update(['status' => School::STATUS_PENDING]);

        $this->get(route('kantin.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_superadmin_is_not_affected_by_school_status(): void
    {
        $school = $this->createSchool('Sekolah Nonaktif Super');
        $this->actingAsSuperadmin();

        $school->update(['status' => School::STATUS_REJECTED]);

        $this->get(route('dashboard'))->assertOk();
    }
}
