<?php

namespace KolayBi\Validation\Mail\Tests;

abstract class SuppressionTestCase extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('kolaybi.mail-checker.suppression.enabled', true);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
