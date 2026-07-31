<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * `/` is the dashboard, not a public landing page — a guest is sent to
     * the login screen.
     *
     * @return void
     */
    public function test_the_root_url_redirects_a_guest_to_login()
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }
}
