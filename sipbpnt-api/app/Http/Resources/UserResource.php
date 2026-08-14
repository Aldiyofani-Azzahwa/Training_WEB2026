<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Mengubah model pengguna menjadi response API.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,

            'role' => $this->role->value,
            'role_label' => $this->role->label(),

            'is_active' => $this->is_active,

            'last_login_at' => $this->last_login_at
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),

            'modules' => $this->role->modules(),
        ];
    }
}