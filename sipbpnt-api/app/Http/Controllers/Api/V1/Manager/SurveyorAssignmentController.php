<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Assignment\StoreSurveyorAssignmentRequest;
use App\Http\Resources\Assignment\SurveyorAssignmentResource;
use App\Services\Assignment\SurveyorAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyorAssignmentController
    extends Controller
{
    public function __construct(
        private readonly SurveyorAssignmentService $service,
    ) {}

    public function index(): JsonResponse
    {
        $result =
            $this->service
                ->listForActivePeriod();

        return response()->json([
            'data'
                => SurveyorAssignmentResource
                    ::collection(
                        $result[
                            'assignments'
                        ]
                    ),

            'meta' => [
                'period' => [
                    'id'
                        => (int) $result[
                            'period'
                        ]->id,

                    'code'
                        => (string) $result[
                            'period'
                        ]->code,

                    'name'
                        => (string) $result[
                            'period'
                        ]->name,

                    'year'
                        => (int) $result[
                            'period'
                        ]->year,
                ],

                'total_kelurahans'
                    => $result[
                        'total_kelurahans'
                    ],

                'assigned_count'
                    => $result[
                        'assigned_count'
                    ],

                'unassigned_count'
                    => $result[
                        'unassigned_count'
                    ],

                'total_assignments'
                    => $result[
                        'total_assignments'
                    ],

                'max_surveyors_per_kelurahan'
                    => SurveyorAssignmentService
                        ::MAX_SURVEYORS_PER_KELURAHAN,
            ],
        ]);
    }

    public function store(
        StoreSurveyorAssignmentRequest $request
    ): JsonResponse {
        $assignment =
            $this->service
                ->assign(
                    $request
                        ->validated(),

                    $request
                        ->user(),

                    $request
                        ->ip(),

                    $request
                        ->userAgent()
                );

        return response()->json([
            'message'
                => 'Penugasan Surveyor berhasil disimpan.',

            'data'
                => new SurveyorAssignmentResource(
                    $assignment
                ),
        ]);
    }

    public function destroy(
        Request $request,
        string $assignment
    ): JsonResponse {
        $this->service
            ->unassign(
                (int) $assignment,

                $request
                    ->user(),

                $request
                    ->ip(),

                $request
                    ->userAgent()
            );

        return response()->json([
            'message'
                => 'Penugasan Surveyor berhasil dihapus.',
        ]);
    }
}