<?php

declare(strict_types=1);

namespace App\DTOs\Bnba;

final readonly class BnbaFileIntegrityResult
{
    public const VERIFIED = 'verified';

    public const MISSING = 'missing';

    public const UNREADABLE = 'unreadable';

    public const CHECKSUM_MISMATCH = 'checksum_mismatch';

    public function __construct(
        public string $status,
    ) {}

    public function isVerified(): bool
    {
        return $this->status === self::VERIFIED;
    }

    public function message(): string
    {
        return match ($this->status) {
            self::MISSING =>
                'File sumber BNBA tidak ditemukan pada private storage.',

            self::UNREADABLE =>
                'File sumber BNBA tidak dapat dibaca untuk verifikasi integritas.',

            self::CHECKSUM_MISMATCH =>
                'Checksum file sumber BNBA berubah. '
                .'Konfirmasi dibatalkan untuk mencegah '
                .'penggunaan bukti sumber yang tidak konsisten.',

            default =>
                'Integritas file sumber BNBA terverifikasi.',
        };
    }
}