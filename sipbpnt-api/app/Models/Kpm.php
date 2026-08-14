<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kpm extends Model
{
    use HasFactory;

    protected $fillable = [
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
            'birth_date' => 'date:Y-m-d',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(
            BpntParticipant::class
        );
    }
}