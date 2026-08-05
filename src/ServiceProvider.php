<?php

namespace KolayBi\Validation\Mail;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use KolayBi\Validation\Mail\Console\ClearMailCache;
use KolayBi\Validation\Mail\Console\ImportSuppressions;
use KolayBi\Validation\Mail\Console\SuppressMail;
use KolayBi\Validation\Mail\Console\UnsuppressMail;
use KolayBi\Validation\Mail\Console\UpdateDisposableDomains;
use KolayBi\Validation\Mail\Console\UpdateDomains;

class ServiceProvider extends IlluminateServiceProvider
{
    public function boot(): void
    {
        $this->bootConfig();
        $this->bootCommands();
        $this->bootMigrations();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mail-checker.php', 'kolaybi.mail-checker');

        if (!$this->app['config']->has('cache.stores.mail-checker')) {
            $this->app['config']->set('cache.stores.mail-checker', [
                'driver' => 'file',
                'path'   => storage_path('framework/cache/mail-checker'),
            ]);
        }
    }

    private function bootConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../config/mail-checker.php' => $this->app->configPath('kolaybi/mail-checker.php'),
        ], 'mail-checker-config');
    }

    private function bootCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearMailCache::class,
                ImportSuppressions::class,
                SuppressMail::class,
                UnsuppressMail::class,
                UpdateDisposableDomains::class,
                UpdateDomains::class,
            ]);

            $this->optimizes(
                clear: 'mail-checker:cache-clear',
                key: 'mail-checker',
            );
        }
    }

    private function bootMigrations(): void
    {
        $this->publishesMigrations([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'mail-checker-migrations');
    }
}
