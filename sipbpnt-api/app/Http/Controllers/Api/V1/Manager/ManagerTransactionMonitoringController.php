<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ListTransactionMonitoringRequest;
use App\Http\Resources\Manager\ManagerTransactionResource;
use App\Models\BpntPeriod;
use App\Services\Manager\ManagerTransactionMonitoringService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ManagerTransactionMonitoringController extends Controller
{
    public function __construct(
        private readonly ManagerTransactionMonitoringService $service,
    ) {}

    public function index(
        ListTransactionMonitoringRequest $request
    ): JsonResponse {
        $result = $this->service->index(
            $request->validated()
        );

        /** @var BpntPeriod|null $period */
        $period = $result['period'];

        /** @var LengthAwarePaginator|null $transactions */
        $transactions = $result['transactions'];

        return response()->json([
            'data' => [
                'period' => $period
                    ? [
                        'id' => (int) $period->id,
                        'code' => (string) $period->code,
                        'name' => (string) $period->name,
                        'year' => (int) $period->year,
                    ]
                    : null,
                'summary' => $result['summary'],
                'breakdowns' => $result['breakdowns'],
                'transactions' => ManagerTransactionResource::collection(
                    $transactions?->items() ?? []
                ),
            ],
            'meta' => [
                'current_page' => $transactions?->currentPage() ?? 1,
                'last_page' => $transactions?->lastPage() ?? 1,
                'per_page' => $transactions?->perPage() ?? 20,
                'total' => $transactions?->total() ?? 0,
            ],
        ]);
    }
}