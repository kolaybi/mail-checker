<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\NeverBounceExternalMailProviderException;

readonly class NeverBounce implements ExternalMailProviderInterface
{
    use ProviderTrait;

    private const string VALID = 'valid';
    private const string CATCH_ALL = 'catchall';

    /**
     * @throws NeverBounceExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'key'   => Arr::get($this->config, 'api_key'),
                'email' => $mail,
            ])
            ->value();

        $response = Http::timeout(Arr::get($this->config, 'timeout'))->get($url);

        if (!$response->successful()) {
            throw new NeverBounceExternalMailProviderException('HTTP request failed', $response->status());
        }

        $result = $response->json();

        if (array_key_exists('result', $result)) {
            return in_array(Arr::get($result, 'result'), [self::VALID, self::CATCH_ALL]);
        }

        throw new NeverBounceExternalMailProviderException(Arr::get($result, 'message', 'Unknown error'));
    }
}
