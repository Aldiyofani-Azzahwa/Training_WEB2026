<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BpntPeriod\StoreBpntPeriodRequest;
use App\Http\Resources\BpntPeriod\BpntPeriodResource;
use App\Services\Bnba\BpntPeriodService;
use Illuminate\Http\JsonResponse;

class BpntPeriodController extends Controller
{
    public function __construct(
        private readonly BpntPeriodService $service,
    ) {}

    /**
     * Menampilkan seluruh periode BPNT.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => BpntPeriodResource::collection(
                $this->service->all()
            ),
        ]);
    }

    /**
     * Membuat periode BPNT baru.
     */
    public function store(
        StoreBpntPeriodRequest $request
    ): JsonResponse {
        $period = $this->service->create(
            $request->validated(),
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Periode BPNT berhasil dibuat.',
            'data' => new BpntPeriodResource($period),
        ], 201);
    }
}