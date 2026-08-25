<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\BpntParticipant;
use App\Models\BpntTransaction;
use App\Models\EWarung;
use App\Models\KpmVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SurveyorKpmActivityRepositoryInterface
{
    public function activeEWarungs(): Collection;

    public function findActiveEWarungForUpdate(
        int $eWarungId
    ): ?EWarung;

    public function findConfirmedParticipantForUpdateByNikHash(
        int $periodId,
        string $nikHash
    ): ?BpntParticipant;

    public function findAssignedParticipantForUpdate(
        int $periodId,
        int $kelurahanId,
        int $participantId
    ): ?BpntParticipant;

    public function findTransaction(
        int $periodId,
        int $participantId
    ): ?BpntTransaction;

    public function findActiveVerification(
        int $periodId,
        int $participantId
    ): ?KpmVerification;

    public function createTransaction(
        array $data
    ): BpntTransaction;

    public function createVerification(
        array $data
    ): KpmVerification;

    public function paginatePendingParticipants(
        int $periodId,
        int $kelurahanId,
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator;

    public function paginateTransactionsForSurveyor(
        int $surveyorId,
        int $perPage
    ): LengthAwarePaginator;

    public function paginateVerificationsForSurveyor(
        int $surveyorId,
        int $perPage
    ): LengthAwarePaginator;

    public function paginateActiveVerificationsForManager(
        int $periodId,
        array $filters
    ): LengthAwarePaginator;

    public function findVerificationForUpdate(
        int $periodId,
        int $verificationId
    ): ?KpmVerification;

    public function cancelVerification(
        KpmVerification $verification,
        int $managerId
    ): KpmVerification;

    public function periodHasActivity(
        int $periodId
    ): bool;
}