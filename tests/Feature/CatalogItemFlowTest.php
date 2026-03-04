<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogItemFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_catalog_item(): void
    {
        $user = $this->signIn();

        $response = $this->post(route('catalog-items.store'), [
            'name' => 'Monthly retainer',
            'description' => 'Pressbooks development retainer',
            'default_unit_price' => 2500,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('catalog-items.index'));

        $this->assertDatabaseHas('catalog_items', [
            'name' => 'Monthly retainer',
            'is_active' => 1,
            'user_id' => $user->id,
        ]);
    }

    public function test_it_updates_a_catalog_item(): void
    {
        $user = $this->signIn();

        $item = CatalogItem::query()->create([
            'user_id' => $user->id,
            'name' => 'Monthly retainer',
            'default_unit_price' => 2500,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $response = $this->put(route('catalog-items.update', $item), [
            'name' => 'Biweekly retainer',
            'default_unit_price' => 1250,
            'default_tax_rate' => 0,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('catalog-items.index'));

        $item->refresh();
        $this->assertSame('Biweekly retainer', $item->name);
        $this->assertFalse($item->is_active);
    }
}
