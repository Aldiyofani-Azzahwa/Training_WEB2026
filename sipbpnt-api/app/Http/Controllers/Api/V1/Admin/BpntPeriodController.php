<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BpntPeriod\StoreBpntPeriodRequest;
use App\Http\Requests\BpntPeriod\UpdateBpntPeriodRequest;
use App\Http\Resources\BpntPeriod\BpntPeriodResource;
use App\Services\Bnba\BpntPeriodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BpntPeriodController
    extends Controller
{
    public function __construct(
        private readonly BpntPeriodService $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data'
                => BpntPeriodResource
                    ::collection(
                        $this->service->all()
                    ),
        ]);
    }

    public function active(): JsonResponse
    {
        $period =
            $this->service
                ->active();

        return response()->json([
            'data'
                => $period
                    ? new BpntPeriodResource(
                        $period
                    )
                    : null,
        ]);
    }

    public function store(
        StoreBpntPeriodRequest $request
    ): JsonResponse {
        $period =
            $this->service
                ->create(
                    $request->validated(),
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Periode BPNT berhasil dibuat.',

            'data'
                => new BpntPeriodResource(
                    $period
                ),
        ], 201);
    }

    public function update(
        UpdateBpntPeriodRequest $request,
        string $period
    ): JsonResponse {
        $updated =
            $this->service
                ->update(
                    (int) $period,
                    $request->validated(),
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Periode BPNT berhasil diperbarui.',

            'data'
                => new BpntPeriodResource(
                    $updated
                ),
        ]);
    }

    public function activate(
        Request $request,
        string $period
    ): JsonResponse {
        $activated =
            $this->service
                ->activate(
                    (int) $period,
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Periode BPNT berhasil diaktifkan.',

            'data'
                => new BpntPeriodResource(
                    $activated
                ),
        ]);
    }

    public function deactivate(
        Request $request,
        string $period
    ): JsonResponse {
        $deactivated =
            $this->service
                ->deactivate(
                    (int) $period,
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Periode BPNT berhasil dinonaktifkan.',

            'data'
                => new BpntPeriodResource(
                    $deactivated
                ),
        ]);
    }

    public function destroy(
        Request $request,
        string $period
    ): JsonResponse {
        $this->service
            ->delete(
                (int) $period,
                $request->user(),
                $request->ip(),
                $request->userAgent()
            );

        return response()->json([
            'message'
                => 'Periode BPNT berhasil dihapus.',
        ]);
    }
}