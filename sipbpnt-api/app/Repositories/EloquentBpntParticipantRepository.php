<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BpntParticipantRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BnbaImport;
use App\Models\BnbaImportRow;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\Kelurahan;
use App\Models\Kpm;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class EloquentBpntParticipantRepository implements BpntParticipantRepositoryInterface
{
    /**
     * @var array<string, Kelurahan>|null
     */
    private ?array $wilayahMap = null;

    public function existingNikHashesForPeriod(
        int $periodId,
        array $nikHashes
    ): array {
        if ($nikHashes === []) {
            return [];
        }

        return BpntParticipant::query()
            ->join(
                'kpms',
                'kpms.id',
                '=',
                'bpnt_participants.kpm_id'
            )
            ->where(
                'bpnt_participants.bpnt_period_id',
                $periodId
            )
            ->whereIn(
                'kpms.nik_hash',
                array_values(
                    array_unique(
                        $nikHashes
                    )
                )
            )
            ->pluck(
                'kpms.nik_hash'
            )
            ->all();
    }

    public function createFromImportRow(
        BpntPeriod $period,
        Kpm $kpm,
        BnbaImport $import,
        BnbaImportRow $row
    ): void {
        $kelurahan =
            $this->resolveKelurahan(
                $row
            );

        BpntParticipant::query()
            ->create([
                'bpnt_period_id'
                => $period->id,

                'kpm_id'
                => $kpm->id,

                /*
                 * Snapshot wilayah periode.
                 */
                'kelurahan_id'
                => $kelurahan->id,

                'bnba_import_id'
                => $import->id,

                'import_row_number'
                => $row->row_number,

                'membership_year'
                => $row
                        ->membership_year,

                'e_warung_name_source'
                => $row
                        ->e_warung_name,

                'source_status'
                => $row
                        ->source_status,

                'source_description'
                => $row
                        ->source_description,

                'monthly_statuses'
                => $row
                        ->monthly_statuses,

                'sk_status'
                => $row
                        ->sk_status,

                'sk_description'
                => $row
                        ->sk_description,

                'apbn_march_status'
                => $row
                        ->apbn_march_status,

                'welfare_rank'
                => $row
                        ->welfare_rank,

                'entitlement_amount'
                => $row->nominal
                    ?? 0,
            ]);
    }

    public function deleteForPeriod(
        int $periodId
    ): int {
        return BpntParticipant::query()
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->delete();
    }

    public function paginateConfirmed(
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator {
        $query =
            BpntParticipant::query()
                ->with([
                    'period',
                    'kpm',
                    'kelurahan.kecamatan',
                    'import',
                ])
                ->where(
                    'bpnt_period_id',
                    (int) 
                    $filters[
                        'period_id'
                    ]
                )
                ->whereHas(
                    'import',
                    function ($query): void {
                        $query->where(
                            'status',
                            BnbaImportStatus
                            ::CONFIRMED
                                ->value
                        );
                    }
                );

        $search =
            trim(
                (string) (
                    $filters[
                        'search'
                    ]
                    ?? ''
                )
            );

        if ($search !== '') {
            $query->where(
                function ($query) use ($search, $nikHash): void {
                    /*
                     * Identitas KPM.
                     */
                    $query->whereHas(
                        'kpm',
                        function ($kpmQuery) use ($search, $nikHash): void {
                            $kpmQuery
                                ->where(
                                    'full_name',
                                    'like',
                                    '%'
                                    . $search
                                    . '%'
                                );

                            if (
                                $nikHash
                                !== null
                            ) {
                                $kpmQuery
                                    ->orWhere(
                                        'nik_hash',
                                        $nikHash
                                    );
                            }
                        }
                    );

                    /*
                     * Wilayah participant
                     * pada periode tersebut.
                     */
                    $query->orWhereHas(
                        'kelurahan',
                        function ($wilayahQuery) use ($search): void {
                            $wilayahQuery
                                ->where(
                                    'name',
                                    'like',
                                    '%'
                                    . $search
                                    . '%'
                                )
                                ->orWhereHas(
                                    'kecamatan',
                                    function ($kecamatanQuery) use ($search): void {
                                        $kecamatanQuery
                                            ->where(
                                                'name',
                                                'like',
                                                '%'
                                                . $search
                                                . '%'
                                            );
                                    }
                                );
                        }
                    );

                    $query->orWhere(
                        'e_warung_name_source',
                        'like',
                        '%'
                        . $search
                        . '%'
                    );
                }
            );
        }

        $kecamatan =
            trim(
                (string) (
                    $filters[
                        'kecamatan'
                    ]
                    ?? ''
                )
            );

        if ($kecamatan !== '') {
            $query->whereHas(
                'kelurahan.kecamatan',
                fn($query) =>
                $query->where(
                    'name',
                    $kecamatan
                )
            );
        }

        $kelurahan =
            trim(
                (string) (
                    $filters[
                        'kelurahan'
                    ]
                    ?? ''
                )
            );

        if ($kelurahan !== '') {
            $query->whereHas(
                'kelurahan',
                fn($query) =>
                $query->where(
                    'name',
                    $kelurahan
                )
            );
        }

        $eWarung =
            trim(
                (string) (
                    $filters[
                        'e_warung'
                    ]
                    ?? ''
                )
            );

        if ($eWarung !== '') {
            $query->where(
                'e_warung_name_source',
                $eWarung
            );
        }

        return $query
            ->orderBy(
                'bpnt_participants.id'
            )
            ->paginate(
                (int) (
                    $filters[
                        'per_page'
                    ]
                    ?? 25
                )
            );
    }

    public function filterOptions(
        int $periodId
    ): array {
        $baseQuery =
            BpntParticipant::query()
                ->where(
                    'bpnt_period_id',
                    $periodId
                )
                ->whereHas(
                    'import',
                    fn($query) =>
                    $query->where(
                        'status',
                        BnbaImportStatus
                        ::CONFIRMED
                            ->value
                    )
                );

        $kecamatan =
            (clone $baseQuery)
                ->join(
                    'kelurahans',
                    'kelurahans.id',
                    '=',
                    'bpnt_participants.kelurahan_id'
                )
                ->join(
                    'kecamatans',
                    'kecamatans.id',
                    '=',
                    'kelurahans.kecamatan_id'
                )
                ->distinct()
                ->orderBy(
                    'kecamatans.name'
                )
                ->pluck(
                    'kecamatans.name'
                )
                ->values()
                ->all();

        $kelurahan =
            (clone $baseQuery)
                ->join(
                    'kelurahans',
                    'kelurahans.id',
                    '=',
                    'bpnt_participants.kelurahan_id'
                )
                ->distinct()
                ->orderBy(
                    'kelurahans.name'
                )
                ->pluck(
                    'kelurahans.name'
                )
                ->values()
                ->all();

        $eWarungs =
            (clone $baseQuery)
                ->whereNotNull(
                    'e_warung_name_source'
                )
                ->where(
                    'e_warung_name_source',
                    '<>',
                    ''
                )
                ->distinct()
                ->orderBy(
                    'e_warung_name_source'
                )
                ->pluck(
                    'e_warung_name_source'
                )
                ->values()
                ->all();

        return [
            'kecamatan'
            => $kecamatan,

            'kelurahan'
            => $kelurahan,

            'e_warungs'
            => $eWarungs,
        ];
    }

    private function resolveKelurahan(
        BnbaImportRow $row
    ): Kelurahan {
        $key =
            $this->makeWilayahKey(
                (string) 
                $row->kecamatan,
                (string) 
                $row->kelurahan
            );

        $kelurahan =
            $this->getWilayahMap()[
                $key
            ]
            ?? null;

        if (
            !$kelurahan
            instanceof Kelurahan
        ) {
            throw ValidationException
                ::withMessages([
                    'wilayah' => [
                        sprintf(
                            'Wilayah pada baris Excel %d tidak ditemukan di Master Wilayah: Kecamatan "%s", Kelurahan "%s".',
                            (int) $row
                                ->row_number,
                            (string) $row
                                ->kecamatan,
                            (string) $row
                                ->kelurahan
                        ),
                    ],
                ]);
        }

        return $kelurahan;
    }

    /**
     * Master hanya 18 kelurahan sehingga
     * cukup dimuat satu kali per request.
     *
     * @return array<string, Kelurahan>
     */
    private function getWilayahMap(
    ): array {
        if (
            $this->wilayahMap
            !== null
        ) {
            return $this
                ->wilayahMap;
        }

        $map = [];

        $kelurahans =
            Kelurahan::query()
                ->with(
                    'kecamatan'
                )
                ->get();

        foreach (
            $kelurahans
            as $kelurahan
        ) {
            $kecamatan =
                $kelurahan
                    ->kecamatan;

            if (
                $kecamatan
                === null
            ) {
                continue;
            }

            $key =
                $this
                    ->makeWilayahKey(
                        (string) 
                        $kecamatan->name,
                        (string) 
                        $kelurahan->name
                    );

            $map[$key] =
                $kelurahan;
        }

        $this->wilayahMap =
            $map;

        return $this
            ->wilayahMap;
    }

    private function makeWilayahKey(
        string $kecamatan,
        string $kelurahan
    ): string {
        return
            $this->normalizeWilayah(
                $kecamatan
            )
            . '|'
            . $this->normalizeWilayah(
                $kelurahan
            );
    }

    private function normalizeWilayah(
        string $value
    ): string {

        $normalized =
            mb_strtoupper(
                trim($value),
                'UTF-8'
            );

        $normalized =
            preg_replace(
                '/\s+/u',
                '',
                $normalized
            );

        return $normalized
            ?? '';
    }
}