<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surveyor\StoreSurveyorRequest;
use App\Http\Requests\Surveyor\UpdateSurveyorRequest;
use App\Http\Requests\Surveyor\UpdateSurveyorStatusRequest;
use App\Http\Resources\Surveyor\SurveyorResource;
use App\Services\UserManagement\SurveyorService;
use Illuminate\Http\JsonResponse;

class SurveyorController
    extends Controller
{
    public function __construct(
        private readonly SurveyorService $service,
    ) {}

    public function index(): JsonResponse
    {
        $surveyors =
            $this->service
                ->all();

        return response()->json([
            'data'
                => SurveyorResource
                    ::collection(
                        $surveyors
                    ),

            'meta' => [
                'total'
                    => $surveyors
                        ->count(),

                'active'
                    => $surveyors
                        ->where(
                            'is_active',
                            true
                        )
                        ->count(),

                'inactive'
                    => $surveyors
                        ->where(
                            'is_active',
                            false
                        )
                        ->count(),
            ],
        ]);
    }

    public function store(
        StoreSurveyorRequest $request
    ): JsonResponse {
        $surveyor =
            $this->service
                ->create(
                    $request->validated(),
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Akun Surveyor berhasil ditambahkan.',

            'data'
                => new SurveyorResource(
                    $surveyor
                ),
        ], 201);
    }

    public function update(
        UpdateSurveyorRequest $request,
        string $surveyor
    ): JsonResponse {
        $updated =
            $this->service
                ->update(
                    (int) $surveyor,
                    $request->validated(),
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Akun Surveyor berhasil diperbarui.',

            'data'
                => new SurveyorResource(
                    $updated
                ),
        ]);
    }

    public function updateStatus(
        UpdateSurveyorStatusRequest $request,
        string $surveyor
    ): JsonResponse {
        $updated =
            $this->service
                ->setActive(
                    (int) $surveyor,

                    (bool) $request
                        ->validated(
                            'is_active'
                        ),

                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => $updated->is_active
                    ? 'Akun Surveyor berhasil diaktifkan.'
                    : 'Akun Surveyor berhasil dinonaktifkan.',

            'data'
                => new SurveyorResource(
                    $updated
                ),
        ]);
    }
}