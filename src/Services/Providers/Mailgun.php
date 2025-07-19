<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\MailgunExternalMailProviderException;
use KolayBi\Validation\Mail\Services\ExternalMailProviderInterface;

readonly class Mailgun implements ExternalMailProviderInterface
{
    public function __construct(
        private array $config,
    ) {}

    public function isReal(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'api_key'              => Arr::get($this->config, 'api_key'),
                'address'              => $mail,
                'mailbox_verification' => 'true',
            ])
            ->value();

        $result = Http::get($url)->json();

        if (!array_key_exists('message', $result)) {
            return Arr::get($result, 'is_valid')
                && !Arr::get($result, 'is_disposable_address')
                && 'false' !== Arr::get($result, 'mailbox_verification');
        }

        throw new MailgunExternalMailProviderException(Arr::get($result, 'message', ''));
    }
}
