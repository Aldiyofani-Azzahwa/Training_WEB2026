<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\EWarungRepositoryInterface;
use App\Models\EWarung;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class EWarungService
{
    public function __construct(
        private readonly EWarungRepositoryInterface $eWarungs,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function all(): Collection
    {
        return $this->eWarungs
            ->all();
    }

    public function create(
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): EWarung {
        return DB::transaction(
            function () use (
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): EWarung {
                $eWarung =
                    $this->eWarungs
                        ->create([
                            'name'
                                => trim(
                                    (string) $data[
                                        'name'
                                    ]
                                ),

                            'is_active'
                                => true,
                        ]);

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'e_warung.created',

                        'auditable_type'
                            => EWarung::class,

                        'auditable_id'
                            => $eWarung->id,

                        'metadata' => [
                            'name'
                                => $eWarung->name,

                            'is_active'
                                => true,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $eWarung;
            }
        );
    }

    public function update(
        int $eWarungId,
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): EWarung {
        return DB::transaction(
            function () use (
                $eWarungId,
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): EWarung {
                $eWarung =
                    $this->eWarungs
                        ->findOrFail(
                            $eWarungId
                        );

                $before = [
                    'name'
                        => $eWarung->name,
                ];

                $updated =
                    $this->eWarungs
                        ->update(
                            $eWarung,
                            [
                                'name'
                                    => trim(
                                        (string) $data[
                                            'name'
                                        ]
                                    ),
                            ]
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'e_warung.updated',

                        'auditable_type'
                            => EWarung::class,

                        'auditable_id'
                            => $updated->id,

                        'metadata' => [
                            'before'
                                => $before,

                            'after' => [
                                'name'
                                    => $updated->name,
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

    public function setActive(
        int $eWarungId,
        bool $isActive,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): EWarung {
        return DB::transaction(
            function () use (
                $eWarungId,
                $isActive,
                $actor,
                $ipAddress,
                $userAgent
            ): EWarung {
                $eWarung =
                    $this->eWarungs
                        ->findOrFail(
                            $eWarungId
                        );

                $before =
                    (bool) $eWarung
                        ->is_active;

                $updated =
                    $this->eWarungs
                        ->update(
                            $eWarung,
                            [
                                'is_active'
                                    => $isActive,
                            ]
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => $isActive
                                ? 'e_warung.activated'
                                : 'e_warung.deactivated',

                        'auditable_type'
                            => EWarung::class,

                        'auditable_id'
                            => $updated->id,

                        'metadata' => [
                            'name'
                                => $updated->name,

                            'before_is_active'
                                => $before,

                            'after_is_active'
                                => (bool) $updated
                                    ->is_active,
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
        int $eWarungId,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): void {
        DB::transaction(
            function () use (
                $eWarungId,
                $actor,
                $ipAddress,
                $userAgent
            ): void {
                $eWarung =
                    $this->eWarungs
                        ->findOrFail(
                            $eWarungId
                        );

                if (
                    $this->eWarungs
                        ->isUsedInTransactions(
                            $eWarung->id
                        )
                ) {
                    throw ValidationException::withMessages([
                        'e_warung' => [
                            'E-Warung sudah memiliki histori transaksi dan tidak boleh dihapus. Nonaktifkan E-Warung jika sudah tidak digunakan.',
                        ],
                    ]);
                }

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'e_warung.deleted',

                        'auditable_type'
                            => EWarung::class,

                        'auditable_id'
                            => $eWarung->id,

                        'metadata' => [
                            'name'
                                => $eWarung->name,

                            'is_active'
                                => (bool) $eWarung
                                    ->is_active,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                $this->eWarungs
                    ->delete(
                        $eWarung
                    );
            }
        );
    }
}