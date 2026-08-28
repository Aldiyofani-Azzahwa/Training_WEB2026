<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use App\Services\Report\BpntReportService;
use App\Services\Report\BpntReportExcelExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class BpntReportController extends Controller
{
    public function __construct(
        private readonly BpntReportService $service,
        private readonly BpntReportExcelExportService $excel,
    ) {}

    public function index(
        Request $request
    ): JsonResponse {
        return response()->json([
            'data' => $this->service->index(
                $request->user()
            ),
        ]);
    }

    public function show(
        Request $request,
        string $period
    ): JsonResponse {
        return response()->json([
            'data' => $this->service->show(
                $request->user(),
                (int) $period
            ),
        ]);
    }

    public function finalize(
        Request $request,
        string $period
    ): JsonResponse {
        $report = $this->service->finalize(
            $request->user(),
            (int) $period,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Laporan berhasil divalidasi dan ditetapkan sebagai laporan final.',
            'data' => $report,
        ], 201);
    }

    public function excel(
        string $period
    ): StreamedResponse {
        return $this->excel->download(
            $this->service->finalSnapshot(
                (int) $period
            )
        );
    }
}