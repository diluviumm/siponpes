<?php

namespace Tests\Feature;

use App\Models\Journal;
use App\Models\Santri;
use App\Models\School;
use App\Models\Wallet;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class SchoolLifecycleTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_nonaktifkan_membuat_admin_tidak_bisa_login(): void
    {
        $school = $this->createSchool('Sekolah Dinonaktifkan');
        $this->createSchoolAdmin($school, 'nonaktif@test.sch.id');

        $this->actingAsSuperadmin();
        $this->post(route('schools.reject', $school))
            ->assertRedirect(route('schools.index'));

        $this->post(route('logout'));

        $this->post(route('login.attempt'), [
            'email' => 'nonaktif@test.sch.id',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_permanent_delete_membutuhkan_kata_konfirmasi(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah X');
        $kelas = $this->newKelas($school, '7A');
        Santri::factory()->create([
            'nama' => 'Anak Contoh',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $this->delete(route('schools.destroy', $school), ['confirm' => 'salah'])
            ->assertRedirect(route('schools.edit', $school))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $school->id]);

        $this->delete(route('schools.destroy', $school), ['confirm' => 'HAPUS'])
            ->assertRedirect(route('schools.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('branches', ['id' => $school->id]);
        $this->assertDatabaseMissing('kelas', ['branch_id' => $school->id]);
        $this->assertDatabaseMissing('santris', ['branch_id' => $school->id]);
        $this->assertDatabaseMissing('users', ['branch_id' => $school->id]);
    }

    public function test_user_can_change_own_password(): void
    {
        $user = $this->actingAsSuperadmin();

        $this->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'baru12345',
            'password_confirmation' => 'baru12345',
        ])->assertRedirect(route('password.change'))
            ->assertSessionHas('success');

        $this->post(route('logout'));

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'baru12345',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_school_admin_data_terisolasi_setelah_login_form_sungguhan(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $schoolB = $this->createSchool('Sekolah B');
        $kelasA = $this->newKelas($schoolA, '7A');
        $kelasB = $this->newKelas($schoolB, '7B');

        $santriA = Santri::factory()->create([
            'nama' => 'Anak Sekolah A',
            'kelas_id' => $kelasA->id,
            'branch_id' => $schoolA->id,
        ]);
        Wallet::create(['santri_id' => $santriA->id, 'balance' => 50000, 'daily_spending_limit' => 0]);

        $santriB = Santri::factory()->create([
            'nama' => 'Anak Sekolah B',
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
        ]);
        Wallet::create(['santri_id' => $santriB->id, 'balance' => 90000, 'daily_spending_limit' => 0]);

        app(JournalService::class)->createJournalEntry(
            'Jurnal Sekolah B',
            'JURNAL-B-001',
            [
                ['account_code' => '1010', 'debit' => 90000, 'credit' => 0],
                ['account_code' => '2010', 'debit' => 0, 'credit' => 90000],
            ],
            now()->toDateString(),
            $schoolB->id
        );

        $this->createSchoolAdmin($schoolA, 'admin.a@test.sch.id');

        $this->post(route('login.attempt'), [
            'email' => 'admin.a@test.sch.id',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rp 50.000,00')
            ->assertDontSee('Rp 90.000,00');

        $this->get(route('financial.journal'))
            ->assertOk()
            ->assertDontSee('JURNAL-B-001');
    }

    public function test_top_up_membuat_jurnal_dengan_branch_id_sekolah(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah Top Up');
        $kelas = $this->newKelas($school, '7A');
        $santri = Santri::factory()->create([
            'nama' => 'Anak Top Up',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);
        Wallet::create(['santri_id' => $santri->id, 'balance' => 0, 'daily_spending_limit' => 0]);

        $this->post(route('wallet.processTransaction', $santri), [
            'type' => 'credit',
            'amount' => 25000,
            'description' => 'Setoran tabungan',
        ])->assertRedirect();

        $this->assertDatabaseHas('journals', [
            'branch_id' => $school->id,
            'reference' => 'WALLET-'.$santri->wallet->id,
        ]);
    }

    public function test_permanent_delete_juga_menghapus_jurnal_sekolah(): void
    {
        $this->actingAsSuperadmin();

        $school = $this->createSchool('Sekolah X');
        $kelas = $this->newKelas($school, '7A');
        $santri = Santri::factory()->create([
            'nama' => 'Anak Contoh',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        app(JournalService::class)->createJournalEntry(
            'Jurnal Sekolah X',
            'JURNAL-X-001',
            [
                ['account_code' => '1010', 'debit' => 50000, 'credit' => 0],
                ['account_code' => '2010', 'debit' => 0, 'credit' => 50000],
            ],
            now()->toDateString(),
            $school->id
        );

        $journal = Journal::where('reference', 'JURNAL-X-001')->firstOrFail();

        $this->delete(route('schools.destroy', $school), ['confirm' => 'HAPUS'])
            ->assertRedirect(route('schools.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('branches', ['id' => $school->id]);
        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
        $this->assertDatabaseMissing('journal_details', ['journal_id' => $journal->id]);
        $this->assertDatabaseMissing('santris', ['branch_id' => $school->id]);
    }

    public function test_superadmin_can_create_school_with_admin_account(): void
    {
        $this->actingAsSuperadmin();

        $this->post(route('schools.store'), [
            'name' => 'Sekolah Baru',
            'status' => 'active',
            'address' => 'Jl. Baru',
            'admin_name' => 'Admin Baru',
            'admin_email' => 'admin.baru@test.sch.id',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
        ])->assertRedirect(route('schools.index'))
            ->assertSessionHas('success');

        $school = School::where('name', 'Sekolah Baru')->first();
        $this->assertNotNull($school);

        $this->assertDatabaseHas('users', [
            'email' => 'admin.baru@test.sch.id',
            'role' => 'admin_sekolah',
            'branch_id' => $school->id,
        ]);
    }
}
