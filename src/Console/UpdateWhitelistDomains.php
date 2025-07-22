<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class UpdateWhitelistDomains extends Command implements Isolatable
{
    protected $signature = 'mail.whitelist:update 
                            {domain* : Domain names to add or remove from whitelist} 
                            {--a|add : Add domain to whitelist} 
                            {--r|remove : Remove domain from whitelist}';

    protected $description = 'Update the whitelist mail domains list';

    private array $config;
    private string $storagePath;

    /**
     * @throws InvalidArgumentException When required configuration is missing
     */
    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('mail-checker.local.whitelist') ?? [];

        if (empty($this->config)) {
            throw new InvalidArgumentException('Mail checker whitelist configuration is missing');
        }

        $this->storagePath = Arr::get($this->config, 'storage_path');

        if (empty($this->storagePath)) {
            throw new InvalidArgumentException('Whitelist storage path configuration is missing');
        }

        // Validate storage path (prevent path traversal)
        if (preg_match('#(\.\./)#', $this->storagePath)) {
            throw new InvalidArgumentException('Invalid storage path detected - possible path traversal attempt');
        }
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

        $existingDomains = Storage::json($this->storagePath) ?? [];

        // Check for duplicates before adding
        $newDomains = array_diff($domains, $existingDomains);

        if (empty($newDomains)) {
            $this->warn('All specified domains are already in the whitelist.');

            return;
        }

        $updatedDomains = array_unique(array_merge($existingDomains, $newDomains));

        if (!$this->save($updatedDomains)) {
            $this->error('Could not save domains to storage.');

            return;
        }

        $addedCount = count($newDomains);
        $skippedCount = count($domains) - $addedCount;

        $this->info("Successfully added {$addedCount} domain(s) to whitelist.");

        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} domain(s) that were already whitelisted.");
        }
    }

    private function removeDomains(array $domains): void
    {
        $this->info('Removing domains: ' . implode(', ', $domains));

        $existingDomains = Storage::json($this->storagePath) ?? [];

        if (empty($existingDomains)) {
            $this->warn('Whitelist is empty. No domains to remove.');

            return;
        }

        // Check which domains actually exist in the list
        $domainsToRemove = array_intersect($domains, $existingDomains);

        if (empty($domainsToRemove)) {
            $this->warn('None of the specified domains are currently whitelisted.');

            return;
        }

        $updatedDomains = array_diff($existingDomains, $domainsToRemove);

        if (!$this->save($updatedDomains)) {
            $this->error('Could not save domains to storage.');

            return;
        }

        $removedCount = count($domainsToRemove);
        $notFoundCount = count($domains) - $removedCount;

        $this->info("Successfully removed {$removedCount} domain(s) from whitelist.");

        if ($notFoundCount > 0) {
            $this->warn("Skipped {$notFoundCount} domain(s) that were not in the whitelist.");
        }
    }

    private function save(array $data): bool
    {
        $this->info("Updating whitelist at {$this->storagePath}");

        return Storage::put($this->storagePath, json_encode($data));
    }
}
