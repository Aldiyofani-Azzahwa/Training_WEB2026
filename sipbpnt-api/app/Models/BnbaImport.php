<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BnbaImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BnbaImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'bpnt_period_id',
        'uploaded_by',
        'confirmed_by',
        'status',
        'original_name',
        'stored_path',
        'file_sha256',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'invalid_rows',
        'duplicate_rows',
        'confirmed_at',
        'error_message',
    ];

    protected $hidden = [
        'stored_path',
        'file_sha256',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status'
                => BnbaImportStatus::class,

            'confirmed_at'
                => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(
            BpntPeriod::class,
            'bpnt_period_id'
        );
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'confirmed_by'
        );
    }

    public function rows(): HasMany
    {
        return $this->hasMany(
            BnbaImportRow::class
        );
    }
}