<?php

declare(strict_types=1);

namespace App\Support\Bnba;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class BnbaHeaderMap
{
    /**
     * @var array<string, string>
     */
    private const MONTHS = [
        'JAN' => 'jan',
        'FEB' => 'feb',
        'MAR' => 'mar',
        'APR' => 'apr',
        'MEI' => 'may',
        'JUN' => 'jun',
        'JUL' => 'jul',
        'AGU' => 'aug',
        'SEP' => 'sep',
        'OKT' => 'oct',
        'NOV' => 'nov',
        'DES' => 'dec',
    ];

    /**
     * @return array<string, int>
     */
    public function resolve(
        Worksheet $sheet,
        int $periodYear
    ): array {
        $found = [];

        $highestColumn =
            Coordinate::columnIndexFromString(
                $sheet->getHighestDataColumn()
            );

        for (
            $column = 1;
            $column <= $highestColumn;
            $column++
        ) {
            $value = $sheet->getCell(
                Coordinate::stringFromColumnIndex(
                    $column
                ).'1'
            )->getValue();

            if (
                $value === null
                || trim((string) $value) === ''
            ) {
                continue;
            }

            $key = $this->mapHeader(
                (string) $value,
                $periodYear
            );

            if ($key === null) {
                continue;
            }

            if (isset($found[$key])) {
                throw ValidationException
                    ::withMessages([
                        'file' => [
                            'Struktur header BNBA '
                            .'tidak valid karena '
                            .'terdapat header ganda '
                            .'untuk kolom '
                            .$key
                            .'.',
                        ],
                    ]);
            }

            $found[$key] = $column;
        }

        $missing = array_values(
            array_diff(
                $this->requiredKeys(),
                array_keys($found)
            )
        );

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => [
                    'Struktur header BNBA tidak sesuai. '
                    .'Kolom internal yang belum dikenali: '
                    .implode(', ', $missing)
                    .'.',
                ],
            ]);
        }

        return $found;
    }

    private function mapHeader(
        string $header,
        int $periodYear
    ): ?string {
        $normalized = $this->normalize($header);

        $static = [
            'TAHUN KEPESERTAAN' => 'membership_year',
            'NIK' => 'nik',
            'NKK' => 'nkk',
            'NAMA LENGKAP' => 'full_name',
            'TEMPAT LAHIR' => 'birth_place',
            'TANGGAL LAHIR' => 'birth_date',
            'NAMA IBU KANDUNG' => 'mother_name',
            'ALAMAT' => 'address',
            'RT' => 'rt',
            'RW' => 'rw',
            'KELURAHAN' => 'kelurahan',
            'KECAMATAN' => 'kecamatan',
            'NOMOR REKENING' => 'account_number',
            'E WAROENG BARU' => 'e_warung_name',
            'PERINGKAT KESEJAHTERAAN KELUARGA'
                => 'welfare_rank',
            'NOMINAL' => 'nominal',
        ];

        if (isset($static[$normalized])) {
            return $static[$normalized];
        }

        if (
            $normalized ===
            "CEK {$periodYear}"
        ) {
            return 'source_status';
        }

        if (
            $normalized ===
            "KETERANGAN {$periodYear}"
        ) {
            return 'source_description';
        }

        if (
            $normalized ===
            "CEK SK {$periodYear}"
        ) {
            return 'sk_status';
        }

        if (
            $normalized ===
            "KET {$periodYear}"
        ) {
            return 'sk_description';
        }

        if (
            $normalized ===
            "BANSOS APBN MARET {$periodYear}"
        ) {
            return 'apbn_march_status';
        }

        foreach (
            self::MONTHS
            as $source => $internal
        ) {
            if (
                $normalized ===
                "{$source} {$periodYear}"
            ) {
                return 'month_'.$internal;
            }
        }

        return null;
    }

    private function normalize(
        string $value
    ): string {
        $value = mb_strtoupper(
            trim($value)
        );

        $value = str_replace(
            [',', '.'],
            ' ',
            $value
        );

        $value = preg_replace(
            '/\s+/u',
            ' ',
            $value
        ) ?? $value;

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    private function requiredKeys(): array
    {
        return [
            'membership_year',
            'nik',
            'nkk',
            'full_name',
            'birth_place',
            'birth_date',
            'mother_name',
            'address',
            'rt',
            'rw',
            'kelurahan',
            'kecamatan',
            'account_number',
            'e_warung_name',
            'source_status',
            'source_description',

            'month_jan',
            'month_feb',
            'month_mar',
            'month_apr',
            'month_may',
            'month_jun',
            'month_jul',
            'month_aug',
            'month_sep',
            'month_oct',
            'month_nov',
            'month_dec',

            'sk_status',
            'sk_description',
            'apbn_march_status',
            'welfare_rank',
            'nominal',
        ];
    }
}