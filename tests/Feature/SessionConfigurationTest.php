<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionConfigurationTest extends TestCase
{
    public function test_production_session_cookies_are_secure_by_default(): void
    {
        $environmentKeys = ['APP_ENV', 'SESSION_SECURE_COOKIE'];
        $previousEnvironment = [];

        foreach ($environmentKeys as $key) {
            $previousEnvironment[$key] = [
                'env' => array_key_exists($key, $_ENV),
                'env_value' => $_ENV[$key] ?? null,
                'server' => array_key_exists($key, $_SERVER),
                'server_value' => $_SERVER[$key] ?? null,
                'process' => getenv($key),
            ];
        }

        try {
            $_ENV['APP_ENV'] = 'production';
            $_SERVER['APP_ENV'] = 'production';
            unset($_ENV['SESSION_SECURE_COOKIE'], $_SERVER['SESSION_SECURE_COOKIE']);
            putenv('APP_ENV=production');
            putenv('SESSION_SECURE_COOKIE');

            $sessionConfiguration = require base_path('config/session.php');
        } finally {
            foreach ($previousEnvironment as $key => $previous) {
                if ($previous['env']) {
                    $_ENV[$key] = $previous['env_value'];
                } else {
                    unset($_ENV[$key]);
                }

                if ($previous['server']) {
                    $_SERVER[$key] = $previous['server_value'];
                } else {
                    unset($_SERVER[$key]);
                }

                if ($previous['process'] === false) {
                    putenv($key);
                } else {
                    putenv($key.'='.$previous['process']);
                }
            }
        }

        $this->assertTrue($sessionConfiguration['secure']);
    }
}
