<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;

interface SurveyorRepositoryInterface
{
    public function all(): Collection;

    public function active(): Collection;

    public function findOrFail(int $id): User;

    public function create(array $data): User;

    public function update(
        User $surveyor,
        array $data
    ): User;
}