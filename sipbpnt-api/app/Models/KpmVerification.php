<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KpmVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpmVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'bpnt_participant_id',
        'surveyor_id',
        'participant_kelurahan_id',
        'surveyor_kelurahan_id',
        'status',
        'reason',
        'active_slot',
        'verified_at',
        'cancelled_by',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => KpmVerificationStatus::class,
            'active_slot' => 'integer',
            'verified_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            BpntPeriod::class,
            'period_id'
        );
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(
            BpntParticipant::class,
            'bpnt_participant_id'
        );
    }

    public function surveyor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'surveyor_id'
        );
    }

    public function participantKelurahan(): BelongsTo
    {
        return $this->belongsTo(
            Kelurahan::class,
            'participant_kelurahan_id'
        );
    }

    public function surveyorKelurahan(): BelongsTo
    {
        return $this->belongsTo(
            Kelurahan::class,
            'surveyor_kelurahan_id'
        );
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );
    }
}