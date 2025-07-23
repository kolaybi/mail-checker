<?php

use KolayBi\Validation\Mail\Services\Providers\AbstractApi;
use KolayBi\Validation\Mail\Services\Providers\MailboxLayer;
use KolayBi\Validation\Mail\Services\Providers\Mailgun;

return [
    'local'    => [
        'cache'      => [
            'enabled' => (bool) env('MAIL_CHECKER_LOCAL_CACHE_ENABLED', true),
            'ttl'     => (int) env('MAIL_CHECKER_LOCAL_CACHE_TTL', 60 * 60 * 24 * 7), // 1 week
            'store'   => env('MAIL_CHECKER_LOCAL_CACHE_STORE'),
        ],
        'whitelist'  => [
            'storage_path' => env('MAIL_CHECKER_WHITELIST_STORAGE_PATH', 'data/domains/whitelisted_domains.json'),
        ],
        'blacklist'  => [
            'storage_path' => env('MAIL_CHECKER_BLACKLIST_STORAGE_PATH', 'data/domains/blacklisted_domains.json'),
        ],
        'disposable' => [
            'storage_path' => env('MAIL_CHECKER_DISPOSABLE_STORAGE_PATH', 'data/domains/disposable_domains.json'),
            'url'          => env('MAIL_CHECKER_DISPOSABLE_URL', 'https://rawgit.com/andreis/disposable-email-domains/master/domains.json'),
        ],
    ],
    'external' => [
        'cache'     => [
            'enabled' => (bool) env('MAIL_CHECKER_EXTERNAL_CACHE_ENABLED', true),
            'ttl'     => (int) env('MAIL_CHECKER_EXTERNAL_CACHE_TTL', 60 * 60 * 24), // 1 day
            'store'   => env('MAIL_CHECKER_EXTERNAL_CACHE_STORE'),
        ],
        'priority'  => explode(
            ',',
            env(
                'MAIL_CHECKER_EXTERNAL_PROVIDER_PRIORITY',
                'abstract_api,mailboxlayer,mailgun',
            ),
        ),
        'providers' => [
            'abstract_api' => [
                'resolver' => AbstractApi::class,
                'config'   => [
                    'endpoint' => env('ABSTRACT_API_EMAIL_ENDPOINT'),
                    'api_key'  => env('ABSTRACT_API_EMAIL_API_KEY'),
                    'timeout'  => (int) env('ABSTRACT_API_EMAIL_TIMEOUT', 10),
                ],
            ],
            'mailboxlayer' => [
                'resolver' => MailboxLayer::class,
                'config'   => [
                    'endpoint'   => env('MAILBOX_LAYER_ENDPOINT'),
                    'access_key' => env('MAILBOX_LAYER_ACCESS_KEY'),
                    'timeout'    => (int) env('MAILBOX_LAYER_TIMEOUT', 10),
                ],
            ],
            'mailgun'      => [
                'resolver' => Mailgun::class,
                'config'   => [
                    'endpoint' => env('MAILGUN_VALIDATION_ENDPOINT'),
                    'api_key'  => env('MAILGUN_API_KEY'),
                    'timeout'  => (int) env('MAILGUN_TIMEOUT', 10),
                ],
            ],
        ],
    ],
];
