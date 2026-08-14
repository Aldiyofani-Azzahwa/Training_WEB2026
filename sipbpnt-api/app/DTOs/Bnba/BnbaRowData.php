<?php

declare(strict_types=1);

namespace App\DTOs\Bnba;

use DateTimeImmutable;

final readonly class BnbaRowData
{
    /**
     * @param array<string, string|null> $monthlyStatuses
     * @param array<int, string> $errors
     * @param array<int, string> $warnings
     */
    public function __construct(
        public int $rowNumber,
        public string $membershipYear,
        public string $nik,
        public string $nkk,
        public string $fullName,
        public ?string $birthPlace,
        public ?DateTimeImmutable $birthDate,
        public ?string $motherName,
        public string $address,
        public ?string $rt,
        public ?string $rw,
        public string $kelurahan,
        public string $kecamatan,
        public string $accountNumber,
        public string $eWarungName,
        public ?string $sourceStatus,
        public ?string $sourceDescription,
        public array $monthlyStatuses,
        public ?string $skStatus,
        public ?string $skDescription,
        public ?string $apbnMarchStatus,
        public ?int $welfareRank,
        public ?int $nominal,
        public array $errors = [],
        public array $warnings = [],
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }
}