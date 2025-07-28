<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\BouncerExternalMailProviderException;

readonly class Bouncer implements ExternalMailProviderInterface
{
    use ProviderTrait;

    private const string UNDELIVERABLE = 'UNDELIVERABLE';

    /**
     * @throws BouncerExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'email' => $mail,
            ])
            ->value();

        $response = Http::withHeader('x-api-key', Arr::get($this->config, 'api_key'))
            ->timeout(Arr::get($this->config, 'timeout'))
            ->get($url);

        if (!$response->successful()) {
            throw new BouncerExternalMailProviderException('HTTP request failed', $response->status());
        }

        $result = $response->json();

        if (array_key_exists('status', $result)) {
            return self::UNDELIVERABLE !== Arr::get($result, 'state');
        }

        throw new BouncerExternalMailProviderException('Unknown error');
    }
}
