<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Canteen;
use App\Models\CanteenItem;
use App\Models\Santri;
use App\Models\Wallet;
use App\Support\AccountCodes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CanteenWebTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_web_kasir_can_record_purchase(): void
    {
        $school = $this->createSchool('Sekolah Kantin Web');
        $kelas = $this->newKelas($school, '7A');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Kantin',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 50000,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'amount' => 20000,
            'canteen_name' => 'Kantin Utama',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'id' => $santri->wallet->id,
            'balance' => 80000,
        ]);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $santri->wallet->id,
            'amount' => 20000,
            'type' => 'debit',
        ]);

        $revenue = Account::where('account_code', AccountCodes::CANTEEN_REVENUE)->value('id');
        $liability = Account::where('account_code', AccountCodes::WALLET_LIABILITY)->value('id');

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $liability,
            'debit' => 20000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $revenue,
            'credit' => 20000,
        ]);
    }

    public function test_web_kasir_rejects_insufficient_balance(): void
    {
        $school = $this->createSchool('Sekolah Kantin Web B');
        $kelas = $this->newKelas($school, '7B');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Kosong',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 5000,
            'daily_spending_limit' => 0,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'amount' => 20000,
            'canteen_name' => 'Kantin Utama',
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('wallets', [
            'id' => $santri->wallet->id,
            'balance' => 5000,
        ]);
    }

    public function test_web_kasir_cannot_buy_for_other_school_santri(): void
    {
        $schoolA = $this->createSchool('Sekolah Kantin Web C');
        $schoolB = $this->createSchool('Sekolah Kantin Web D');
        $kelasB = $this->newKelas($schoolB, '7D');

        $santriB = Santri::factory()->create([
            'nama' => 'Anak Sekolah Lain',
            'kelas_id' => $kelasB->id,
            'branch_id' => $schoolB->id,
        ]);

        Wallet::create([
            'santri_id' => $santriB->id,
            'balance' => 50000,
            'daily_spending_limit' => 0,
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->post(route('kantin.store'), [
            'santri_id' => $santriB->id,
            'amount' => 10000,
            'canteen_name' => 'Kantin',
        ])->assertSessionHasErrors('santri_id');
    }

    public function test_kasir_page_lists_santri_with_wallet(): void
    {
        $school = $this->createSchool('Sekolah Kantin Web E');
        $kelas = $this->newKelas($school, '7E');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Kasir',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 50000,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('kantin.index'))
            ->assertOk()
            ->assertSee('Anak Kasir')
            ->assertSee('Rp 100.000,00');
    }

    public function test_web_kasir_records_itemized_purchase_with_menu_and_adhoc(): void
    {
        $school = $this->createSchool('Sekolah Kantin Itemized');
        $kelas = $this->newKelas($school, '7F');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Item',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Item',
        ]);

        $nasi = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Nasi + Ayam',
            'price' => 15000,
            'unit' => 'porsi',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 30000,
            'items' => [
                [
                    'canteen_item_id' => $nasi->id,
                    'name' => 'Nasi + Ayam',
                    'unit_price' => 15000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
                [
                    'canteen_item_id' => '',
                    'name' => 'Nasi Goreng',
                    'unit_price' => 15000,
                    'quantity' => 1,
                    'unit' => 'pcs',
                ],
            ],
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'id' => $santri->wallet->id,
            'balance' => 70000,
        ]);

        $this->assertDatabaseHas('canteen_transactions', [
            'branch_id' => $school->id,
            'canteen_id' => $canteen->id,
            'total' => 30000,
        ]);

        $this->assertDatabaseHas('canteen_transaction_items', [
            'item_name' => 'Nasi + Ayam',
            'canteen_item_id' => $nasi->id,
            'unit_price' => 15000,
            'total' => 15000,
        ]);

        $this->assertDatabaseHas('canteen_transaction_items', [
            'item_name' => 'Nasi Goreng',
            'canteen_item_id' => null,
            'unit_price' => 15000,
            'total' => 15000,
        ]);
    }

    public function test_cash_payment_does_not_touch_wallet_and_uses_cash_journal(): void
    {
        $school = $this->createSchool('Sekolah Kantin Cash');
        $kelas = $this->newKelas($school, '7G');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Tunai',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 5000,
            'daily_spending_limit' => 10,
        ]);

        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Tunai',
        ]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Sate',
            'price' => 20000,
            'unit' => 'porsi',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'payment_method' => 'cash',
            'amount' => 20000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Sate',
                    'unit_price' => 20000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('wallets', [
            'id' => $santri->wallet->id,
            'balance' => 5000,
        ]);

        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $santri->wallet->id,
            'amount' => 20000,
        ]);

        $this->assertDatabaseHas('canteen_transactions', [
            'branch_id' => $school->id,
            'canteen_id' => $canteen->id,
            'payment_method' => 'cash',
            'total' => 20000,
        ]);

        $cash = Account::where('account_code', AccountCodes::CASH)->value('id');
        $revenue = Account::where('account_code', AccountCodes::CANTEEN_REVENUE)->value('id');

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $cash,
            'debit' => 20000,
        ]);

        $this->assertDatabaseHas('journal_details', [
            'account_id' => $revenue,
            'credit' => 20000,
        ]);
    }

    public function test_menu_stock_is_decremented_and_enforced(): void
    {
        $school = $this->createSchool('Sekolah Kantin Stok');
        $kelas = $this->newKelas($school, '7H');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Stok',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Stok',
        ]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Ayam Bakar',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 5,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 20000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Ayam Bakar',
                    'unit_price' => 10000,
                    'quantity' => 2,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_items', [
            'id' => $item->id,
            'stock' => 3,
        ]);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 100000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Ayam Bakar',
                    'unit_price' => 10000,
                    'quantity' => 10,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('canteen_items', [
            'id' => $item->id,
            'stock' => 3,
        ]);
    }

    public function test_insufficient_stock_rolls_back_everything(): void
    {
        $school = $this->createSchool('Sekolah Kantin Rollback');
        $kelas = $this->newKelas($school, '7R');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Rollback',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Rollback', 'is_active' => true]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Nasi Rollback',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 1,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 20000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Nasi Rollback',
                    'unit_price' => 10000,
                    'quantity' => 2,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('wallets', [
            'id' => $santri->wallet->id,
            'balance' => 100000,
        ]);

        $this->assertDatabaseMissing('wallet_transactions', [
            'wallet_id' => $santri->wallet->id,
        ]);

        $this->assertDatabaseCount('canteen_transactions', 0);
        $this->assertDatabaseCount('canteen_transaction_items', 0);

        $this->assertDatabaseHas('canteen_items', [
            'id' => $item->id,
            'stock' => 1,
        ]);
    }

    public function test_sold_out_item_is_hidden_from_kasir_page(): void
    {
        $school = $this->createSchool('Sekolah Kantin Habis');
        $kelas = $this->newKelas($school, '7I');

        Santri::factory()->create([
            'nama' => 'Anak Habis',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Habis',
        ]);

        CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Popmie Habis',
            'price' => 5000,
            'unit' => 'pcs',
            'stock' => 0,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('kantin.index'))
            ->assertOk()
            ->assertDontSee('Popmie Habis');
    }

    public function test_rekap_page_lists_sales_without_error(): void
    {
        $school = $this->createSchool('Sekolah Rekap Kantin');
        $kelas = $this->newKelas($school, '7J');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Rekap',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Rekap',
        ]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Teh Gelas',
            'price' => 3000,
            'unit' => 'gelas',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 3000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Teh Gelas',
                    'unit_price' => 3000,
                    'quantity' => 1,
                    'unit' => 'gelas',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->get(route('kantin.rekap'))
            ->assertOk()
            ->assertSee('Teh Gelas')
            ->assertSee('Kantin Rekap');
    }

    public function test_rekap_splits_cash_and_wallet_sales(): void
    {
        $school = $this->createSchool('Sekolah Rekap Split');
        $kelas = $this->newKelas($school, '7K');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Split',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Split',
        ]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Es Teh',
            'price' => 3000,
            'unit' => 'gelas',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 3000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Es Teh',
                    'unit_price' => 3000,
                    'quantity' => 1,
                    'unit' => 'gelas',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'payment_method' => 'cash',
            'amount' => 3000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Es Teh',
                    'unit_price' => 3000,
                    'quantity' => 1,
                    'unit' => 'gelas',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->get(route('kantin.rekap'))
            ->assertOk()
            ->assertSee('Saldo (Wallet)')
            ->assertSee('Tunai (Cash)');
    }

    public function test_manage_page_can_create_canteen_and_menu(): void
    {
        $school = $this->createSchool('Sekolah Kelola Kantin');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.canteen.store'), [
            'branch_id' => $school->id,
            'name' => 'Kantin Kelola',
        ])->assertRedirect(route('kantin.manage'))
            ->assertSessionHas('success');

        $canteen = Canteen::where('branch_id', $school->id)->firstOrFail();

        $this->post(route('kantin.item.store'), [
            'branch_id' => $school->id,
            'canteen_id' => $canteen->id,
            'name' => 'Nasi + Lauk',
            'price' => 10000,
            'unit' => 'porsi',
        ])->assertRedirect(route('kantin.manage.show', $canteen))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_items', [
            'canteen_id' => $canteen->id,
            'name' => 'Nasi + Lauk',
            'price' => 10000,
            'unit' => 'porsi',
        ]);

        $this->get(route('kantin.manage'))
            ->assertOk()
            ->assertSee('Kantin Kelola');

        $this->get(route('kantin.manage.show', $canteen))
            ->assertOk()
            ->assertSee('Nasi + Lauk');
    }

    public function test_manage_search_filters_canteens(): void
    {
        $school = $this->createSchool('Sekolah Cari Kantin');
        $this->actingAsSchoolAdmin($school);

        Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Mentari', 'is_active' => true]);
        Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Awan', 'is_active' => true]);

        $this->get(route('kantin.manage', ['q' => 'Mentari']))
            ->assertOk()
            ->assertSee('Kantin Mentari')
            ->assertDontSee('Kantin Awan');
    }

    public function test_menu_unit_must_be_among_allowed_food_units(): void
    {
        $school = $this->createSchool('Sekolah Satuan Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Satuan', 'is_active' => true]);

        $this->post(route('kantin.item.store'), [
            'branch_id' => $school->id,
            'canteen_id' => $canteen->id,
            'name' => 'Roti Lembar',
            'price' => 5000,
            'unit' => 'lembar',
        ])->assertSessionHasErrors('unit');

        $this->assertDatabaseCount('canteen_items', 0);

        $this->post(route('kantin.item.store'), [
            'branch_id' => $school->id,
            'canteen_id' => $canteen->id,
            'name' => 'Es Teh Cup',
            'price' => 4000,
            'unit' => 'cup',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_items', [
            'canteen_id' => $canteen->id,
            'name' => 'Es Teh Cup',
            'unit' => 'cup',
        ]);
    }

    public function test_amount_must_match_total_of_items(): void
    {
        $school = $this->createSchool('Sekolah Total Item');
        $kelas = $this->newKelas($school, '7L');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Total',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Total', 'is_active' => true]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Nasi Kuning',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 15000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Nasi Kuning',
                    'unit_price' => 10000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertSessionHasErrors('amount');

        $this->assertDatabaseHas('wallets', [
            'id' => $santri->wallet->id,
            'balance' => 100000,
        ]);

        $this->assertDatabaseCount('canteen_transactions', 0);
    }

    public function test_item_from_other_canteen_is_rejected(): void
    {
        $school = $this->createSchool('Sekolah Item Silang');
        $kelas = $this->newKelas($school, '7M');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Silang',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteenA = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin A', 'is_active' => true]);
        $canteenB = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin B', 'is_active' => true]);

        $itemB = CanteenItem::create([
            'canteen_id' => $canteenB->id,
            'name' => 'Menu B',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteenA->id,
            'amount' => 10000,
            'items' => [
                [
                    'canteen_item_id' => $itemB->id,
                    'name' => 'Menu B',
                    'unit_price' => 10000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertSessionHasErrors('items.0.canteen_item_id');
    }

    public function test_ad_hoc_mode_rejects_registered_item(): void
    {
        $school = $this->createSchool('Sekolah Adhoc Terdaftar');
        $kelas = $this->newKelas($school, '7N');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Adhoc',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Adhoc', 'is_active' => true]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Menu Terdaftar',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 100,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_name' => 'Kantin Adhoc',
            'amount' => 10000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Menu Terdaftar',
                    'unit_price' => 10000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertSessionHasErrors('items.0.canteen_item_id');
    }

    public function test_deleting_canteen_soft_deletes_and_can_be_restored(): void
    {
        $school = $this->createSchool('Sekolah Soft Delete Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Restore', 'is_active' => true]);

        $this->delete(route('kantin.canteen.destroy', $canteen))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('canteens', ['id' => $canteen->id]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Canteen::class,
            'auditable_id' => $canteen->id,
            'action' => 'deleted',
            'branch_id' => $school->id,
        ]);

        $this->post(route('kantin.canteen.restore', $canteen->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteens', ['id' => $canteen->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Canteen::class,
            'auditable_id' => $canteen->id,
            'action' => 'restored',
            'branch_id' => $school->id,
        ]);
    }

    public function test_deleting_canteen_also_soft_deletes_its_items(): void
    {
        $school = $this->createSchool('Sekolah Soft Delete Item Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Menu', 'is_active' => true]);
        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Nasi Goreng',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 5,
        ]);

        $this->delete(route('kantin.canteen.destroy', $canteen))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('canteen_items', ['id' => $item->id]);

        $this->post(route('kantin.canteen.restore', $canteen->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_deleting_menu_soft_deletes_and_can_be_restored(): void
    {
        $school = $this->createSchool('Sekolah Soft Delete Menu');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Menu B', 'is_active' => true]);
        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Es Teh',
            'price' => 3000,
            'unit' => 'gelas',
            'stock' => 10,
        ]);

        $this->delete(route('kantin.item.destroy', $item))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('canteen_items', ['id' => $item->id]);

        $this->post(route('kantin.item.restore', $item->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_items', ['id' => $item->id, 'deleted_at' => null]);
    }

    public function test_kantin_riwayat_page_shows_deleted_items_with_restore(): void
    {
        $school = $this->createSchool('Sekolah Riwayat Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Riwayat', 'is_active' => true]);
        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Menu Riwayat',
            'price' => 5000,
            'unit' => 'pcs',
            'stock' => 3,
        ]);

        $this->delete(route('kantin.item.destroy', $item))->assertSessionHas('success');

        $this->get(route('kantin.canteen.riwayat', $canteen))
            ->assertOk()
            ->assertSee('Menu Riwayat')
            ->assertSee('Pulihkan');
    }

    public function test_toggling_canteen_active_status(): void
    {
        $school = $this->createSchool('Sekolah Toggle Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Toggle', 'is_active' => true]);

        $this->post(route('kantin.canteen.toggle', $canteen))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteens', ['id' => $canteen->id, 'is_active' => 0]);

        $this->post(route('kantin.canteen.toggle', $canteen))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteens', ['id' => $canteen->id, 'is_active' => 1]);
    }

    public function test_manage_page_shows_deleted_canteens_section(): void
    {
        $school = $this->createSchool('Sekolah Terhapus Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Lama', 'is_active' => true]);

        $this->delete(route('kantin.canteen.destroy', $canteen))->assertSessionHas('success');

        $this->get(route('kantin.manage'))
            ->assertOk()
            ->assertSee('Kantin Terhapus')
            ->assertSee('Kantin Lama')
            ->assertSee('Pulihkan');
    }

    public function test_manage_page_shows_recent_purchase_history_per_canteen(): void
    {
        $school = $this->createSchool('Sekolah Belanja Kantin');
        $kelas = $this->newKelas($school, '7O');

        $santri = Santri::factory()->create([
            'nama' => 'Anak Belanja',
            'kelas_id' => $kelas->id,
            'branch_id' => $school->id,
        ]);

        Wallet::create([
            'santri_id' => $santri->id,
            'balance' => 100000,
            'daily_spending_limit' => 0,
        ]);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Belanja', 'is_active' => true]);

        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Teh Belanja',
            'price' => 4000,
            'unit' => 'gelas',
            'stock' => 10,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 4000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Teh Belanja',
                    'unit_price' => 4000,
                    'quantity' => 1,
                    'unit' => 'gelas',
                ],
            ],
        ])->assertSessionHas('success');

        $this->get(route('kantin.manage'))
            ->assertOk()
            ->assertSee('Riwayat Belanjaan')
            ->assertSee('Teh Belanja');
    }

    public function test_recreating_deleted_canteen_restores_it(): void
    {
        $school = $this->createSchool('Sekolah Recreate Kantin');
        $this->actingAsSchoolAdmin($school);

        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin Ulang', 'is_active' => true]);

        $this->delete(route('kantin.canteen.destroy', $canteen))->assertSessionHas('success');
        $this->assertSoftDeleted('canteens', ['id' => $canteen->id]);

        $this->post(route('kantin.canteen.store'), [
            'branch_id' => $school->id,
            'name' => 'Kantin Ulang',
        ])->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteens', ['id' => $canteen->id, 'deleted_at' => null]);
        $this->assertSame(1, Canteen::withoutTrashed()->where('branch_id', $school->id)->count());
    }
}
