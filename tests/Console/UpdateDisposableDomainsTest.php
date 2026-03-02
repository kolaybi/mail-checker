<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Http::preventStrayRequests();
    Storage::fake('local');
});

it('fetches and saves disposable domains', function () {
    Http::fake([
        '*' => Http::response(['tempmail.com', 'disposable.org']),
    ]);

    $this->artisan('mail-checker:update-disposable-domains')
        ->assertSuccessful()
        ->expectsOutputToContain('updated successfully');

    expect(Storage::exists('data/domains/disposable_domains.json'))->toBeTrue();

    $saved = Storage::json('data/domains/disposable_domains.json');

    expect($saved)->toContain('disposable.org')
        ->toContain('tempmail.com');
});

it('fails when remote returns empty', function () {
    Http::fake([
        '*' => Http::response([]),
    ]);

    $this->artisan('mail-checker:update-disposable-domains')
        ->assertExitCode(2);
});

it('fails when save returns false', function () {
    Http::fake([
        '*' => Http::response(['tempmail.com', 'disposable.org']),
    ]);

    Storage::shouldReceive('put')->andReturn(false);

    $this->artisan('mail-checker:update-disposable-domains')
        ->assertFailed()
        ->expectsOutputToContain('Could not write');
});

it('fails when remote request throws', function () {
    Http::fake([
        '*' => Http::response([], 500),
    ]);

    $this->artisan('mail-checker:update-disposable-domains')
        ->assertExitCode(2);
});
