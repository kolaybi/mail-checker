<?php

namespace KolayBi\Validation\Mail\Tests;

use KolayBi\Validation\Mail\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('filesystems.disks.local.root', __DIR__ . '/fixtures');
    }
}
