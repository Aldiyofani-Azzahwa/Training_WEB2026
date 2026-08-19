<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
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

                            /*
                             * Tidak lagi digunakan
                             * sebagai business rule.
                             */
                            'is_active'
                                => false,
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

                /*
                 * Tahun dikunci selama
                 * periode masih punya BNBA.
                 *
                 * Setelah BNBA dihapus,
                 * imports menjadi 0 dan
                 * tahun dapat diedit lagi.
                 */
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

                $metadata = [
                    'name'
                        => $period->name,

                    'year'
                        => $period->year,
                ];

                $this->periods
                    ->delete($period);

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