<?php

use KolayBi\Validation\Mail\Services\Providers\AbstractApi;
use KolayBi\Validation\Mail\Services\Providers\Bouncer;
use KolayBi\Validation\Mail\Services\Providers\Emailable;
use KolayBi\Validation\Mail\Services\Providers\Hunter;
use KolayBi\Validation\Mail\Services\Providers\Kickbox;
use KolayBi\Validation\Mail\Services\Providers\MailboxLayer;
use KolayBi\Validation\Mail\Services\Providers\Mailgun;
use KolayBi\Validation\Mail\Services\Providers\NeverBounce;

$globalTimeout = (int) env('MAIL_CHECKER_EXTERNAL_TIMEOUT', 10);

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
        'cache'                => [
            'enabled' => (bool) env('MAIL_CHECKER_EXTERNAL_CACHE_ENABLED', true),
            'ttl'     => (int) env('MAIL_CHECKER_EXTERNAL_CACHE_TTL', 60 * 60 * 24), // 1 day
            'store'   => env('MAIL_CHECKER_EXTERNAL_CACHE_STORE'),
        ],
        'fail_if_no_providers' => (bool) env('MAIL_CHECKER_FAIL_IF_NO_PROVIDERS', true),
        'priority'             => explode(
            ',',
            env(
                'MAIL_CHECKER_EXTERNAL_PROVIDER_PRIORITY',
                'abstract_api,mailboxlayer,mailgun,neverbounce,emailable,hunter,kickbox,bouncer',
            ),
        ),
        'providers'            => [
            'abstract_api' => [
                'resolver' => AbstractApi::class,
                'config'   => [
                    'endpoint' => env('ABSTRACT_API_EMAIL_ENDPOINT', 'https://emailvalidation.abstractapi.com/v1/'),
                    'api_key'  => env('ABSTRACT_API_EMAIL_API_KEY'),
                    'timeout'  => (int) env('ABSTRACT_API_EMAIL_TIMEOUT', $globalTimeout),
                ],
            ],
            'bouncer'      => [
                'resolver' => Bouncer::class,
                'config'   => [
                    'endpoint' => env('BOUNCER_ENDPOINT', 'https://api.usebouncer.com/v1.1/email/verify'),
                    'api_key'  => env('BOUNCER_API_KEY'),
                    'timeout'  => (int) env('BOUNCER_TIMEOUT', $globalTimeout),
                ],
            ],
            'emailable'    => [
                'resolver' => Emailable::class,
                'config'   => [
                    'endpoint' => env('EMAILABLE_ENDPOINT', 'https://api.emailable.com/v1/verify'),
                    'api_key'  => env('EMAILABLE_API_KEY'),
                    'timeout'  => (int) env('EMAILABLE_TIMEOUT', $globalTimeout),
                ],
            ],
            'hunter'       => [
                'resolver' => Hunter::class,
                'config'   => [
                    'endpoint' => env('HUNTER_VALIDATION_ENDPOINT', 'https://api.hunter.io/v2/email-verifier'),
                    'api_key'  => env('HUNTER_API_KEY'),
                    'timeout'  => (int) env('HUNTER_TIMEOUT', $globalTimeout),
                ],
            ],
            'kickbox'      => [
                'resolver' => Kickbox::class,
                'config'   => [
                    'endpoint' => env('KICKBOX_ENDPOINT', 'https://api.kickbox.com/v2/verify'),
                    'api_key'  => env('KICKBOX_API_KEY'),
                    'timeout'  => (int) env('KICKBOX_TIMEOUT', $globalTimeout),
                ],
            ],
            'mailboxlayer' => [
                'resolver' => MailboxLayer::class,
                'config'   => [
                    'endpoint'   => env('MAILBOX_LAYER_ENDPOINT', 'https://apilayer.net/api/check'),
                    'access_key' => env('MAILBOX_LAYER_ACCESS_KEY'),
                    'timeout'    => (int) env('MAILBOX_LAYER_TIMEOUT', $globalTimeout),
                ],
            ],
            'mailgun'      => [
                'resolver' => Mailgun::class,
                'config'   => [
                    'endpoint' => env('MAILGUN_VALIDATION_ENDPOINT', 'https://api.mailgun.net/v4/address/validate'),
                    'api_key'  => env('MAILGUN_API_KEY'),
                    'timeout'  => (int) env('MAILGUN_TIMEOUT', $globalTimeout),
                ],
            ],
            'neverbounce'  => [
                'resolver' => NeverBounce::class,
                'config'   => [
                    'endpoint' => env('NEVER_BOUNCE_VALIDATION_ENDPOINT', 'https://api.neverbounce.com/v4/single/check'),
                    'api_key'  => env('NEVER_BOUNCE_API_KEY'),
                    'timeout'  => (int) env('NEVER_BOUNCE_TIMEOUT', $globalTimeout),
                ],
            ],
        ],
    ],
];
