<?php

declare(strict_types=1);

namespace App\Support\Security;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class SensitiveIdentity
{
    public function hash(string $value): string
    {
        $key = (string) config(
            'sipbpnt.identity_hash_key'
        );

        if ($key === '') {
            throw new RuntimeException(
                'SIPBPNT_IDENTITY_HASH_KEY belum dikonfigurasi.'
            );
        }

        return hash_hmac(
            'sha256',
            $value,
            $key
        );
    }

    public function encrypt(string $value): string
    {
        return Crypt::encryptString($value);
    }

    public function decrypt(string $ciphertext): string
    {
        return Crypt::decryptString($ciphertext);
    }

    public function maskCiphertext(
        ?string $ciphertext,
        int $prefix = 4,
        int $suffix = 4
    ): ?string {
        if ($ciphertext === null || $ciphertext === '') {
            return null;
        }

        return $this->mask(
            $this->decrypt($ciphertext),
            $prefix,
            $suffix
        );
    }

    public function mask(
        string $value,
        int $prefix = 4,
        int $suffix = 4
    ): string {
        $length = mb_strlen($value);

        if ($length <= ($prefix + $suffix)) {
            return str_repeat(
                '*',
                max($length, 4)
            );
        }

        return mb_substr(
            $value,
            0,
            $prefix
        )
            .str_repeat(
                '*',
                $length - $prefix - $suffix
            )
            .mb_substr(
                $value,
                -$suffix
            );
    }
}