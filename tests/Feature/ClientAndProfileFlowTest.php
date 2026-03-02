<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\IssuerProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAndProfileFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_updates_a_client(): void
    {
        $createResponse = $this->post(route('clients.store'), [
            'name' => 'Book Oven Inc (dba Pressbooks)',
            'email' => 'billingadmin@pressbooks.com',
            'address' => 'Canada',
            'details' => 'Tax ID 123',
            'is_active' => true,
        ]);

        $createResponse->assertRedirect(route('clients.index'));

        $client = Client::query()->first();
        $this->assertNotNull($client);

        $updateResponse = $this->put(route('clients.update', $client), [
            'name' => 'Book Oven Inc (dba Pressbooks)',
            'email' => 'accounting@pressbooks.com',
            'address' => 'Toronto, Canada',
            'details' => 'Tax ID 456',
            'is_active' => false,
        ]);

        $updateResponse->assertRedirect(route('clients.index'));

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'email' => 'accounting@pressbooks.com',
            'is_active' => 0,
        ]);
    }

    public function test_it_updates_issuer_profile(): void
    {
        $profile = IssuerProfile::query()->create([
            'name' => 'Ricardo',
            'email' => 'ricardo@example.com',
        ]);

        $response = $this->put(route('issuer-profile.update'), [
            'name' => 'Ricardo Aragon',
            'email' => 'me@example.com',
            'address' => 'Madrid',
            'nie' => 'X1234567A',
            'additional_info' => 'Autonomo',
        ]);

        $response->assertRedirect(route('issuer-profile.edit'));

        $this->assertDatabaseHas('issuer_profiles', [
            'id' => $profile->id,
            'name' => 'Ricardo Aragon',
            'nie' => 'X1234567A',
        ]);
    }
}
