<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_login_and_logout(): void
    {
        $registerResponse = $this->post(route('register.store'), [
            'name' => 'Ricardo Aragon',
            'email' => 'ricardo@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $registerResponse->assertRedirect(route('invoices.index'));
        $this->assertAuthenticated();

        auth()->logout();

        $loginResponse = $this->post(route('login.store'), [
            'email' => 'ricardo@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('invoices.index'));
        $this->assertAuthenticated();

        $logoutResponse = $this->post(route('logout'));

        $logoutResponse->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'ricardo@example.com',
            'password' => 'password123',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'ricardo@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
