<?php

declare(strict_types=1);

namespace App\Http\Resources\Surveyor;

use App\Enums\KpmVerificationStatus;
use App\Support\Security\SensitiveIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SurveyorParticipantResource
    extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        /** @var SensitiveIdentity $identity */
        $identity =
            app(
                SensitiveIdentity::class
            );

        $kelurahan =
            $this->kelurahan;

        $kecamatan =
            $kelurahan?->kecamatan;

        return [
            'id'
                => (int) $this->id,

            'kpm' => [
                'id'
                    => (int) $this
                        ->kpm
                        ->id,

                'nik'
                    => $identity
                        ->maskCiphertext(
                            $this->kpm
                                ->nik_ciphertext
                        ),

                'full_name'
                    => (string) $this
                        ->kpm
                        ->full_name,

                'birth_place'
                    => $this
                        ->kpm
                        ->birth_place,

                'birth_date'
                    => $this
                        ->kpm
                        ->birth_date
                        ?->format(
                            'Y-m-d'
                        ),

                'address'
                    => (string) $this
                        ->kpm
                        ->address,

                'rt'
                    => $this
                        ->kpm
                        ->rt,

                'rw'
                    => $this
                        ->kpm
                        ->rw,
            ],

            'wilayah' => [
                'kelurahan' => [
                    'id'
                        => $kelurahan
                            ? (int) $kelurahan->id
                            : null,

                    'name'
                        => $kelurahan?->name,
                ],

                'kecamatan' => [
                    'id'
                        => $kecamatan
                            ? (int) $kecamatan->id
                            : null,

                    'name'
                        => $kecamatan?->name,
                ],
            ],

            /*
             * Database tetap menggunakan
             * entitlement_amount.
             *
             * UI menggunakan istilah:
             *
             * Saldo BPNT.
             */
            'saldo_bpnt'
                => (int) $this
                    ->entitlement_amount,

            'activity'
                => $this
                    ->activityState(),
        ];
    }

    private function activityState(): ?array
    {
        /*
         * Resource transaksi lama juga memakai
         * SurveyorParticipantResource.
         *
         * Jika query tidak meminta status
         * aktivitas, response tetap aman
         * dengan activity null.
         */
        if (
            ! array_key_exists(
                'has_transaction',
                $this
                    ->resource
                    ->getAttributes()
            )
            ||
            ! $this
                ->resource
                ->relationLoaded(
                    'activeVerification'
                )
        ) {
            return null;
        }

        if (
            (bool) $this
                ->has_transaction
        ) {
            return [
                'code'
                    => 'transacted',

                'label'
                    => 'Sudah Bertransaksi',

                'is_final'
                    => true,

                'can_record_transaction'
                    => false,
            ];
        }

        $verification =
            $this
                ->activeVerification;

        if ($verification !== null) {
            /** @var KpmVerificationStatus $status */
            $status =
                $verification
                    ->status;

            return [
                'code'
                    => $status->value,

                'label'
                    => $status->label(),

                'is_final'
                    => true,

                'can_record_transaction'
                    => false,
            ];
        }

        return [
            'code'
                => 'pending',

            'label'
                => 'Belum Transaksi',

            'is_final'
                => false,

            'can_record_transaction'
                => true,
        ];
    }
}