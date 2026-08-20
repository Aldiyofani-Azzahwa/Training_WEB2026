<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EWarung\StoreEWarungRequest;
use App\Http\Requests\EWarung\UpdateEWarungRequest;
use App\Http\Requests\EWarung\UpdateEWarungStatusRequest;
use App\Http\Resources\EWarung\EWarungResource;
use App\Services\EWarungService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EWarungController
    extends Controller
{
    public function __construct(
        private readonly EWarungService $service,
    ) {}

    public function index(): JsonResponse
    {
        $eWarungs =
            $this->service
                ->all();

        return response()->json([
            'data'
                => EWarungResource
                    ::collection(
                        $eWarungs
                    ),

            'meta' => [
                'total'
                    => $eWarungs
                        ->count(),

                'active'
                    => $eWarungs
                        ->where(
                            'is_active',
                            true
                        )
                        ->count(),

                'inactive'
                    => $eWarungs
                        ->where(
                            'is_active',
                            false
                        )
                        ->count(),
            ],
        ]);
    }

    public function store(
        StoreEWarungRequest $request
    ): JsonResponse {
        $eWarung =
            $this->service
                ->create(
                    $request->validated(),
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'E-Warung berhasil ditambahkan.',

            'data'
                => new EWarungResource(
                    $eWarung
                ),
        ], 201);
    }

    public function update(
        UpdateEWarungRequest $request,
        string $eWarung
    ): JsonResponse {
        $updated =
            $this->service
                ->update(
                    (int) $eWarung,
                    $request->validated(),
                    $request->user(),
                    $request->ip(),
                    $request->userAgent()
                );

        return response()->json([
            'message'
                => 'E-Warung berhasil diperbarui.',

            'data'
                => new EWarungResource(
                    $updated
                ),
        ]);
    }

    public function updateStatus(
        UpdateEWarungStatusRequest $request,
        string $eWarung
    ): JsonResponse {
        $updated =
            $this->service
                ->setActive(
                    (int) $eWarung,

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
                    ? 'E-Warung berhasil diaktifkan.'
                    : 'E-Warung berhasil dinonaktifkan.',

            'data'
                => new EWarungResource(
                    $updated
                ),
        ]);
    }

    public function destroy(
        Request $request,
        string $eWarung
    ): JsonResponse {
        $this->service
            ->delete(
                (int) $eWarung,
                $request->user(),
                $request->ip(),
                $request->userAgent()
            );

        return response()->json([
            'message'
                => 'E-Warung berhasil dihapus.',
        ]);
    }
}