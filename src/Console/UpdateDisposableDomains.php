<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use KolayBi\Validation\Mail\Enums\ServiceType;
use Throwable;

class UpdateDisposableDomains extends Command
{
    protected $signature = 'mail-checker:update-disposable-domains';

    protected $description = 'Update the disposable mail domains list';

    private array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('kolaybi.mail-checker.local.disposable', []);
    }

    public function handle(): int
    {
        $domains = $this->fetchDomains();
        if (empty($domains)) {
            return Command::INVALID;
        }

        if (!$this->save($domains)) {
            $this->error('Could not write disposable domains to storage. Aborting...');

            return Command::FAILURE;
        }

        Artisan::call(
            'mail-checker:cache-clear',
            ['--type' => ServiceType::LOCAL->value],
        );

        $this->info('Disposable domains list has been updated successfully.');

        return Command::SUCCESS;
    }

    private function fetchDomains(): array
    {
        $remoteUrl = Arr::get($this->config, 'url');

        $this->info("Fetching disposable domains from {$remoteUrl}");

        try {
            $content = Http::get($remoteUrl)
                ->throw()
                ->json();

            if (!empty($content)) {
                return $content;
            }

            $this->error("Unable to get disposable domains from ({$remoteUrl}).");
        } catch (Throwable) {
            $this->error("Failed to interpret the URL ({$remoteUrl}) while fetching disposable domains.");
        }

        return [];
    }

    private function save(array $data): bool
    {
        $storagePath = Arr::get($this->config, 'storage_path');

        $this->info("Saving disposable domains to {$storagePath}");

        sort($data);

        return Storage::put(
            $storagePath,
            json_encode(array_values($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }
}
