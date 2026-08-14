<?php

declare(strict_types=1);

namespace App\Http\Resources\Bnba;

use App\Support\Security\SensitiveIdentity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BnbaImportRowResource
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

        return [
            'id'
                => $this->id,

            'row_number'
                => $this->row_number,

            'status'
                => $this->status->value,

            'membership_year'
                => $this->membership_year,

            'nik'
                => $identity
                    ->maskCiphertext(
                        $this
                            ->nik_ciphertext
                    ),

            'nkk'
                => $identity
                    ->maskCiphertext(
                        $this
                            ->nkk_ciphertext
                    ),

            'full_name'
                => $this->full_name,

            'birth_place'
                => $this->birth_place,

            'birth_date'
                => $this
                    ->birth_date
                    ?->format('Y-m-d'),

            'mother_name'
                => $this->mother_name,

            'address'
                => $this->address,

            'rt'
                => $this->rt,

            'rw'
                => $this->rw,

            'kelurahan'
                => $this->kelurahan,

            'kecamatan'
                => $this->kecamatan,

            'account_number'
                => $identity
                    ->maskCiphertext(
                        $this
                            ->account_ciphertext,
                        2,
                        2
                    ),

            'e_warung_name'
                => $this->e_warung_name,

            'source_status'
                => $this->source_status,

            'source_description'
                => $this
                    ->source_description,

            'monthly_statuses'
                => $this
                    ->monthly_statuses
                    ?? [],

            'sk_status'
                => $this->sk_status,

            'sk_description'
                => $this
                    ->sk_description,

            'apbn_march_status'
                => $this
                    ->apbn_march_status,

            'welfare_rank'
                => $this->welfare_rank,

            'nominal'
                => $this->nominal,

            'errors'
                => $this->errors ?? [],

            'warnings'
                => $this->warnings ?? [],
        ];
    }
}