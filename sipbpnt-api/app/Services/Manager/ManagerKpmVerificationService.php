<?php

declare(strict_types=1);

namespace App\Services\Manager;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorKpmActivityRepositoryInterface;
use App\Models\BpntPeriod;
use App\Models\KpmVerification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManagerKpmVerificationService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SurveyorKpmActivityRepositoryInterface $activities,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function index(
        array $filters
    ): LengthAwarePaginator {
        $period = $this->requireActivePeriod();

        return $this->activities
            ->paginateActiveVerificationsForManager(
                (int) $period->id,
                $filters
            );
    }

    public function cancel(
        User $manager,
        int $verificationId,
        ?string $ipAddress,
        ?string $userAgent
    ): KpmVerification {
        return DB::transaction(
            function () use (
                $manager,
                $verificationId,
                $ipAddress,
                $userAgent
            ): KpmVerification {
                $activePeriod = $this->requireActivePeriod();

                $period = $this->periods->findForUpdate(
                    (int) $activePeriod->id
                );

                if (
                    ! $period->is_active
                    || (int) $period->active_slot !== 1
                ) {
                    throw ValidationException::withMessages([
                        'period' => [
                            'Periode BPNT aktif telah berubah. Silakan muat ulang halaman.',
                        ],
                    ]);
                }

                $verification = $this->activities
                    ->findVerificationForUpdate(
                        (int) $period->id,
                        $verificationId
                    );

                if (! $verification instanceof KpmVerification) {
                    throw ValidationException::withMessages([
                        'verification' => [
                            'Verifikasi aktif tidak ditemukan pada periode aktif.',
                        ],
                    ]);
                }

                $cancelled = $this->activities
                    ->cancelVerification(
                        $verification,
                        (int) $manager->id
                    );

                $this->auditLogs->record([
                    'user_id' => $manager->id,
                    'action'
                        => 'manager.kpm_verification.cancelled',
                    'auditable_type'
                        => KpmVerification::class,
                    'auditable_id' => $cancelled->id,

                    'metadata' => [
                        'period_id' => $cancelled->period_id,
                        'bpnt_participant_id'
                            => $cancelled->bpnt_participant_id,
                        'status' => $cancelled->status->value,
                        'surveyor_id' => $cancelled->surveyor_id,
                    ],

                    'ip_address' => $ipAddress,
                    'user_agent' => $userAgent,
                ]);

                return $cancelled;
            },
            3
        );
    }

    private function requireActivePeriod(): BpntPeriod
    {
        $period = $this->periods->active();

        if (! $period instanceof BpntPeriod) {
            throw ValidationException::withMessages([
                'period' => [
                    'Belum ada periode BPNT aktif.',
                ],
            ]);
        }

        return $period;
    }
}