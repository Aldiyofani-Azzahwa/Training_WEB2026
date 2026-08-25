<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BpntParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'bpnt_period_id',
        'kpm_id',
        'kelurahan_id',
        'bnba_import_id',
        'import_row_number',
        'membership_year',
        'e_warung_name_source',
        'source_status',
        'source_description',
        'monthly_statuses',
        'sk_status',
        'sk_description',
        'apbn_march_status',
        'welfare_rank',
        'entitlement_amount',
    ];

    protected function casts(): array
    {
        return [
            'monthly_statuses'
                => 'array',

            'welfare_rank'
                => 'integer',

            'entitlement_amount'
                => 'integer',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            BpntPeriod::class,
            'bpnt_period_id'
        );
    }

    public function kpm(): BelongsTo
    {
        return $this->belongsTo(
            Kpm::class
        );
    }

    public function kelurahan(): BelongsTo
    {
        return $this->belongsTo(
            Kelurahan::class
        );
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(
            BnbaImport::class,
            'bnba_import_id'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(
            BpntTransaction::class,
            'bpnt_participant_id'
        );
    }

    public function activeVerification(): HasOne
    {
        return $this->hasOne(
            KpmVerification::class,
            'bpnt_participant_id'
        )->where(
            'active_slot',
            1
        );
    }
}