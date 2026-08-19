<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Ambil seluruh participant existing
         * beserta wilayah yang saat ini
         * tersimpan pada KPM.
         */
        $participants =
            DB::table('bpnt_participants')
                ->join(
                    'kpms',
                    'kpms.id',
                    '=',
                    'bpnt_participants.kpm_id'
                )
                ->select([
                    'bpnt_participants.id',
                    'kpms.kecamatan',
                    'kpms.kelurahan',
                ])
                ->get();

        /*
         * Bangun lookup dari Master Wilayah.
         */
        $wilayahMap = [];

        $masterWilayah =
            DB::table('kelurahans')
                ->join(
                    'kecamatans',
                    'kecamatans.id',
                    '=',
                    'kelurahans.kecamatan_id'
                )
                ->select([
                    'kelurahans.id',
                    'kelurahans.name as kelurahan',
                    'kecamatans.name as kecamatan',
                ])
                ->get();

        foreach (
            $masterWilayah
            as $wilayah
        ) {
            $key =
                $this->makeKey(
                    (string) $wilayah->kecamatan,
                    (string) $wilayah->kelurahan
                );

            $wilayahMap[$key] =
                (int) $wilayah->id;
        }

        $assignments = [];
        $unmatched = [];

        foreach (
            $participants
            as $participant
        ) {
            $key =
                $this->makeKey(
                    (string) $participant->kecamatan,
                    (string) $participant->kelurahan
                );

            $kelurahanId =
                $wilayahMap[$key]
                ?? null;

            if (
                $kelurahanId === null
            ) {
                $unmatched[] =
                    sprintf(
                        'participant_id=%d, kecamatan="%s", kelurahan="%s"',
                        (int) $participant->id,
                        (string) $participant->kecamatan,
                        (string) $participant->kelurahan
                    );

                continue;
            }

            $assignments[] = [
                'participant_id'
                    => (int) $participant->id,

                'kelurahan_id'
                    => $kelurahanId,
            ];
        }

        /*
         * Jangan membuat schema setengah benar
         * bila data lama tidak dapat dipetakan.
         */
        if ($unmatched !== []) {
            throw new \RuntimeException(
                'Migration dibatalkan karena terdapat participant dengan wilayah yang tidak ditemukan pada Master Wilayah: '
                .implode(
                    '; ',
                    array_slice(
                        $unmatched,
                        0,
                        10
                    )
                )
            );
        }

        Schema::table(
            'bpnt_participants',
            function (
                Blueprint $table
            ): void {
                $table
                    ->foreignId(
                        'kelurahan_id'
                    )
                    ->nullable()
                    ->after('kpm_id')
                    ->constrained(
                        'kelurahans'
                    )
                    ->restrictOnDelete();

                $table->index(
                    [
                        'bpnt_period_id',
                        'kelurahan_id',
                    ],
                    'bpnt_participant_period_kelurahan_index'
                );
            }
        );

        /*
         * Backfill participant existing.
         */
        foreach (
            $assignments
            as $assignment
        ) {
            DB::table(
                'bpnt_participants'
            )
                ->where(
                    'id',
                    $assignment[
                        'participant_id'
                    ]
                )
                ->update([
                    'kelurahan_id'
                        => $assignment[
                            'kelurahan_id'
                        ],
                ]);
        }
    }

    public function down(): void
    {
        Schema::table(
            'bpnt_participants',
            function (
                Blueprint $table
            ): void {
                $table->dropIndex(
                    'bpnt_participant_period_kelurahan_index'
                );

                $table
                    ->dropConstrainedForeignId(
                        'kelurahan_id'
                    );
            }
        );
    }

    private function makeKey(
        string $kecamatan,
        string $kelurahan
    ): string {
        return
            $this->normalize(
                $kecamatan
            )
            .'|'
            .$this->normalize(
                $kelurahan
            );
    }

    private function normalize(
    string $value
): string {
    $normalized =
        mb_strtoupper(
            trim($value),
            'UTF-8'
        );

    $normalized =
        preg_replace(
            '/\s+/u',
            '',
            $normalized
        );

    return $normalized
        ?? '';
}
};