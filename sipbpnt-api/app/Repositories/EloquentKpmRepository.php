<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\KpmRepositoryInterface;
use App\Models\BnbaImportRow;
use App\Models\Kpm;

final class EloquentKpmRepository
    implements KpmRepositoryInterface
{
    public function upsertFromImportRow(
        BnbaImportRow $row
    ): Kpm {
        $kpm =
            Kpm::query()
                ->firstOrNew([
                    'nik_hash'
                        => $row->nik_hash,
                ]);

        $kpm->fill([
            'nik_ciphertext'
                => $row->nik_ciphertext,

            'nkk_hash'
                => $row->nkk_hash,

            'nkk_ciphertext'
                => $row->nkk_ciphertext,

            'full_name'
                => $row->full_name,

            'birth_place'
                => $row->birth_place,

            'birth_date'
                => $row->birth_date,

            'mother_name'
                => $row->mother_name,

            'address'
                => $row->address,

            'rt'
                => $row->rt,

            'rw'
                => $row->rw,

            'kelurahan'
                => $row->kelurahan,

            'kecamatan'
                => $row->kecamatan,

            'account_ciphertext'
                => $row->account_ciphertext,
        ]);

        $kpm->save();

        return $kpm;
    }
}