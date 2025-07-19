<?php

return [
    'local'    => [
        'whitelist'  => [
            'storage_path' => env('MAIL_CHECKER_WHITELIST_STORAGE_PATH', 'data/domains/whitelisted_domains.json'),
        ],
        'blacklist'  => [
            'storage_path' => env('MAIL_CHECKER_BLACKLIST_STORAGE_PATH', 'data/domains/blacklisted_domains.json'),
        ],
        'disposable' => [
            'storage_path' => env('MAIL_CHECKER_DISPOSABLE_STORAGE_PATH', 'data/domains/disposable_domains.json'),
        ],
    ],
    'external' => [
        'priority'  => explode(
            ',',
            env(
                'MAIL_CHECKER_EXTERNAL_PROVIDER_PRIORITY',
                'abstract_api,mailboxlayer,mailgun',
            ),
        ),
        'providers' => [
            'abstract_api' => [
                'resolver' => '',
                'config'   => [
                    'endpoint' => env('ABSTRACT_API_EMAIL_ENDPOINT'),
                    'api_key'  => env('ABSTRACT_API_EMAIL_API_KEY'),
                ],
            ],
            'mailboxlayer' => [
                'resolver' => '',
                'config'   => [
                    'endpoint'   => env('MAILBOX_LAYER_ENDPOINT'),
                    'access_key' => env('MAILBOX_LAYER_ACCESS_KEY'),
                ],
            ],
            'mailgun'      => [
                'resolver' => '',
                'config'   => [
                    'endpoint' => env('MAILGUN_VALIDATION_ENDPOINT'),
                    'secret'   => env('MAILGUN_SECRET'),
                ],
            ],
        ],
    ],
];
