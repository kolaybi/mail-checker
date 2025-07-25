<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\AbstractApiExternalMailProviderException;

readonly class Clearout implements ExternalMailProviderInterface
{
    use ProviderTrait;

    private const string VALID = 'valid';

    /**
     * @throws AbstractApiExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'email' => $mail,
            ])
            ->value();

        $response = Http::withToken(Arr::get($this->config, 'api_key'), '')
            ->timeout(Arr::get($this->config, 'timeout'))
            ->post($url);

        if (!$response->successful()) {
            throw new AbstractApiExternalMailProviderException('HTTP request failed', $response->status());
        }

        $result = $response->json();

        if (array_key_exists('data', $result)) {
            return self::VALID === Arr::get($result, 'data.status');
        }

        throw new AbstractApiExternalMailProviderException('Unknown error');
    }
}
