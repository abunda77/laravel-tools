<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $this->guardAgainstUnsafeTestingDatabase($app);

        return $app;
    }

    private function guardAgainstUnsafeTestingDatabase(Application $app): void
    {
        $connectionName = config('database.default');
        $connection = config("database.connections.{$connectionName}", []);
        $driver = $connection['driver'] ?? null;
        $database = $connection['database'] ?? null;

        if ($app->environment('testing') && $driver === 'sqlite' && $database === ':memory:') {
            return;
        }

        throw new RuntimeException(
            'Unsafe test database configuration detected. Run php artisan config:clear and make sure phpunit.xml uses DB_CONNECTION=sqlite with DB_DATABASE=:memory: before running tests.',
        );
    }
}
