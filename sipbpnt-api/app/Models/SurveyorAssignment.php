<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyorAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'surveyor_id',
        'kelurahan_id',
        'assigned_by',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            BpntPeriod::class,
            'period_id'
        );
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'surveyor_id'
        );
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(
            Kelurahan::class
        );
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_by'
        );
    }
}