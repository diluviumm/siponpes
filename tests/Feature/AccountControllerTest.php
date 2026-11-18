<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class AccountControllerTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_superadmin_can_create_account(): void
    {
        $this->actingAsSuperadmin();

        $response = $this->post(route('accounts.store'), [
            'account_code' => '5090',
            'account_name' => 'Beban Percobaan',
            'type' => 'expense',
        ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'account_code' => '5090',
            'account_name' => 'Beban Percobaan',
            'type' => 'expense',
        ]);
    }

    public function test_account_code_must_be_unique(): void
    {
        $this->actingAsSuperadmin();

        $this->post(route('accounts.store'), [
            'account_code' => '1010',
            'account_name' => 'Duplikat',
            'type' => 'asset',
        ])->assertSessionHasErrors('account_code');
    }

    public function test_school_admin_cannot_access_account_management(): void
    {
        $school = $this->createSchool();
        $this->actingAsSchoolAdmin($school);

        $this->get(route('accounts.index'))->assertForbidden();
    }

    public function test_referenced_account_cannot_be_deleted(): void
    {
        $this->actingAsSuperadmin();

        $account = Account::where('account_code', '1010')->first();

        app(JournalService::class)->createJournalEntry(
            'Pembayaran SPP santri',
            'INV-1001',
            [
                ['account_code' => '1010', 'debit' => 100000, 'credit' => 0],
                ['account_code' => '4020', 'debit' => 0, 'credit' => 100000],
            ]
        );

        $this->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_unused_account_can_be_deleted(): void
    {
        $this->actingAsSuperadmin();

        $account = Account::create([
            'account_code' => '9010',
            'account_name' => 'Akun Percobaan',
            'type' => 'equity',
        ]);

        $this->delete(route('accounts.destroy', $account))
            ->assertRedirect(route('accounts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
