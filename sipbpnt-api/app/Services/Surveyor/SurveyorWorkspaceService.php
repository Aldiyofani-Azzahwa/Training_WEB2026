<?php

declare(strict_types=1);

namespace App\Services\Surveyor;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Contracts\Repositories\SurveyorAssignmentRepositoryInterface;
use App\Contracts\Repositories\SurveyorWorkspaceRepositoryInterface;
use App\Models\BpntParticipant;
use App\Models\BpntPeriod;
use App\Models\SurveyorAssignment;
use App\Models\User;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SurveyorWorkspaceService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly SurveyorAssignmentRepositoryInterface $assignments,
        private readonly SurveyorWorkspaceRepositoryInterface $workspace,
        private readonly AuditLogRepositoryInterface $auditLogs,
        private readonly SensitiveIdentity $identity,
    ) {}

    /**
     * @return array{
     *     surveyor: User,
     *     period: BpntPeriod|null,
     *     assignment: SurveyorAssignment|null,
     *     kpm_count: int
     * }
     */
    public function context(
        User $surveyor
    ): array {
        $period =
            $this->periods
                ->active();

        /*
         * Tidak ada fallback ke periode lama.
         *
         * Jika Admin belum mengaktifkan
         * periode, frontend mendapatkan
         * state aman.
         */
        if (
            ! $period
            instanceof BpntPeriod
        ) {
            return [
                'surveyor'
                    => $surveyor,

                'period'
                    => null,

                'assignment'
                    => null,

                'kpm_count'
                    => 0,
            ];
        }

        $assignment =
            $this->assignments
                ->findForSurveyorInPeriod(
                    (int) $period->id,
                    (int) $surveyor->id
                );

        /*
         * Periode aktif sudah ada tetapi
         * Manager belum memberikan wilayah.
         *
         * Context tetap HTTP 200 agar
         * tampilan Surveyor dapat menunjukkan:
         *
         * "Belum mendapat wilayah tugas".
         */
        if (
            ! $assignment
            instanceof SurveyorAssignment
        ) {
            return [
                'surveyor'
                    => $surveyor,

                'period'
                    => $period,

                'assignment'
                    => null,

                'kpm_count'
                    => 0,
            ];
        }

        return [
            'surveyor'
                => $surveyor,

            'period'
                => $period,

            'assignment'
                => $assignment,

            'kpm_count'
                => $this
                    ->workspace
                    ->countConfirmedParticipants(
                        (int) $period->id,
                        (int) $assignment
                            ->kelurahan_id
                    ),
        ];
    }

    /**
     * Browse KPM.
     *
     * Hanya boleh melihat KPM
     * dari kelurahan assignment.
     *
     * @param array<string, mixed> $filters
     */
    public function participants(
        User $surveyor,
        array $filters
    ): LengthAwarePaginator {
        [
            'period'
                => $period,

            'assignment'
                => $assignment,
        ] =
            $this
                ->requireOperationalContext(
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

        /*
         * Jika pencarian di halaman
         * KPM Wilayah adalah NIK 16 digit,
         * gunakan hash exact selain nama.
         *
         * Scope kelurahan masih tetap aktif.
         */
        if (
            preg_match(
                '/^\d{16}$/',
                $search
            ) === 1
        ) {
            $nikHash =
                $this->identity
                    ->hash(
                        $search
                    );
        }

        return $this
            ->workspace
            ->paginateConfirmedParticipants(
                (int) $period->id,
                (int) $assignment
                    ->kelurahan_id,
                $filters,
                $nikHash
            );
    }

    /**
     * Exact NIK lookup.
     *
     * Berbeda dengan browse KPM:
     * lookup ini boleh menemukan KPM
     * dari kelurahan lain selama masih
     * berada dalam periode aktif.
     *
     * @return array{
     *     participant: BpntParticipant,
     *     assignment: SurveyorAssignment,
     *     is_outside_assignment: bool
     * }
     */
    public function lookupNik(
        User $surveyor,
        string $nik,
        ?string $ipAddress,
        ?string $userAgent
    ): array {
        [
            'period'
                => $period,

            'assignment'
                => $assignment,
        ] =
            $this
                ->requireOperationalContext(
                    $surveyor
                );

        $nikHash =
            $this->identity
                ->hash(
                    $nik
                );

        $participant =
            $this->workspace
                ->findConfirmedParticipantByNikHash(
                    (int) $period->id,
                    $nikHash
                );

        if (
            ! $participant
            instanceof BpntParticipant
        ) {
            throw new NotFoundHttpException(
                'KPM dengan NIK tersebut tidak ditemukan pada periode aktif.'
            );
        }

        $isOutsideAssignment =
            (int) $participant
                ->kelurahan_id
            !==
            (int) $assignment
                ->kelurahan_id;

        /*
         * Exact NIK lookup dicatat.
         *
         * Raw NIK TIDAK disimpan.
         * Yang dicatat hanya hash NIK
         * dan participant yang ditemukan.
         */
        $this->auditLogs
            ->record([
                'user_id'
                    => $surveyor->id,

                'action'
                    => 'surveyor.kpm.lookup',

                'auditable_type'
                    => BpntParticipant::class,

                'auditable_id'
                    => $participant->id,

                'metadata' => [
                    'period_id'
                        => $period->id,

                    'assignment_kelurahan_id'
                        => $assignment
                            ->kelurahan_id,

                    'participant_kelurahan_id'
                        => $participant
                            ->kelurahan_id,

                    'outside_assignment'
                        => $isOutsideAssignment,

                    'nik_hash'
                        => $nikHash,
                ],

                'ip_address'
                    => $ipAddress,

                'user_agent'
                    => $userAgent,
            ]);

        return [
            'participant'
                => $participant,

            'assignment'
                => $assignment,

            'is_outside_assignment'
                => $isOutsideAssignment,
        ];
    }

    /**
     * @return array{
     *     period: BpntPeriod,
     *     assignment: SurveyorAssignment
     * }
     */
    private function requireOperationalContext(
        User $surveyor
    ): array {
        $period =
            $this->periods
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
            $this->assignments
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

        return [
            'period'
                => $period,

            'assignment'
                => $assignment,
        ];
    }
}