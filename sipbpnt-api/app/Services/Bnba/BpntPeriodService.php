<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\BpntPeriodRepositoryInterface;
use App\Models\BpntPeriod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
                $period =
                    $this->periods->create([
                        'code'
                            => strtoupper(
                                trim(
                                    (string)
                                    $data['code']
                                )
                            ),

                        'name'
                            => trim(
                                (string)
                                $data['name']
                            ),

                        'year'
                            => (int)
                                $data['year'],

                        'is_active'
                            => (bool) (
                                $data['is_active']
                                ?? true
                            ),
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
                            'code'
                                => $period->code,

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
}