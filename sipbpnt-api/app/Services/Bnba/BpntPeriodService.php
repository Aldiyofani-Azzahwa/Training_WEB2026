<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Enums\BnbaImportStatus;
use App\Models\BpntPeriod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class BpntPeriodService
{
    public function __construct(
        private readonly BpntPeriodRepositoryInterface $periods,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function all(): Collection
    {
        return $this->periods->all();
    }

    public function active(): ?BpntPeriod
    {
        return $this->periods->active();
    }

    public function create(
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): BpntPeriod {
        return DB::transaction(
            function () use (
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): BpntPeriod {
                $year =
                    (int) $data['year'];

                $period =
                    $this->periods
                        ->create([
                            'code'
                                => $this
                                    ->generateInternalCode(
                                        $year
                                    ),

                            'name'
                                => trim(
                                    (string) $data['name']
                                ),

                            'year'
                                => $year,

                            'is_active'
                                => false,

                            'active_slot'
                                => null,
                        ]);

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'bpnt.period.created',

                        'auditable_type'
                            => BpntPeriod::class,

                        'auditable_id'
                            => $period->id,

                        'metadata' => [
                            'name'
                                => $period->name,

                            'year'
                                => $period->year,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $period;
            }
        );
    }

    public function update(
        int $periodId,
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): BpntPeriod {
        return DB::transaction(
            function () use (
                $periodId,
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): BpntPeriod {
                $period =
                    $this->periods
                        ->findOrFail(
                            $periodId
                        );

                $newYear =
                    (int) $data['year'];

                if (
                    $this->periods
                        ->hasImports(
                            $period->id
                        )
                    &&
                    $newYear !== $period->year
                ) {
                    throw ValidationException
                        ::withMessages([
                            'year' => [
                                'Tahun tidak dapat diubah selama periode masih memiliki data BNBA. Hapus BNBA terlebih dahulu.',
                            ],
                        ]);
                }

                $before = [
                    'name'
                        => $period->name,

                    'year'
                        => $period->year,
                ];

                $updated =
                    $this->periods
                        ->update(
                            $period,
                            [
                                'name'
                                    => trim(
                                        (string) $data['name']
                                    ),

                                'year'
                                    => $newYear,
                            ]
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'bpnt.period.updated',

                        'auditable_type'
                            => BpntPeriod::class,

                        'auditable_id'
                            => $updated->id,

                        'metadata' => [
                            'before'
                                => $before,

                            'after' => [
                                'name'
                                    => $updated->name,

                                'year'
                                    => $updated->year,
                            ],
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $updated;
            }
        );
    }

    public function activate(
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): BpntPeriod {
        return DB::transaction(
            function () use (
                $periodId,
                $actor,
                $ipAddress,
                $userAgent
            ): BpntPeriod {
                $period =
                    $this->periods
                        ->findOrFail(
                            $periodId
                        );

                $latestImport =
                    $period->latestImport;

                if (
                    $latestImport === null
                    ||
                    $latestImport->status
                        !== BnbaImportStatus::CONFIRMED
                    ||
                    (int) $period->participants_count
                        <= 0
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period' => [
                                'Periode hanya dapat diaktifkan setelah BNBA dikonfirmasi dan data KPM tersedia.',
                            ],
                        ]);
                }

                if (
                    (bool) $period->is_active
                    &&
                    (int) $period->active_slot === 1
                ) {
                    return $period;
                }

                $previousActive =
                    $this->periods
                        ->active();

                $activated =
                    $this->periods
                        ->activateExclusive(
                            $period
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'bpnt.period.activated',

                        'auditable_type'
                            => BpntPeriod::class,

                        'auditable_id'
                            => $activated->id,

                        'metadata' => [
                            'period_name'
                                => $activated->name,

                            'period_year'
                                => $activated->year,

                            'previous_active_period_id'
                                => $previousActive?->id,

                            'previous_active_period_name'
                                => $previousActive?->name,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $activated;
            }
        );
    }

    public function deactivate(
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): BpntPeriod {
        return DB::transaction(
            function () use (
                $periodId,
                $actor,
                $ipAddress,
                $userAgent
            ): BpntPeriod {
                $period =
                    $this->periods
                        ->findOrFail(
                            $periodId
                        );

                if (
                    ! (bool) $period->is_active
                    ||
                    (int) $period->active_slot !== 1
                ) {
                    return $period;
                }

                $deactivated =
                    $this->periods
                        ->deactivate(
                            $period
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'bpnt.period.deactivated',

                        'auditable_type'
                            => BpntPeriod::class,

                        'auditable_id'
                            => $deactivated->id,

                        'metadata' => [
                            'period_name'
                                => $deactivated->name,

                            'period_year'
                                => $deactivated->year,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $deactivated;
            }
        );
    }

    public function assertBnbaCanBeDeleted(
        int $periodId
    ): void {
        $period =
            $this->periods
                ->findOrFail(
                    $periodId
                );

        /*
         * BNBA periode aktif tidak boleh
         * dihapus karena sedang menjadi
         * periode operasional sistem.
         *
         * Assignment bukan blocker di sini.
         * Assignment periode nonaktif akan
         * dibersihkan otomatis sebelum BNBA
         * dihapus.
         */
        if (
            (bool) $period->is_active
            &&
            (int) $period->active_slot === 1
        ) {
            throw ValidationException
                ::withMessages([
                    'period' => [
                        'BNBA pada periode aktif tidak dapat dihapus. Nonaktifkan periode terlebih dahulu.',
                    ],
                ]);
        }
    }

    public function delete(
        int $periodId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        DB::transaction(
            function () use (
                $periodId,
                $actor,
                $ipAddress,
                $userAgent
            ): void {
                $period =
                    $this->periods
                        ->findOrFail(
                            $periodId
                        );

                if (
                    $this->periods
                        ->hasImports(
                            $period->id
                        )
                    ||
                    $this->periods
                        ->hasParticipants(
                            $period->id
                        )
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period' => [
                                'Periode masih memiliki data BNBA. Hapus BNBA terlebih dahulu sebelum menghapus periode.',
                            ],
                        ]);
                }

                if (
                    $this->periods
                        ->hasAssignments(
                            $period->id
                        )
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period' => [
                                'Periode masih memiliki penugasan Surveyor. Hapus penugasan terlebih dahulu sebelum menghapus periode.',
                            ],
                        ]);
                }

                if (
                    (bool) $period->is_active
                    &&
                    (int) $period->active_slot === 1
                ) {
                    throw ValidationException
                        ::withMessages([
                            'period' => [
                                'Periode aktif tidak dapat dihapus. Nonaktifkan periode terlebih dahulu.',
                            ],
                        ]);
                }

                $metadata = [
                    'name'
                        => $period->name,

                    'year'
                        => $period->year,
                ];

                $this->periods
                    ->delete(
                        $period
                    );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'bpnt.period.deleted',

                        'auditable_type'
                            => BpntPeriod::class,

                        'auditable_id'
                            => $periodId,

                        'metadata'
                            => $metadata,

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);
            }
        );
    }

    private function generateInternalCode(
        int $year
    ): string {
        return sprintf(
            'BPNT-%d-%s',
            $year,
            strtoupper(
                substr(
                    (string) Str::ulid(),
                    -8
                )
            )
        );
    }
}