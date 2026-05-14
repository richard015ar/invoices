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
        $user = $this->signIn();

        $wellness = CatalogItem::query()->create([
            'user_id' => $user->id,
            'name' => 'Wellness Allowance',
            'default_unit_price' => 1500,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $invoice2026 = Invoice::query()->create([
            'user_id' => $user->id,
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
            'user_id' => $user->id,
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
        $response->assertSee('pb-allowances/history?year=2026&amp;allowance=wellness', false);
        $response->assertDontSee('CAD 1000.00');
    }

    public function test_it_defaults_to_current_year_when_invalid_year_is_sent(): void
    {
        $this->signIn();

        $response = $this->get(route('pb-allowances.index', ['year' => 9999]));

        $response->assertOk();
        $response->assertSee((string) now()->year);
    }

    public function test_it_shows_allowance_history_filtered_by_year_and_allowance(): void
    {
        $user = $this->signIn();

        $wellness = CatalogItem::query()->create([
            'user_id' => $user->id,
            'name' => 'Wellness Allowance',
            'default_unit_price' => 1500,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $tech = CatalogItem::query()->create([
            'user_id' => $user->id,
            'name' => 'Tech Allowance',
            'default_unit_price' => 1200,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $matchingInvoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0002',
            'issue_date' => '2026-03-14',
            'status' => 'paid',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $matchingInvoice->lines()->create([
            'catalog_item_id' => $wellness->id,
            'description' => 'Gym membership',
            'quantity' => 1,
            'unit_price' => 320,
            'tax_rate' => 0,
            'line_total' => 320,
        ]);

        $otherAllowanceInvoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0003',
            'issue_date' => '2026-03-20',
            'status' => 'sent',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $otherAllowanceInvoice->lines()->create([
            'catalog_item_id' => $tech->id,
            'description' => 'Keyboard',
            'quantity' => 1,
            'unit_price' => 120,
            'tax_rate' => 0,
            'line_total' => 120,
        ]);

        $otherYearInvoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2025-0005',
            'issue_date' => '2025-08-10',
            'status' => 'paid',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $otherYearInvoice->lines()->create([
            'catalog_item_id' => $wellness->id,
            'description' => 'Therapy',
            'quantity' => 1,
            'unit_price' => 200,
            'tax_rate' => 0,
            'line_total' => 200,
        ]);

        $response = $this->get(route('pb-allowances.history', [
            'year' => 2026,
            'allowance' => 'wellness',
        ]));

        $response->assertOk();
        $response->assertSee('Allowance history');
        $response->assertSee('INV-2026-0002');
        $response->assertSee('Gym membership');
        $response->assertSee('CAD 320.00');
        $response->assertDontSee('INV-2026-0003');
        $response->assertDontSee('INV-2025-0005');
    }

    public function test_it_filters_allowance_history_by_status(): void
    {
        $user = $this->signIn();

        $wellness = CatalogItem::query()->create([
            'user_id' => $user->id,
            'name' => 'Wellness Allowance',
            'default_unit_price' => 1500,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $paidInvoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0100',
            'issue_date' => '2026-01-11',
            'status' => 'paid',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $paidInvoice->lines()->create([
            'catalog_item_id' => $wellness->id,
            'description' => 'Therapy',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_rate' => 0,
            'line_total' => 100,
        ]);

        $draftInvoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0101',
            'issue_date' => '2026-01-12',
            'status' => 'draft',
            'currency' => 'CAD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $draftInvoice->lines()->create([
            'catalog_item_id' => $wellness->id,
            'description' => 'Pharmacy',
            'quantity' => 1,
            'unit_price' => 80,
            'tax_rate' => 0,
            'line_total' => 80,
        ]);

        $response = $this->get(route('pb-allowances.history', [
            'year' => 2026,
            'allowance' => 'wellness',
            'status' => 'paid',
        ]));

        $response->assertOk();
        $response->assertSee('INV-2026-0100');
        $response->assertDontSee('INV-2026-0101');
    }
}
