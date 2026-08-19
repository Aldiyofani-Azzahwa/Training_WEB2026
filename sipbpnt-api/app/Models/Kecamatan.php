<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kecamatan extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function kelurahans(): HasMany
    {
        return $this->hasMany(
            Kelurahan::class
        );
    }
}