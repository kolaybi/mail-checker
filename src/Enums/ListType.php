<?php

namespace KolayBi\Validation\Mail\Enums;

enum ListType: string
{
    case BLACKLIST = 'blacklist';
    case WHITELIST = 'whitelist';
    case DISPOSABLE = 'disposable';
}
