<?php

return [
    'services' => [
        'local' => [
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
    ],
];
