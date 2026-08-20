<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\WilayahRepositoryInterface;
use App\Models\Kecamatan;
use Illuminate\Support\Collection;

final class EloquentWilayahRepository
    implements WilayahRepositoryInterface
{
    public function allWithKelurahans(): Collection
    {
        return Kecamatan::query()
            ->withCount(
                'kelurahans'
            )
            ->with([
                'kelurahans'
                    => static function (
                        $query
                    ): void {
                        $query
                            ->orderBy(
                                'code'
                            )
                            ->orderBy(
                                'name'
                            );
                    },
            ])
            ->orderBy(
                'code'
            )
            ->get();
    }
}