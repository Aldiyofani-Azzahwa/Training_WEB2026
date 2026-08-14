<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BnbaRowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BnbaImportRow extends Model
{
    use HasFactory;

    protected $fillable = [
        'bnba_import_id',
        'row_number',
        'status',
        'membership_year',
        'nik_hash',
        'nik_ciphertext',
        'nkk_hash',
        'nkk_ciphertext',
        'full_name',
        'birth_place',
        'birth_date',
        'mother_name',
        'address',
        'rt',
        'rw',
        'kelurahan',
        'kecamatan',
        'account_ciphertext',
        'e_warung_name',
        'source_status',
        'source_description',
        'monthly_statuses',
        'sk_status',
        'sk_description',
        'apbn_march_status',
        'welfare_rank',
        'nominal',
        'errors',
        'warnings',
    ];

    protected $hidden = [
        'nik_hash',
        'nik_ciphertext',
        'nkk_hash',
        'nkk_ciphertext',
        'account_ciphertext',
    ];

    protected function casts(): array
    {
        return [
            'status'
                => BnbaRowStatus::class,

            'birth_date'
                => 'date:Y-m-d',

            'monthly_statuses'
                => 'array',

            'errors'
                => 'array',

            'warnings'
                => 'array',

            'welfare_rank'
                => 'integer',

            'nominal'
                => 'integer',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(
            BnbaImport::class,
            'bnba_import_id'
        );
    }
}