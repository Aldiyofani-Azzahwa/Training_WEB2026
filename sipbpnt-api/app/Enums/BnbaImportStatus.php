<?php

declare(strict_types=1);

namespace App\Enums;

enum BnbaImportStatus: string
{
    case PREVIEW_READY = 'preview_ready';
    case CONFIRMED = 'confirmed';
    case FAILED = 'failed';
}