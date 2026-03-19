<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_redirects_to_login_for_guests(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_the_root_redirects_to_invoices_for_authenticated_users(): void
    {
        $this->signIn();

        $response = $this->get('/');

        $response->assertRedirect(route('invoices.index'));
    }
}
