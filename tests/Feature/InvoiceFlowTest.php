<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_invoice_with_line_items(): void
    {
        $item = CatalogItem::query()->create([
            'name' => 'Consulting - 1 hora',
            'description' => 'Consulting Pressbooks',
            'default_unit_price' => 120,
            'default_tax_rate' => 0,
            'is_active' => true,
        ]);

        $response = $this->post(route('invoices.store'), [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'from_email' => 'ricardo@example.com',
            'from_address' => 'Buenos Aires',
            'client_name' => 'Pressbooks',
            'client_email' => 'accounts@pressbooks.com',
            'client_address' => 'Canada',
            'notes' => 'Gracias',
            'lines' => [
                [
                    'catalog_item_id' => $item->id,
                    'description' => 'Consulting Pressbooks',
                    'quantity' => 2,
                    'unit_price' => 120,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $response->assertRedirect();

        $invoice = Invoice::query()->first();

        $this->assertNotNull($invoice);
        $this->assertSame('Pressbooks', $invoice->client_name);
        $this->assertEquals(240.00, (float) $invoice->subtotal);
        $this->assertCount(1, $invoice->lines);
    }

    public function test_it_downloads_invoice_pdf(): void
    {
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $invoice->lines()->create([
            'description' => 'Service',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_rate' => 0,
            'line_total' => 100,
        ]);

        $invoice->recalculateTotals();

        $response = $this->get(route('invoices.pdf', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_it_updates_invoice_status_from_index(): void
    {
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-0002',
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $response = $this->from(route('invoices.index'))
            ->patch(route('invoices.status.update', $invoice), [
                'status' => 'paid',
            ]);

        $response->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_it_prefills_create_form_when_cloning_from_existing_invoice(): void
    {
        $invoice = Invoice::query()->create([
            'invoice_number' => 'INV-2026-0003',
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $invoice->lines()->create([
            'description' => 'Cloned service item',
            'quantity' => 1,
            'unit_price' => 100,
            'tax_rate' => 0,
            'line_total' => 100,
        ]);

        $response = $this->get(route('invoices.create', ['clone_from' => $invoice->id]));

        $response->assertOk();
        $response->assertSee('Cloned service item');
        $response->assertSee('Pressbooks');
    }
}
