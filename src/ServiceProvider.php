<?php

namespace KolayBi\Validation\Mail;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use KolayBi\Validation\Mail\Console\ClearMailCache;
use KolayBi\Validation\Mail\Console\UpdateDisposableDomains;
use KolayBi\Validation\Mail\Console\UpdateDomains;

class ServiceProvider extends IlluminateServiceProvider implements DeferrableProvider
{
    public function boot(): void
    {
        $this->bootConfig();
        $this->bootCommands();
    }

    public function register(): void
    {
        // Register the main service
        $this->app->singleton(MailChecker::class, fn($app) => new MailChecker());

        $this->mergeConfigFrom(__DIR__ . '/../config/mail-checker.php', 'mail-checker');
    }

    public function provides(): array
    {
        return [
            MailChecker::class,
            ClearMailCache::class,
            UpdateDisposableDomains::class,
            UpdateDomains::class,
        ];
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
                UpdateDisposableDomains::class,
                UpdateDomains::class,
            ]);

            $this->optimizes(
                clear: 'mail-checker:cache-clear',
                key: 'mail-checker',
            );
        }
    }
}
