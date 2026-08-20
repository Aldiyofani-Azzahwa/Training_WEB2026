<?php

declare(strict_types=1);

namespace App\Services\Wilayah;

use App\Contracts\Repositories\WilayahRepositoryInterface;
use Illuminate\Support\Collection;

final class WilayahService
{
    public function __construct(
        private readonly WilayahRepositoryInterface $wilayah,
    ) {}

    /**
     * @return array{
     *     kecamatans: Collection,
     *     kecamatans_count: int,
     *     kelurahans_count: int
     * }
     */
    public function master(): array
    {
        $kecamatans =
            $this->wilayah
                ->allWithKelurahans();

        return [
            'kecamatans'
                => $kecamatans,

            'kecamatans_count'
                => $kecamatans
                    ->count(),

            'kelurahans_count'
                => (int) $kecamatans
                    ->sum(
                        static fn (
                            $kecamatan
                        ): int =>
                            (int) $kecamatan
                                ->kelurahans_count
                    ),
        ];
    }
}