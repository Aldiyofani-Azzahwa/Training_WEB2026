<?php

declare(strict_types=1);

namespace App\Http\Resources\Surveyor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyorResource
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

            'email'
                => $this->email,

            'phone'
                => $this->phone,

            'is_active'
                => (bool) $this->is_active,

            'last_login_at'
                => $this->last_login_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),

            'created_at'
                => $this->created_at
                    ?->timezone(
                        'Asia/Jakarta'
                    )
                    ->toIso8601String(),
        ];
    }
}