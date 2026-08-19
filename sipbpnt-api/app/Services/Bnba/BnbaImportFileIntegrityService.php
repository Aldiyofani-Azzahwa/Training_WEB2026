<?php

declare(strict_types=1);

namespace App\Services\Bnba;

use App\DTOs\Bnba\BnbaFileIntegrityResult;
use App\Models\BnbaImport;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class BnbaImportFileIntegrityService
{
    public function inspect(
        BnbaImport $import
    ): BnbaFileIntegrityResult {
        $disk = $this->disk();

        if (
            ! $disk->exists(
                $import->stored_path
            )
        ) {
            return new BnbaFileIntegrityResult(
                BnbaFileIntegrityResult::MISSING
            );
        }

        try {
            $absolutePath =
                $disk->path(
                    $import->stored_path
                );

            $actualHash =
                hash_file(
                    'sha256',
                    $absolutePath
                );
        } catch (Throwable) {
            return new BnbaFileIntegrityResult(
                BnbaFileIntegrityResult::UNREADABLE
            );
        }

        if ($actualHash === false) {
            return new BnbaFileIntegrityResult(
                BnbaFileIntegrityResult::UNREADABLE
            );
        }

        if (
            ! hash_equals(
                (string) $import->file_sha256,
                $actualHash
            )
        ) {
            return new BnbaFileIntegrityResult(
                BnbaFileIntegrityResult
                    ::CHECKSUM_MISMATCH
            );
        }

        return new BnbaFileIntegrityResult(
            BnbaFileIntegrityResult::VERIFIED
        );
    }

    private function disk(): Filesystem
    {
        return Storage::disk(
            'local'
        );
    }
}