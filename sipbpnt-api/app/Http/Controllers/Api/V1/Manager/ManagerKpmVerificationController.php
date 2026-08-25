<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Manager;

use App\Http\Controllers\Controller;
use App\Http\Requests\Manager\ListKpmVerificationRequest;
use App\Http\Resources\Surveyor\KpmVerificationResource;
use App\Services\Manager\ManagerKpmVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ManagerKpmVerificationController
    extends Controller
{
    public function __construct(
        private readonly ManagerKpmVerificationService $service,
    ) {}

    public function index(
        ListKpmVerificationRequest $request
    ): JsonResponse {
        $verifications = $this->service->index(
            $request->validated()
        );

        return response()->json([
            'data' => KpmVerificationResource::collection(
                $verifications->items()
            ),

            'meta' => [
                'current_page' => $verifications->currentPage(),
                'last_page' => $verifications->lastPage(),
                'per_page' => $verifications->perPage(),
                'total' => $verifications->total(),
            ],
        ]);
    }

    public function cancel(
        Request $request,
        int $verification
    ): JsonResponse {
        $cancelled = $this->service->cancel(
            $request->user(),
            $verification,
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message'
                => 'Verifikasi KPM berhasil dibatalkan.',

            'data'
                => new KpmVerificationResource(
                    $cancelled
                ),
        ]);
    }
}