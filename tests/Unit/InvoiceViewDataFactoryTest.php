<?php

namespace Tests\Unit;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\IssuerProfile;
use App\Models\User;
use App\Services\InvoiceViewDataFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceViewDataFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_issuer_data_from_profile(): void
    {
        $profile = IssuerProfile::query()->create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Ricardo Aragon',
            'email' => 'ricardo@example.com',
            'address' => 'Madrid',
            'nie' => 'X1234567A',
            'additional_info' => 'Autonomo',
        ]);

        $data = app(InvoiceViewDataFactory::class)->issuer($profile);

        $this->assertSame('Ricardo Aragon', $data['name']);
        $this->assertSame('ricardo@example.com', $data['email']);
        $this->assertSame('Madrid', $data['address']);
        $this->assertSame('X1234567A', $data['nie']);
        $this->assertSame('Autonomo', $data['additional_info']);
    }

    public function test_it_prefers_linked_client_data_over_invoice_snapshot(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->create([
            'user_id' => $user->id,
            'name' => 'Book Oven Inc (dba Pressbooks)',
            'email' => 'billingadmin@pressbooks.com',
            'address' => 'Montreal',
            'details' => 'Tax Reg No.: 7064128',
            'is_active' => true,
        ]);

        $invoice = Invoice::query()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-2026-0001',
            'issue_date' => now()->toDateString(),
            'status' => 'draft',
            'currency' => 'USD',
            'template' => 'aurora',
            'accent_color' => '#0f766e',
            'from_name' => 'Ricardo',
            'client_name' => 'Old Snapshot',
            'client_email' => 'old@example.com',
        ]);

        $invoice->load('client');

        $data = app(InvoiceViewDataFactory::class)->client($invoice);

        $this->assertSame($client->name, $data['name']);
        $this->assertSame($client->email, $data['email']);
        $this->assertSame($client->address, $data['address']);
        $this->assertSame($client->details, $data['details']);
    }
}
