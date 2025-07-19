<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\AbstractApiExternalMailProviderException;
use KolayBi\Validation\Mail\Services\ExternalMailProviderInterface;

readonly class AbstractApi implements ExternalMailProviderInterface
{
    private const string UNDELIVERABLE = 'UNDELIVERABLE';

    public function __construct(
        private array $config,
    ) {}

    public function isReal(string $mail): bool
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
