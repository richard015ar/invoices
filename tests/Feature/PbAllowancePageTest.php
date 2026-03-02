<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PbAllowancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_allowance_usage_for_selected_year(): void
    {
        $wellness = CatalogItem::query()->create([
            'name' => 'Wellness Allowance',
            'default_unit_price' => 1500,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $invoice2026 = Invoice::query()->create([
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => '2026-02-20',
            'status' => 'paid',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $invoice2026->lines()->create([
            'catalog_item_id' => $wellness->id,
            'description' => 'Wellness Allowance',
            'quantity' => 1,
            'unit_price' => 200,
            'tax_rate' => 0,
            'line_total' => 200,
        ]);

        $invoice2025 = Invoice::query()->create([
            'invoice_number' => 'INV-2025-0001',
            'issue_date' => '2025-10-20',
            'status' => 'paid',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $invoice2025->lines()->create([
            'catalog_item_id' => $wellness->id,
            'description' => 'Wellness Allowance',
            'quantity' => 1,
            'unit_price' => 1000,
            'tax_rate' => 0,
            'line_total' => 1000,
        ]);

        $response = $this->get(route('pb-allowances.index', ['year' => 2026]));

        $response->assertOk();
        $response->assertSee('PB Allowances');
        $response->assertSee('Wellness Allowance');
        $response->assertSee('CAD 200.00');
        $response->assertSee('CAD 1,300.00');
        $response->assertDontSee('CAD 1000.00');
    }

    public function test_it_defaults_to_current_year_when_invalid_year_is_sent(): void
    {
        $response = $this->get(route('pb-allowances.index', ['year' => 9999]));

        $response->assertOk();
        $response->assertSee((string) now()->year);
    }
}
