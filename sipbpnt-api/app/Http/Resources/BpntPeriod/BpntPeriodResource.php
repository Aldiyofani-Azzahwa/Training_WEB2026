<?php

declare(strict_types=1);

namespace App\Http\Resources\BpntPeriod;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BpntPeriodResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id'
                => $this->id,

            'code'
                => $this->code,

            'name'
                => $this->name,

            'year'
                => $this->year,

            'is_active'
                => $this->is_active,

            'created_at'
                => $this->created_at
                    ?->toIso8601String(),
        ];
    }
}