<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\EmailableExternalMailProviderException;

readonly class Emailable implements ExternalMailProviderInterface
{
    use ProviderTrait;

    private const string UNDELIVERABLE = 'UNDELIVERABLE';

    /**
     * @throws EmailableExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'api_key' => Arr::get($this->config, 'api_key'),
                'email'   => $mail,
            ])
            ->value();

        $response = Http::timeout(Arr::get($this->config, 'timeout'))->get($url);

        if (!$response->successful()) {
            throw new EmailableExternalMailProviderException('HTTP request failed', $response->status());
        }

        $result = $response->json();

        if (array_key_exists('state', $result)) {
            return self::UNDELIVERABLE !== Arr::get($result, 'state');
        }

        throw new EmailableExternalMailProviderException('Unknown error');
    }
}
