<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\AbstractApiExternalMailProviderException;

readonly class AbstractApi implements ExternalMailProviderInterface
{
    use ProviderTrait;

    private const string UNDELIVERABLE = 'UNDELIVERABLE';

    /**
     * @throws AbstractApiExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'api_key'      => Arr::get($this->config, 'api_key'),
                'email'        => $mail,
                'auto_correct' => 'false',
            ])
            ->value();

        $result = Http::get($url)->json();

        if (array_key_exists('deliverability', $result)) {
            return self::UNDELIVERABLE !== Arr::get($result, 'deliverability');
        }

        throw new AbstractApiExternalMailProviderException(Arr::get($result, 'error.message', ''));
    }
}
