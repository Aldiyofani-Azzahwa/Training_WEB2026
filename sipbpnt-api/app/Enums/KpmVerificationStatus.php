<?php

declare(strict_types=1);

namespace App\Enums;

enum KpmVerificationStatus: string
{
    case DECEASED = 'deceased';
    case MOVED_DOMICILE = 'moved_domicile';
    case NOT_CLAIMED = 'not_claimed';

    public function label(): string
    {
        return match ($this) {
            self::DECEASED => 'Meninggal',
            self::MOVED_DOMICILE => 'Pindah Domisili',
            self::NOT_CLAIMED => 'Tidak Mengambil',
        };
    }
}