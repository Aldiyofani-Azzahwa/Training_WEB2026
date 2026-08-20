<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SurveyorRepositoryInterface;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;

final class EloquentSurveyorRepository
    implements SurveyorRepositoryInterface
{
    public function all(): Collection
    {
        return User::query()
            ->where(
                'role',
                UserRole::SURVEYOR->value
            )
            ->orderByDesc(
                'is_active'
            )
            ->orderBy(
                'name'
            )
            ->orderBy(
                'id'
            )
            ->get();
    }

    public function active(): Collection
    {
        return User::query()
            ->where(
                'role',
                UserRole::SURVEYOR->value
            )
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

    public function findOrFail(
        int $id
    ): User {
        return User::query()
            ->where(
                'role',
                UserRole::SURVEYOR->value
            )
            ->findOrFail(
                $id
            );
    }

    public function create(
        array $data
    ): User {
        return User::query()
            ->create(
                $data
            );
    }

    public function update(
        User $surveyor,
        array $data
    ): User {
        $surveyor
            ->fill(
                $data
            )
            ->save();

        return $surveyor
            ->fresh();
    }
}