<?php

namespace Tests\Feature;

use App\Models\Canteen;
use App\Models\CanteenItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class CanteenManageControllerTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_admin_cannot_update_item_of_another_school(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $schoolB = $this->createSchool('Sekolah B');

        $canteenB = Canteen::create(['branch_id' => $schoolB->id, 'name' => 'Kantin B', 'is_active' => true]);
        $itemB = CanteenItem::create([
            'canteen_id' => $canteenB->id,
            'name' => 'Menu B',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 5,
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->put(route('kantin.item.update', $itemB), [
            'name' => 'Menu B Diganti',
            'price' => 15000,
            'unit' => 'porsi',
            'stock' => 5,
        ])->assertForbidden();

        $this->assertDatabaseHas('canteen_items', [
            'id' => $itemB->id,
            'name' => 'Menu B',
            'price' => 10000,
            'stock' => 5,
        ]);
    }

    public function test_admin_cannot_delete_item_of_another_school(): void
    {
        $schoolA = $this->createSchool('Sekolah A');
        $schoolB = $this->createSchool('Sekolah B');

        $canteenB = Canteen::create(['branch_id' => $schoolB->id, 'name' => 'Kantin B', 'is_active' => true]);
        $itemB = CanteenItem::create([
            'canteen_id' => $canteenB->id,
            'name' => 'Menu B',
            'price' => 10000,
            'unit' => 'porsi',
            'stock' => 5,
        ]);

        $this->actingAsSchoolAdmin($schoolA);

        $this->delete(route('kantin.item.destroy', $itemB))->assertForbidden();

        $this->assertDatabaseHas('canteen_items', ['id' => $itemB->id]);
    }

    public function test_admin_can_update_item_of_own_school(): void
    {
        $school = $this->createSchool('Sekolah A');
        $canteen = Canteen::create(['branch_id' => $school->id, 'name' => 'Kantin A', 'is_active' => true]);
        $item = CanteenItem::create([
            'canteen_id' => $canteen->id,
            'name' => 'Menu A',
            'price' => 5000,
            'unit' => 'pcs',
            'stock' => 3,
        ]);

        $this->actingAsSchoolAdmin($school);

        $this->put(route('kantin.item.update', $item), [
            'name' => 'Menu A Baru',
            'price' => 6000,
            'unit' => 'pcs',
            'stock' => 10,
        ])->assertRedirect(route('kantin.manage.show', $canteen))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('canteen_items', [
            'id' => $item->id,
            'name' => 'Menu A Baru',
            'price' => 6000,
            'stock' => 10,
        ]);
    }
}
