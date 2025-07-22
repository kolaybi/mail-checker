<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateDisposableDomains extends Command implements Isolatable
{
    protected $signature = 'mail-checker:update-disposable-domains';

    protected $description = 'Update the disposable mail domains list';

    private array $config;

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('mail-checker.local.disposable');
    }

    public function handle(): void
    {
        $domains = $this->fetchDomains();
        if (empty($domains)) {
            return;
        }

        if (!$this->save($domains)) {
            $this->error('Could not write disposable domains to storage. Aborting...');

            return;
        }

        $this->info('Disposable domains list has been updated successfully.');
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

        return Storage::put($storagePath, json_encode($data));
    }
}
