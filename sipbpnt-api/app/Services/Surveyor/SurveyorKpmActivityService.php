<?php

declare(strict_types=1);

namespace App\Services\Surveyor;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Contracts\Repositories\SurveyorKpmActivityRepositoryInterface;
use App\Enums\KpmVerificationStatus;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\BpntTransaction;
use App\Models\EWarung;
use App\Models\KpmVerification;
use App\Models\SurveyorAssignment;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SurveyorKpmActivityService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SurveyorAssignmentRepositoryInterface $assignments,
        private readonly SurveyorKpmActivityRepositoryInterface $activities,
        private readonly AuditLogRepositoryInterface $auditLogs,
        private readonly SensitiveIdentity $identity,
    ) {}

    public function activeEWarungs(
        User $surveyor
    ): Collection {
        $this->requireOperationalContext(
            $surveyor
        );

        return $this
            ->activities
            ->activeEWarungs();
    }

    public function pendingParticipants(
        User $surveyor,
        array $filters
    ): LengthAwarePaginator {
        [
            'period'
                => $period,

            'assignment'
                => $assignment,
        ] =
            $this->requireOperationalContext(
                $surveyor
            );

        $search =
            trim(
                (string) (
                    $filters['search']
                    ?? ''
                )
            );

        $nikHash =
            null;

        if (
            preg_match(
                '/^\d{16}$/',
                $search
            ) === 1
        ) {
            $nikHash =
                $this
                    ->identity
                    ->hash(
                        $search
                    );
        }

        return $this
            ->activities
            ->paginatePendingParticipants(
                (int) $period->id,
                (int) $assignment->kelurahan_id,
                $filters,
                $nikHash
            );
    }

    public function storeTransaction(
        User $surveyor,
        array $data,
        ?string $ipAddress,
        ?string $userAgent
    ): BpntTransaction {
        try {
            return DB::transaction(
                function () use (
                    $surveyor,
                    $data,
                    $ipAddress,
                    $userAgent
                ): BpntTransaction {
                    [
                        'period'
                            => $period,

                        'assignment'
                            => $assignment,
                    ] =
                        $this
                            ->requireLockedOperationalContext(
                                $surveyor
                            );

                    if (
                        isset(
                            $data['bpnt_participant_id']
                        )
                    ) {
                        /*
                         * Jalur halaman KPM.
                         *
                         * Participant ID harus berada
                         * pada wilayah assignment.
                         */
                        $participant =
                            $this
                                ->activities
                                ->findAssignedParticipantForUpdate(
                                    (int) $period->id,
                                    (int) $assignment
                                        ->kelurahan_id,
                                    (int) $data[
                                        'bpnt_participant_id'
                                    ]
                                );

                        $participantErrorField =
                            'bpnt_participant_id';

                        $participantErrorMessage =
                            'KPM tidak ditemukan pada wilayah tugas Anda.';
                    } else {
                        /*
                         * Jalur exact NIK.
                         *
                         * Tetap boleh lintas wilayah.
                         */
                        $participant =
                            $this
                                ->activities
                                ->findConfirmedParticipantForUpdateByNikHash(
                                    (int) $period->id,
                                    $this
                                        ->identity
                                        ->hash(
                                            (string) $data[
                                                'nik'
                                            ]
                                        )
                                );

                        $participantErrorField =
                            'nik';

                        $participantErrorMessage =
                            'KPM dengan NIK tersebut tidak ditemukan pada periode aktif.';
                    }

                    if (
                        ! $participant
                        instanceof BpntParticipant
                    ) {
                        throw ValidationException
                            ::withMessages([
                                $participantErrorField => [
                                    $participantErrorMessage,
                                ],
                            ]);
                    }

                    $this
                        ->ensureParticipantHasNoFinalActivity(
                            (int) $period->id,
                            (int) $participant->id
                        );

                    $eWarung =
                        $this
                            ->activities
                            ->findActiveEWarungForUpdate(
                                (int) $data[
                                    'e_warung_id'
                                ]
                            );

                    if (
                        ! $eWarung
                        instanceof EWarung
                    ) {
                        throw ValidationException
                            ::withMessages([
                                'e_warung_id' => [
                                    'E-Warung tidak ditemukan atau sudah tidak aktif.',
                                ],
                            ]);
                    }

                    $transaction =
                        $this
                            ->activities
                            ->createTransaction([
                                'period_id'
                                    => $period->id,

                                'bpnt_participant_id'
                                    => $participant->id,

                                'surveyor_id'
                                    => $surveyor->id,

                                'e_warung_id'
                                    => $eWarung->id,

                                'participant_kelurahan_id'
                                    => $participant
                                        ->kelurahan_id,

                                'surveyor_kelurahan_id'
                                    => $assignment
                                        ->kelurahan_id,

                                'transacted_at'
                                    => now(),
                            ]);

                    $this
                        ->auditLogs
                        ->record([
                            'user_id'
                                => $surveyor->id,

                            'action'
                                => 'surveyor.transaction.created',

                            'auditable_type'
                                => BpntTransaction::class,

                            'auditable_id'
                                => $transaction->id,

                            'metadata' => [
                                'period_id'
                                    => $period->id,

                                'bpnt_participant_id'
                                    => $participant->id,

                                'e_warung_id'
                                    => $eWarung->id,

                                'participant_kelurahan_id'
                                    => $participant
                                        ->kelurahan_id,

                                'surveyor_kelurahan_id'
                                    => $assignment
                                        ->kelurahan_id,

                                'outside_assignment'
                                    => (int) $participant
                                        ->kelurahan_id
                                        !==
                                        (int) $assignment
                                            ->kelurahan_id,
                            ],

                            'ip_address'
                                => $ipAddress,

                            'user_agent'
                                => $userAgent,
                        ]);

                    return $transaction;
                },
                3
            );
        } catch (
            QueryException $exception
        ) {
            $this
                ->convertActivityConstraintViolation(
                    $exception
                );

            throw $exception;
        }
    }

    public function storeVerification(
        User $surveyor,
        array $data,
        ?string $ipAddress,
        ?string $userAgent
    ): KpmVerification {
        try {
            return DB::transaction(
                function () use (
                    $surveyor,
                    $data,
                    $ipAddress,
                    $userAgent
                ): KpmVerification {
                    [
                        'period'
                            => $period,

                        'assignment'
                            => $assignment,
                    ] =
                        $this
                            ->requireLockedOperationalContext(
                                $surveyor
                            );

                    $participant =
                        $this
                            ->activities
                            ->findAssignedParticipantForUpdate(
                                (int) $period->id,
                                (int) $assignment
                                    ->kelurahan_id,
                                (int) $data[
                                    'bpnt_participant_id'
                                ]
                            );

                    if (
                        ! $participant
                        instanceof BpntParticipant
                    ) {
                        throw ValidationException
                            ::withMessages([
                                'bpnt_participant_id' => [
                                    'KPM tidak ditemukan pada wilayah tugas Anda.',
                                ],
                            ]);
                    }

                    $this
                        ->ensureParticipantHasNoFinalActivity(
                            (int) $period->id,
                            (int) $participant->id
                        );

                    $status =
                        KpmVerificationStatus::from(
                            (string) $data[
                                'status'
                            ]
                        );

                    $verification =
                        $this
                            ->activities
                            ->createVerification([
                                'period_id'
                                    => $period->id,

                                'bpnt_participant_id'
                                    => $participant->id,

                                'surveyor_id'
                                    => $surveyor->id,

                                'participant_kelurahan_id'
                                    => $participant
                                        ->kelurahan_id,

                                'surveyor_kelurahan_id'
                                    => $assignment
                                        ->kelurahan_id,

                                'status'
                                    => $status->value,

                                'reason'
                                    => $status
                                        ===
                                        KpmVerificationStatus
                                            ::NOT_CLAIMED
                                        ? (string) $data[
                                            'reason'
                                        ]
                                        : null,

                                'active_slot'
                                    => 1,

                                'verified_at'
                                    => now(),
                            ]);

                    $this
                        ->auditLogs
                        ->record([
                            'user_id'
                                => $surveyor->id,

                            'action'
                                => 'surveyor.kpm_verification.created',

                            'auditable_type'
                                => KpmVerification::class,

                            'auditable_id'
                                => $verification->id,

                            'metadata' => [
                                'period_id'
                                    => $period->id,

                                'bpnt_participant_id'
                                    => $participant->id,

                                'status'
                                    => $status->value,

                                'participant_kelurahan_id'
                                    => $participant
                                        ->kelurahan_id,

                                'surveyor_kelurahan_id'
                                    => $assignment
                                        ->kelurahan_id,
                            ],

                            'ip_address'
                                => $ipAddress,

                            'user_agent'
                                => $userAgent,
                        ]);

                    return $verification;
                },
                3
            );
        } catch (
            QueryException $exception
        ) {
            $this
                ->convertActivityConstraintViolation(
                    $exception
                );

            throw $exception;
        }
    }

    public function history(
        User $surveyor,
        int $perPage
    ): array {
        return [
            'transactions'
                => $this
                    ->activities
                    ->paginateTransactionsForSurveyor(
                        (int) $surveyor->id,
                        $perPage
                    ),

            'verifications'
                => $this
                    ->activities
                    ->paginateVerificationsForSurveyor(
                        (int) $surveyor->id,
                        $perPage
                    ),
        ];
    }

    private function ensureParticipantHasNoFinalActivity(
        int $periodId,
        int $participantId
    ): void {
        if (
            $this
                ->activities
                ->findTransaction(
                    $periodId,
                    $participantId
                )
        ) {
            throw ValidationException
                ::withMessages([
                    'kpm' => [
                        'KPM sudah bertransaksi pada periode aktif.',
                    ],
                ]);
        }

        if (
            $this
                ->activities
                ->findActiveVerification(
                    $periodId,
                    $participantId
                )
        ) {
            throw ValidationException
                ::withMessages([
                    'kpm' => [
                        'KPM sudah mempunyai hasil verifikasi final pada periode aktif.',
                    ],
                ]);
        }
    }

    private function requireOperationalContext(
        User $surveyor
    ): array {
        $period =
            $this
                ->periods
                ->active();

        if (
            ! $period
            instanceof BpntPeriod
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'Belum ada periode BPNT aktif. Hubungi Admin Dinsos.',
                    ],
                ]);
        }

        $assignment =
            $this
                ->assignments
                ->findForSurveyorInPeriod(
                    (int) $period->id,
                    (int) $surveyor->id
                );

        if (
            ! $assignment
            instanceof SurveyorAssignment
        ) {
            throw ValidationException
                ::withMessages([
                    'assignment' => [
                        'Anda belum memiliki wilayah tugas pada periode aktif.',
                    ],
                ]);
        }

        return compact(
            'period',
            'assignment'
        );
    }

    private function requireLockedOperationalContext(
        User $surveyor
    ): array {
        $activePeriod =
            $this
                ->periods
                ->active();

        if (
            ! $activePeriod
            instanceof BpntPeriod
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'Belum ada periode BPNT aktif. Hubungi Admin Dinsos.',
                    ],
                ]);
        }

        $period =
            $this
                ->periods
                ->findForUpdate(
                    (int) $activePeriod->id
                );

        if (
            ! $period->is_active
            ||
            (int) $period
                ->active_slot
                !== 1
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'Periode BPNT aktif telah berubah. Silakan muat ulang halaman.',
                    ],
                ]);
        }

        $assignment =
            $this
                ->assignments
                ->findForSurveyorInPeriod(
                    (int) $period->id,
                    (int) $surveyor->id
                );

        if (
            ! $assignment
            instanceof SurveyorAssignment
        ) {
            throw ValidationException
                ::withMessages([
                    'assignment' => [
                        'Anda belum memiliki wilayah tugas pada periode aktif.',
                    ],
                ]);
        }

        return compact(
            'period',
            'assignment'
        );
    }

    private function convertActivityConstraintViolation(
        QueryException $exception
    ): void {
        $message =
            $exception
                ->getMessage();

        if (
            str_contains(
                $message,
                'transaction_period_participant_unique'
            )
            ||
            str_contains(
                $message,
                'kpm_verification_active_unique'
            )
            ||
            str_contains(
                $message,
                'transactions.period_id, transactions.bpnt_participant_id'
            )
            ||
            str_contains(
                $message,
                'kpm_verifications.period_id, kpm_verifications.bpnt_participant_id, kpm_verifications.active_slot'
            )
        ) {
            throw ValidationException
                ::withMessages([
                    'kpm' => [
                        'KPM sudah mempunyai hasil final pada periode aktif.',
                    ],
                ]);
        }
    }
}