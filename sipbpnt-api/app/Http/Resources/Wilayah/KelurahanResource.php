<?php

declare(strict_types=1);

namespace App\Http\Resources\Wilayah;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KelurahanResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id'
                => (int) $this->id,

            'code'
                => (string) $this->code,

            'name'
                => (string) $this->name,
        ];
    }
}