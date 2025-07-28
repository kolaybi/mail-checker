<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\HunterExternalMailProviderException;

readonly class Hunter implements ExternalMailProviderInterface
{
    use ProviderTrait;

    private const array VALID_STATUSES = [
        'valid',
        'accept_all',
        'webmail',
    ];

    /**
     * @throws HunterExternalMailProviderException
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
            throw new HunterExternalMailProviderException('HTTP request failed', $response->status());
        }

        $result = $response->json();

        if (array_key_exists('data', $result)) {
            return in_array(Arr::get($result, 'data.status'), self::VALID_STATUSES);
        }

        throw new HunterExternalMailProviderException(Arr::get($result, 'message', 'Unknown error'));
    }
}
