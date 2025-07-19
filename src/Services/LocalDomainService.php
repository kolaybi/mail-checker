<?php

namespace KolayBi\Validation\Mail\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

class LocalDomainService
{
    private array $config;

    public function __construct()
    {
        $this->config = Config::get('mail-checker.services.local');
    }

    public function isWhitelisted(string $mail): bool
    {
        $domains = $this->getDomains(Arr::get($this->config, 'whitelist.storage_path'));

        return $this->contains($mail, $domains);
    }

    public function isBlacklisted(string $mail): bool
    {
        $domains = $this->getDomains(Arr::get($this->config, 'blacklist.storage_path'));

        return $this->contains($mail, $domains);
    }

    public function isDisposable(string $mail): bool
    {
        $domains = $this->getDomains(Arr::get($this->config, 'disposable.storage_path'));

        return $this->contains($mail, $domains);
    }

    private function getDomains(string $filePath): array
    {
        return Storage::json($filePath) ?? [];
    }

    /**
     * Check whether the given mail address' domain matches one from the given domains
     */
    private function contains(string $mail, array $domains): bool
    {
        // Parse the mail to its top level domain.
        preg_match(
            '/([^.\/]+)(\.[^.\/]+)((\.[^.\/]+)+)?$/',
            explode('@', $mail, 2)[1] ?? '',
            $domain,
        );

        // Just ignore this validator if the value doesn't even resemble a mail or domain.
        if (0 === count($domain)) {
            return false;
        }

        return in_array($domain[0], $domains);
    }
}
