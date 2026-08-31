<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SurveyorMonitoringReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'assignment_id',
        'surveyor_id',
        'kelurahan_id',
        'commodities',
        'social_officer_name',
        'distribution_assistant_name',
    ];

    protected function casts(): array
    {
        return [
            'commodities' => 'array',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            BpntPeriod::class,
            'period_id'
        );
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(
            SurveyorAssignment::class,
            'assignment_id'
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
            Kelurahan::class,
            'kelurahan_id'
        );
    }
}