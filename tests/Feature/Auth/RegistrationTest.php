<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    /** A phone number is the required identifier; e-mail is optional. */
    public function test_new_users_can_register(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('student', 'web');

        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'phone'                 => '901112233',
            'email'                 => 'test@example.com',
            'password'              => 'Parol12345!',
            'password_confirmation' => 'Parol12345!',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['phone' => '998901112233']);
    }

    public function test_registration_requires_a_phone(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test User',
            'password'              => 'Parol12345!',
            'password_confirmation' => 'Parol12345!',
        ]);

        $response->assertSessionHasErrors('phone');
        $this->assertGuest();
    }
}
