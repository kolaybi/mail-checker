<?php

use Illuminate\Support\Facades\Http;
use KolayBi\Validation\Mail\Services\Providers\AbstractApi;

it('works without cache service', function () {
    Http::preventStrayRequests();
    Http::fake([
        'emailvalidation.abstractapi.com/*' => Http::response(['deliverability' => 'DELIVERABLE']),
    ]);

    $provider = new AbstractApi([
        'endpoint' => 'https://emailvalidation.abstractapi.com/v1/',
        'api_key'  => 'test-key',
        'timeout'  => 5,
    ]);

    expect($provider->isReal('user@example.com'))->toBeTrue();
});
