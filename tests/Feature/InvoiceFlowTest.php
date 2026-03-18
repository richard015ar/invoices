<?php

namespace Tests\Feature;

use App\Mail\InvoiceEmail;
use App\Models\CatalogItem;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_invoice_with_line_items(): void
    {
        $user = $this->signIn();

        $item = CatalogItem::query()->create([
            'user_id' => $user->id,
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
        $this->assertSame($user->id, $invoice->user_id);
    }

    public function test_it_downloads_invoice_pdf(): void
    {
        $user = $this->signIn();

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
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
        $user = $this->signIn();

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
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
        $user = $this->signIn();

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
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

    public function test_it_updates_existing_invoice_without_dropping_previous_line_items(): void
    {
        $user = $this->signIn();

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0200',
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
                'description' => 'Original service A',
                'quantity' => 1,
                'unit_price' => 100,
                'tax_rate' => 0,
                'line_total' => 100,
            ],
            [
                'position' => 1,
                'description' => 'Original service B',
                'quantity' => 1,
                'unit_price' => 200,
                'tax_rate' => 0,
                'line_total' => 200,
            ],
        ]);

        $invoice->recalculateTotals();

        $response = $this->put(route('invoices.update', $invoice), [
            'invoice_number' => $invoice->invoice_number,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'from_email' => 'ricardo@example.com',
            'from_address' => 'Madrid',
            'client_name' => 'Pressbooks',
            'client_email' => 'billingadmin@pressbooks.com',
            'client_address' => 'Canada',
            'client_details' => 'Tax Reg No.: 7064128',
            'lines' => [
                [
                    'description' => 'Original service A',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 0,
                ],
                [
                    'description' => 'Original service B',
                    'quantity' => 1,
                    'unit_price' => 200,
                    'tax_rate' => 0,
                ],
                [
                    'description' => 'New allowance C',
                    'quantity' => 1,
                    'unit_price' => 300,
                    'tax_rate' => 0,
                ],
                [
                    'description' => 'New allowance D',
                    'quantity' => 1,
                    'unit_price' => 400,
                    'tax_rate' => 0,
                ],
            ],
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));

        $invoice->refresh();
        $this->assertCount(4, $invoice->lines()->get());
        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoice->id,
            'description' => 'Original service A',
        ]);
        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoice->id,
            'description' => 'Original service B',
        ]);
        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoice->id,
            'description' => 'New allowance C',
        ]);
        $this->assertDatabaseHas('invoice_lines', [
            'invoice_id' => $invoice->id,
            'description' => 'New allowance D',
        ]);
    }

    public function test_it_stores_invoice_attachments_and_allows_download(): void
    {
        Storage::fake('local');
        $user = $this->signIn();

        $response = $this->post(route('invoices.store'), [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'from_email' => 'ricardo@example.com',
            'from_address' => 'Madrid',
            'client_name' => 'Pressbooks',
            'client_email' => 'billingadmin@pressbooks.com',
            'client_address' => 'Canada',
            'lines' => [
                [
                    'description' => 'Service',
                    'quantity' => 1,
                    'unit_price' => 100,
                    'tax_rate' => 0,
                ],
            ],
            'attachments' => [
                UploadedFile::fake()->create('supporting-doc.pdf', 200, 'application/pdf'),
            ],
        ]);

        $invoice = Invoice::query()->firstOrFail();
        $attachment = $invoice->attachments()->first();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertNotNull($attachment);
        Storage::disk('local')->assertExists($attachment->path);

        $downloadResponse = $this->get(route('invoices.attachments.download', [$invoice, $attachment]));

        $downloadResponse->assertOk();
    }

    public function test_it_sends_invoice_email_with_pdf_attachment(): void
    {
        Mail::fake();
        Storage::fake('local');
        $user = $this->signIn();

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0099',
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

        $path = UploadedFile::fake()->create('supporting-doc.pdf', 200, 'application/pdf')
            ->store('invoice-attachments/'.$invoice->id, 'local');

        $invoice->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'supporting-doc.pdf',
            'mime_type' => 'application/pdf',
            'size' => 204800,
        ]);

        $invoice->recalculateTotals();

        $response = $this->post(route('invoices.send', $invoice), [
            'recipient_email' => 'billingadmin@pressbooks.com',
            'subject' => 'Invoice INV-2026-0099',
            'body' => 'Please find attached invoice.',
        ]);

        $response->assertRedirect();

        Mail::assertSent(InvoiceEmail::class, function (InvoiceEmail $mail): bool {
            return $mail->hasTo('billingadmin@pressbooks.com')
                && $mail->subjectLine === 'Invoice INV-2026-0099'
                && $mail->messageBody === 'Please find attached invoice.';
        });
    }

    public function test_it_sends_invoice_email_with_extra_attachments(): void
    {
        Mail::fake();
        Storage::fake('local');
        $user = $this->signIn();

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
            'invoice_number' => 'INV-2026-0100',
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

        $path = UploadedFile::fake()->image('receipt-2.jpg')
            ->store('invoice-attachments/'.$invoice->id, 'local');

        $invoice->attachments()->create([
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'receipt-2.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $invoice->recalculateTotals();

        $response = $this->post(route('invoices.send', $invoice), [
            'recipient_email' => 'billingadmin@pressbooks.com',
            'subject' => 'Invoice INV-2026-0100',
            'body' => 'Please find attached invoice and receipts.',
        ]);

        $response->assertRedirect();

        Mail::assertSent(InvoiceEmail::class, function (InvoiceEmail $mail): bool {
            return $mail->hasTo('billingadmin@pressbooks.com')
                && $mail->subjectLine === 'Invoice INV-2026-0100';
        });
    }
}
