<?php

declare(strict_types=1);

namespace App\Http\Resources\Bnba;

use App\Http\Resources\BpntPeriod\BpntPeriodResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BnbaImportResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id'
                => $this->id,

            'period'
                => new BpntPeriodResource(
                    $this->whenLoaded(
                        'period'
                    )
                ),

            'status'
                => $this->status->value,

            'original_name'
                => $this->original_name,

            'summary' => [
                'total'
                    => $this->total_rows,

                'valid'
                    => $this->valid_rows,

                'warning'
                    => $this->warning_rows,

                'invalid'
                    => $this->invalid_rows,

                'duplicate'
                    => $this->duplicate_rows,
            ],

            'uploaded_by'
                => $this->whenLoaded(
                    'uploader',
                    fn () => [
                        'id'
                            => $this
                                ->uploader?->id,

                        'name'
                            => $this
                                ->uploader?->name,

                        'username'
                            => $this
                                ->uploader
                                ?->username,
                    ]
                ),

            'confirmed_at'
                => $this->confirmed_at
                    ?->toIso8601String(),

            'created_at'
                => $this->created_at
                    ?->toIso8601String(),
        ];
    }
}