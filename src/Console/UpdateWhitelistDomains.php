<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class UpdateWhitelistDomains extends Command implements Isolatable
{
    protected $signature = 'mail.whitelist:update 
                            {domain*} 
                            {--a|add : Add domain to whitelist} 
                            {--r|remove : Remove domain from whitelist}';

    protected $description = 'Update the whitelist mail domains list';

    private array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('mail-checker.local.whitelist');
    }

    public function handle(): void
    {
        $domains = Arr::wrap($this->argument('domain'));
        $shouldAdd = $this->option('add');
        $shouldRemove = $this->option('remove');

        if ($shouldAdd && $shouldRemove) {
            $this->error('Cannot specify both add and remove options.');

            return;
        }

        if (!$shouldAdd && !$shouldRemove) {
            $this->error('Must specify either --add (-a) or --remove (-r) option.');

            return;
        }

        if ($shouldAdd) {
            $this->addDomains($domains);
        } else {
            $this->removeDomains($domains);
        }
    }

    private function addDomains(array $domains): void
    {
        $this->info('Adding domains: ' . implode(', ', $domains));

        $storagePath = Arr::get($this->config, 'storage_path');
        $existingDomains = Storage::json($storagePath) ?? [];

        $updatedDomains = array_unique(array_merge($existingDomains, $domains));

        if (!$this->save($updatedDomains)) {
            $this->error('Could not save domains to storage.');

            return;
        }

        $this->info('Domains added successfully.');
    }

    private function removeDomains(array $domains): void
    {
        $this->info('Removing domains: ' . implode(', ', $domains));

        $storagePath = Arr::get($this->config, 'storage_path');
        $existingDomains = Storage::json($storagePath) ?? [];

        $updatedDomains = array_diff($existingDomains, $domains);

        if (!$this->save($updatedDomains)) {
            $this->error('Could not save domains to storage.');

            return;
        }

        $this->info('Domains removed successfully.');
    }

    private function save(array $data): bool
    {
        $storagePath = Arr::get($this->config, 'storage_path');

        $this->info("Updating whitelist at {$storagePath}");

        return Storage::put($storagePath, json_encode($data));
    }
}
