<?php

declare(strict_types=1);

namespace App\Http\Resources\Assignment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyorAssignmentResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id'
                => (int) $this->id,

            'period' => [
                'id'
                    => (int) $this
                        ->period
                        ->id,

                'code'
                    => (string) $this
                        ->period
                        ->code,

                'name'
                    => (string) $this
                        ->period
                        ->name,

                'year'
                    => (int) $this
                        ->period
                        ->year,
            ],

            'kelurahan' => [
                'id'
                    => (int) $this
                        ->kelurahan
                        ->id,

                'code'
                    => (string) $this
                        ->kelurahan
                        ->code,

                'name'
                    => (string) $this
                        ->kelurahan
                        ->name,

                'kecamatan' => [
                    'id'
                        => (int) $this
                            ->kelurahan
                            ->kecamatan
                            ->id,

                    'code'
                        => (string) $this
                            ->kelurahan
                            ->kecamatan
                            ->code,

                    'name'
                        => (string) $this
                            ->kelurahan
                            ->kecamatan
                            ->name,
                ],
            ],

            'surveyor' => [
                'id'
                    => (int) $this
                        ->surveyor
                        ->id,

                'name'
                    => (string) $this
                        ->surveyor
                        ->name,

                'username'
                    => (string) $this
                        ->surveyor
                        ->username,

                'phone'
                    => $this
                        ->surveyor
                        ->phone,

                'is_active'
                    => (bool) $this
                        ->surveyor
                        ->is_active,
            ],

            'assigned_by' => [
                'id'
                    => (int) $this
                        ->assignedBy
                        ->id,

                'name'
                    => (string) $this
                        ->assignedBy
                        ->name,

                'username'
                    => (string) $this
                        ->assignedBy
                        ->username,
            ],

            'assigned_at'
                => $this
                    ->assigned_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),

            'created_at'
                => $this
                    ->created_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),

            'updated_at'
                => $this
                    ->updated_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),
        ];
    }
}