<?php

declare(strict_types=1);

namespace App\Http\Resources\Surveyor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyorOptionResource
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

            'username'
                => (string) $this->username,

            'phone'
                => $this->phone,
        ];
    }
}