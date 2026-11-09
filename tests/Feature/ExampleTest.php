<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_requires_an_entry_password(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('access.login'));
    }
}
