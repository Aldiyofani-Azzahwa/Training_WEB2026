<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SurveyorKpmActivityRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BpntParticipant;
use App\Models\BpntTransaction;
use App\Models\EWarung;
use App\Models\KpmVerification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EloquentSurveyorKpmActivityRepository
    implements SurveyorKpmActivityRepositoryInterface
{
    private const TRANSACTION_RELATIONS = [
        'period',
        'participant.kpm',
        'participant.kelurahan.kecamatan',
        'eWarung',
        'surveyor',
        'participantKelurahan.kecamatan',
        'surveyorKelurahan.kecamatan',
    ];

    private const VERIFICATION_RELATIONS = [
        'period',
        'participant.kpm',
        'participant.kelurahan.kecamatan',
        'surveyor',
        'participantKelurahan.kecamatan',
        'surveyorKelurahan.kecamatan',
        'cancelledBy',
    ];

    public function activeEWarungs(): Collection
    {
        return EWarung::query()
            ->where(
                'is_active',
                true
            )
            ->orderBy(
                'name'
            )
            ->orderBy(
                'id'
            )
            ->get();
    }

    public function findActiveEWarungForUpdate(
        int $eWarungId
    ): ?EWarung {
        return EWarung::query()
            ->whereKey(
                $eWarungId
            )
            ->where(
                'is_active',
                true
            )
            ->lockForUpdate()
            ->first();
    }

    public function findConfirmedParticipantForUpdateByNikHash(
        int $periodId,
        string $nikHash
    ): ?BpntParticipant {
        return $this
            ->confirmedParticipantQuery(
                $periodId
            )
            ->whereHas(
                'kpm',
                fn (Builder $query) =>
                    $query->where(
                        'nik_hash',
                        $nikHash
                    )
            )
            ->lockForUpdate()
            ->first();
    }

    public function findAssignedParticipantForUpdate(
        int $periodId,
        int $kelurahanId,
        int $participantId
    ): ?BpntParticipant {
        return $this
            ->confirmedParticipantQuery(
                $periodId
            )
            ->whereKey(
                $participantId
            )
            ->where(
                'kelurahan_id',
                $kelurahanId
            )
            ->lockForUpdate()
            ->first();
    }

    public function findTransaction(
        int $periodId,
        int $participantId
    ): ?BpntTransaction {
        return BpntTransaction::query()
            ->with(
                self::TRANSACTION_RELATIONS
            )
            ->where(
                'period_id',
                $periodId
            )
            ->where(
                'bpnt_participant_id',
                $participantId
            )
            ->first();
    }

    public function findActiveVerification(
        int $periodId,
        int $participantId
    ): ?KpmVerification {
        return KpmVerification::query()
            ->with(
                self::VERIFICATION_RELATIONS
            )
            ->where(
                'period_id',
                $periodId
            )
            ->where(
                'bpnt_participant_id',
                $participantId
            )
            ->where(
                'active_slot',
                1
            )
            ->first();
    }

    public function createTransaction(
        array $data
    ): BpntTransaction {
        return BpntTransaction::query()
            ->create(
                $data
            )
            ->load(
                self::TRANSACTION_RELATIONS
            );
    }

    public function createVerification(
        array $data
    ): KpmVerification {
        return KpmVerification::query()
            ->create(
                $data
            )
            ->load(
                self::VERIFICATION_RELATIONS
            );
    }

    public function paginatePendingParticipants(
        int $periodId,
        int $kelurahanId,
        array $filters,
        ?string $nikHash = null
    ): LengthAwarePaginator {
        $query =
            $this
                ->confirmedParticipantQuery(
                    $periodId
                )
                ->where(
                    'kelurahan_id',
                    $kelurahanId
                )
                ->whereNotExists(
                    fn ($transactionQuery) =>
                        $transactionQuery
                            ->selectRaw(
                                '1'
                            )
                            ->from(
                                'transactions'
                            )
                            ->whereColumn(
                                'transactions.bpnt_participant_id',
                                'bpnt_participants.id'
                            )
                            ->where(
                                'transactions.period_id',
                                $periodId
                            )
                )
                ->whereNotExists(
                    fn ($verificationQuery) =>
                        $verificationQuery
                            ->selectRaw(
                                '1'
                            )
                            ->from(
                                'kpm_verifications'
                            )
                            ->whereColumn(
                                'kpm_verifications.bpnt_participant_id',
                                'bpnt_participants.id'
                            )
                            ->where(
                                'kpm_verifications.period_id',
                                $periodId
                            )
                            ->where(
                                'kpm_verifications.active_slot',
                                1
                            )
                );

        $search =
            trim(
                (string) (
                    $filters['search']
                    ?? ''
                )
            );

        if ($search !== '') {
            $query->whereHas(
                'kpm',
                function (
                    Builder $kpmQuery
                ) use (
                    $search,
                    $nikHash
                ): void {
                    $kpmQuery->where(
                        'full_name',
                        'like',
                        '%'
                        . $search
                        . '%'
                    );

                    if ($nikHash !== null) {
                        $kpmQuery->orWhere(
                            'nik_hash',
                            $nikHash
                        );
                    }
                }
            );
        }

        return $query
            ->orderBy(
                'bpnt_participants.id'
            )
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 20
                )
            );
    }

    public function paginateTransactionsForSurveyor(
        int $surveyorId,
        int $perPage
    ): LengthAwarePaginator {
        return BpntTransaction::query()
            ->with(
                self::TRANSACTION_RELATIONS
            )
            ->where(
                'surveyor_id',
                $surveyorId
            )
            ->orderByDesc(
                'transacted_at'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                $perPage,
                ['*'],
                'transaction_page'
            );
    }

    public function paginateVerificationsForSurveyor(
        int $surveyorId,
        int $perPage
    ): LengthAwarePaginator {
        return KpmVerification::query()
            ->with(
                self::VERIFICATION_RELATIONS
            )
            ->where(
                'surveyor_id',
                $surveyorId
            )
            ->orderByDesc(
                'verified_at'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                $perPage,
                ['*'],
                'verification_page'
            );
    }

    public function paginateActiveVerificationsForManager(
        int $periodId,
        array $filters
    ): LengthAwarePaginator {
        $query =
            KpmVerification::query()
                ->with(
                    self::VERIFICATION_RELATIONS
                )
                ->where(
                    'period_id',
                    $periodId
                )
                ->where(
                    'active_slot',
                    1
                );

        $status =
            trim(
                (string) (
                    $filters['status']
                    ?? ''
                )
            );

        if ($status !== '') {
            $query->where(
                'status',
                $status
            );
        }

        return $query
            ->orderByDesc(
                'verified_at'
            )
            ->orderByDesc(
                'id'
            )
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 20
                )
            );
    }

    public function findVerification(
        int $periodId,
        int $verificationId
    ): ?KpmVerification {
        return KpmVerification::query()
            ->with(
                self::VERIFICATION_RELATIONS
            )
            ->whereKey(
                $verificationId
            )
            ->where(
                'period_id',
                $periodId
            )
            ->where(
                'active_slot',
                1
            )
            ->first();
    }

    public function findVerificationForUpdate(
        int $periodId,
        int $verificationId
    ): ?KpmVerification {
        return KpmVerification::query()
            ->whereKey(
                $verificationId
            )
            ->where(
                'period_id',
                $periodId
            )
            ->where(
                'active_slot',
                1
            )
            ->lockForUpdate()
            ->first();
    }

    public function cancelVerification(
        KpmVerification $verification,
        int $managerId
    ): KpmVerification {
        $verification
            ->forceFill([
                'active_slot'
                    => null,

                'cancelled_by'
                    => $managerId,

                'cancelled_at'
                    => now(),
            ])
            ->save();

        return $verification
            ->fresh()
            ->load(
                self::VERIFICATION_RELATIONS
            );
    }

    public function periodHasActivity(
        int $periodId
    ): bool {
        return BpntTransaction::query()
            ->where(
                'period_id',
                $periodId
            )
            ->exists()
            ||
            KpmVerification::query()
                ->where(
                    'period_id',
                    $periodId
                )
                ->exists();
    }

    private function confirmedParticipantQuery(
        int $periodId
    ): Builder {
        return BpntParticipant::query()
            ->with([
                'period',
                'kpm',
                'kelurahan.kecamatan',
                'import',
                'activeVerification',
            ])
            ->withExists([
                'transactions as has_transaction' =>
                    fn (Builder $query) =>
                        $query->where(
                            'period_id',
                            $periodId
                        ),
            ])
            ->where(
                'bpnt_period_id',
                $periodId
            )
            ->whereHas(
                'import',
                fn (Builder $query) =>
                    $query->where(
                        'status',
                        BnbaImportStatus
                            ::CONFIRMED
                            ->value
                    )
            );
    }
}