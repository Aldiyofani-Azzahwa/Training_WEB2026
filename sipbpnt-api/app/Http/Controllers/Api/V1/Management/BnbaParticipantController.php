<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bnba\ListBnbaParticipantRequest;
use App\Http\Resources\Bnba\BpntParticipantResource;
use App\Services\Bnba\BnbaParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BnbaParticipantController
    extends Controller
{
    public function __construct(
        private readonly BnbaParticipantService $service,
    ) {}

    public function index(
        ListBnbaParticipantRequest $request
    ): JsonResponse {
        $participants =
            $this->service->paginate(
                $request->validated()
            );

        return response()->json([
            'data'
                => BpntParticipantResource
                    ::collection(
                        $participants->items()
                    ),

            'meta' => [
                'current_page'
                    => $participants
                        ->currentPage(),

                'last_page'
                    => $participants
                        ->lastPage(),

                'per_page'
                    => $participants
                        ->perPage(),

                'total'
                    => $participants
                        ->total(),
            ],
        ]);
    }

    public function options(
        Request $request
    ): JsonResponse {
        $validated =
            $request->validate([
                'period_id' => [
                    'required',
                    'integer',
                    'exists:bpnt_periods,id',
                ],
            ]);

        return response()->json([
            'data'
                => $this->service
                    ->filterOptions(
                        (int)
                        $validated[
                            'period_id'
                        ]
                    ),
        ]);
    }
}