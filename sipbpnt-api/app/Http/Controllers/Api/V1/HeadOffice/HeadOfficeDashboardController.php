<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\HeadOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\HeadOffice\ShowHeadOfficeDashboardRequest;
use App\Services\HeadOffice\HeadOfficeDashboardService;
use Illuminate\Http\JsonResponse;

final class HeadOfficeDashboardController extends Controller
{
    public function __construct(
        private readonly HeadOfficeDashboardService $service,
    ) {}

    public function show(
        ShowHeadOfficeDashboardRequest $request
    ): JsonResponse {
        return response()->json([
            'data' => $this->service->show(
                $request->validated()
            ),
        ]);
    }
}