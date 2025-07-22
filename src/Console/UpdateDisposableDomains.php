<?php

namespace KolayBi\Validation\Mail\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateDisposableDomains extends Command implements Isolatable
{
    protected $signature = 'mail.disposable:update';

    protected $description = 'Update the disposable mail domains list';

    private array $config;

    private ?string $logChannel;

    public function __construct()
    {
        parent::__construct();

        $this->config = Config::get('mail-checker.local.disposable');
        $this->logChannel = Config::get('mail-checker.log_channel');
    }

    public function handle(): void
    {
        $domains = $this->fetchDomains();
        if (empty($domains)) {
            return;
        }

        if (!$this->save($domains)) {
            Log::channel($this->logChannel)
                ->error('Could not write disposable domains to storage. Aborting...');

            return;
        }

        Log::channel($this->logChannel)
            ->info('Disposable domains list has been updated successfully.');
    }

    private function fetchDomains(): array
    {
        $remoteUrl = Arr::get($this->config, 'url');

        Log::channel($this->logChannel)
            ->info("Fetching disposable domains from {$remoteUrl}");

        try {
            $content = Http::get($remoteUrl)
                ->throw()
                ->json();
            if (!empty($content)) {
                return $content;
            }

            Log::channel($this->logChannel)
                ->error("Unable to get disposable domains from ({$remoteUrl}).");
        } catch (Throwable) {
            Log::channel($this->logChannel)
                ->error("Failed to interpret the URL ({$remoteUrl}) while fetching disposable domains.");
        }

        return [];
    }

    private function save(array $data): bool
    {
        $storagePath = Arr::get($this->config, 'storage_path');

        Log::channel($this->logChannel)
            ->info("Saving disposable domains to {$storagePath}");

        return Storage::put($storagePath, json_encode($data));
    }
}
