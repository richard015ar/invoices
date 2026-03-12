<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTotalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_recalculates_subtotal_tax_and_grand_total(): void
    {
        $invoice = Invoice::query()->create([
            'user_id' => User::factory()->create()->id,
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Pressbooks',
        ]);

        $invoice->lines()->createMany([
            [
                'position' => 0,
                'description' => 'Service A',
                'quantity' => 2,
                'unit_price' => 100,
                'tax_rate' => 10,
                'line_total' => 200,
            ],
            [
                'position' => 1,
                'description' => 'Service B',
                'quantity' => 1,
                'unit_price' => 50,
                'tax_rate' => 0,
                'line_total' => 50,
            ],
        ]);

        $invoice->recalculateTotals();
        $invoice->refresh();

        $this->assertSame(250.0, (float) $invoice->subtotal);
        $this->assertSame(20.0, (float) $invoice->tax_total);
        $this->assertSame(270.0, (float) $invoice->grand_total);
    }
}
