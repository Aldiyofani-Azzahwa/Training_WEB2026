<?php

declare(strict_types=1);

namespace App\Http\Resources\Surveyor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyorTransactionResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => (int) $this->id,

            'status' => [
                'code' => 'transacted',
                'label' => 'Sudah Bertransaksi',
            ],

            'period' => [
                'id' => (int) $this->period->id,
                'code' => (string) $this->period->code,
                'name' => (string) $this->period->name,
                'year' => (int) $this->period->year,
            ],

            'participant' => new SurveyorParticipantResource(
                $this->participant
            ),

            'e_warung' => [
                'id' => (int) $this->eWarung->id,
                'name' => (string) $this->eWarung->name,
            ],

            'surveyor' => [
                'id' => (int) $this->surveyor->id,
                'name' => (string) $this->surveyor->name,
            ],

            'outside_assignment' =>
                (int) $this->participant_kelurahan_id
                !== (int) $this->surveyor_kelurahan_id,

            'transacted_at' => $this->transacted_at
                ?->timezone('Asia/Jakarta')
                ->toIso8601String(),
        ];
    }
}