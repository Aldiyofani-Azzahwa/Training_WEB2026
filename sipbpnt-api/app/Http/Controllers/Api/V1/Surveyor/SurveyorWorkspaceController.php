<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Surveyor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surveyor\ListSurveyorParticipantRequest;
use App\Http\Requests\Surveyor\LookupSurveyorNikRequest;
use App\Http\Resources\Surveyor\SurveyorContextResource;
use App\Http\Resources\Surveyor\SurveyorParticipantResource;
use App\Services\Surveyor\SurveyorWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SurveyorWorkspaceController
    extends Controller
{
    public function __construct(
        private readonly SurveyorWorkspaceService $service,
    ) {}

    public function context(
        Request $request
    ): SurveyorContextResource {
        return new SurveyorContextResource(
            $this->service
                ->context(
                    $request->user()
                )
        );
    }

    public function participants(
        ListSurveyorParticipantRequest $request
    ): JsonResponse {
        $participants =
            $this->service
                ->participants(
                    $request->user(),
                    $request->validated()
                );

        return response()->json([
            'data'
                => SurveyorParticipantResource
                    ::collection(
                        $participants
                            ->items()
                    ),

            'meta' => [
                'current_page'
                    => $participants
                        ->currentPage(),

                'last_page'
                    => $participants
                        ->lastPage(),

                'per_page'
                    => $participants
                        ->perPage(),

                'total'
                    => $participants
                        ->total(),
            ],
        ]);
    }

    public function lookupNik(
        LookupSurveyorNikRequest $request
    ): JsonResponse {
        $result =
            $this->service
                ->lookupNik(
                    $request->user(),

                    (string) $request
                        ->validated(
                            'nik'
                        ),

                    $request->ip(),

                    $request->userAgent()
                );

        $assignment =
            $result[
                'assignment'
            ];

        $outsideAssignment =
            (bool) $result[
                'is_outside_assignment'
            ];

        return response()->json([
            'data' => [
                'participant'
                    => new SurveyorParticipantResource(
                        $result[
                            'participant'
                        ]
                    ),

                'scope' => [
                    'outside_assignment'
                        => $outsideAssignment,

                    /*
                     * UI dapat langsung menampilkan:
                     *
                     * KPM Luar Wilayah
                     *
                     * atau:
                     *
                     * KPM Wilayah JAGALAN
                     */
                    'label'
                        => $outsideAssignment
                            ? 'KPM Luar Wilayah'
                            : 'KPM Wilayah '
                                . $assignment
                                    ->kelurahan
                                    ->name,

                    'surveyor_kelurahan' => [
                        'id'
                            => (int) $assignment
                                ->kelurahan
                                ->id,

                        'name'
                            => (string) $assignment
                                ->kelurahan
                                ->name,
                    ],
                ],
            ],
        ]);
    }
}