<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BpntReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BpntReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'status',
        'summary',
        'snapshot',
        'finalized_by',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BpntReportStatus::class,
            'summary' => 'array',
            'snapshot' => 'array',
            'finalized_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            BpntPeriod::class,
            'period_id'
        );
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'finalized_by'
        );
    }
}
