<?php

declare(strict_types=1);

namespace App\Enums;

enum BnbaRowStatus: string
{
    case VALID = 'valid';
    case WARNING = 'warning';
    case INVALID = 'invalid';
    case DUPLICATE = 'duplicate';
}