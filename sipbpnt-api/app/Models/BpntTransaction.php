<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BpntTransaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'period_id',
        'bpnt_participant_id',
        'surveyor_id',
        'e_warung_id',
        'participant_kelurahan_id',
        'surveyor_kelurahan_id',
        'transacted_at',
    ];

    protected function casts(): array
    {
        return [
            'transacted_at' => 'datetime',
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

    public function eWarung(): BelongsTo
    {
        return $this->belongsTo(
            EWarung::class,
            'e_warung_id'
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
}