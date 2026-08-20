<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Management;

use App\Http\Controllers\Controller;
use App\Http\Resources\Wilayah\KecamatanResource;
use App\Services\Wilayah\WilayahService;
use Illuminate\Http\JsonResponse;

class WilayahController
    extends Controller
{
    public function __construct(
        private readonly WilayahService $service,
    ) {}

    public function index(): JsonResponse
    {
        $master =
            $this->service
                ->master();

        return response()->json([
            'data'
                => KecamatanResource
                    ::collection(
                        $master[
                            'kecamatans'
                        ]
                    ),

            'meta' => [
                'kecamatans_count'
                    => $master[
                        'kecamatans_count'
                    ],

                'kelurahans_count'
                    => $master[
                        'kelurahans_count'
                    ],
            ],
        ]);
    }
}