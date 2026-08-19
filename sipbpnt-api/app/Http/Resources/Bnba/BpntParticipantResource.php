<?php

declare(strict_types=1);

namespace App\Http\Resources\Bnba;

use App\Support\Security\SensitiveIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BpntParticipantResource
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
            $kelurahan
                ?->kecamatan;

        return [
            'id'
                => $this->id,

            'period' => [
                'id'
                    => $this
                        ->period
                        ->id,

                'code'
                    => $this
                        ->period
                        ->code,

                'name'
                    => $this
                        ->period
                        ->name,

                'year'
                    => $this
                        ->period
                        ->year,
            ],

            'kpm' => [
                'id'
                    => $this
                        ->kpm
                        ->id,

                'nik'
                    => $identity
                        ->maskCiphertext(
                            $this
                                ->kpm
                                ->nik_ciphertext
                        ),

                'nkk'
                    => $identity
                        ->maskCiphertext(
                            $this
                                ->kpm
                                ->nkk_ciphertext
                        ),

                'full_name'
                    => $this
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

                'mother_name'
                    => $this
                        ->kpm
                        ->mother_name,

                'address'
                    => $this
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

                /*
                 * Lokasi berasal dari participant
                 * periode, bukan lagi lokasi mutable
                 * pada master KPM.
                 */
                'kelurahan'
                    => $kelurahan
                        ?->name,

                'kecamatan'
                    => $kecamatan
                        ?->name,

                'account_number'
                    => $identity
                        ->maskCiphertext(
                            $this
                                ->kpm
                                ->account_ciphertext,
                            2,
                            2
                        ),
            ],

            'membership_year'
                => $this
                    ->membership_year,

            'e_warung_name'
                => $this
                    ->e_warung_name_source,

            'source_status'
                => $this
                    ->source_status,

            'source_description'
                => $this
                    ->source_description,

            'monthly_statuses'
                => $this
                    ->monthly_statuses
                    ?? [],

            'sk_status'
                => $this
                    ->sk_status,

            'sk_description'
                => $this
                    ->sk_description,

            'apbn_march_status'
                => $this
                    ->apbn_march_status,

            'welfare_rank'
                => $this
                    ->welfare_rank,

            'entitlement_amount'
                => $this
                    ->entitlement_amount,

            'import' => [
                'id'
                    => $this
                        ->import
                        ->id,

                'row_number'
                    => $this
                        ->import_row_number,

                'confirmed_at'
                    => $this
                        ->import
                        ->confirmed_at
                        ?->toIso8601String(),
            ],
        ];
    }
}