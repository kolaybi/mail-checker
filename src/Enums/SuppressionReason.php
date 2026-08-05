<?php

namespace KolayBi\Validation\Mail\Enums;

enum SuppressionReason: string
{
    case BOUNCE = 'bounce';
    case COMPLAINT = 'complaint';
    case MANUAL = 'manual';
}
