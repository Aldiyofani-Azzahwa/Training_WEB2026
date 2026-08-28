<?php

declare(strict_types=1);

namespace App\Enums;

enum BpntReportStatus: string
{
    case FINAL = 'final';

    public function label(): string
    {
        return match ($this) {
            self::FINAL => 'Final',
        };
    }
}