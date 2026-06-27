<?php

namespace Tests;

use RuntimeException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->forceSafeTestingDatabase();

        parent::setUp();
    }

    private function forceSafeTestingDatabase(): void
    {
        $configCachePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';

        if (file_exists($configCachePath)) {
            throw new RuntimeException('Config cache mavjud. Testlarni ishga tushirishdan oldin php artisan config:clear qiling.');
        }

        foreach ([
            'APP_ENV'       => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE'   => ':memory:',
        ] as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}
