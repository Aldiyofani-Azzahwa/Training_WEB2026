<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BpntParticipant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SurveyorWorkspaceRepositoryInterface
{
    public function countConfirmedParticipants(
        int $periodId,
        int $kelurahanId
    ): int;

    public function paginateConfirmedParticipants(
        int $periodId,
        int $kelurahanId,
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator;

    public function findConfirmedParticipantByNikHash(
        int $periodId,
        string $nikHash
    ): ?BpntParticipant;
}