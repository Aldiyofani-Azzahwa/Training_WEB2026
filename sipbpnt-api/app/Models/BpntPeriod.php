<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BpntPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'year',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function imports(): HasMany
    {
        return $this->hasMany(
            BnbaImport::class
        );
    }

    public function participants(): HasMany
    {
        return $this->hasMany(
            BpntParticipant::class
        );
    }

    public function latestImport(): HasOne
    {
        return $this
            ->hasOne(
                BnbaImport::class
            )
            ->latestOfMany();
    }
}