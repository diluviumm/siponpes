<?php

namespace Tests\Feature;

use App\Models\Santri;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_creating_santri_logs_audit_trail(): void
    {
        $school = $this->createSchool('Sekolah Audit');
        $kelas = $this->newKelas($school, '7M');
        $admin = $this->actingAsSchoolAdmin($school);

        $santri = Santri::factory()->create([
            'nama' => 'Anak Audit',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => Santri::class,
            'auditable_id' => $santri->id,
            'summary' => 'Anak Audit',
        ]);
    }

    public function test_deleting_santri_logs_audit_trail(): void
    {
        $school = $this->createSchool('Sekolah Audit B');
        $kelas = $this->newKelas($school, '7N');
        $admin = $this->actingAsSchoolAdmin($school);

        $santri = Santri::factory()->create([
            'nama' => 'Anak Dihapus',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $santri->delete();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'deleted',
            'auditable_type' => Santri::class,
            'auditable_id' => $santri->id,
        ]);
    }

    public function test_audit_page_is_accessible_by_superadmin(): void
    {
        $this->actingAsSuperadmin();

        $this->get(route('audit.index'))
            ->assertOk()
            ->assertSee('Audit Trail');
    }

    public function test_audit_page_can_filter_by_action(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah Filter Audit');
        $kelas = $this->newKelas($school, '7O');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Filter',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $santri->delete();

        $this->get(route('audit.index', ['action' => 'created']))
            ->assertOk()
            ->assertSee('Anak Filter')
            ->assertSee('Dibuat');

        $this->get(route('audit.index', ['action' => 'deleted']))
            ->assertOk()
            ->assertSee('Anak Filter')
            ->assertSee('Dihapus');
    }

    public function test_audit_page_can_filter_by_model_and_search(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah Filter Model');
        $kelas = $this->newKelas($school, '7P');

        Santri::factory()->create([
            'nama' => 'Anak Model',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->get(route('audit.index', ['model' => 'Santri']))
            ->assertOk()
            ->assertSee('Anak Model');

        $this->get(route('audit.index', ['search' => 'Anak Model']))
            ->assertOk()
            ->assertSee('Anak Model');

        $this->get(route('audit.index', ['search' => 'Tidak Ada']))
            ->assertOk()
            ->assertDontSee('Anak Model');
    }
}
