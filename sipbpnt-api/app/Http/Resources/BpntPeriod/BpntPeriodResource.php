<?php

declare(strict_types=1);

namespace App\Http\Resources\BpntPeriod;

use App\Enums\BnbaImportStatus;
use App\Models\BnbaImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BpntPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $importsCount =
            (int) ($this->imports_count ?? 0);

        $participantsCount =
            (int) ($this->participants_count ?? 0);

        $assignmentsCount =
            (int) ($this->assignments_count ?? 0);

        $isActive =
            (bool) $this->is_active
            &&
            (int) $this->active_slot === 1;

        $latestImport =
            $this->relationLoaded('latestImport')
                ? $this->latestImport
                : null;

        $hasConfirmedBnba =
            $latestImport instanceof BnbaImport
            &&
            $latestImport->status
                === BnbaImportStatus::CONFIRMED
            &&
            $participantsCount > 0;

        return [
            'id' => (int) $this->id,

            'code' => (string) $this->code,

            'name' => (string) $this->name,

            'year' => (int) $this->year,

            'is_active' => $isActive,

            'imports_count' => $importsCount,

            'participants_count' => $participantsCount,

            'assignments_count' => $assignmentsCount,

            'can_activate' =>
                ! $isActive
                && $hasConfirmedBnba,

            'can_delete' =>
                ! $isActive
                && $importsCount === 0
                && $participantsCount === 0
                && $assignmentsCount === 0,

            'can_edit_year' =>
                $importsCount === 0,

            'can_delete_bnba' =>
                ! $isActive
                && $assignmentsCount === 0,

            'bnba' =>
                $latestImport instanceof BnbaImport
                    ? $this->serializeBnba($latestImport)
                    : null,

            'created_at' =>
                $this->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $this->updated_at
                    ?->toIso8601String(),
        ];
    }

    private function serializeBnba(
        BnbaImport $import
    ): array {
        $status =
            $import->status;

        return [
            'id'
                => (int) $import->id,

            'status'
                => $status instanceof BnbaImportStatus
                    ? $status->value
                    : (
                        is_string($status)
                            ? $status
                            : null
                    ),

            'original_name'
                => (string) $import->original_name,

            'summary' => [
                'total'
                    => (int) $import->total_rows,

                'valid'
                    => (int) $import->valid_rows,

                'warning'
                    => (int) $import->warning_rows,

                'invalid'
                    => (int) $import->invalid_rows,

                'duplicate'
                    => (int) $import->duplicate_rows,
            ],

            'confirmed_at'
                => $import->confirmed_at
                    ?->toIso8601String(),

            'created_at'
                => $import->created_at
                    ?->toIso8601String(),
        ];
    }
}