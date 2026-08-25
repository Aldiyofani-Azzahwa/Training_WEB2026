<?php

declare(strict_types=1);

namespace App\Http\Resources\Surveyor;

use App\Enums\KpmVerificationStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KpmVerificationResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        /** @var KpmVerificationStatus $status */
        $status = $this->status;

        return [
            'id' => (int) $this->id,

            'status' => [
                'code' => $status->value,
                'label' => $status->label(),
            ],

            'reason' => $this->reason,

            'is_cancelled' => $this->cancelled_at !== null,

            'period' => [
                'id' => (int) $this->period->id,
                'code' => (string) $this->period->code,
                'name' => (string) $this->period->name,
                'year' => (int) $this->period->year,
            ],

            'participant' => new SurveyorParticipantResource(
                $this->participant
            ),

            'surveyor' => [
                'id' => (int) $this->surveyor->id,
                'name' => (string) $this->surveyor->name,
            ],

            'verified_at' => $this->verified_at
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),

            'cancelled_at' => $this->cancelled_at
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),

            'cancelled_by' => $this->cancelledBy
                ? [
                    'id' => (int) $this->cancelledBy->id,
                    'name' => (string) $this->cancelledBy->name,
                ]
                : null,
        ];
    }
}