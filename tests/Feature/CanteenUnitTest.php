<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CanteenItem;
use App\Models\CanteenTransactionItem;
use App\Models\Santri;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CanteenUnitTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    private function context(string $name): array
    {
        $school = $this->createSchool($name);
        $kelas = $this->newKelas($school, '7A');
        $santri = Santri::factory()->create([
            'nama' => 'Anak Satuan',
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
            'name' => 'Kantin Satuan',
            'is_active' => true,
        ]);
        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Indomie',
            'price' => 5000,
            'unit' => 'porsi',
            'stock' => 10,
        ]);

        return [$school, $santri, $canteen, $item];
    }

    public function test_transaction_stores_menu_unit_not_default_pcs(): void
    {
        [$school, $santri, $canteen, $item] = $this->context('Sekolah Satuan A');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 10000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Indomie',
                    'unit_price' => 5000,
                    'quantity' => 2,
                    'unit' => 'pcs',
                ],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_transaction_items', [
            'canteen_item_id' => $item->id,
            'item_name' => 'Indomie',
            'unit' => 'porsi',
            'quantity' => 2,
        ]);
    }

    public function test_rekap_shows_the_stored_menu_unit(): void
    {
        [$school, $santri, $canteen, $item] = $this->context('Sekolah Satuan B');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 5000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Indomie',
                    'unit_price' => 5000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertSessionHas('success');

        $this->get(route('kantin.rekap'))
            ->assertOk()
            ->assertSee('Indomie')
            ->assertSee('porsi');
    }

    public function test_changing_menu_unit_does_not_change_old_transaction_snapshot(): void
    {
        [$school, $santri, $canteen, $item] = $this->context('Sekolah Satuan C');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 5000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Indomie',
                    'unit_price' => 5000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertSessionHas('success');

        $this->put(route('kantin.item.update', $item), [
            'name' => 'Indomie',
            'price' => 5000,
            'unit' => 'gelas',
            'stock' => 10,
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_transaction_items', [
            'canteen_item_id' => $item->id,
            'unit' => 'porsi',
        ]);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 5000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Indomie',
                    'unit_price' => 5000,
                    'quantity' => 1,
                    'unit' => 'gelas',
                ],
            ],
        ])->assertSessionHas('success');

        $this->assertSame(2, CanteenTransactionItem::count());
        $this->assertSame(1, CanteenTransactionItem::where('unit', 'porsi')->count());
        $this->assertSame(1, CanteenTransactionItem::where('unit', 'gelas')->count());
    }

    public function test_adhoc_item_defaults_to_pcs(): void
    {
        [$school, $santri, $canteen] = $this->context('Sekolah Satuan D');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 10000,
            'items' => [
                [
                    'canteen_item_id' => '',
                    'name' => 'Nasi Goreng Spesial',
                    'unit_price' => 10000,
                    'quantity' => 1,
                    'unit' => 'pcs',
                ],
            ],
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_transaction_items', [
            'item_name' => 'Nasi Goreng Spesial',
            'canteen_item_id' => null,
            'unit' => 'pcs',
        ]);
    }

    public function test_rekap_can_be_exported_as_pdf_and_excel(): void
    {
        [$school, $santri, $canteen, $item] = $this->context('Sekolah Satuan E');
        $this->actingAsSchoolAdmin($school);

        $this->post(route('kantin.store'), [
            'santri_id' => $santri->id,
            'canteen_id' => $canteen->id,
            'amount' => 5000,
            'items' => [
                [
                    'canteen_item_id' => $item->id,
                    'name' => 'Indomie',
                    'unit_price' => 5000,
                    'quantity' => 1,
                    'unit' => 'porsi',
                ],
            ],
        ])->assertSessionHas('success');

        $this->get(route('kantin.rekap', ['pdf' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->get(route('kantin.rekap', ['excel' => 1]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
