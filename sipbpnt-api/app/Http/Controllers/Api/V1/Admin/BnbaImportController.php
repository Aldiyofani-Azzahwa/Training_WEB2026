<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bnba\PreviewBnbaImportRequest;
use App\Http\Requests\Bnba\StoreBnbaImportRequest;
use App\Http\Resources\Bnba\BnbaImportResource;
use App\Http\Resources\Bnba\BnbaImportRowResource;
use App\Services\Bnba\BnbaImportService;
use App\Services\Bnba\BnbaPeriodDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BnbaImportController
    extends Controller
{
    public function __construct(
        private readonly BnbaImportService $service,
        private readonly BnbaPeriodDeletionService $deletionService,
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $perPage =
            min(
                max(
                    (int) $request
                        ->integer(
                            'per_page',
                            15
                        ),
                    1
                ),
                100
            );

        $imports =
            $this->service
                ->history(
                    $perPage
                );

        return response()->json([
            'data'
                => BnbaImportResource
                    ::collection(
                        $imports->items()
                    ),

            'meta' => [
                'current_page'
                    => $imports
                        ->currentPage(),

                'last_page'
                    => $imports
                        ->lastPage(),

                'per_page'
                    => $imports
                        ->perPage(),

                'total'
                    => $imports
                        ->total(),
            ],
        ]);
    }

    public function store(
        StoreBnbaImportRequest $request
    ): JsonResponse {
        $import =
            $this->service
                ->upload(
                    $request->file('file'),

                    (int) $request
                        ->validated(
                            'period_id'
                        ),

                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'File BNBA berhasil dibaca. Silakan periksa preview sebelum konfirmasi.',

            'data'
                => new BnbaImportResource(
                    $import
                ),
        ], 201);
    }

    public function preview(
        PreviewBnbaImportRequest $request,
        string $import
    ): JsonResponse {
        $result =
            $this->service
                ->preview(
                    (int) $import,

                    $request
                        ->validated(
                            'status'
                        ),

                    $request
                        ->validated(
                            'search'
                        ),

                    (int) (
                        $request
                            ->validated(
                                'per_page'
                            )
                        ?? 50
                    )
                );

        $rows =
            $result['rows'];

        return response()->json([
            'data' => [
                'import'
                    => new BnbaImportResource(
                        $result[
                            'import'
                        ]
                    ),

                'rows'
                    => BnbaImportRowResource
                        ::collection(
                            $rows->items()
                        ),
            ],

            'meta' => [
                'current_page'
                    => $rows
                        ->currentPage(),

                'last_page'
                    => $rows
                        ->lastPage(),

                'per_page'
                    => $rows
                        ->perPage(),

                'total'
                    => $rows
                        ->total(),
            ],
        ]);
    }

    public function confirm(
        Request $request,
        string $import
    ): JsonResponse {
        $confirmed =
            $this->service
                ->confirm(
                    (int) $import,
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Import BNBA berhasil dikonfirmasi.',

            'data'
                => new BnbaImportResource(
                    $confirmed
                ),
        ]);
    }

    public function destroyForPeriod(
        Request $request,
        string $period
    ): JsonResponse {
        $result =
            $this->deletionService
                ->deleteForPeriod(
                    (int) $period,
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'Data BNBA berhasil dihapus.',

            'data'
                => $result,
        ]);
    }
}