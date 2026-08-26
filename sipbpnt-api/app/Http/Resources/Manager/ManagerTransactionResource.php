<?php

declare(strict_types=1);

namespace App\Http\Resources\Manager;

use App\Support\Security\SensitiveIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ManagerTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SensitiveIdentity $identity */
        $identity = app(SensitiveIdentity::class);

        $participant = $this->participant;
        $kpm = $participant->kpm;
        $participantKelurahan = $this->participantKelurahan;
        $surveyorKelurahan = $this->surveyorKelurahan;

        return [
            'id' => (int) $this->id,

            'participant' => [
                'id' => (int) $participant->id,
                'kpm' => [
                    'id' => (int) $kpm->id,
                    'nik' => $identity->maskCiphertext(
                        $kpm->nik_ciphertext
                    ),
                    'full_name' => (string) $kpm->full_name,
                    'address' => (string) $kpm->address,
                    'rt' => $kpm->rt,
                    'rw' => $kpm->rw,
                ],
                'wilayah' => [
                    'kecamatan' => [
                        'id' => $participantKelurahan?->kecamatan
                            ? (int) $participantKelurahan->kecamatan->id
                            : null,
                        'name' => $participantKelurahan?->kecamatan?->name,
                    ],
                    'kelurahan' => [
                        'id' => $participantKelurahan
                            ? (int) $participantKelurahan->id
                            : null,
                        'name' => $participantKelurahan?->name,
                    ],
                ],
            ],

            'surveyor' => [
                'id' => (int) $this->surveyor->id,
                'name' => (string) $this->surveyor->name,
                'username' => (string) $this->surveyor->username,
                'assignment' => [
                    'kecamatan' => [
                        'id' => $surveyorKelurahan?->kecamatan
                            ? (int) $surveyorKelurahan->kecamatan->id
                            : null,
                        'name' => $surveyorKelurahan?->kecamatan?->name,
                    ],
                    'kelurahan' => [
                        'id' => $surveyorKelurahan
                            ? (int) $surveyorKelurahan->id
                            : null,
                        'name' => $surveyorKelurahan?->name,
                    ],
                ],
            ],

            'e_warung' => [
                'id' => (int) $this->eWarung->id,
                'name' => (string) $this->eWarung->name,
                'is_active' => (bool) $this->eWarung->is_active,
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