<?php

namespace KolayBi\Validation\Mail\Console;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use KolayBi\Validation\Mail\Enums\ListType;
use KolayBi\Validation\Mail\Enums\ServiceType;

class UpdateDomains extends Command
{
    protected $signature = 'mail-checker:update-domains
                            {--type= : Domain list type (whitelist|blacklist)}
                            {--add=* : Add domains to list}
                            {--remove=* : Remove domains from list}
                            {--list : List all domains in the specified type}';

    protected $description = 'Manage whitelisted and blacklisted email domains for mail checker';

    private string $storagePath;

    private ?string $listType;

    public function handle(): int
    {
        $this->listType = $this->option('type');

        // Validate list type
        if (!in_array($this->listType, [ListType::WHITELIST->value, ListType::BLACKLIST->value])) {
            $this->error('Invalid type. Must be either "whitelist" or "blacklist".');

            return Command::FAILURE;
        }

        // Get storage path from config based on type
        $this->storagePath = Config::get("kolaybi.mail-checker.local.{$this->listType}.storage_path");

        // Validate storage path configuration
        if (empty($this->storagePath)) {
            $this->error("{$this->getListTypeLabel()} storage path not configured. Please check your mail-checker config.");

            return Command::FAILURE;
        }

        $addDomains = $this->option('add') ?? [];
        $removeDomains = $this->option('remove') ?? [];
        $listDomains = $this->option('list');

        // Validate that at least one option is provided
        if (empty($addDomains) && empty($removeDomains) && !$listDomains) {
            $this->error('Please specify at least one option: --add, --remove, or --list');
            $this->line('Use --help for usage information.');

            return Command::FAILURE;
        }

        // Handle list operation first (can be combined with other operations)
        if ($listDomains) {
            $this->listDomains();

            // If only listing, return early
            if (empty($addDomains) && empty($removeDomains)) {
                return Command::SUCCESS;
            }

            $this->newLine();
        }

        // Handle add operations
        if (!empty($addDomains)) {
            $this->addDomains($addDomains);

            if (!empty($removeDomains)) {
                $this->newLine();
            }
        }

        // Handle remove operations
        if (!empty($removeDomains)) {
            $this->removeDomains($removeDomains);
        }

        // Show final status if multiple operations were performed
        if (count(array_filter([$addDomains, $removeDomains, $listDomains])) > 1) {
            $this->newLine();
            $this->info('All operations completed successfully.');
        }

        Artisan::call(
            'mail-checker:cache-clear',
            ['--type' => ServiceType::LOCAL->value],
        );

        return Command::SUCCESS;
    }

    private function addDomains(array $domains): void
    {
        $validDomains = $this->validateDomains($domains);

        if (empty($validDomains)) {
            $this->error('No valid domains to add.');

            return;
        }

        $this->info("Adding domains to {$this->listType}: " . implode(', ', $validDomains));

        $existingDomains = (array) (Storage::json($this->storagePath) ?? []);

        // Check for duplicates before adding
        $newDomains = array_diff($validDomains, $existingDomains);

        if (empty($newDomains)) {
            $this->warn("All specified domains are already in the {$this->listType}.");

            return;
        }

        $updatedDomains = array_unique(array_merge($existingDomains, $newDomains));

        if (!$this->save($updatedDomains)) {
            $this->error('Could not save domains to storage.');

            return;
        }

        $addedCount = count($newDomains);
        $skippedCount = count($validDomains) - $addedCount;

        $this->info("Successfully added {$addedCount} domain(s) to {$this->listType}.");

        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} domain(s) that were already {$this->listType}ed.");
        }
    }

    private function removeDomains(array $domains): void
    {
        $validDomains = $this->validateDomains($domains);

        if (empty($validDomains)) {
            $this->error('No valid domains to remove.');

            return;
        }

        $this->info("Removing domains from {$this->listType}: " . implode(', ', $validDomains));

        $existingDomains = (array) (Storage::json($this->storagePath) ?? []);

        if (empty($existingDomains)) {
            $this->warn("{$this->getListTypeLabel()} is empty. No domains to remove.");

            return;
        }

        // Check which domains actually exist in the list
        $domainsToRemove = array_intersect($validDomains, $existingDomains);

        if (empty($domainsToRemove)) {
            $this->warn("None of the specified domains are currently {$this->listType}ed.");

            return;
        }

        $updatedDomains = array_diff($existingDomains, $domainsToRemove);

        if (!$this->save($updatedDomains)) {
            $this->error('Could not save domains to storage.');

            return;
        }

        $removedCount = count($domainsToRemove);
        $notFoundCount = count($validDomains) - $removedCount;

        $this->info("Successfully removed {$removedCount} domain(s) from {$this->listType}.");

        if ($notFoundCount > 0) {
            $this->warn("Skipped {$notFoundCount} domain(s) that were not in the {$this->listType}.");
        }
    }

    private function listDomains(): void
    {
        $domains = (array) (Storage::json($this->storagePath) ?? []);

        if (empty($domains)) {
            $this->warn("No domains found in {$this->listType}.");
            $this->line("Use --add to add domains to the {$this->listType}.");

            return;
        }

        $this->info("Current {$this->listType}ed domains:");
        $this->newLine();

        // Sort domains for consistent display
        sort($domains);

        // Display domains in a numbered list
        foreach ($domains as $index => $domain) {
            $this->line(sprintf('%3d. %s', $index + 1, $domain));
        }

        $this->newLine();
        $this->info('Total domains: ' . count($domains));
        $this->line('Storage location: ' . $this->storagePath);
    }

    private function validateDomains(array $domains): array
    {
        $validDomains = [];
        $invalidDomains = [];

        foreach ($domains as $domain) {
            $sanitizedDomain = strtolower(trim($domain));

            if ($this->isValidDomain($sanitizedDomain)) {
                $validDomains[] = $sanitizedDomain;
            } else {
                $invalidDomains[] = $domain;
            }
        }

        if (!empty($invalidDomains)) {
            $this->warn('Invalid domains skipped: ' . implode(', ', $invalidDomains));
        }

        return array_unique($validDomains);
    }

    private function isValidDomain(string $domain): bool
    {
        // Check if domain is not empty
        if (empty($domain)) {
            return false;
        }

        // Check domain length (max 253 characters for FQDN)
        if (strlen($domain) > 253) {
            return false;
        }

        // Check for valid domain format using filter_var
        if (!filter_var('test@' . $domain, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Additional regex check for proper domain format
        $domainPattern = '/^[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?(\.[a-z0-9]([a-z0-9\-]{0,61}[a-z0-9])?)*$/i';

        if (!preg_match($domainPattern, $domain)) {
            return false;
        }

        // Check for minimum domain structure (at least one dot for TLD)
        return !(substr_count($domain, '.') < 1);
    }

    private function save(array $domains): bool
    {
        try {
            // Sort domains alphabetically for consistent ordering
            sort($domains);

            // Ensure storage directory exists
            $this->ensureStorageDirectoryExists();

            // Convert to JSON with pretty formatting
            $jsonData = json_encode(array_values($domains), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if (false === $jsonData) {
                $this->error('Failed to encode domains to JSON.');

                return false;
            }

            // Use Storage facade to save the file
            $saved = Storage::put($this->storagePath, $jsonData);

            if (!$saved) {
                $this->error('Failed to write domains to storage file.');

                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->error('Storage error: ' . $e->getMessage());

            return false;
        }
    }

    private function ensureStorageDirectoryExists(): void
    {
        $directory = dirname($this->storagePath);

        if ($directory && '.' !== $directory && !Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }
    }

    private function getListTypeLabel(): string
    {
        return ucfirst($this->listType);
    }
}
