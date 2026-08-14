<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\BpntParticipantRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class BnbaParticipantService
{
    public function __construct(
        private readonly BpntParticipantRepositoryInterface $participants,
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SensitiveIdentity $identity,
    ) {}

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        /*
         * Memastikan periode benar-benar ada.
         */
        $this->periods->findOrFail(
            (int) $filters['period_id']
        );

        $search = trim(
            (string) (
                $filters['search']
                ?? ''
            )
        );

        $nikHash = null;

        if (
            preg_match(
                '/^\d{16}$/',
                $search
            ) === 1
        ) {
            $nikHash =
                $this->identity->hash(
                    $search
                );
        }

        return $this->participants
            ->paginateConfirmed(
                $filters,
                $nikHash
            );
    }

    /**
     * @return array{
     *     kecamatan: array<int, string>,
     *     kelurahan: array<int, string>,
     *     e_warungs: array<int, string>
     * }
     */
    public function filterOptions(
        int $periodId
    ): array {
        $this->periods
            ->findOrFail($periodId);

        return $this->participants
            ->filterOptions(
                $periodId
            );
    }
}