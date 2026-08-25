<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Surveyor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Surveyor\ListSurveyorActivityRequest;
use App\Http\Requests\Surveyor\ListSurveyorParticipantRequest;
use App\Http\Requests\Surveyor\StoreKpmVerificationRequest;
use App\Http\Requests\Surveyor\StoreSurveyorTransactionRequest;
use App\Http\Resources\EWarung\EWarungResource;
use App\Http\Resources\Surveyor\KpmVerificationResource;
use App\Http\Resources\Surveyor\SurveyorParticipantResource;
use App\Http\Resources\Surveyor\SurveyorTransactionResource;
use App\Services\Surveyor\SurveyorKpmActivityService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class SurveyorKpmActivityController
    extends Controller
{
    public function __construct(
        private readonly SurveyorKpmActivityService $service,
    ) {}

    public function eWarungs(
        Request $request
    ): JsonResponse {
        return response()->json([
            'data' => EWarungResource::collection(
                $this->service->activeEWarungs(
                    $request->user()
                )
            ),
        ]);
    }

    public function pending(
        ListSurveyorParticipantRequest $request
    ): JsonResponse {
        $participants = $this->service
            ->pendingParticipants(
                $request->user(),
                $request->validated()
            );

        return response()->json([
            'data' => SurveyorParticipantResource::collection(
                $participants->items()
            ),

            'meta' => $this->paginationMeta(
                $participants
            ),
        ]);
    }

    public function storeTransaction(
        StoreSurveyorTransactionRequest $request
    ): JsonResponse {
        $transaction = $this->service
            ->storeTransaction(
                $request->user(),
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

        return response()->json([
            'message'
                => 'KPM berhasil ditandai Sudah Bertransaksi.',

            'data'
                => new SurveyorTransactionResource(
                    $transaction
                ),
        ], 201);
    }

    public function storeVerification(
        StoreKpmVerificationRequest $request
    ): JsonResponse {
        $verification = $this->service
            ->storeVerification(
                $request->user(),
                $request->validated(),
                $request->ip(),
                $request->userAgent()
            );

        return response()->json([
            'message'
                => 'Hasil verifikasi KPM berhasil disimpan.',

            'data'
                => new KpmVerificationResource(
                    $verification
                ),
        ], 201);
    }

    public function history(
        ListSurveyorActivityRequest $request
    ): JsonResponse {
        $history = $this->service->history(
            $request->user(),
            (int) $request->validated(
                'per_page',
                20
            )
        );

        $transactions = $history['transactions'];
        $verifications = $history['verifications'];

        return response()->json([
            'data' => [
                'transactions'
                    => SurveyorTransactionResource::collection(
                        $transactions->items()
                    ),

                'verifications'
                    => KpmVerificationResource::collection(
                        $verifications->items()
                    ),
            ],

            'meta' => [
                'transactions'
                    => $this->paginationMeta(
                        $transactions
                    ),

                'verifications'
                    => $this->paginationMeta(
                        $verifications
                    ),
            ],
        ]);
    }

    private function paginationMeta(
        LengthAwarePaginator $paginator
    ): array {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}