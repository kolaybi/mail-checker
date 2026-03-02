<?php

namespace KolayBi\Validation\Mail\Services\Providers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use KolayBi\Validation\Mail\Exceptions\MailgunExternalMailProviderException;

readonly class Mailgun implements ExternalMailProviderInterface
{
    use ProviderTrait;

    /**
     * @throws MailgunExternalMailProviderException
     */
    private function performValidation(string $mail): bool
    {
        $url = Uri::of(Arr::get($this->config, 'endpoint'))
            ->withQuery([
                'address'              => $mail,
                'mailbox_verification' => 'true',
            ])
            ->value();

        $response = Http::withBasicAuth('api', Arr::get($this->config, 'api_key'))
            ->timeout(Arr::get($this->config, 'timeout'))
            ->get($url);

        if (!$response->successful()) {
            throw new MailgunExternalMailProviderException('HTTP request failed', $response->status());
        }

        $result = $response->json();

        if (!array_key_exists('message', $result)) {
            return Arr::get($result, 'is_valid')
                && !Arr::get($result, 'is_disposable_address')
                && 'false' !== Arr::get($result, 'mailbox_verification');
        }

        throw new MailgunExternalMailProviderException(Arr::get($result, 'message', 'Unknown error'));
    }
}
