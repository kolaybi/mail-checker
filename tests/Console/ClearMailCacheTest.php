<?php

it('clears all caches by default', function () {
    $this->artisan('mail-checker:cache-clear')
        ->assertSuccessful()
        ->expectsOutputToContain('all cache cleared');
});

it('clears local cache only', function () {
    $this->artisan('mail-checker:cache-clear', ['--type' => 'local'])
        ->assertSuccessful()
        ->expectsOutputToContain('local cache cleared');
});

it('clears external cache only', function () {
    $this->artisan('mail-checker:cache-clear', ['--type' => 'external'])
        ->assertSuccessful()
        ->expectsOutputToContain('external cache cleared');
});
