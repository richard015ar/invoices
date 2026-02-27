<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_redirects_to_invoices_index(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('invoices.index'));
    }
}
