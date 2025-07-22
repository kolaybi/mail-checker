<?php

namespace KolayBi\Validation\Mail\Enums;

enum ServiceType: string
{
    case LOCAL = 'local';
    case EXTERNAL = 'external';
    case ALL = 'all';
}
