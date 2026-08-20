<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Management;

use App\Http\Controllers\Controller;
use App\Http\Resources\Surveyor\SurveyorOptionResource;
use App\Services\UserManagement\SurveyorService;
use Illuminate\Http\JsonResponse;

class SurveyorOptionController
    extends Controller
{
    public function __construct(
        private readonly SurveyorService $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data'
                => SurveyorOptionResource
                    ::collection(
                        $this->service
                            ->activeOptions()
                    ),
        ]);
    }
}