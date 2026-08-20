<?php

declare(strict_types=1);

namespace App\Http\Resources\EWarung;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EWarungResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id'
                => (int) $this->id,

            'name'
                => (string) $this->name,

            'is_active'
                => (bool) $this->is_active,

            'created_at'
                => $this->created_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),

            'updated_at'
                => $this->updated_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),
        ];
    }
}