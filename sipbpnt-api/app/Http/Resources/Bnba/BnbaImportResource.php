<?php

declare(strict_types=1);

namespace App\Http\Resources\Bnba;

use App\Enums\BnbaImportStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BnbaImportResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        $status =
            $this->status;

        return [
            'id'
                => (int) $this->id,

            'period'
                => $this->whenLoaded(
                    'period',
                    fn () => [
                        'id'
                            => (int)
                                $this
                                    ->period
                                    ->id,

                        'code'
                            => (string)
                                $this
                                    ->period
                                    ->code,

                        'name'
                            => (string)
                                $this
                                    ->period
                                    ->name,

                        'year'
                            => (int)
                                $this
                                    ->period
                                    ->year,
                    ]
                ),

            'status'
                => $status
                    instanceof
                    BnbaImportStatus
                        ? $status->value
                        : (string) $status,

            'original_name'
                => $this->original_name,

            'summary' => [
                'total'
                    => (int)
                        $this->total_rows,

                'valid'
                    => (int)
                        $this->valid_rows,

                'warning'
                    => (int)
                        $this->warning_rows,

                'invalid'
                    => (int)
                        $this->invalid_rows,

                'duplicate'
                    => (int)
                        $this->duplicate_rows,
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
                => $this
                    ->confirmed_at
                    ?->toIso8601String(),

            'created_at'
                => $this
                    ->created_at
                    ?->toIso8601String(),
        ];
    }
}