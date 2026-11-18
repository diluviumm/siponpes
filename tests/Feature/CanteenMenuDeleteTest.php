<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CanteenItem;
use App\Models\CanteenTransaction;
use App\Models\CanteenTransactionItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CanteenMenuDeleteTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_manage_menu_page_has_scrollable_table_and_custom_delete_confirmation(): void
    {
        $school = $this->createSchool('Sekolah Menu Aksi');
        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Aksi',
            'is_active' => true,
        ]);
        CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Menu Aksi',
            'price' => 5000,
            'unit' => 'porsi',
            'stock' => 5,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->get(route('kantin.manage.show', $canteen))
            ->assertOk()
            ->assertSee('overflow-x-auto', false)
            ->assertSee('update-item-', false)
            ->assertSee('data-confirm', false)
            ->assertSee('Hapus', false)
            ->assertSee('Simpan', false);
    }

    public function test_deleted_menu_still_keeps_old_transaction_history(): void
    {
        $school = $this->createSchool('Sekolah Menu Riwayat');
        $canteen = Canteen::create([
            'branch_id' => $school->id,
            'name' => 'Kantin Riwayat',
            'is_active' => true,
        ]);
        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Menu Lama',
            'price' => 5000,
            'unit' => 'porsi',
            'stock' => 5,
        ]);

        $transactionItem = CanteenTransactionItem::create([
            'canteen_transaction_id' => CanteenTransaction::create([
                'canteen_id' => $canteen->id,
                'branch_id' => $school->id,
                'payment_method' => 'cash',
                'total' => 5000,
                'transaction_date' => now()->toDateString(),
            ])->id,
            'canteen_item_id' => $item->id,
            'item_name' => 'Menu Lama',
            'unit' => 'porsi',
            'quantity' => 1,
            'unit_price' => 5000,
            'total' => 5000,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->delete(route('kantin.item.destroy', $item))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSoftDeleted('canteen_items', ['id' => $item->id]);
        $this->assertDatabaseHas('canteen_transaction_items', [
            'id' => $transactionItem->id,
            'item_name' => 'Menu Lama',
            'unit' => 'porsi',
        ]);
    }
}
