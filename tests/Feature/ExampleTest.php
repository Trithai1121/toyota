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
    public function test_the_homepage_returns_a_successful_response_without_leaking_output_buffers(): void
    {
        $initialOutputBufferLevel = ob_get_level();
        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame($initialOutputBufferLevel, ob_get_level());
    }
}
