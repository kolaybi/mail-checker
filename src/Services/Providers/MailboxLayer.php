<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\MailboxLayerExternalMailProviderException;

readonly class MailboxLayer implements ExternalMailProviderInterface
{
    use ProviderTrait;

    /**
     * @throws MailboxLayerExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'access_key' => Arr::get($this->config, 'access_key'),
                'email'      => $mail,
            ])
            ->value();

        $result = Http::get($url)->json();

        if (array_key_exists('format_valid', $result)) {
            return Arr::get($result, 'format_valid')
                && !Arr::get($result, 'disposable')
                && Arr::get($result, 'mx_found');
        }

        throw new MailboxLayerExternalMailProviderException(Arr::get($result, 'error.type', ''));
    }
}
