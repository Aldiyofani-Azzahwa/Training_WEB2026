<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $wilayah = [
            [
                'code' => '35.76.01',
                'name' => 'Prajurit Kulon',

                'kelurahans' => [
                    [
                        'code' => '1003',
                        'name' => 'Mentikan',
                    ],
                    [
                        'code' => '1004',
                        'name' => 'Kauman',
                    ],
                    [
                        'code' => '1005',
                        'name' => 'Pulorejo',
                    ],
                    [
                        'code' => '1006',
                        'name' => 'Prajurit Kulon',
                    ],
                    [
                        'code' => '1007',
                        'name' => 'Surodinawan',
                    ],
                    [
                        'code' => '1008',
                        'name' => 'Blooto',
                    ],
                ],
            ],

            [
                'code' => '35.76.02',
                'name' => 'Magersari',

                'kelurahans' => [
                    [
                        'code' => '1001',
                        'name' => 'Gununggedangan',
                    ],
                    [
                        'code' => '1003',
                        'name' => 'Magersari',
                    ],
                    [
                        'code' => '1004',
                        'name' => 'Gedongan',
                    ],
                    [
                        'code' => '1008',
                        'name' => 'Balongsari',
                    ],
                    [
                        'code' => '1009',
                        'name' => 'Kedundung',
                    ],
                    [
                        'code' => '1010',
                        'name' => 'Wates',
                    ],
                ],
            ],

            [
                'code' => '35.76.03',
                'name' => 'Kranggan',

                'kelurahans' => [
                    [
                        'code' => '1001',
                        'name' => 'Kranggan',
                    ],
                    [
                        'code' => '1002',
                        'name' => 'Miji',
                    ],
                    [
                        'code' => '1003',
                        'name' => 'Meri',
                    ],
                    [
                        'code' => '1004',
                        'name' => 'Jagalan',
                    ],
                    [
                        'code' => '1005',
                        'name' => 'Sentanan',
                    ],
                    [
                        'code' => '1006',
                        'name' => 'Purwotengah',
                    ],
                ],
            ],
        ];

        foreach (
            $wilayah
            as $kecamatanData
        ) {
            DB::table(
                'kecamatans'
            )->updateOrInsert(
                [
                    'code'
                        => $kecamatanData[
                            'code'
                        ],
                ],
                [
                    'name'
                        => $kecamatanData[
                            'name'
                        ],

                    'created_at'
                        => $now,

                    'updated_at'
                        => $now,
                ]
            );

            $kecamatanId =
                DB::table(
                    'kecamatans'
                )
                    ->where(
                        'code',
                        $kecamatanData[
                            'code'
                        ]
                    )
                    ->value(
                        'id'
                    );

            if (
                $kecamatanId === null
            ) {
                throw new RuntimeException(
                    sprintf(
                        'Kecamatan dengan kode %s gagal disiapkan.',
                        $kecamatanData[
                            'code'
                        ]
                    )
                );
            }

            foreach (
                $kecamatanData[
                    'kelurahans'
                ]
                as $kelurahanData
            ) {
                DB::table(
                    'kelurahans'
                )->updateOrInsert(
                    [
                        'kecamatan_id'
                            => (int) $kecamatanId,

                        'code'
                            => $kelurahanData[
                                'code'
                            ],
                    ],
                    [
                        'name'
                            => $kelurahanData[
                                'name'
                            ],

                        'created_at'
                            => $now,

                        'updated_at'
                            => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
       
    }
};