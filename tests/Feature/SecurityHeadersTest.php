<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_public_responses_include_baseline_security_headers(): void
    {
        $this->get(route('robots'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_hsts_is_sent_only_for_secure_production_requests(): void
    {
        $this->app['env'] = 'production';

        $this->call('GET', 'https://localhost/robots.txt', server: ['HTTPS' => 'on'])
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');

        $this->call('GET', 'http://localhost/robots.txt', server: ['HTTPS' => 'off'])
            ->assertHeaderMissing('Strict-Transport-Security');
    }
}
