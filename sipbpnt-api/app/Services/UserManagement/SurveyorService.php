<?php

declare(strict_types=1);

namespace App\Services\UserManagement;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\SurveyorRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class SurveyorService
{
    public function __construct(
        private readonly SurveyorRepositoryInterface $surveyors,
        private readonly AuditLogRepositoryInterface $auditLogs,
    ) {}

    public function all(): Collection
    {
        return $this->surveyors
            ->all();
    }

    public function activeOptions(): Collection
    {
        return $this->surveyors
            ->active();
    }

    public function create(
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): User {
        return DB::transaction(
            function () use (
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): User {
                $surveyor =
                    $this->surveyors
                        ->create([
                            'name'
                                => trim(
                                    (string) $data[
                                        'name'
                                    ]
                                ),

                            'username'
                                => Str::lower(
                                    trim(
                                        (string) $data[
                                            'username'
                                        ]
                                    )
                                ),

                            'email'
                                => $this
                                    ->nullableLowerString(
                                        $data[
                                            'email'
                                        ] ?? null
                                    ),

                            'phone'
                                => $this
                                    ->nullableString(
                                        $data[
                                            'phone'
                                        ] ?? null
                                    ),

                            'password'
                                => (string) $data[
                                    'password'
                                ],

                            'role'
                                => UserRole::SURVEYOR,

                            'is_active'
                                => true,
                        ]);

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'surveyor.created',

                        'auditable_type'
                            => User::class,

                        'auditable_id'
                            => $surveyor->id,

                        'metadata' => [
                            'name'
                                => $surveyor->name,

                            'username'
                                => $surveyor->username,

                            'email'
                                => $surveyor->email,

                            'phone'
                                => $surveyor->phone,

                            'is_active'
                                => true,
                        ],

                        'ip_address'
                            => $ipAddress,

                        'user_agent'
                            => $userAgent,
                    ]);

                return $surveyor;
            }
        );
    }

    public function update(
        int $surveyorId,
        array $data,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): User {
        return DB::transaction(
            function () use (
                $surveyorId,
                $data,
                $actor,
                $ipAddress,
                $userAgent
            ): User {
                $surveyor =
                    $this->surveyors
                        ->findOrFail(
                            $surveyorId
                        );

                $before = [
                    'name'
                        => $surveyor->name,

                    'username'
                        => $surveyor->username,

                    'email'
                        => $surveyor->email,

                    'phone'
                        => $surveyor->phone,
                ];

                $updateData = [
                    'name'
                        => trim(
                            (string) $data[
                                'name'
                            ]
                        ),

                    'username'
                        => Str::lower(
                            trim(
                                (string) $data[
                                    'username'
                                ]
                            )
                        ),

                    'email'
                        => $this
                            ->nullableLowerString(
                                $data[
                                    'email'
                                ] ?? null
                            ),

                    'phone'
                        => $this
                            ->nullableString(
                                $data[
                                    'phone'
                                ] ?? null
                            ),
                ];

                if (
                    ! empty(
                        $data[
                            'password'
                        ]
                    )
                ) {
                    $updateData[
                        'password'
                    ] =
                        (string) $data[
                            'password'
                        ];
                }

                $updated =
                    $this->surveyors
                        ->update(
                            $surveyor,
                            $updateData
                        );

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => 'surveyor.updated',

                        'auditable_type'
                            => User::class,

                        'auditable_id'
                            => $updated->id,

                        'metadata' => [
                            'before'
                                => $before,

                            'after' => [
                                'name'
                                    => $updated->name,

                                'username'
                                    => $updated->username,

                                'email'
                                    => $updated->email,

                                'phone'
                                    => $updated->phone,
                            ],

                            'password_changed'
                                => ! empty(
                                    $data[
                                        'password'
                                    ]
                                ),
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
        int $surveyorId,
        bool $isActive,
        User $actor,
        ?string $ipAddress,
        ?string $userAgent
    ): User {
        return DB::transaction(
            function () use (
                $surveyorId,
                $isActive,
                $actor,
                $ipAddress,
                $userAgent
            ): User {
                $surveyor =
                    $this->surveyors
                        ->findOrFail(
                            $surveyorId
                        );

                if (
                    (bool) $surveyor
                        ->is_active
                    === $isActive
                ) {
                    return $surveyor;
                }

                $updated =
                    $this->surveyors
                        ->update(
                            $surveyor,
                            [
                                'is_active'
                                    => $isActive,
                            ]
                        );

                /*
                 * Ketika akun dinonaktifkan,
                 * hapus session yang masih
                 * tersimpan untuk user tersebut.
                 */
                if (! $isActive) {
                    DB::table(
                        'sessions'
                    )
                        ->where(
                            'user_id',
                            $updated->id
                        )
                        ->delete();
                }

                $this->auditLogs
                    ->record([
                        'user_id'
                            => $actor->id,

                        'action'
                            => $isActive
                                ? 'surveyor.activated'
                                : 'surveyor.deactivated',

                        'auditable_type'
                            => User::class,

                        'auditable_id'
                            => $updated->id,

                        'metadata' => [
                            'name'
                                => $updated->name,

                            'username'
                                => $updated->username,

                            'is_active'
                                => $isActive,
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

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $normalized =
            trim(
                (string) $value
            );

        return $normalized === ''
            ? null
            : $normalized;
    }

    private function nullableLowerString(
        mixed $value
    ): ?string {
        $normalized =
            $this->nullableString(
                $value
            );

        return $normalized === null
            ? null
            : Str::lower(
                $normalized
            );
    }
}