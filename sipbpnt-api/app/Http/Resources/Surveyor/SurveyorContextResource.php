<?php

declare(strict_types=1);

namespace App\Http\Resources\Surveyor;

use App\Models\BpntPeriod;
use App\Models\SurveyorAssignment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyorContextResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        /** @var User $surveyor */
        $surveyor =
            $this->resource[
                'surveyor'
            ];

        /** @var BpntPeriod|null $period */
        $period =
            $this->resource[
                'period'
            ];

        /** @var SurveyorAssignment|null $assignment */
        $assignment =
            $this->resource[
                'assignment'
            ];

        return [
            'surveyor' => [
                'id'
                    => (int) $surveyor->id,

                'name'
                    => (string) $surveyor->name,

                'username'
                    => (string) $surveyor
                        ->username,
            ],

            'period'
                => $period
                    instanceof BpntPeriod
                        ? [
                            'id'
                                => (int) $period->id,

                            'code'
                                => (string) $period->code,

                            'name'
                                => (string) $period->name,

                            'year'
                                => (int) $period->year,
                        ]
                        : null,

            'assignment'
                => $assignment
                    instanceof SurveyorAssignment
                        ? [
                            'id'
                                => (int) $assignment->id,

                            'kecamatan' => [
                                'id'
                                    => (int) $assignment
                                        ->kelurahan
                                        ->kecamatan
                                        ->id,

                                'name'
                                    => (string) $assignment
                                        ->kelurahan
                                        ->kecamatan
                                        ->name,
                            ],

                            'kelurahan' => [
                                'id'
                                    => (int) $assignment
                                        ->kelurahan
                                        ->id,

                                'name'
                                    => (string) $assignment
                                        ->kelurahan
                                        ->name,
                            ],
                        ]
                        : null,

            'kpm_count'
                => (int) $this
                    ->resource[
                        'kpm_count'
                    ],
        ];
    }
}