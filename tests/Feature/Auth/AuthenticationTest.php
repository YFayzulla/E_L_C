<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    /**
     * Sign-in is by phone or name, not e-mail — see LoginRequest::authenticate().
     * Most accounts here are created by an admin and never have an address.
     */
    public function test_users_can_authenticate_using_their_phone(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'login'    => $user->phone,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    /** The number may be typed in any local format; it is normalised first. */
    public function test_users_can_authenticate_using_an_unnormalised_phone(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login'    => '+998 ' . substr($user->phone, 3),
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_users_can_authenticate_using_their_name(): void
    {
        $user = User::factory()->create(['name' => 'Dilnoza Karimova']);

        $this->post('/login', [
            'login'    => 'Dilnoza Karimova',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'login'    => $user->phone,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_unknown_login_is_rejected(): void
    {
        $this->post('/login', [
            'login'    => '998900000000',
            'password' => 'password',
        ]);

        $this->assertGuest();
    }
}
