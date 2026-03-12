<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoiceAttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceAttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_and_removes_invoice_attachments(): void
    {
        Storage::fake('local');

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

        $service = app(InvoiceAttachmentService::class);

        $service->storeForInvoice($invoice, [
            UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]);

        $attachment = $invoice->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($attachment->path);

        $service->removeFromInvoice($invoice, [$attachment->id]);

        $this->assertDatabaseMissing('invoice_attachments', [
            'id' => $attachment->id,
        ]);
        Storage::disk('local')->assertMissing($attachment->path);
    }
}
