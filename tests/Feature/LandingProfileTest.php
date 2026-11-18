<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class LandingProfileTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_guest_sees_landing_page(): void
    {
        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('SIPONPES')
            ->assertSee('Manajemen Pondok Pesantren');
    }

    public function test_root_url_serves_landing_page_for_guest(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('SIPONPES');
    }

    public function test_authenticated_user_is_redirected_from_landing_to_dashboard(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $this->get(route('landing'))->assertRedirect(route('dashboard'));
    }

    public function test_profile_page_shows_own_data(): void
    {
        $school = $this->createSchool();
        $user = $this->actingAsSchoolAdmin($school);

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($user->name)
            ->assertSee($user->email);
    }

    public function test_profile_update_changes_name(): void
    {
        $school = $this->createSchool();
        $user = $this->actingAsSchoolAdmin($school);

        $this->put(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => $user->email,
        ]);
    }

    public function test_profile_update_with_new_email_requires_verification(): void
    {
        $school = $this->createSchool();
        $user = $this->actingAsSchoolAdmin($school);

        $this->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'baru@test.sch.id',
        ])->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'baru@test.sch.id',
            'email_verified_at' => null,
        ]);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        $school = $this->createSchool();
        $this->createSchoolAdmin($school, 'duplicate@test.sch.id');
        $user = $this->actingAsSchoolAdmin($school);

        $this->put(route('profile.update'), [
            'name' => $user->name,
            'email' => 'duplicate@test.sch.id',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_records_audit_and_last_login(): void
    {
        $school = $this->createSchool();
        $user = $this->createSchoolAdmin($school, 'auditlogin@test.sch.id');

        $this->post(route('login.attempt'), [
            'email' => 'auditlogin@test.sch.id',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->last_login_at);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'branch_id' => $school->id,
            'action' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_audit_page_filters_by_login_action(): void
    {
        $school = $this->createSchool();
        $this->createSchoolAdmin($school, 'auditlogin2@test.sch.id');
        $super = $this->actingAsSuperadmin();

        AuditLog::create([
            'user_id' => $super->id,
            'branch_id' => null,
            'action' => 'login',
            'auditable_type' => User::class,
            'auditable_id' => $super->id,
            'summary' => $super->email,
        ]);

        $this->get(route('audit.index', ['action' => 'login']))
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_school_index_accepts_search_and_sort(): void
    {
        $this->createSchool('Alpha Academy');
        $this->createSchool('Beta Boarding');
        $this->actingAsSuperadmin();

        $this->get(route('schools.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Academy')
            ->assertDontSee('Beta Boarding');

        $this->get(route('schools.index', ['sort' => 'name', 'direction' => 'asc']))
            ->assertOk();
    }

    public function test_kelas_index_accepts_search(): void
    {
        $school = $this->createSchool();
        $this->newKelas($school, 'Kelas 7A');
        $this->newKelas($school, 'Kelas 8B');
        $this->actingAsSchoolAdmin($school);

        $this->get(route('kelas.index', ['search' => '7A']))
            ->assertOk()
            ->assertSee('Kelas 7A')
            ->assertDontSee('Kelas 8B');
    }

    public function test_error_pages_are_branded(): void
    {
        $this->get('/unknown-page-xyz')->assertNotFound();
        $this->get('/unknown-page-xyz')->assertSee('Halaman yang Anda cari tidak ditemukan');
    }
}
